<?php

namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminNotificationService
{
    /**
     * Créer une nouvelle notification
     *
     * @param string $title
     * @param string|null $content
     * @param string $type
     * @param array $options
     * @return AdminNotification
     */
    public function create(string $title, ?string $content = null, string $type = 'info', array $options = [])
    {
        $data = array_merge([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'user_id' => Auth::id(),
        ], $options);

        $notification = AdminNotification::create($data);
        
        // Vider le cache des notifications
        $this->clearNotificationCache();
        
        return $notification;
    }

    /**
     * Récupérer les notifications non lues pour un ou plusieurs rôles
     *
     * @param array|string $roles
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnreadForRoles($roles, int $limit = 10)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $cacheKey = 'admin_notifications_unread_' . implode('_', $roles) . '_' . $limit;
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($roles, $limit) {
            $query = AdminNotification::unread()->notExpired();
            
            // Appliquer les filtres de rôle
            foreach ($roles as $role) {
                $query->forRole($role);
            }
            
            return $query->latest()->limit($limit)->get();
        });
    }

    /**
     * Récupérer toutes les notifications pour un ou plusieurs rôles
     *
     * @param array|string $roles
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllForRoles($roles, int $limit = 50)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $cacheKey = 'admin_notifications_all_' . implode('_', $roles) . '_' . $limit;
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($roles, $limit) {
            $query = AdminNotification::notExpired();
            
            // Appliquer les filtres de rôle
            foreach ($roles as $role) {
                $query->forRole($role);
            }
            
            return $query->latest()->limit($limit)->get();
        });
    }

    /**
     * Marquer une notification comme lue
     *
     * @param int $id
     * @return bool
     */
    public function markAsRead(int $id)
    {
        $notification = AdminNotification::find($id);
        
        if (!$notification) {
            return false;
        }
        
        $notification->markAsRead();
        
        // Vider le cache des notifications
        $this->clearNotificationCache();
        
        return true;
    }

    /**
     * Marquer toutes les notifications comme lues pour un ou plusieurs rôles
     *
     * @param array|string|null $roles
     * @return int Nombre de notifications mises à jour
     */
    public function markAllAsRead($roles = null)
    {
        $query = AdminNotification::unread();
        
        if ($roles) {
            $roles = is_array($roles) ? $roles : [$roles];
            
            foreach ($roles as $role) {
                $query->forRole($role);
            }
        }
        
        $count = $query->count();
        
        if ($count > 0) {
            $query->update(['read_at' => now()]);
            
            // Vider le cache des notifications
            $this->clearNotificationCache();
        }
        
        return $count;
    }

    /**
     * Supprimer une notification
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id)
    {
        $notification = AdminNotification::find($id);
        
        if (!$notification) {
            return false;
        }
        
        $notification->delete();
        
        // Vider le cache des notifications
        $this->clearNotificationCache();
        
        return true;
    }

    /**
     * Vider le cache des notifications
     */
    protected function clearNotificationCache()
    {
        // Supprimer toutes les clés de cache liées aux notifications
        $cacheKeys = Cache::get('admin_notification_cache_keys', []);
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Réinitialiser la liste des clés
        Cache::put('admin_notification_cache_keys', [], now()->addDay());
    }
}