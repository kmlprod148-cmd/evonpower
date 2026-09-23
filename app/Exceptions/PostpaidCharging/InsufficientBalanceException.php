<?php

namespace App\Exceptions\PostpaidCharging;

class InsufficientBalanceException extends PostpaidChargingException
{
    public function __construct(
        float $currentBalance,
        float $requiredBalance,
        float $missingBalance,
        float $minimumRecharge,
        float $recommendedRecharge,
        array $context = []
    ) {
        $message = sprintf(
            'Solde insuffisant pour démarrer une session postpayée. Solde actuel: %.2f EUR. Minimum requis: %.2f EUR. Veuillez recharger votre wallet d\'au moins %.2f EUR.',
            $currentBalance,
            $requiredBalance,
            $minimumRecharge
        );

        parent::__construct(
            $message,
            'insufficient_balance',
            402, // Payment Required
            null,
            array_merge($context, [
                'current_balance' => $currentBalance,
                'required_balance' => $requiredBalance,
                'missing_balance' => $missingBalance,
                'minimum_recharge' => $minimumRecharge,
                'recommended_recharge' => $recommendedRecharge,
            ])
        );
    }
}

