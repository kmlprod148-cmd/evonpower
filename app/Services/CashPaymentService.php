<?php

namespace App\Services;

use App\Models\CreditRecharge;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\MoneyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des paiements en espèces des clients
 * 
 * Ce service permet d'enregistrer les paiements en espèces effectués par les clients
 * et d'ajouter les crédits correspondants à leur portefeuille.
 * 
 * Le flux de paiement en espèces:
 * 1. L'opérateur/enregistrant enregistre un paiement en espèces du client
 * 2. Le système vérifie le wallet du client
 * 3. Les crédits sont ajoutés au wallet du client
 * 4. Un reçu/reçu est généré pour le client
 */
class CashPaymentService
{
    /**
     * Constantes pour les statuts de paiement
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Constantes pour le type de paiement
     */
    public const PAYMENT_METHOD = 'espece_client';
    public const PAYMENT_METHOD_LABEL = 'Espèces Client';

    /**
     * Montants suggérés pour les paiements en espèces
     */
    public const SUGGESTED_AMOUNTS = [50, 100, 200, 500, 1000];

    public function __construct(
        protected MoneyService $moneyService
    ) {}

    /**
     * Enregistre un nouveau paiement en espèces
     * 
     * @param User $client Le client qui effectue le paiement
     * @param float $amount Montant du paiement
     * @param string $currency Devise du paiement
     * @param User|null $processedBy L'opérateur qui traite le paiement
     * @param array $metadata Métadonnées supplémentaires
     * @return CreditRecharge
     * @throws \Exception
     */
    public function createCashPayment(
        User $client,
        float $amount,
        string $currency = 'MAD',
        ?User $processedBy = null,
        array $metadata = []
    ): CreditRecharge {
        // Validation du montant
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0');
        }

        // Vérifier que le client a un wallet
        $wallet = $this->getOrCreateClientWallet($client);

