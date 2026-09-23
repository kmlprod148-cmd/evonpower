<?php

namespace App\Http\Controllers\Integrator;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Integrator;
use App\Models\Wallet;
use App\Services\WithdrawalRequestService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalRequestController extends Controller
{
    protected WithdrawalRequestService $service;

    public function __construct(WithdrawalRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * Display withdrawal requests for the current integrator
     */
    public function index(Request $request): View
    {
        $integrator = auth()->user()->integrator;
        
        if (!$integrator) {
            abort(403, 'Aucun intégrateur associé à ce compte');
        }

        $filters = $request->only([
            'status',
            'withdrawal_method',
            'date_from',
            'date_to',
            'order_by',
            'order_dir',
        ]);

        $withdrawals = $this->service->getForOwner($integrator, $filters, 15);
        $statuses = WithdrawalStatus::getOptions();

        // Get wallet for balance display
        $wallet = Wallet::where('owner_type', Integrator::class)
            ->where('owner_id', $integrator->id)
            ->first();

        return view('integrator.withdrawals.index', compact(
            'withdrawals',
            'filters',
            'statuses',
            'wallet',
            'integrator'
        ));
    }

    /**
     * Show the form for creating a new withdrawal request
     */
    public function create(): View
    {
        $integrator = auth()->user()->integrator;
        
        if (!$integrator) {
            abort(403, 'Aucun intégrateur associé à ce compte');
        }

        $wallet = Wallet::where('owner_type', Integrator::class)
            ->where('owner_id', $integrator->id)
            ->first();

        if (!$wallet) {
            abort(404, 'Portefeuille non trouvé');
        }

        return view('integrator.withdrawals.create', compact('wallet', 'integrator'));
    }

    /**
     * Store a newly created withdrawal request
     */
    public function store(Request $request)
    {
        $integrator = auth()->user()->integrator;
        
        if (!$integrator) {
            abort(403, 'Aucun intégrateur associé à ce compte');
        }

        $wallet = Wallet::where('owner_type', Integrator::class)
            ->where('owner_id', $integrator->id)
            ->first();

        if (!$wallet) {
            return back()->with('error', 'Portefeuille non trouvé');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'withdrawal_method' => 'required|string|in:bank_transfer,card,wallet',
            'bank_name' => 'required_if:withdrawal_method,bank_transfer|string|max:255',
            'bank_account' => 'required_if:withdrawal_method,bank_transfer|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'card_last4' => 'required_if:withdrawal_method,card|string|size:4',
            'card_brand' => 'required_if:withdrawal_method,card|string|in:visa,mastercard,amex',
            'destination_wallet_id' => 'required_if:withdrawal_method,wallet|exists:wallets,id',
        ]);

        try {
            $withdrawal = $this->service->create($integrator, $wallet, $validated);
            
            return redirect()
                ->route('integrator.withdrawals.show', $withdrawal->id)
                ->with('success', 'Demande de retrait soumise avec succès');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified withdrawal request
     */
    public function show(int $id): View
    {
        $integrator = auth()->user()->integrator;
        
        if (!$integrator) {
            abort(403, 'Aucun intégrateur associé à ce compte');
        }

        $withdrawal = $this->service->getById($id);
        
        if (!$withdrawal) {
            abort(404, 'Demande de retrait non trouvée');
        }

        // Verify ownership
        if ($withdrawal->owner_type !== Integrator::class || $withdrawal->owner_id !== $integrator->id) {
            abort(403, 'Accès refusé');
        }

        return view('integrator.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Cancel a pending withdrawal request
     */
    public function cancel(Request $request, int $id)
    {
        $integrator = auth()->user()->integrator;
        
        if (!$integrator) {
            abort(403, 'Aucun intégrateur associé à ce compte');
        }

        $withdrawal = $this->service->getById($id);
        
        if (!$withdrawal) {
            return back()->with('error', 'Demande de retrait non trouvée');
        }

        // Verify ownership
        if ($withdrawal->owner_type !== Integrator::class || $withdrawal->owner_id !== $integrator->id) {
            return back()->with('error', 'Accès refusé');
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $result = $this->service->cancel($id, $request->notes);

        if (!$result) {
            return back()->with('error', 'Impossible d\'annuler cette demande');
        }

        return back()->with('success', 'Demande de retrait annulée');
    }

    /**
     * Get wallet balance for the current integrator
     */
    public function getBalance(): array
    {
        $integrator = auth()->user()->integrator;
        
        if (!$integrator) {
            return ['error' => 'Aucun intégrateur associé'];
        }

        $wallet = Wallet::where('owner_type', Integrator::class)
            ->where('owner_id', $integrator->id)
            ->first();

        if (!$wallet) {
            return ['error' => 'Portefeuille non trouvé'];
        }

        return [
            'balance' => $wallet->balance,
            'balance_to_collect' => $wallet->balance_to_collect,
            'available_for_withdrawal' => $wallet->balance - $wallet->balance_to_collect,
            'currency' => $wallet->currency,
        ];
    }
}
