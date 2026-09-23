<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Services\CMIReservationPaymentService;
use Illuminate\Support\Facades\Log;

class TestCmiPaymentData extends Command
{
    protected $signature = 'cmi:test-payment-data {reservation-id}';
    protected $description = 'Tester et afficher les données de paiement CMI pour une réservation';

    public function handle()
    {
        $reservationId = $this->argument('reservation-id');
        
        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            $this->info("=== Test des données de paiement CMI pour la réservation #{$reservationId} ===");
            $this->newLine();
            
            $service = app(CMIReservationPaymentService::class);
            $paymentData = $service->preparePaymentData($reservation);
            
            $this->info('✅ Données préparées avec succès');
            $this->newLine();
            
            // Afficher tous les paramètres
            $this->table(
                ['Paramètre', 'Valeur', 'Statut'],
                $this->formatPaymentData($paymentData)
            );
            
            // Vérifications critiques
            $this->newLine();
            $this->info('🔍 Vérifications critiques :');
            $this->checkCriticalParams($paymentData);
            
            // Générer le hash manuellement pour vérification
            $this->newLine();
            $this->info('🔐 Vérification du hash :');
            $this->verifyHash($paymentData, $service);
            
            // Afficher l'URL de callback
            $this->newLine();
            $this->info('🌐 URL de callback :');
            $this->line('  ' . ($paymentData['callbackUrl'] ?? 'MANQUANT'));
            
            // Test d'accessibilité
            $this->newLine();
            $this->info('🔗 Test d\'accessibilité de l\'URL de callback :');
            $this->testCallbackUrl($paymentData['callbackUrl'] ?? '');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    protected function formatPaymentData(array $data): array
    {
        $formatted = [];
        $required = ['clientid', 'amount', 'oid', 'callbackUrl', 'okUrl', 'failUrl', 'storetype', 'HASH'];
        
        foreach ($data as $key => $value) {
            $isRequired = in_array($key, $required);
            $status = $isRequired ? ($value ? '✅' : '❌ MANQUANT') : 'ℹ️';
            
            // Masquer les valeurs sensibles
            if ($key === 'HASH') {
                $displayValue = substr($value, 0, 30) . '... (longueur: ' . strlen($value) . ')';
            } elseif (strlen((string)$value) > 100) {
                $displayValue = substr($value, 0, 100) . '...';
            } else {
                $displayValue = $value ?: '(vide)';
            }
            
            $formatted[] = [
                $key,
                $displayValue,
                $status
            ];
        }
        
        return $formatted;
    }
    
    protected function checkCriticalParams(array $data): void
    {
        $checks = [
            'clientid' => 'ID client CMI',
            'callbackUrl' => 'URL de callback (CRITIQUE)',
            'okUrl' => 'URL de succès',
            'failUrl' => 'URL d\'échec',
            'amount' => 'Montant',
            'oid' => 'ID de commande',
            'storetype' => 'Type de magasin',
            'HASH' => 'Hash de sécurité',
        ];
        
        foreach ($checks as $param => $label) {
            if (isset($data[$param]) && !empty($data[$param])) {
                $this->line("  ✅ {$label}: Présent");
                
                // Vérifications spécifiques
                if ($param === 'callbackUrl' || $param === 'okUrl' || $param === 'failUrl') {
                    if (filter_var($data[$param], FILTER_VALIDATE_URL)) {
                        $this->line("     → URL valide");
                    } else {
                        $this->error("     → ❌ URL invalide !");
                    }
                    
                    if (strpos($data[$param], 'localhost') !== false || strpos($data[$param], '127.0.0.1') !== false) {
                        $this->error("     → ❌ URL pointe vers localhost - CMI ne peut pas y accéder !");
                    }
                }
                
                if ($param === 'storetype' && $data[$param] !== '3D_PAY_HOSTING') {
                    $this->error("     → ❌ Doit être exactement '3D_PAY_HOSTING'");
                }
                
                if ($param === 'HASH') {
                    $length = strlen($data[$param]);
                    if ($length < 80 || $length > 100) {
                        $this->warn("     → ⚠️ Longueur du hash suspecte: {$length} caractères");
                    } else {
                        $this->line("     → Longueur OK: {$length} caractères");
                    }
                }
            } else {
                $this->error("  ❌ {$label}: MANQUANT !");
            }
        }
    }
    
    protected function verifyHash(array $data, CMIReservationPaymentService $service): void
    {
        // Recréer le hash pour vérification
        $calculatedHash = $service->generateHash($data);
        
        if (isset($data['HASH'])) {
            if ($calculatedHash === $data['HASH']) {
                $this->line('  ✅ Hash calculé correctement');
            } else {
                $this->error('  ❌ Hash ne correspond pas !');
                $this->line('     Hash dans les données: ' . substr($data['HASH'], 0, 30) . '...');
                $this->line('     Hash calculé: ' . substr($calculatedHash, 0, 30) . '...');
            }
        }
    }
    
    protected function testCallbackUrl(string $url): void
    {
        if (empty($url)) {
            $this->error('  ❌ URL de callback manquante');
            return;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('  ❌ URL de callback invalide');
            return;
        }
        
        $this->line("  URL: {$url}");
        
        // Tester avec curl si disponible
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $this->warn("  ⚠️ Erreur curl: {$error}");
            } else {
                if ($httpCode >= 200 && $httpCode < 300) {
                    $this->line("  ✅ URL accessible (HTTP {$httpCode})");
                } elseif ($httpCode === 404) {
                    $this->error("  ❌ URL non trouvée (HTTP 404)");
                } elseif ($httpCode === 403) {
                    $this->error("  ❌ Accès interdit (HTTP 403) - Vérifier les middlewares");
                } else {
                    $this->warn("  ⚠️ Réponse HTTP {$httpCode}");
                }
            }
        } else {
            $this->warn('  ⚠️ curl non disponible - test manuel requis');
            $this->line("  Testez avec: curl -X POST '{$url}' -d 'test=1'");
        }
    }
}

