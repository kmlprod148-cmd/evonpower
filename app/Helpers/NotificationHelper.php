<?php

namespace App\Helpers;

use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class NotificationHelper
{
    /**
     * Créer une notification système
     */
    public static function system(string $title, string $message, array $options = []): ?AdminNotification
    {
        return self::create($title, $message, 'system', $options);
    }

    /**
     * Créer une notification d'information
     */
    public static function info(string $title, string $message, array $options = []): ?AdminNotification
    {
        return self::create($title, $message, 'info', $options);
    }

    /**
     * Créer une notification d'avertissement
     */
    public static function warning(string $title, string $message, array $options = []): ?AdminNotification
    {
        return self::create($title, $message, 'warning', $options);
    }

    /**
     * Créer une notification de succès
     */
    public static function success(string $title, string $message, array $options = []): ?AdminNotification
    {
        return self::create($title, $message, 'success', $options);
    }

    /**
     * Créer une notification d'erreur
     */
    public static function error(string $title, string $message, array $options = []): ?AdminNotification
    {
        return self::create($title, $message, 'error', $options);
    }

    /**
     * Créer une notification publique
     */
    public static function public(string $title, string $message, string $type = 'info', array $options = []): ?AdminNotification
    {
        $options['is_public'] = true;
        return self::create($title, $message, $type, $options);
    }

    /**
     * Créer une notification pour des rôles spécifiques
     */
    public static function forRoles(string $title, string $message, array $roles, string $type = 'info', array $options = []): ?AdminNotification
    {
        $options['target_roles'] = $roles;
        return self::create($title, $message, $type, $options);
    }

    /**
     * Créer une notification avec priorité
     */
    public static function priority(string $title, string $message, string $priority = 'normal', string $type = 'info', array $options = []): ?AdminNotification
    {
        $options['priority'] = $priority;
        return self::create($title, $message, $type, $options);
    }

    /**
     * Créer une notification avec expiration
     */
    public static function expiring(string $title, string $message, \DateTime $expiresAt, string $type = 'info', array $options = []): ?AdminNotification
    {
        $options['expires_at'] = $expiresAt;
        return self::create($title, $message, $type, $options);
    }

    /**
     * Méthode principale pour créer une notification
     */
    protected static function create(string $title, string $message, string $type, array $options = []): ?AdminNotification
    {
        try {
            $service = app(NotificationService::class);
            return $service->createNotification($title, $message, $type, $options);
        } catch (\Exception $e) {
            \Log::error('NotificationHelper: Error creating notification', [
                'error' => $e->getMessage(),
                'title' => $title,
                'type' => $type
            ]);
            return null;
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public static function markAsRead(int $id): bool
    {
        try {
            $service = app(NotificationService::class);
            return $service->markAsRead($id);
        } catch (\Exception $e) {
            \Log::error('NotificationHelper: Error marking notification as read', [
                'error' => $e->getMessage(),
                'notification_id' => $id
            ]);
            return false;
        }
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public static function getUnreadCount(): int
    {
        try {
            $service = app(NotificationService::class);
            return $service->getUnreadCount();
        } catch (\Exception $e) {
            \Log::error('NotificationHelper: Error getting unread count', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Obtenir les notifications récentes
     */
    public static function getRecent(int $limit = 10)
    {
        try {
            $service = app(NotificationService::class);
            return $service->getNotifications($limit);
        } catch (\Exception $e) {
            \Log::error('NotificationHelper: Error getting notifications', [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }
}
