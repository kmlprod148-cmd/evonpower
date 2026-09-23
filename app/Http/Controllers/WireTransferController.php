<?php

namespace App\Http\Controllers;

use App\Models\WireTransfer;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class WireTransferController extends Controller
{
    /**
     * Afficher la liste des virements avec les balances des utilisateurs
     */
    public function index(Request $request)
    {
        Gate::authorize('view_wire_transfers');

        $query = WireTransfer::with(['sender', 'recipient', 'processor'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', $request->transfer_type);
        }

        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        if ($request->filled('recipient_id')) {
            $query->where('recipient_id', $request->recipient_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $wireTransfers = $query->paginate(20);

        // Obtenir les utilisateurs pour les filtres
        $users = User::select('id', 'name', 'email', 'role')
            ->whereIn('role', ['admin', 'integrator', 'operator'])
            ->orderBy('name')
            ->get();

        // Statistiques des balances
        $balanceStats = $this->getBalanceStatistics();

        return view('wire-transfers.index', compact(
            'wireTransfers', 
            'users', 
            'balanceStats'
        ));
    }

    /**
     * Afficher le formulaire de création de virement
     */
    public function create()
    {
        Gate::authorize('create_wire_transfers');

        $users = User::select('id', 'name', 'email', 'role', 'balance')
            ->whereIn('role', ['admin', 'integrator', 'operator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $transferTypes = [
            WireTransfer::TYPE_ADMIN_TO_INTEGRATOR => 'Admin → Intégrateur',
            WireTransfer::TYPE_INTEGRATOR_TO_OPERATOR => 'Intégrateur → Opérateur',
            WireTransfer::TYPE_OPERATOR_TO_CLIENT => 'Opérateur → Client',
            WireTransfer::TYPE_ADMIN_TO_OPERATOR => 'Admin → Opérateur',
            WireTransfer::TYPE_INTEGRATOR_TO_CLIENT => 'Intégrateur → Client',
            WireTransfer::TYPE_MANUAL => 'Manuel',
        ];

        return view('wire-transfers.create', compact('users', 'transferTypes'));
    }

    /**
     * Créer un nouveau virement
     */
    public function store(Request $request)
    {
        Gate::authorize('create_wire_transfers');

        $request->validate([
            'sender_id' => 'required|exists:users,id',
            'recipient_id' => 'required|exists:users,id|different:sender_id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'transfer_type' => 'required|in:' . implode(',', [
                WireTransfer::TYPE_ADMIN_TO_INTEGRATOR,
                WireTransfer::TYPE_INTEGRATOR_TO_OPERATOR,
                WireTransfer::TYPE_OPERATOR_TO_CLIENT,
                WireTransfer::TYPE_ADMIN_TO_OPERATOR,
                WireTransfer::TYPE_INTEGRATOR_TO_CLIENT,
                WireTransfer::TYPE_MANUAL,
            ]),
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $sender = User::findOrFail($request->sender_id);
            $recipient = User::findOrFail($request->recipient_id);

            // Vérifier le solde de l'expéditeur
            if (!$sender->hasSufficientBalance($request->amount)) {
                return back()->withErrors([
                    'amount' => "Solde insuffisant. Solde actuel: {$sender->getFormattedBalance()}"
                ])->withInput();
            }

            // Créer le virement
            $wireTransfer = WireTransfer::create([
                'sender_id' => $request->sender_id,
                'recipient_id' => $request->recipient_id,
                'amount' => $request->amount,
                'currency' => 'EUR',
                'transfer_type' => $request->transfer_type,
                'description' => $request->description,
                'notes' => $request->notes,
                'status' => WireTransfer::STATUS_PENDING,
            ]);

            DB::commit();

            Log::info('Virement créé', [
                'transfer_id' => $wireTransfer->id,
                'reference' => $wireTransfer->transfer_reference,
                'sender' => $sender->name,
                'recipient' => $recipient->name,
                'amount' => $request->amount,
                'created_by' => Auth::user()->name,
            ]);

            return redirect()->route('wire-transfers.show', $wireTransfer)
                ->with('success', 'Virement créé avec succès. Référence: ' . $wireTransfer->transfer_reference);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du virement', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return back()->withErrors(['error' => 'Erreur lors de la création du virement: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Afficher les détails d'un virement
     */
    public function show(WireTransfer $wireTransfer)
    {
        Gate::authorize('view_wire_transfers');

        $wireTransfer->load(['sender', 'recipient', 'processor', 'transactions']);

        // Obtenir l'historique des transactions liées
        $relatedTransactions = Transaction::where('wire_transfer_id', $wireTransfer->id)
            ->with(['user', 'businessProfile'])
            ->orderBy('created_at')
            ->get();

        return view('wire-transfers.show', compact('wireTransfer', 'relatedTransactions'));
    }

    /**
     * Traiter un virement (approuver et exécuter)
     */
    public function process(Request $request, WireTransfer $wireTransfer)
    {
        Gate::authorize('process_wire_transfers');

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($wireTransfer->status !== WireTransfer::STATUS_PENDING) {
            return back()->withErrors(['error' => 'Ce virement ne peut pas être traité']);
        }

        $result = $wireTransfer->process(Auth::user());

        if ($result['success']) {
            Log::info('Virement traité avec succès', [
                'transfer_id' => $wireTransfer->id,
                'reference' => $wireTransfer->transfer_reference,
                'processed_by' => Auth::user()->name,
            ]);

            return redirect()->route('wire-transfers.show', $wireTransfer)
                ->with('success', 'Virement traité avec succès');
        } else {
            Log::error('Erreur lors du traitement du virement', [
                'transfer_id' => $wireTransfer->id,
                'error' => $result['message'],
            ]);

            return back()->withErrors(['error' => $result['message']]);
        }
    }

    /**
     * Rejeter un virement
     */
    public function reject(Request $request, WireTransfer $wireTransfer)
    {
        Gate::authorize('process_wire_transfers');

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($wireTransfer->status !== WireTransfer::STATUS_PENDING) {
            return back()->withErrors(['error' => 'Ce virement ne peut pas être rejeté']);
        }

        $success = $wireTransfer->reject($request->rejection_reason, Auth::user());

        if ($success) {
            Log::info('Virement rejeté', [
                'transfer_id' => $wireTransfer->id,
                'reference' => $wireTransfer->transfer_reference,
                'reason' => $request->rejection_reason,
                'rejected_by' => Auth::user()->name,
            ]);

            return redirect()->route('wire-transfers.show', $wireTransfer)
                ->with('success', 'Virement rejeté avec succès');
        } else {
            return back()->withErrors(['error' => 'Erreur lors du rejet du virement']);
        }
    }

    /**
     * Annuler un virement
     */
    public function cancel(WireTransfer $wireTransfer)
    {
        Gate::authorize('process_wire_transfers');

        if ($wireTransfer->status !== WireTransfer::STATUS_PENDING) {
            return back()->withErrors(['error' => 'Ce virement ne peut pas être annulé']);
        }

        $success = $wireTransfer->cancel(Auth::user());

        if ($success) {
            Log::info('Virement annulé', [
                'transfer_id' => $wireTransfer->id,
                'reference' => $wireTransfer->transfer_reference,
                'cancelled_by' => Auth::user()->name,
            ]);

            return redirect()->route('wire-transfers.show', $wireTransfer)
                ->with('success', 'Virement annulé avec succès');
        } else {
            return back()->withErrors(['error' => 'Erreur lors de l\'annulation du virement']);
        }
    }

    /**
     * Afficher les balances des utilisateurs
     */
    public function balances()
    {
        Gate::authorize('view_wire_transfers');

        $users = User::select('id', 'name', 'email', 'role', 'balance', 'currency', 'created_at')
            ->whereIn('role', ['admin', 'integrator', 'operator'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        // Calculer les statistiques
        $balanceStats = $this->getBalanceStatistics();

        // Obtenir l'historique des mouvements récents
        $recentMovements = $this->getRecentBalanceMovements();

        return view('wire-transfers.balances', compact('users', 'balanceStats', 'recentMovements'));
    }

    /**
     * Obtenir les statistiques des balances
     */
    private function getBalanceStatistics(): array
    {
        $stats = User::whereIn('role', ['admin', 'integrator', 'operator'])
            ->selectRaw('
                COUNT(*) as total_users,
                SUM(balance) as total_balance,
                AVG(balance) as average_balance,
                MAX(balance) as max_balance,
                MIN(balance) as min_balance,
                SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) as positive_balance_count,
                SUM(CASE WHEN balance = 0 THEN 1 ELSE 0 END) as zero_balance_count,
                SUM(CASE WHEN balance < 0 THEN 1 ELSE 0 END) as negative_balance_count
            ')
            ->first();

        return [
            'total_users' => $stats->total_users ?? 0,
            'total_balance' => $stats->total_balance ?? 0,
            'average_balance' => $stats->average_balance ?? 0,
            'max_balance' => $stats->max_balance ?? 0,
            'min_balance' => $stats->min_balance ?? 0,
            'positive_balance_count' => $stats->positive_balance_count ?? 0,
            'zero_balance_count' => $stats->zero_balance_count ?? 0,
            'negative_balance_count' => $stats->negative_balance_count ?? 0,
        ];
    }

    /**
     * Obtenir les mouvements récents des balances
     */
    private function getRecentBalanceMovements()
    {
        return Transaction::whereIn('transaction_type', ['wire_transfer_debit', 'wire_transfer_credit'])
            ->with(['user', 'wireTransfer'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Exporter les virements
     */
    public function export(Request $request)
    {
        Gate::authorize('view_wire_transfers');

        $query = WireTransfer::with(['sender', 'recipient', 'processor']);

        // Appliquer les mêmes filtres que l'index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', $request->transfer_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $wireTransfers = $query->orderBy('created_at', 'desc')->get();

        $filename = 'virements_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($wireTransfers) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'Référence',
                'Expéditeur',
                'Destinataire',
                'Montant (EUR)',
                'Type',
                'Statut',
                'Description',
                'Date de création',
                'Date de traitement',
                'Traité par'
            ]);

            // Données
            foreach ($wireTransfers as $transfer) {
                fputcsv($file, [
                    $transfer->transfer_reference,
                    $transfer->sender->name,
                    $transfer->recipient->name,
                    $transfer->amount,
                    $transfer->getFormattedType(),
                    $transfer->getFormattedStatus(),
                    $transfer->description,
                    $transfer->created_at->format('d/m/Y H:i'),
                    $transfer->processed_at ? $transfer->processed_at->format('d/m/Y H:i') : '',
                    $transfer->processor ? $transfer->processor->name : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