        return DB::transaction(function () use ($client, $wallet, $amount, $currency, $processedBy, $metadata) {
            // Créer l'enregistrement de recharge
            $recharge = CreditRecharge::create([
                'user_id' => $client->id,
                'wallet_id' => $wallet->id,
                'is_custom' => true,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => self::PAYMENT_METHOD,
                'status' => self::STATUS_PENDING,
                'reference' => $this->generateReference(),
                'description' => 'Paiement en espèces - ' . ($metadata['description'] ?? ''),
                'metadata' => array_merge($metadata, [
                    'payment_method_label' => self::PAYMENT_METHOD_LABEL,
                    'recorded_at' => now()->toISOString(),
                ]),
                'processed_by' => $processedBy?->id,
            ]);

            try {
                // Ajouter les crédits au wallet
                $this->addCreditsToWallet($wallet, $amount, $currency, $recharge);

                // Mettre à jour le statut
                $recharge->update([
                    'status' => self::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);

                // Invalider le cache du solde client après l'ajout de crédits
                if ($client instanceof User) {
                    app(\App\Services\ClientBalanceService::class)->invalidate($client);
                }

                Log::info('CashPaymentService: Paiement en espèces enregistré', [
                    'recharge_id' => $recharge->id,
                    'reference' => $recharge->reference,
                    'client_id' => $client->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'processed_by' => $processedBy?->id,
                ]);

            } catch (\Exception $e) {
                $recharge->update([
                    'status' => self::STATUS_FAILED,
                    'failed_at' => now(),
                    'failure_reason' => $e->getMessage(),
                ]);

                Log::error('CashPaymentService: Échec du paiement en espèces', [
                    'recharge_id' => $recharge->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }

            return $recharge->fresh();
        });
    }

    /**
     * Ajoute des crédits au wallet du client
     * 
     * @param Wallet $wallet
     * @param float $amount
     * @param string $currency
     * @param CreditRecharge $recharge
     * @return WalletTransaction
     */
    protected function addCreditsToWallet(
        Wallet $wallet,
        float $amount,
        string $currency,
        CreditRecharge $recharge
    ): WalletTransaction {
        // Convertir en EUR pour le stockage interne
        $eurAmount = $this->moneyService->toEur($amount, $currency);

        return $wallet->credit(
            $eurAmount,
            "Paiement en espèces - Référence: {$recharge->reference}",
            [
                'recharge_id' => $recharge->id,
                'payment_method' => self::PAYMENT_METHOD,
                'original_amount' => $amount,
                'original_currency' => $currency,
            ],
            $currency
        );
    }

    /**
     * Obtient ou crée le wallet du client
     * 
     * @param User $client
     * @return Wallet
     */
    protected function getOrCreateClientWallet(User $client): Wallet
    {
        $wallet = $client->wallet;

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $client->id,
                'balance' => 0,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => "Wallet - {$client->name}",
                'description' => "Portefeuille client pour {$client->name}",
            ]);

            Log::info('CashPaymentService: Nouveau wallet créé pour le client', [
                'client_id' => $client->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        return $wallet;
    }

    /**
     * Génère une référence unique pour le paiement
     * 
     * @return string
     */
    protected function generateReference(): string
    {
        return 'CASH-' . strtoupper(uniqid()) . '-' . date('Ymd');
    }

    /**
     * Annule un paiement en espèces
     * 
     * @param CreditRecharge $recharge
     * @param string $reason
     * @param User|null $cancelledBy
     * @return CreditRecharge
     * @throws \Exception
     */
    public function cancelCashPayment(
        CreditRecharge $recharge,
        string $reason,
        ?User $cancelledBy = null
    ): CreditRecharge {
        if ($recharge->payment_method !== self::PAYMENT_METHOD) {
            throw new \InvalidArgumentException('Ce paiement n\'est pas un paiement en espèces');
        }

        if ($recharge->status === self::STATUS_CANCELLED) {
            throw new \InvalidArgumentException('Ce paiement est déjà annulé');
        }

        if ($recharge->status !== self::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Seuls les paiements complétés peuvent être annulés');
        }

        return DB::transaction(function () use ($recharge, $reason, $cancelledBy) {
            // Debiter le wallet (inverse du crédit)
            $wallet = $recharge->wallet;
            
            if ($wallet) {
                $eurAmount = $this->moneyService->toEur(
                    $recharge->amount, 
                    $recharge->currency
                );

                $wallet->debit(
                    $eurAmount,
                    "Annulation paiement en espèces - Référence: {$recharge->reference}",
                    [
                        'recharge_id' => $recharge->id,
                        'cancelled_by' => $cancelledBy?->id,
                        'cancellation_reason' => $reason,
                    ]
                );
            }

            // Mettre à jour le statut
            $recharge->update([
                'status' => self::STATUS_CANCELLED,
                'metadata' => array_merge($recharge->metadata ?? [], [
                    'cancelled_at' => now()->toISOString(),
                    'cancelled_by' => $cancelledBy?->id,
                    'cancellation_reason' => $reason,
                ]),
            ]);

            Log::info('CashPaymentService: Paiement en espèces annulé', [
                'recharge_id' => $recharge->id,
                'reference' => $recharge->reference,
                'cancelled_by' => $cancelledBy?->id,
                'reason' => $reason,
            ]);

            return $recharge->fresh();
        });
    }

    /**
     * Obtient l'historique des paiements en espèces d'un client
     * 
     * @param User $client
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClientCashPaymentHistory(User $client, int $limit = 20)
    {
        return CreditRecharge::where('user_id', $client->id)
            ->where('payment_method', self::PAYMENT_METHOD)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calcule le total des paiements en espèces pour un client
     * 
     * @param User $client
     * @param string|null $currency
     * @return float
     */
    public function getClientTotalCashPayments(User $client, ?string $currency = null): float
    {
        $query = CreditRecharge::where('user_id', $client->id)
            ->where('payment_method', self::PAYMENT_METHOD)
            ->where('status', self::STATUS_COMPLETED);

        if ($currency) {
            $query->where('currency', $currency);
            return (float) $query->sum('amount');
        }

        // Retourner en EUR
        $totalEur = 0;
        $recharges = $query->get(['amount', 'currency']);
        
        foreach ($recharges as $recharge) {
            $totalEur += $this->moneyService->toEur($recharge->amount, $recharge->currency);
        }

        return $totalEur;
    }

    /**
     * Génère un reçu pour le paiement
     * 
     * @param CreditRecharge $recharge
     * @return array
     */
    public function generateReceipt(CreditRecharge $recharge): array
    {
        $client = $recharge->user;
        
        return [
            'receipt_number' => $recharge->reference,
            'date' => $recharge->processed_at?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s'),
            'client' => [
                'name' => $client?->name,
                'email' => $client?->email,
                'phone' => $client?->phone,
            ],
            'payment' => [
                'method' => self::PAYMENT_METHOD_LABEL,
                'amount' => number_format($recharge->amount, 2, ',', ' '),
                'currency' => $recharge->currency,
                'status' => $recharge->status,
            ],
            'credits' => [
                'amount' => number_format($recharge->amount, 2, ',', ' '),
                'currency' => $recharge->currency,
            ],
            'processed_by' => $recharge->processor?->name,
        ];
    }

    /**
     * Valide un paiement en espèces
     * 
     * @param float $amount
     * @param string $currency
     * @return array
     */
    public function validateCashPayment(float $amount, string $currency = 'MAD'): array
    {
        $errors = [];
        $warnings = [];

        // Validation du montant
        if ($amount <= 0) {
            $errors[] = 'Le montant doit être supérieur à 0';
        }

        if ($amount > 10000) {
            $warnings[] = 'Montant inhabituel - vérification recommandée';
        }

        // Validation de la devise
        $supportedCurrencies = ['MAD', 'EUR', 'USD'];
        if (!in_array($currency, $supportedCurrencies)) {
            $errors[] = "Devise non supportée: {$currency}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Liste les paiements en espèces en attente
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingCashPayments(int $limit = 50)
    {
        return CreditRecharge::where('payment_method', self::PAYMENT_METHOD)
            ->where('status', self::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->with(['user', 'wallet', 'processor'])
            ->get();
    }

    /**
     * Obtient les statistiques des paiements en espèces
     * 
     * @param User|null $client
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getCashPaymentStatistics(
        ?User $client = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = CreditRecharge::where('payment_method', self::PAYMENT_METHOD)
            ->where('status', self::STATUS_COMPLETED);

        if ($client) {
            $query->where('user_id', $client->id);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $count = $query->count();
        $totalAmount = (float) $query->sum('amount');
        
        // Grouper par devise
        $byCurrency = $query->groupBy('currency')
            ->selectRaw('currency, SUM(amount) as total')
            ->get()
            ->pluck('total', 'currency')
            ->toArray();

        return [
            'count' => $count,
            'total_amount' => $totalAmount,
            'by_currency' => $byCurrency,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }
}
