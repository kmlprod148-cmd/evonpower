<?php

namespace App\Http\Middleware;

use App\Services\TransactionRepartitionService;
use Closure;
use Illuminate\Http\Request;

class EnsureTransactionRepartition
{
    protected $repartitionService;

    public function __construct(TransactionRepartitionService $repartitionService)
    {
        $this->repartitionService = $repartitionService;
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
        // Vérifier si c'est une route de transaction
        if ($request->route('transaction')) {
            $transaction = $request->route('transaction');
            
            // Vérifier si la transaction a une répartition
            if (!$transaction->repartitions()->exists()) {
                try {
                    // Créer automatiquement la répartition
                    $this->repartitionService->createRepartition($transaction);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas bloquer la requête
                    \Log::error('Erreur lors de la création automatique de la répartition', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $next($request);
    }
}
