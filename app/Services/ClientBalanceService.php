<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service compact de gestion du solde client.
 * Point d'accès unique pour l'affichage et la récupération du solde des clients.
 */
class ClientBalanceService
{
    /** Per-request memoization cache: user_id => balance */
    private array $cache = [];

    public function __construct(
        protected BalanceSynchronizationService $balanceSync
    ) {}

    /**
     * Récupère le solde frais d'un utilisateur (évite le cache).
     */
    public function getBalance(mixed $user = null): float
    {
        $user ??= Auth::user();
        if (!$user) {
            return 0.0;
        }

        $userId = $user->getAuthIdentifier();
        if (array_key_exists($userId, $this->cache)) {
            return $this->cache[$userId];
        }

        $user->unsetRelation('wallet');
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();

        $balance = (float) ($wallet->balance ?? 0);

        // Admin/Intégrateur/Opérateur : solde calculé depuis TransactionDetails (source de vérité)
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
            try {
                $calculated = $this->balanceSync->calculateBalanceFromApprovedTransactions($user);
                if (abs($balance - $calculated) > 0.01) {
                    $balance = (float) $calculated;
                }
            } catch (\Throwable $e) {
                Log::debug('ClientBalanceService: balanceSync fallback', ['error' => $e->getMessage()]);
            }
        }

        $this->cache[$userId] = $balance;

        return $balance;
    }

    /**
     * Invalide le cache pour forcer un recalcul frais (ex: après une transaction).
     */
    public function invalidate(mixed $user = null): void
    {
        $user ??= Auth::user();
        if ($user) {
            unset($this->cache[$user->getAuthIdentifier()]);
        }
    }

    /**
     * Récupère le solde formaté (ex: "1 234,56 EUR").
     */
    public function getFormatted(mixed $user = null): string
    {
        $user ??= Auth::user();
        if (!$user) {
            return '0.00 EUR';
        }
        $balance = $this->getBalance($user);
        $currency = $user->wallet?->currency ?? $user->getOrCreateWallet()->currency ?? 'EUR';

        return \App\Services\MoneyService::format($balance, $currency);
    }

    /**
     * Réponse API standard pour le solde (utilisée par getBalanceApi).
     */
    public function toApiResponse(mixed $user = null): array
    {
        $user ??= Auth::user();
        if (!$user) {
            return ['success' => false, 'message' => 'Non authentifié'];
        }

        try {
            $balance = $this->getBalance($user);
            $formatted = $this->getFormatted($user);

            return [
                'success' => true,
                'balance' => $balance,
                'formatted_balance' => $formatted,
                'currency' => $user->getOrCreateWallet()->currency ?? 'EUR',
                'user_id' => $user->id,
            ];
        } catch (\Exception $e) {
            Log::error('ClientBalanceService::toApiResponse', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération du solde',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Vérifie si l'utilisateur a un solde suffisant.
     */
    public function hasSufficient(float $amount, mixed $user = null): bool
    {
        return $this->getBalance($user) >= $amount;
    }
}
