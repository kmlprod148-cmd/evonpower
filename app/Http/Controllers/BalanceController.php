<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CreditClientScopeService;
use App\Services\ClientBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BalanceController extends Controller
{
    public function __construct(
        protected ClientBalanceService $balanceService
    ) {
        // Balance pages are accessible to both system users and client users.
        // Ensure the controller honors both guards instead of the default 'web' guard.
        $this->middleware('auth:web,client');
    }

    protected function getClientScopeService(): ?CreditClientScopeService
    {
        try {
            return app(CreditClientScopeService::class);
        } catch (\Throwable $e) {
            Log::warning('BalanceController: CreditClientScopeService unavailable', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function canAccessClient(User $manager, int $clientId): bool
    {
        if ($manager->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        $service = $this->getClientScopeService();
        return $service ? $service->canAccessClient($manager, $clientId) : false;
    }

    protected function getClientsForSelector(User $user, bool $canManage): \Illuminate\Support\Collection
    {
        if (!$canManage) {
            return collect([]);
        }

        $service = $this->getClientScopeService();
        if ($service) {
            try {
                return $service->getRelatedClientsQuery($user)->get();
            } catch (\Throwable $e) {
                Log::warning('BalanceController: getRelatedClientsQuery failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['user', 'client']))
            ->orderBy('name')
            ->get();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $canManage = $user->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']);

        $selectedUserId = null;
        $targetUser = $user;

        if ($canManage && $request->filled('user_id')) {
            $candidate = User::find((int) $request->input('user_id'));
            if ($candidate && $candidate->hasAnyRole(['user', 'client']) && $this->canAccessClient($user, (int) $candidate->id)) {
                $targetUser = $candidate;
                $selectedUserId = $candidate->id;
            }
        }

        $wallet = $targetUser->getOrCreateWallet();
        $wallet->refresh();

        $balance = $this->balanceService->getBalance($targetUser);
        $formattedBalance = $this->balanceService->getFormatted($targetUser);

        $filters = $request->only(['type', 'date_from', 'date_to', 'reservation_id', 'per_page', 'user_id']);
        $perPage = (int) ($filters['per_page'] ?? 20);

        $query = WalletTransaction::where('wallet_id', $wallet->id)->orderBy('created_at', 'desc');
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['reservation_id'])) {
            $query->where('metadata->reservation_id', (int) $filters['reservation_id']);
        }

        $transactions = $query->paginate($perPage)->withQueryString();

        $statsQuery = WalletTransaction::where('wallet_id', $wallet->id);
        $totalCredits = (float) (clone $statsQuery)->where('type', 'credit')->sum('amount');
        $totalDebits = (float) (clone $statsQuery)->where('type', 'debit')->sum('amount');

        $clients = $this->getClientsForSelector($user, $canManage);

        return view('balances.index', compact(
            'targetUser',
            'balance',
            'formattedBalance',
            'transactions',
            'filters',
            'totalCredits',
            'totalDebits',
            'canManage',
            'clients',
            'selectedUserId'
        ));
    }
}
