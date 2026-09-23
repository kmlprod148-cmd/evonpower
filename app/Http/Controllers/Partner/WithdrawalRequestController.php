<?php

namespace App\Http\Controllers\Partner;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
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
     * Display withdrawal requests for the current partner
     */
    public function index(Request $request): View
    {
        $partner = auth()->user()->partner;
        
        if (!$partner) {
            abort(403, 'Aucun partenaire associé à ce compte');
        }

        $filters = $request->only([
            'status',
            'withdrawal_method',
            'date_from',
            'date_to',
            'order_by',
            'order_dir',
        ]);

        $withdrawals = $this->service->getForOwner($partner, $filters, 15);
        $statuses = WithdrawalStatus::getOptions();

        // Get wallet for balance display
        $wallet = Wallet::where('owner_type', Partner::class)
            ->where('owner_id', $partner->id)
            ->first();

        return view('partner.withdrawals.index', compact(
            'withdrawals',
            'filters',
            'statuses',
            'wallet',
            'partner'
        ));
    }

    /**
     * Show the form for creating a new withdrawal request
     */
    public function create(): View
    {
        $partner = auth()->user()->partner;
        
        if (!$partner) {
            abort(403, 'Aucun partenaire associé à ce compte');
        }

        $wallet = Wallet::where('owner_type', Partner::class)
            ->where('owner_id', $partner->id)
            ->first();

        if (!$wallet) {
            abort(404, 'Portefeuille non trouvé');
        }

        return view('partner.withdrawals.create', compact('wallet', 'partner'));
    }

    /**
     * Store a newly created withdrawal request
     */
    public function store(Request $request)
    {
        $partner = auth()->user()->partner;
        
        if (!$partner) {
            abort(403, 'Aucun partenaire associé à ce compte');
        }

        $wallet = Wallet::where('owner_type', Partner::class)
            ->where('owner_id', $partner->id)
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
            $withdrawal = $this->service->create($partner, $wallet, $validated);
            
            return redirect()
                ->route('partner.withdrawals.show', $withdrawal->id)
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
        $partner = auth()->user()->partner;
        
        if (!$partner) {
            abort(403, 'Aucun partenaire associé à ce compte');
        }

        $withdrawal = $this->service->getById($id);
        
        if (!$withdrawal) {
            abort(404, 'Demande de retrait non trouvée');
        }

        // Verify ownership
        if ($withdrawal->owner_type !== Partner::class || $withdrawal->owner_id !== $partner->id) {
            abort(403, 'Accès refusé');
        }

        return view('partner.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Cancel a pending withdrawal request
     */
    public function cancel(Request $request, int $id)
    {
        $partner = auth()->user()->partner;
        
        if (!$partner) {
            abort(403, 'Aucun partenaire associé à ce compte');
        }

        $withdrawal = $this->service->getById($id);
        
        if (!$withdrawal) {
            return back()->with('error', 'Demande de retrait non trouvée');
        }

        // Verify ownership
        if ($withdrawal->owner_type !== Partner::class || $withdrawal->owner_id !== $partner->id) {
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
     * Get wallet balance for the current partner
     */
    public function getBalance(): array
    {
        $partner = auth()->user()->partner;
        
        if (!$partner) {
            return ['error' => 'Aucun partenaire associé'];
        }

        $wallet = Wallet::where('owner_type', Partner::class)
            ->where('owner_id', $partner->id)
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
