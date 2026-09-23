<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CMIPaymentService
{
    private $baseUrl;
    private $storeId;
    private $storeKey;

    public function __construct()
    {
        $this->baseUrl = config('payments.cmi.base_url', config('payment.cmi.base_url'));
        // Utiliser client_id si disponible, sinon store_id
        $this->storeId = config('payments.cmi.client_id', config('payment.cmi.client_id', config('payment.cmi.store_id')));
        $this->storeKey = config('payments.cmi.store_key', config('payment.cmi.store_key'));
        
        // Valider la configuration
        if (empty($this->storeKey)) {
            Log::warning('CMI Store Key manquante dans la configuration');
        }
        if (empty($this->storeId)) {
            Log::warning('CMI Store ID/Client ID manquant dans la configuration');
        }
    }

    /**
     * Create CMI payment
     */
    public function createPayment(Payment $payment)
    {
        try {
            // Valider la configuration
            if (empty($this->storeKey) || empty($this->storeId)) {
                throw new \Exception('Configuration CMI incomplète. Veuillez vérifier les clés de configuration.');
            }

            // Valider le montant
            if ($payment->amount <= 0) {
                throw new \Exception('Le montant du paiement doit être supérieur à 0');
            }

            $amount = (int) round($payment->amount * 100); // Convert to cents avec arrondi
            $currency = strtoupper($payment->currency ?? 'EUR');
            
            // Générer un identifiant unique pour la transaction
            $transactionId = $payment->transaction_id ?? 'PAY-' . $payment->id . '-' . time();
            
            $data = [
                'storetype' => '3D_PAY_HOSTING',
                'clientid' => $this->storeId,
                'amount' => (string) $amount,
                'currency' => $currency,
                'oid' => $transactionId,
                'okUrl' => route('payment.success', $payment->id),
                'failUrl' => route('payment.failure', $payment->id),
                'rnd' => (string) time(),
                'hashAlgorithm' => 'ver3',
                'refreshtime' => '0',
                'lang' => 'fr',
                'email' => $payment->reservation->user_email ?? $payment->reservation->user->email ?? '',
                'tel' => $payment->reservation->user_phone ?? $payment->reservation->user->phone ?? '',
            ];

            // Generate hash selon la documentation CMI
            $hashString = $data['amount'] . $data['oid'] . $data['okUrl'] . $data['failUrl'] . $data['rnd'] . $this->storeKey;
            $data['hash'] = base64_encode(pack('H*', sha1($hashString)));

            // Update payment data
            $payment->update([
                'payment_data' => $data,
                'status' => 'processing',
                'transaction_id' => $transactionId,
            ]);

            Log::info('CMI Payment créé avec succès', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => true,
                'redirect_url' => $this->baseUrl,
                'form_data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur création paiement CMI', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $payment->update([
                'status' => 'failed',
                'payment_data' => ['error' => $e->getMessage()]
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la création du paiement CMI: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle CMI webhook
     */
    public function handleWebhook(array $data)
    {
        try {
            Log::info('CMI Webhook received', $data);

            $transactionId = $data['oid'] ?? null;
            $status = $data['Response'] ?? null;
            $hash = $data['HASH'] ?? null;

            if (!$transactionId || !$status) {
                Log::warning('CMI Webhook: Paramètres manquants', [
                    'has_oid' => !empty($transactionId),
                    'has_response' => !empty($status),
                    'data' => $data
                ]);
                return ['success' => false, 'message' => 'Paramètres requis manquants'];
            }

            // Verify hash
            if (!$this->verifyHash($data, $hash)) {
                Log::warning('CMI Hash verification failed', [
                    'transaction_id' => $transactionId,
                    'data' => $data
                ]);
                return ['success' => false, 'message' => 'Échec de la vérification du hash'];
            }

            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::warning('CMI Webhook: Paiement non trouvé', [
                    'transaction_id' => $transactionId
                ]);
                return ['success' => false, 'message' => 'Paiement non trouvé'];
            }

            // Vérifier le code de retour
            $procReturnCode = $data['ProcReturnCode'] ?? null;
            if ($procReturnCode !== '00' && $status !== 'Approved') {
                $errorMsg = $data['ErrMsg'] ?? 'Paiement échoué';
                $payment->markAsFailed($errorMsg);
                Log::warning('CMI Payment failed', [
                    'payment_id' => $payment->id,
                    'reason' => $errorMsg,
                    'proc_return_code' => $procReturnCode
                ]);
                return ['success' => true, 'message' => 'Paiement échoué enregistré'];
            }

            if ($status === 'Approved' || $procReturnCode === '00') {
                $transId = $data['TransId'] ?? null;
                $payment->markAsCompleted($transId);
                Log::info('CMI Payment completed', [
                    'payment_id' => $payment->id,
                    'trans_id' => $transId,
                    'amount' => $data['amount'] ?? null
                ]);
            } else {
                $errorMsg = $data['ErrMsg'] ?? 'Paiement échoué';
                $payment->markAsFailed($errorMsg);
                Log::warning('CMI Payment failed', [
                    'payment_id' => $payment->id,
                    'reason' => $errorMsg,
                    'status' => $status,
                    'proc_return_code' => $procReturnCode
                ]);
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Erreur traitement webhook CMI', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify CMI hash
     */
    private function verifyHash(array $data, string $receivedHash)
    {
        $hashString = $data['amount'] . $data['oid'] . $data['Response'] . $this->storeKey;
        $calculatedHash = base64_encode(pack('H*', sha1($hashString)));
        
        return hash_equals($calculatedHash, $receivedHash);
    }
}
