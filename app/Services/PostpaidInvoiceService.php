<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\User;
use App\Services\ChargingSessionCostService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PostpaidInvoiceService
{
    /**
     * Générer une facture détaillée pour une session postpayée
     * 
     * @param ChargingSession $session
     * @return array
     */
    public function generateInvoice(ChargingSession $session): array
    {
        try {
            if (!$session->isPostpaid() || $session->status !== 'completed') {
                throw new \Exception('La session doit être postpayée et terminée pour générer une facture');
            }

            $chargingPoint = $session->chargingPoint;
            $user = $session->user;
            $pricingPlan = $chargingPoint->businessProfile->pricingPlan ?? null;

            // Calculer le détail des coûts
            $costBreakdown = ChargingSessionCostService::calculateDetailedCost(
                $chargingPoint,
                $session->energy_consumed ?? 0,
                $session->duration ?? 0
            );

            // Générer le numéro de facture
            $invoiceNumber = $this->generateInvoiceNumber($session);

            // Créer la structure de la facture
            $invoice = [
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now()->format('Y-m-d'),
                'invoice_time' => now()->format('H:i:s'),
                'session' => [
                    'session_id' => $session->session_id,
                    'started_at' => $session->started_at->format('Y-m-d H:i:s'),
                    'ended_at' => $session->ended_at->format('Y-m-d H:i:s'),
                    'duration_minutes' => $session->duration ?? 0,
                    'duration_formatted' => $this->formatDuration($session->duration ?? 0),
                    'energy_consumed_kwh' => $session->energy_consumed ?? 0,
                ],
                'charging_point' => [
                    'id' => $chargingPoint->id,
                    'name' => $chargingPoint->name,
                    'address' => $this->formatAddress($chargingPoint),
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name ?? $user->email,
                    'email' => $user->email,
                ],
                'pricing' => [
                    'activation_fee' => $costBreakdown['activation_fee'],
                    'energy_cost' => $costBreakdown['energy_cost'],
                    'time_cost' => $costBreakdown['time_cost'],
                    'fixed_cost' => $costBreakdown['fixed_cost'],
                    'subtotal' => $costBreakdown['subtotal'],
                    'vat_rate' => $costBreakdown['vat_rate'],
                    'vat_amount' => $costBreakdown['vat_amount'],
                    'total' => $costBreakdown['total_with_vat'],
                    'currency' => $costBreakdown['currency'],
                ],
                'breakdown' => $this->generateDetailedBreakdown($session, $costBreakdown, $pricingPlan),
                'payment' => [
                    'status' => $session->payment_status,
                    'paid_at' => $session->ended_at->format('Y-m-d H:i:s'),
                    'method' => 'wallet',
                ],
            ];

            // Enregistrer la facture dans les métadonnées de la session
            $session->update([
                'metadata' => array_merge($session->metadata ?? [], [
                    'invoice' => $invoice,
                ]),
            ]);

            // Envoyer la facture par email si configuré
            $this->sendInvoiceEmail($user, $invoice);

            Log::info('Facture générée pour session postpayée', [
                'session_id' => $session->session_id,
                'invoice_number' => $invoiceNumber,
                'total' => $costBreakdown['total_with_vat'],
            ]);

            return $invoice;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération de la facture', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Générer un numéro de facture unique
     */
    protected function generateInvoiceNumber(ChargingSession $session): string
    {
        $date = now()->format('Ymd');
        $sessionId = substr($session->session_id, -8);
        return 'INV-' . $date . '-' . strtoupper($sessionId);
    }

    /**
     * Formater la durée en format lisible
     */
    protected function formatDuration(float $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = floor($minutes % 60);
        $secs = floor(($minutes * 60) % 60);

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $mins, $secs);
        } elseif ($mins > 0) {
            return sprintf('%dm %02ds', $mins, $secs);
        } else {
            return sprintf('%ds', $secs);
        }
    }

    /**
     * Formater l'adresse de la borne
     */
    protected function formatAddress($chargingPoint): string
    {
        $parts = array_filter([
            $chargingPoint->address,
            $chargingPoint->city,
            $chargingPoint->postal_code,
            $chargingPoint->country,
        ]);

        return implode(', ', $parts) ?: 'Adresse non disponible';
    }

    /**
     * Générer le détail ligne par ligne de la facture
     */
    protected function generateDetailedBreakdown(ChargingSession $session, array $costBreakdown, $pricingPlan): array
    {
        $lines = [];

        // Frais d'activation
        if ($costBreakdown['activation_fee'] > 0) {
            $lines[] = [
                'description' => 'Frais d\'activation',
                'quantity' => 1,
                'unit_price' => $costBreakdown['activation_fee'],
                'total' => $costBreakdown['activation_fee'],
                'type' => 'activation_fee',
            ];
        }

        // Coût par kWh
        if ($costBreakdown['energy_cost'] > 0 && $session->energy_consumed > 0) {
            $unitPrice = $pricingPlan ? ($pricingPlan->price_per_kwh ?? 0) : 0;
            $lines[] = [
                'description' => 'Énergie consommée',
                'quantity' => round($session->energy_consumed, 3),
                'unit' => 'kWh',
                'unit_price' => $unitPrice,
                'total' => $costBreakdown['energy_cost'],
                'type' => 'energy',
            ];
        }

        // Coût par minute
        if ($costBreakdown['time_cost'] > 0 && $session->duration > 0) {
            $unitPrice = $pricingPlan ? ($pricingPlan->price_per_minute ?? 0) : 0;
            $lines[] = [
                'description' => 'Durée de recharge',
                'quantity' => round($session->duration, 2),
                'unit' => 'min',
                'unit_price' => $unitPrice,
                'total' => $costBreakdown['time_cost'],
                'type' => 'time',
            ];
        }

        // Prix fixe
        if ($costBreakdown['fixed_cost'] > 0) {
            $lines[] = [
                'description' => 'Prix fixe',
                'quantity' => 1,
                'unit_price' => $costBreakdown['fixed_cost'],
                'total' => $costBreakdown['fixed_cost'],
                'type' => 'fixed',
            ];
        }

        return $lines;
    }

    /**
     * Envoyer la facture par email
     */
    protected function sendInvoiceEmail(User $user, array $invoice): void
    {
        try {
            if (!config('charging.send_invoice_email', true)) {
                return;
            }

            if (empty($user->email)) {
                Log::warning('Impossible d\'envoyer la facture: email utilisateur manquant', [
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Utiliser la classe Mail de Laravel pour envoyer l'email
            // Pour l'instant, on log juste l'action
            Log::info('Facture prête à être envoyée par email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'invoice_number' => $invoice['invoice_number'],
            ]);

            // TODO: Créer une vue email pour la facture et utiliser Mail::send()
            // Mail::to($user->email)->send(new PostpaidInvoiceMail($invoice));

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la facture par email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Récupérer une facture existante
     */
    public function getInvoice(ChargingSession $session): ?array
    {
        if (isset($session->metadata['invoice'])) {
            return $session->metadata['invoice'];
        }

        return null;
    }
}

