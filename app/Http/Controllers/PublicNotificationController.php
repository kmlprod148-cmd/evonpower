<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicNotificationController extends Controller
{
    /**
     * Affiche les notifications publiques
     * Accessible sans authentification
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Cache les notifications publiques pour améliorer les performances
        $notifications = Cache::remember('public_notifications', 300, function () {
            return AdminNotification::where('is_public', true)
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });

        // Si c'est une requête AJAX/API
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notifications' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                        'human_time' => $notification->created_at->diffForHumans()
                    ];
                })
            ]);
        }

        return view('notifications.public', compact('notifications'));
    }

    /**
     * Affiche une notification publique spécifique
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $notification = AdminNotification::where('is_public', true)
            ->findOrFail($id);

        // Si c'est une requête AJAX/API
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'notification' => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'human_time' => $notification->created_at->diffForHumans()
                ]
            ]);
        }

        return view('notifications.public-show', compact('notification'));
    }

    /**
     * Récupère les notifications système (maintenance, annonces, etc.)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemNotifications()
    {
        $notifications = Cache::remember('system_notifications', 60, function () {
            return AdminNotification::where('is_public', true)
                ->where('type', 'system')
                ->where('is_read', false)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        });

        return response()->json([
            'success' => true,
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'priority' => $notification->priority ?? 'normal',
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }

    /**
     * Récupère les annonces publiques
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnnouncements()
    {
        $announcements = Cache::remember('public_announcements', 300, function () {
            return AdminNotification::where('is_public', true)
                ->where('type', 'announcement')
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        });

        return response()->json([
            'success' => true,
            'announcements' => $announcements->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'message' => $announcement->message,
                    'created_at' => $announcement->created_at->format('Y-m-d H:i:s'),
                    'human_time' => $announcement->created_at->diffForHumans()
                ];
            })
        ]);
    }

    /**
     * Marque une notification publique comme vue (pour les statistiques)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsViewed($id)
    {
        $notification = AdminNotification::where('is_public', true)
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        // Incrémenter le compteur de vues (si vous avez ce champ)
        if (method_exists($notification, 'incrementViews')) {
            $notification->incrementViews();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme vue'
        ]);
    }

    /**
     * Webhook pour recevoir des notifications externes
     * (Pour intégration avec des services tiers)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        // Valider la signature du webhook si nécessaire
        if (!$this->validateWebhookSignature($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Signature invalide'
            ], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'string|in:info,warning,error,success,system,announcement',
            'priority' => 'string|in:low,normal,high,urgent'
        ]);

        try {
            $notification = AdminNotification::create([
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'] ?? 'info',
                'is_public' => true,
                'is_read' => false,
                'priority' => $validated['priority'] ?? 'normal'
            ]);

            // Invalider le cache
            Cache::forget('public_notifications');
            Cache::forget('system_notifications');
            Cache::forget('public_announcements');

            return response()->json([
                'success' => true,
                'notification_id' => $notification->id,
                'message' => 'Notification créée avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la notification'
            ], 500);
        }
    }

    /**
     * Valide la signature du webhook
     *
     * @param Request $request
     * @return bool
     */
    private function validateWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('services.webhook.secret');

        if (!$signature || !$secret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
} 