<?php

namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Get unread notification count for the current user
     *
     * @return int
     */
    public function getUnreadCount(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        try {
            // Check if the table exists first
            if (!Schema::hasTable('admin_notifications')) {
                return 0;
            }

            // Check if the model has the required methods
            if (!method_exists(AdminNotification::class, 'unread') || 
                !method_exists(AdminNotification::class, 'notExpired') || 
                !method_exists(AdminNotification::class, 'forRole')) {
                return 0;
            }

            return AdminNotification::unread()
                ->notExpired()
                ->forRole(Auth::user()->getRoleNames()->toArray())
                ->count();
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::warning('NotificationService: Error getting unread count', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return 0;
        }
    }

    /**
     * Check if notification system is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            return Schema::hasTable('admin_notifications') && 
                   class_exists(AdminNotification::class);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get notifications for the current user
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotifications(int $limit = 10)
    {
        if (!Auth::check() || !$this->isAvailable()) {
            return collect();
        }

        try {
            return AdminNotification::unread()
                ->notExpired()
                ->forRole(Auth::user()->getRoleNames()->toArray())
                ->latest()
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            \Log::warning('NotificationService: Error getting notifications', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return collect();
        }
    }
}
