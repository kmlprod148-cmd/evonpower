<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Broadcast;

class NotificationService
{
    protected const CACHE_TTL = 300; // 5 minutes
    protected const NOTIFICATION_TYPES = [
        'info' => 'Information',
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
        'security' => 'Security Alert',
        'financial' => 'Financial Update',
        'system' => 'System Notification'
    ];

    /**
     * Send notification to user
     */
    public function sendNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $broadcast = true
    ): Notification {
        $notification = Notification::create([
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => array_merge($data, [
                'title' => $title,
                'message' => $message
            ]),
            'read_at' => null,
            'created_at' => now()
        ]);

        // Clear user notification cache
        $this->clearUserNotificationCache($user->id);

        // Broadcast real-time notification
        if ($broadcast) {
            $this->broadcastNotification($notification);
        }

        // Send email for important notifications
        if (in_array($type, ['error', 'security', 'financial'])) {
            $this->sendEmailNotification($user, $notification);
        }

        Log::info("Notification sent", [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title
        ]);

        return $notification;
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulkNotification(
        array $userIds,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $broadcast = true
    ): array {
        $notifications = [];
        
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $notifications[] = $this->sendNotification(
                    $user,
                    $type,
                    $title,
                    $message,
                    $data,
                    $broadcast
                );
            }
        }

        return $notifications;
    }

    /**
     * Send notification to users by role
     */
    public function sendRoleNotification(
        string $role,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $broadcast = true
    ): array {
        $users = User::role($role)->get();
        $userIds = $users->pluck('id')->toArray();
        
        return $this->sendBulkNotification(
            $userIds,
            $type,
            $title,
            $message,
            $data,
            $broadcast
        );
    }

    /**
     * Get user notifications with pagination
     */
    public function getUserNotifications(
        User $user,
        int $perPage = 20,
        array $filters = []
    ) {
        $query = $user->notifications();

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $query->whereNull('read_at');
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadCount(User $user): int
    {
        $cacheKey = "user_unread_notifications:{$user->id}";
        
        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () use ($user) {
            return $user->notifications()->whereNull('read_at')->count();
        });
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        if ($notification->read_at) {
            return true; // Already read
        }

        $notification->update(['read_at' => now()]);
        
        // Clear user notification cache
        $this->clearUserNotificationCache($notification->user_id);

        return true;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): int
    {
        $count = $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Clear user notification cache
        $this->clearUserNotificationCache($user->id);

        return $count;
    }

    /**
     * Delete notification
     */
    public function deleteNotification(Notification $notification): bool
    {
        $userId = $notification->user_id;
        $deleted = $notification->delete();
        
        if ($deleted) {
            // Clear user notification cache
            $this->clearUserNotificationCache($userId);
        }

        return $deleted;
    }

    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deleted = Notification::where('created_at', '<', $cutoffDate)
            ->whereNotNull('read_at')
            ->delete();

        Log::info("Old notifications cleaned up", [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateString()
        ]);

        return $deleted;
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $stats = [
            'total_notifications' => Notification::where('created_at', '>=', $startDate)->count(),
            'unread_notifications' => Notification::where('created_at', '>=', $startDate)
                ->whereNull('read_at')
                ->count(),
            'notifications_by_type' => Notification::where('created_at', '>=', $startDate)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'type')
                ->toArray(),
            'notifications_by_day' => Notification::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray()
        ];

        return $stats;
    }

    /**
     * Broadcast notification to user
     */
    private function broadcastNotification(Notification $notification): void
    {
        try {
            Broadcast::toUser($notification->user)
                ->event('notification.received', [
                    'notification' => $notification->toArray()
                ]);
        } catch (\Exception $e) {
            Log::error("Failed to broadcast notification", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(User $user, Notification $notification): void
    {
        try {
            // This would integrate with your email service
            // For now, we'll just log it
            Log::info("Email notification would be sent", [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'type' => $notification->type
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send email notification", [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear user notification cache
     */
    private function clearUserNotificationCache(int $userId): void
    {
        $cacheKeys = [
            "user_notifications:{$userId}",
            "user_unread_notifications:{$userId}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get available notification types
     */
    public function getNotificationTypes(): array
    {
        return self::NOTIFICATION_TYPES;
    }

    /**
     * Create system notification
     */
    public function createSystemNotification(
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        // Send to all admin users
        $this->sendRoleNotification(
            'admin',
            $type,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create security alert
     */
    public function createSecurityAlert(
        string $title,
        string $message,
        array $data = []
    ): void {
        $this->createSystemNotification(
            'security',
            $title,
            $message,
            array_merge($data, [
                'severity' => 'high',
                'requires_attention' => true
            ])
        );
    }
}
