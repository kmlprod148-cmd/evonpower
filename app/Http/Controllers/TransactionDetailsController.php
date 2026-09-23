<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionDetailsController extends Controller
{
    protected $transactionCalculator;

    public function __construct(TransactionCalculator $transactionCalculator)
    {
        $this->transactionCalculator = $transactionCalculator;
    }

    /**
     * Affiche les détails d'une transaction avec sa hiérarchie
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Accès refusé.');
        }

        try {
            $transaction = Transaction::with([
                'chargingPoint',
                'chargingPoint.integrator',
                'chargingPoint.partner',
                'repartitions',
                'transactionDetail',
                'transactionDetail.adminCreator',
                'transactionDetail.integratorCreator',
                'transactionDetail.operator'
            ])->findOrFail($id);

            // Vérifier que l'utilisateur peut accéder à cette transaction
            if (!Transaction::visibleToUser($user)->where('id', $transaction->id)->exists()) {
                abort(403, 'Vous n\'avez pas accès à cette transaction.');
            }

            // Si la transaction n'a pas de TransactionDetail, essayer d'en créer un rétroactivement
            $transactionDetailErrors = [];
            if (!$transaction->transactionDetail) {
                try {
                    $transactionService = app(\App\Services\ReservationTransactionService::class);
                    $result = $transactionService->createRetroactiveTransactionDetail($transaction);
                    
                    if ($result['success'] && $result['transaction_detail']) {
                        // Recharger la transaction avec le TransactionDetail fraîchement créé
                        $transaction->refresh();
                        $transaction->load([
                            'transactionDetail',
                            'transactionDetail.adminCreator',
                            'transactionDetail.integratorCreator',
                            'transactionDetail.operator'
                        ]);
                        \Log::info('TransactionDetail créé rétroactivement pour transaction #' . $transaction->id);
                    } else {
                        // Stocker les erreurs pour les afficher dans la vue
                        $transactionDetailErrors = $result['errors'] ?? ['Impossible de créer rétroactivement un TransactionDetail'];
                        \Log::warning('Impossible de créer rétroactivement un TransactionDetail pour transaction #' . $transaction->id, [
                            'errors' => $transactionDetailErrors
                        ]);
                    }
                } catch (\Exception $e) {
                    $transactionDetailErrors = ['Erreur technique : ' . $e->getMessage()];
                    \Log::error('Erreur lors de la création rétroactive de TransactionDetail', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Utiliser le nouveau service pour calculer et créer la répartition
            $repartition = $this->transactionCalculator->createRepartition($transaction);
            
            // Obtenir les détails complets du calcul
            $calculationDetails = $this->transactionCalculator->calculateDetailed($transaction);

            // Générer les transactions hiérarchiques
            $hierarchicalTransactions = $this->generateHierarchicalTransactions($transaction, $repartition);

            return view('transactions.details', compact(
                'transaction',
                'repartition',
                'hierarchicalTransactions',
                'calculationDetails',
                'transactionDetailErrors'
            ));

        } catch (\Exception $e) {
            Log::error('Erreur affichage détails transaction: ' . $e->getMessage(), [
                'transaction_id' => $id
            ]);
            
            return redirect()->back()
                ->with('error', 'Impossible d\'afficher les détails de la transaction.');
        }
    }

    /**
     * Génère les transactions hiérarchiques selon le flux Admin → Intégrateur → Opérateur
     */
    private function generateHierarchicalTransactions(Transaction $transaction, TransactionRepartition $repartition)
    {
        $hierarchical = [];

        // Transaction Admin → Intégrateur (si applicable)
        if ($repartition->integrator_amount > 0) {
            $hierarchical[] = [
                'type' => 'admin_to_integrator',
                'from' => 'Admin',
                'to' => $transaction->chargingPoint->integrator ? $transaction->chargingPoint->integrator->name : 'Intégrateur',
                'amount' => $repartition->admin_amount,
                'status' => 'completed',
                'transaction_id' => $transaction->transaction_id . '-ADM-INT',
                'description' => 'Part admin vers intégrateur',
                'color' => 'blue'
            ];
        }

        // Transaction Intégrateur → Opérateur (si applicable)
        if ($repartition->operator_amount > 0) {
            $hierarchical[] = [
                'type' => 'integrator_to_operator',
                'from' => $transaction->chargingPoint->integrator ? $transaction->chargingPoint->integrator->name : 'Intégrateur',
                'to' => $transaction->chargingPoint->partner ? $transaction->chargingPoint->partner->name : 'Opérateur',
                'amount' => $repartition->integrator_amount + $repartition->operator_amount,
                'status' => 'completed',
                'transaction_id' => $transaction->transaction_id . '-INT-OP',
                'description' => 'Part intégrateur vers opérateur',
                'color' => 'green'
            ];
        }

        // Si pas d'intégrateur, transaction directe Admin → Opérateur
        if ($repartition->integrator_amount == 0 && $repartition->operator_amount > 0) {
            $hierarchical[] = [
                'type' => 'admin_to_operator',
                'from' => 'Admin',
                'to' => $transaction->chargingPoint->partner ? $transaction->chargingPoint->partner->name : 'Opérateur',
                'amount' => $repartition->operator_amount,
                'status' => 'completed',
                'transaction_id' => $transaction->transaction_id . '-ADM-OP',
                'description' => 'Part admin vers opérateur',
                'color' => 'purple'
            ];
        }

        return $hierarchical;
    }

    /**
     * API endpoint pour récupérer les transactions hiérarchiques
     */
    public function getHierarchicalTransactions($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        try {
            $transaction = Transaction::with([
                'chargingPoint',
                'chargingPoint.integrator',
                'chargingPoint.partner',
                'repartitions'
            ])->findOrFail($id);

            // Vérifier que l'utilisateur peut accéder à cette transaction
            if (!Transaction::visibleToUser($user)->where('id', $transaction->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Accès refusé à cette transaction.'], 403);
            }

            $repartition = $transaction->repartitions->first();
            if (!$repartition) {
                $repartitionData = $this->transactionCalculator->calculate($transaction);
                $repartition = TransactionRepartition::createFromCalculation($transaction, $repartitionData);
            }

            $hierarchicalTransactions = $this->generateHierarchicalTransactions($transaction, $repartition);

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'repartition' => $repartition,
                'hierarchical_transactions' => $hierarchicalTransactions
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération transactions hiérarchiques: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer les transactions hiérarchiques'
            ], 500);
        }
    }
}
