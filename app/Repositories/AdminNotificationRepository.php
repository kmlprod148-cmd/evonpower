<?php

namespace App\Repositories;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Collection;

class AdminNotificationRepository
{
    /**
     * Get all notifications for specific roles
     */
    public function getAllForRoles(array $roles): Collection
    {
        return AdminNotification::where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhereJsonContains('target_roles', $role);
                }
                $query->orWhereNull('target_roles')
                      ->orWhere('target_roles', '[]');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread notifications for specific roles
     */
    public function getUnreadForRoles(array $roles): Collection
    {
        return AdminNotification::where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhereJsonContains('target_roles', $role);
                }
                $query->orWhereNull('target_roles')
                      ->orWhere('target_roles', '[]');
            })
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $id): bool
    {
        $notification = AdminNotification::find($id);
        
        if (!$notification) {
            return false;
        }

        $notification->update(['is_read' => true]);
        return true;
    }

    /**
     * Mark all notifications as read for specific roles
     */
    public function markAllAsRead(array $roles): int
    {
        return AdminNotification::where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhereJsonContains('target_roles', $role);
                }
                $query->orWhereNull('target_roles')
                      ->orWhere('target_roles', '[]');
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Delete a notification
     */
    public function delete(int $id): bool
    {
        $notification = AdminNotification::find($id);
        
        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Create a new notification
     */
    public function create(array $data): AdminNotification
    {
        return AdminNotification::create($data);
    }

    /**
     * Get notification by ID
     */
    public function find(int $id): ?AdminNotification
    {
        return AdminNotification::find($id);
    }

    /**
     * Get notifications count for roles
     */
    public function getCountForRoles(array $roles): int
    {
        return AdminNotification::where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhereJsonContains('target_roles', $role);
                }
                $query->orWhereNull('target_roles')
                      ->orWhere('target_roles', '[]');
            })
            ->where('is_read', false)
            ->count();
    }
}