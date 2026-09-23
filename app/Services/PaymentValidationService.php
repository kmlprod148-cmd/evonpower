<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Service de validation des paiements
 * 
 * Valide les données de paiement avant traitement
 */
class PaymentValidationService
{
    /**
     * Valide une demande de paiement
     * 
     * @param Reservation $reservation
     * @param string $paymentMethod
     * @param array $data
     * @return array
     */
    public function validatePaymentRequest(Reservation $reservation, string $paymentMethod, array $data = []): array
    {
        $errors = [];

        // Validation de base
        if (!$reservation) {
            $errors[] = 'Réservation non trouvée';
            return $this->buildValidationResponse(false, $errors);
        }

        // Vérifier le statut de la réservation (gérer enum ou string)
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;
        if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
            $errors[] = 'Cette réservation ne peut pas être payée';
        }

        // Vérifier le montant
        $amount = $reservation->estimated_cost ?? $reservation->amount ?? 0;
        if ($amount <= 0) {
            $errors[] = 'Le montant de la réservation est invalide';
        }

        // Vérifier le montant minimum
        $minAmount = config('payments.general.min_amount', 0.50);
        if ($amount < $minAmount) {
            $errors[] = "Le montant minimum est de {$minAmount} EUR";
        }

        // Vérifier le montant maximum
        $maxAmount = config('payments.general.max_amount', 10000);
        if ($amount > $maxAmount) {
            $errors[] = "Le montant maximum est de {$maxAmount} EUR";
        }

        // Validation spécifique selon la méthode
        switch ($paymentMethod) {
            case 'cmi':
                $cmiErrors = $this->validateCmiPayment($reservation, $data);
                $errors = array_merge($errors, $cmiErrors);
                break;
            case 'stripe':
                $stripeErrors = $this->validateStripePayment($reservation, $data);
                $errors = array_merge($errors, $stripeErrors);
                break;
            default:
                $errors[] = "Méthode de paiement non supportée: {$paymentMethod}";
        }

        return $this->buildValidationResponse(empty($errors), $errors);
    }

    /**
     * Valide un paiement CMI
     */
    protected function validateCmiPayment(Reservation $reservation, array $data): array
    {
        $errors = [];

        // Vérifier que l'utilisateur a les informations nécessaires
        $user = $reservation->user;
        if (!$user) {
            $errors[] = 'Utilisateur non trouvé pour la réservation';
            return $errors;
        }

        if (empty($user->email)) {
            $errors[] = 'L\'adresse email est requise pour le paiement CMI';
        }

        // Valider l'email
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email n\'est pas valide';
        }

        // Vérifier que CMI est configuré et activé
        $cmiConfig = config('payments.cmi', []);
        if (empty($cmiConfig['store_key']) && empty(env('CMI_STORE_KEY'))) {
            $errors[] = 'CMI n\'est pas correctement configuré. Veuillez contacter l\'administrateur.';
        }

        // Vérifier que CMI est activé via PaymentKeysService
        try {
            $paymentKeysService = app(\App\Services\PaymentKeysService::class);
            $cmiKeys = $paymentKeysService->getActiveCmiKeys();
            if (empty($cmiKeys['storekey'])) {
                $errors[] = 'CMI n\'est pas activé. Veuillez contacter l\'administrateur.';
            }
        } catch (\Exception $e) {
            Log::warning('Erreur lors de la vérification des clés CMI', [
                'error' => $e->getMessage()
            ]);
            // Ne pas bloquer si le service n'est pas disponible, mais logger
        }

        return $errors;
    }

    /**
     * Valide un paiement Stripe
     */
    protected function validateStripePayment(Reservation $reservation, array $data): array
    {
        $errors = [];

        // Vérifier que l'utilisateur a les informations nécessaires
        $user = $reservation->user;
        if (!$user) {
            $errors[] = 'Utilisateur non trouvé pour la réservation';
            return $errors;
        }

        if (empty($user->email)) {
            $errors[] = 'L\'adresse email est requise pour le paiement Stripe';
        }

        // Valider l'email
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email n\'est pas valide';
        }

        // Vérifier que Stripe est configuré et activé
        $stripeConfig = config('payments.stripe', []);
        if (empty($stripeConfig['secret_key']) && empty(env('STRIPE_SECRET_KEY'))) {
            $errors[] = 'Stripe n\'est pas correctement configuré. Veuillez contacter l\'administrateur.';
        }

        // Vérifier que Stripe est activé
        $paymentKeysService = app(\App\Services\PaymentKeysService::class);
        $stripeKeys = $paymentKeysService->getActiveStripeKeys();
        if (empty($stripeKeys['secret_key'])) {
            $errors[] = 'Stripe n\'est pas activé. Veuillez contacter l\'administrateur.';
        }

        return $errors;
    }

    /**
     * Valide les données de callback CMI
     */
    public function validateCmiCallback(array $callbackData): array
    {
        $errors = [];

        // Champs requis
        $requiredFields = ['oid', 'amount', 'hash', 'ProcReturnCode'];
        foreach ($requiredFields as $field) {
            if (!isset($callbackData[$field])) {
                $errors[] = "Le champ {$field} est requis dans le callback CMI";
            }
        }

        // Vérifier le code de retour
        if (isset($callbackData['ProcReturnCode']) && $callbackData['ProcReturnCode'] !== '00') {
            $errorMsg = $callbackData['ErrMsg'] ?? 'Code retour invalide';
            $errors[] = "Paiement échoué: {$errorMsg}";
        }

        return $this->buildValidationResponse(empty($errors), $errors);
    }

    /**
     * Valide les données de webhook Stripe
     */
    public function validateStripeWebhook(array $webhookData): array
    {
        $errors = [];

        // Vérifier la structure de base
        if (!isset($webhookData['type'])) {
            $errors[] = 'Le type d\'événement est requis';
        }

        if (!isset($webhookData['data']['object'])) {
            $errors[] = 'Les données de l\'objet sont requises';
        }

        // Vérifier les types d'événements supportés
        $supportedEvents = [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
        ];

        if (isset($webhookData['type']) && !in_array($webhookData['type'], $supportedEvents)) {
            // Ne pas considérer comme erreur, juste un événement non géré
            Log::info('Événement Stripe non géré', [
                'type' => $webhookData['type']
            ]);
        }

        return $this->buildValidationResponse(empty($errors), $errors);
    }

    /**
     * Construit la réponse de validation
     */
    protected function buildValidationResponse(bool $valid, array $errors = []): array
    {
        return [
            'valid' => $valid,
            'errors' => $errors,
            'error' => !empty($errors) ? implode(', ', $errors) : null,
            'message' => !empty($errors) ? implode(', ', $errors) : 'Validation réussie'
        ];
    }
}
