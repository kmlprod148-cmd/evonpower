<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user
     */
    public function getUnreadJson(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                    'notifications' => []
                ], 401);
            }

            // Pour l'instant, retourner des notifications vides
            // Vous pouvez implémenter la logique de récupération des notifications ici
            $notifications = [];

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
                'notifications' => []
            ], 500);
        }
    }
}
