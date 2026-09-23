<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\BalanceSynchronizationService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour synchroniser automatiquement la balance de l'utilisateur
 * 
 * Ce middleware peut être appliqué aux routes où la balance doit être à jour.
 * Il synchronise automatiquement la balance si une différence est détectée.
 * 
 * Usage dans routes/web.php :
 * Route::middleware(['auth', 'auto.sync.balance'])->group(function() {
 *     Route::get('/transactions', ...);
 *     Route::get('/wallet', ...);
 * });
 */
class AutoSyncBalance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ne synchroniser que pour les utilisateurs authentifiés
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Ne synchroniser que pour les utilisateurs avec des rôles pertinents
        if (!$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
            return $next($request);
        }

        try {
            $wallet = $user->getOrCreateWallet();
            $balanceSyncService = app(BalanceSynchronizationService::class);
            
            // Calculer la balance attendue depuis TransactionDetails
            $calculatedBalance = $balanceSyncService->calculateBalanceFromApprovedTransactions($user);
            
            // Vérifier si une synchronisation est nécessaire
            $difference = abs($wallet->balance - $calculatedBalance);
            
            // Si la différence est significative (> 0.01), synchroniser
            if ($difference > 0.01) {
                // Synchroniser en arrière-plan (ne pas bloquer la requête)
                // Utiliser dispatchAfterResponse pour exécuter après la réponse
                dispatch(function() use ($user, $balanceSyncService) {
                    try {
                        $balanceSyncService->synchronizeUserBalance($user);
                        Log::info('Balance synchronisée automatiquement via middleware', [
                            'user_id' => $user->id,
                            'route' => request()->path()
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Erreur lors de la synchronisation automatique de la balance', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                })->afterResponse();
            }
        } catch (\Exception $e) {
            // Ne pas bloquer la requête en cas d'erreur de synchronisation
            Log::warning('Erreur dans AutoSyncBalance middleware', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $next($request);
    }
}

