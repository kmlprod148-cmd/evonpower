<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Facades\View;

class NotificationMiddleware
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Partager les données de notifications avec toutes les vues
        if ($this->notificationService->isAvailable()) {
            try {
                $unreadCount = $this->notificationService->getUnreadCount();
                $notifications = $this->notificationService->getNotifications(5);
                
                View::share('notificationCount', $unreadCount);
                View::share('notifications', $notifications);
            } catch (\Exception $e) {
                // En cas d'erreur, partager des valeurs par défaut
                View::share('notificationCount', 0);
                View::share('notifications', collect());
            }
        } else {
            View::share('notificationCount', 0);
            View::share('notifications', collect());
        }

        return $next($request);
    }
}
