<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\CMIReservationPaymentService;
use App\Services\CMICreditRechargeService;
use App\Models\Reservation;
use App\Models\CreditRecharge;

class DiagnoseCmiPayment extends Command
{
    protected $signature = 'cmi:diagnose {--reservation-id=} {--recharge-id=}';
    protected $description = 'Diagnostiquer la configuration CMI et les données de paiement';

    public function handle()
    {
        $this->info('=== Diagnostic CMI Payment ===');
        $this->newLine();

        // Vérifier la configuration
        $this->checkConfiguration();
        $this->newLine();

        // Vérifier les URLs
        $this->checkUrls();
        $this->newLine();

        // Tester avec une réservation ou recharge
        if ($reservationId = $this->option('reservation-id')) {
            $this->testReservation($reservationId);
        } elseif ($rechargeId = $this->option('recharge-id')) {
            $this->testRecharge($rechargeId);
        } else {
            $this->warn('Utilisez --reservation-id=X ou --recharge-id=X pour tester avec des données réelles');
        }
    }

    protected function checkConfiguration()
    {
        $this->info('1. Vérification de la configuration...');
        
        $clientId = config('cmi.clientid') ?? env('CMI_CLIENTID');
        $storeKey = config('cmi.storekey') ?? env('CMI_STOREKEY');
        $appUrl = config('app.url');
        $paymentUrl = config('cmi.payment_url');

        $this->table(
            ['Paramètre', 'Valeur', 'Statut'],
            [
                ['CMI_CLIENTID', $clientId ?: 'NON DÉFINI', $clientId ? '✅' : '❌'],
                ['CMI_STOREKEY', $storeKey ? substr($storeKey, 0, 10) . '...' : 'NON DÉFINI', $storeKey ? '✅' : '❌'],
                ['APP_URL', $appUrl ?: 'NON DÉFINI', $appUrl ? '✅' : '❌'],
                ['CMI_PAYMENT_URL', $paymentUrl ?: 'NON DÉFINI', $paymentUrl ? '✅' : '❌'],
            ]
        );

        if (!$clientId) {
            $this->error('❌ CMI_CLIENTID n\'est pas défini dans .env');
        }
        if (!$storeKey) {
            $this->error('❌ CMI_STOREKEY n\'est pas défini dans .env');
        }
        if (!$appUrl) {
            $this->error('❌ APP_URL n\'est pas défini dans .env');
        } elseif (strpos($appUrl, 'localhost') !== false || strpos($appUrl, '127.0.0.1') !== false) {
            $this->error('❌ APP_URL pointe vers localhost - CMI ne peut pas y accéder !');
            $this->warn('   Utilisez une URL publique (ex: https://votre-domaine.com ou ngrok)');
        }
    }

    protected function checkUrls()
    {
        $this->info('2. Vérification des URLs de callback...');
        
        $appUrl = rtrim(config('app.url'), '/');
        if (!preg_match('/^https?:\/\//', $appUrl)) {
            $appUrl = 'https://' . $appUrl;
        }

        $testReservationId = 1;
        $testRechargeId = 1;

        $urls = [
            ['Type', 'URL', 'Statut'],
            [
                'Callback Réservation',
                $appUrl . '/payment/cmi/reservation/' . $testReservationId . '/callback',
                '⚠️ À tester'
            ],
            [
                'Success Réservation',
                $appUrl . '/payment/cmi/reservation/' . $testReservationId . '/success',
                '⚠️ À tester'
            ],
            [
                'Callback Recharge',
                $appUrl . '/credit-recharge/cmi/' . $testRechargeId . '/callback',
                '⚠️ À tester'
            ],
            [
                'Return Recharge',
                $appUrl . '/credit-recharge/cmi/' . $testRechargeId . '/return',
                '⚠️ À tester'
            ],
        ];

        $this->table($urls[0], array_slice($urls, 1));

        $this->warn('⚠️  Vérifiez manuellement que ces URLs sont accessibles publiquement');
        $this->info('   Utilisez: curl -X POST <URL> -d "test=1"');
    }

    protected function testReservation($id)
    {
        $this->info("3. Test avec la réservation #{$id}...");
        
        try {
            $reservation = Reservation::findOrFail($id);
            $service = app(CMIReservationPaymentService::class);
            
            $paymentData = $service->preparePaymentData($reservation);
            
            $this->info('✅ Données de paiement préparées avec succès');
            $this->newLine();
            
            $this->table(
                ['Paramètre', 'Valeur'],
                [
                    ['clientid', $paymentData['clientid'] ?? 'MANQUANT'],
                    ['amount', $paymentData['amount'] ?? 'MANQUANT'],
                    ['oid', $paymentData['oid'] ?? 'MANQUANT'],
                    ['callbackUrl', $paymentData['callbackUrl'] ?? 'MANQUANT'],
                    ['okUrl', $paymentData['okUrl'] ?? 'MANQUANT'],
                    ['failUrl', $paymentData['failUrl'] ?? 'MANQUANT'],
                    ['storetype', $paymentData['storetype'] ?? 'MANQUANT'],
                    ['HASH', $paymentData['HASH'] ? substr($paymentData['HASH'], 0, 20) . '...' : 'MANQUANT'],
                ]
            );

            // Vérifier les URLs
            if (isset($paymentData['callbackUrl'])) {
                $callbackUrl = $paymentData['callbackUrl'];
                if (filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
                    $this->info("✅ callbackUrl est valide: {$callbackUrl}");
                } else {
                    $this->error("❌ callbackUrl est invalide: {$callbackUrl}");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    protected function testRecharge($id)
    {
        $this->info("3. Test avec la recharge #{$id}...");
        
        try {
            $recharge = CreditRecharge::findOrFail($id);
            $service = app(CMICreditRechargeService::class);
            
            $paymentData = $service->preparePaymentData($recharge);
            
            $this->info('✅ Données de paiement préparées avec succès');
            $this->newLine();
            
            $this->table(
                ['Paramètre', 'Valeur'],
                [
                    ['clientid', $paymentData['clientid'] ?? 'MANQUANT'],
                    ['amount', $paymentData['amount'] ?? 'MANQUANT'],
                    ['oid', $paymentData['oid'] ?? 'MANQUANT'],
                    ['callbackUrl', $paymentData['callbackUrl'] ?? 'MANQUANT'],
                    ['okUrl', $paymentData['okUrl'] ?? 'MANQUANT'],
                    ['failUrl', $paymentData['failUrl'] ?? 'MANQUANT'],
                    ['storetype', $paymentData['storetype'] ?? 'MANQUANT'],
                    ['HASH', $paymentData['HASH'] ? substr($paymentData['HASH'], 0, 20) . '...' : 'MANQUANT'],
                ]
            );

            // Vérifier les URLs
            if (isset($paymentData['callbackUrl'])) {
                $callbackUrl = $paymentData['callbackUrl'];
                if (filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
                    $this->info("✅ callbackUrl est valide: {$callbackUrl}");
                } else {
                    $this->error("❌ callbackUrl est invalide: {$callbackUrl}");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}

