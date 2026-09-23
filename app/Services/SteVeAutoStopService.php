<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service d'arrêt automatique de la recharge SETEVE
 * 
 * @deprecated Use SteVeSessionService instead. This class will be removed in a future version.
 * 
 * Ce service gère l'arrêt automatique des sessions de recharge
 * lorsque les limites de réservation (minutes ou kWh) sont atteintes
 */
class SteVeAutoStopService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected SteVeRetryService $retryService;
    protected array $endpoints;

    public function __construct(SteVeRetryService $retryService)
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->retryService = $retryService;
        
        $this->endpoints = [
            'charging_sessions' => '/api/v1/charging-sessions',
            'stop_session' => '/api/v1/charging-sessions/{id}/stop',
            'session_status' => '/api/v1/charging-sessions/{id}',
            'meter_values' => '/api/v1/charging-sessions/{id}/meter-values'
        ];
    }

    /**
     * Vérifier et arrêter automatiquement les sessions qui ont atteint leurs limites
     */
    public function checkAndStopExpiredSessions(): array
    {
        try {
            Log::info('SteVeAutoStopService: Vérification des sessions expirées');

            // Récupérer toutes les sessions actives
            $activeSessions = $this->getActiveChargingSessions();
            
            if (empty($activeSessions)) {
                return [
                    'success' => true,
                    'message' => 'Aucune session active trouvée',
                    'sessions_checked' => 0,
                    'sessions_stopped' => 0
                ];
            }

            $sessionsChecked = 0;
            $sessionsStopped = 0;
            $results = [];

            foreach ($activeSessions as $session) {
                $sessionsChecked++;
                
                // Vérifier si la session doit être arrêtée
                $shouldStop = $this->shouldStopSession($session);
                
                if ($shouldStop['should_stop']) {
                    $stopResult = $this->stopSession($session, $shouldStop['reason']);
                    
                    if ($stopResult['success']) {
                        $sessionsStopped++;
                        $results[] = [
                            'session_id' => $session['id'],
                            'reservation_id' => $session['reservation_id'] ?? null,
                            'action' => 'stopped',
                            'reason' => $shouldStop['reason'],
                            'result' => $stopResult
                        ];
                    } else {
                        $results[] = [
                            'session_id' => $session['id'],
                            'reservation_id' => $session['reservation_id'] ?? null,
                            'action' => 'stop_failed',
                            'reason' => $shouldStop['reason'],
                            'error' => $stopResult['error']
                        ];
                    }
                } else {
                    $results[] = [
                        'session_id' => $session['id'],
                        'reservation_id' => $session['reservation_id'] ?? null,
                        'action' => 'continue',
                        'reason' => 'Limites non atteintes',
                        'remaining' => $shouldStop['remaining'] ?? null
                    ];
                }
            }

            Log::info('SteVeAutoStopService: Vérification terminée', [
                'sessions_checked' => $sessionsChecked,
                'sessions_stopped' => $sessionsStopped,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => "Vérification terminée: {$sessionsChecked} sessions vérifiées, {$sessionsStopped} arrêtées",
                'sessions_checked' => $sessionsChecked,
                'sessions_stopped' => $sessionsStopped,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la vérification des sessions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification des sessions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer toutes les sessions de recharge actives
     */
    protected function getActiveChargingSessions(): array
    {
        try {
            $url = $this->baseUrl . $this->endpoints['charging_sessions'];
            
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($url);

            if ($response->successful()) {
                $sessions = $response->json();
                
                // Filtrer seulement les sessions actives
                return array_filter($sessions, function($session) {
                    return isset($session['status']) && 
                           in_array($session['status'], ['active', 'charging', 'in_progress']);
                });
            } else {
                Log::warning('SteVeAutoStopService: Impossible de récupérer les sessions actives', [
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return [];
            }

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la récupération des sessions', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Vérifier si une session doit être arrêtée
     */
    protected function shouldStopSession(array $session): array
    {
        try {
            $sessionId = $session['id'];
            $reservationId = $session['reservation_id'] ?? null;
            
            if (!$reservationId) {
                return [
                    'should_stop' => false,
                    'reason' => 'Aucune réservation associée'
                ];
            }

            // Récupérer la réservation
            $reservation = Reservation::with(['pricingPlan'])->find($reservationId);
            if (!$reservation) {
                return [
                    'should_stop' => false,
                    'reason' => 'Réservation non trouvée'
                ];
            }

            // Vérifier les limites selon le type de réservation
            if ($reservation->reservation_type === 'minute') {
                return $this->checkTimeLimit($session, $reservation);
            } elseif ($reservation->reservation_type === 'kwh') {
                return $this->checkEnergyLimit($session, $reservation);
            }

            return [
                'should_stop' => false,
                'reason' => 'Type de réservation non supporté'
            ];

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la vérification de la session', [
                'session_id' => $session['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'should_stop' => false,
                'reason' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier la limite de temps (minutes)
     */
    protected function checkTimeLimit(array $session, Reservation $reservation): array
    {
        try {
            $maxDuration = $reservation->reservation_value; // en minutes
            $startTime = Carbon::parse($session['start_time'] ?? $session['created_at']);
            $currentTime = Carbon::now();
            $elapsedMinutes = $currentTime->diffInMinutes($startTime);

            if ($elapsedMinutes >= $maxDuration) {
                return [
                    'should_stop' => true,
                    'reason' => "Limite de temps atteinte ({$elapsedMinutes}min >= {$maxDuration}min)",
                    'elapsed_minutes' => $elapsedMinutes,
                    'max_minutes' => $maxDuration
                ];
            }

            return [
                'should_stop' => false,
                'reason' => "Limite de temps non atteinte",
                'remaining' => [
                    'elapsed_minutes' => $elapsedMinutes,
                    'max_minutes' => $maxDuration,
                    'remaining_minutes' => $maxDuration - $elapsedMinutes
                ]
            ];

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la vérification de la limite de temps', [
                'session_id' => $session['id'],
                'error' => $e->getMessage()
            ]);

            return [
                'should_stop' => false,
                'reason' => 'Erreur lors de la vérification de la limite de temps'
            ];
        }
    }

    /**
     * Vérifier la limite d'énergie (kWh)
     */
    protected function checkEnergyLimit(array $session, Reservation $reservation): array
    {
        try {
            $maxEnergy = $reservation->reservation_value; // en kWh
            
            // Récupérer les valeurs de compteur actuelles
            $meterValues = $this->getMeterValues($session['id']);
            $currentEnergy = $meterValues['energy_delivered'] ?? 0;

            if ($currentEnergy >= $maxEnergy) {
                return [
                    'should_stop' => true,
                    'reason' => "Limite d'énergie atteinte ({$currentEnergy}kWh >= {$maxEnergy}kWh)",
                    'current_energy' => $currentEnergy,
                    'max_energy' => $maxEnergy
                ];
            }

            return [
                'should_stop' => false,
                'reason' => "Limite d'énergie non atteinte",
                'remaining' => [
                    'current_energy' => $currentEnergy,
                    'max_energy' => $maxEnergy,
                    'remaining_energy' => $maxEnergy - $currentEnergy
                ]
            ];

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la vérification de la limite d\'énergie', [
                'session_id' => $session['id'],
                'error' => $e->getMessage()
            ]);

            return [
                'should_stop' => false,
                'reason' => 'Erreur lors de la vérification de la limite d\'énergie'
            ];
        }
    }

    /**
     * Récupérer les valeurs de compteur d'une session
     */
    protected function getMeterValues(string $sessionId): array
    {
        try {
            $url = $this->baseUrl . str_replace('{id}', $sessionId, $this->endpoints['meter_values']);
            
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'energy_delivered' => $data['energy_delivered'] ?? 0,
                    'power' => $data['power'] ?? 0,
                    'current' => $data['current'] ?? 0,
                    'voltage' => $data['voltage'] ?? 0,
                    'timestamp' => $data['timestamp'] ?? null
                ];
            } else {
                Log::warning('SteVeAutoStopService: Impossible de récupérer les valeurs de compteur', [
                    'session_id' => $sessionId,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'energy_delivered' => 0,
                    'power' => 0,
                    'current' => 0,
                    'voltage' => 0
                ];
            }

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la récupération des valeurs de compteur', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'energy_delivered' => 0,
                'power' => 0,
                'current' => 0,
                'voltage' => 0
            ];
        }
    }

    /**
     * Arrêter une session de recharge
     */
    protected function stopSession(array $session, string $reason): array
    {
        try {
            $sessionId = $session['id'];
            $reservationId = $session['reservation_id'] ?? null;
            
            Log::info('SteVeAutoStopService: Arrêt de session', [
                'session_id' => $sessionId,
                'reservation_id' => $reservationId,
                'reason' => $reason
            ]);

            // Utiliser le service de retry pour arrêter la session
            $result = $this->retryService->executeWithRetry(function() use ($sessionId) {
                return $this->performStopSession($sessionId);
            }, [
                'operation' => 'stop_session',
                'session_id' => $sessionId,
                'reservation_id' => $reservationId
            ]);

            if ($result['success']) {
                // Mettre à jour la réservation
                if ($reservationId) {
                    $this->updateReservationAfterStop($reservationId, $session, $reason);
                }

                // Envoyer une notification
                $this->sendStopNotification($reservationId, $session, $reason);

                Log::info('SteVeAutoStopService: Session arrêtée avec succès', [
                    'session_id' => $sessionId,
                    'reservation_id' => $reservationId,
                    'reason' => $reason
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de l\'arrêt de la session', [
                'session_id' => $session['id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de l\'arrêt de la session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Effectuer l'arrêt de la session via l'API SETEVE
     */
    protected function performStopSession(string $sessionId): array
    {
        try {
            $url = $this->baseUrl . str_replace('{id}', $sessionId, $this->endpoints['stop_session']);
            
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->post($url);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'message' => 'Session arrêtée avec succès',
                    'data' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Erreur API: ' . $response->body(),
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'arrêt: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mettre à jour la réservation après arrêt
     */
    protected function updateReservationAfterStop(int $reservationId, array $session, string $reason): void
    {
        try {
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return;
            }

            // Récupérer les valeurs finales
            $meterValues = $this->getMeterValues($session['id']);
            $finalEnergy = $meterValues['energy_delivered'] ?? 0;
            $finalDuration = $this->calculateFinalDuration($session);

            // Mettre à jour la réservation
            $reservation->update([
                'status' => 'completed',
                'charging_stopped_at' => now(),
                'actual_energy' => $finalEnergy,
                'actual_duration' => $finalDuration,
                'auto_stop_reason' => $reason,
                'auto_stopped' => true
            ]);

            // Mettre à jour la transaction si elle existe
            $transaction = $reservation->transaction;
            if ($transaction) {
                $transaction->update([
                    'status' => 'completed',
                    'stop_timestamp' => now(),
                    'meter_stop' => $finalEnergy,
                    'energy_delivered' => $finalEnergy,
                    'duration_minutes' => $finalDuration
                ]);
            }

            // Gestion du coût final et des remboursements/débits automatiques
            try {
                $chargingPoint = $reservation->chargingPoint;
                if ($chargingPoint) {
                    $costData = \App\Services\ChargingSessionCostService::calculateDetailedCost(
                        $chargingPoint, 
                        (float) $finalEnergy, 
                        (float) $finalDuration
                    );
                    $actualCost = $costData['total_with_vat'] ?? 0;
                    
                    $reservation->actual_cost = $actualCost;
                    $reservation->save();

                    // Mettre à jour la transaction avec le montant réel
                    if ($transaction) {
                        $transaction->price_total = $actualCost;
                        $transaction->save();
                    }

                    // Débit ou Remboursement automatique
                    $paymentMode = strtolower($reservation->payment_mode ?? '');
                    if ($paymentMode === 'postpaid') {
                        $paymentResult = app(\App\Services\CreditPaymentService::class)->finalizePostpaidPayment($reservation, $actualCost);
                        Log::info('Tentative de paiement postpayé finalisé depuis SteVeAutoStopService', ['reservation_id' => $reservationId, 'actual_cost' => $actualCost, 'success' => $paymentResult['success']]);
                        
                        if (!$paymentResult['success']) {
                            // Wallet payment failed (e.g., insufficient funds). Generate link via UnifiedPaymentService
                            try {
                                $reservation->update(['estimated_cost' => $actualCost, 'amount' => $actualCost]);
                                $unifiedService = app(\App\Services\UnifiedPaymentService::class);
                                $unifiedResult = $unifiedService->initiatePayment($reservation, 'stripe', ['postpaid_invoice' => true]);
                                
                                $reservation->update([
                                    'payment_status' => 'pending_invoice',
                                    'metadata' => array_merge($reservation->metadata ?? [], [
                                        'stripe_payment_url' => $unifiedResult['payment_url'] ?? $unifiedResult['redirect_url'] ?? null
                                    ])
                                ]);
                                Log::info('Lien de facturation Stripe généré pour la réservation ' . $reservationId);
                            } catch (\Exception $e) {
                                $reservation->update(['payment_status' => 'failed']);
                                Log::error('Erreur lors de la génération de facture Stripe pour la réservation ' . $reservationId, ['error' => $e->getMessage()]);
                            }
                        }
                    } elseif ($paymentMode === 'prepaid') {
                        // Récupérer le montant payé ou estimé si prepaid_amount n'est pas rempli
                        $prepaidAmount = $reservation->prepaid_amount ?? $reservation->estimated_cost ?? $reservation->amount ?? 0;
                        if ($actualCost < $prepaidAmount) {
                            app(\App\Services\CreditPaymentService::class)->processPrepaidRefund($reservation);
                            Log::info('Remboursement prépayé processé depuis SteVeAutoStopService', ['reservation_id' => $reservationId, 'actual_cost' => $actualCost, 'prepaid_amount' => $prepaidAmount]);
                        }
                    }
                }
            } catch (\Exception $costError) {
                Log::error('SteVeAutoStopService: Erreur lors du calcul du coût/paiement final', [
                    'reservation_id' => $reservationId,
                    'error' => $costError->getMessage()
                ]);
            }

            Log::info('SteVeAutoStopService: Réservation mise à jour après arrêt', [
                'reservation_id' => $reservationId,
                'final_energy' => $finalEnergy,
                'final_duration' => $finalDuration,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la mise à jour de la réservation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculer la durée finale de la session
     */
    protected function calculateFinalDuration(array $session): int
    {
        try {
            $startTime = Carbon::parse($session['start_time'] ?? $session['created_at']);
            $stopTime = Carbon::now();
            
            return $stopTime->diffInMinutes($startTime);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Envoyer une notification d'arrêt
     */
    protected function sendStopNotification(int $reservationId, array $session, string $reason): void
    {
        try {
            $reservation = Reservation::with(['user', 'chargingPoint'])->find($reservationId);
            if (!$reservation) {
                return;
            }

            $customerEmail = $reservation->guest_email ?? $reservation->user?->email;
            $chargingPoint = $reservation->chargingPoint;
            
            Log::info('SteVeAutoStopService: Notification d\'arrêt automatique', [
                'reservation_id' => $reservationId,
                'customer_email' => $customerEmail,
                'charging_point' => $chargingPoint?->name ?? 'Inconnu',
                'reason' => $reason,
                'session_id' => $session['id']
            ]);

            // Ici vous pouvez ajouter l'envoi d'email, SMS, etc.
            // Par exemple:
            // if ($customerEmail) {
            //     Mail::to($customerEmail)->send(new ChargingAutoStoppedMail($reservation, $reason));
            // }

        } catch (\Exception $e) {
            Log::warning('SteVeAutoStopService: Erreur lors de l\'envoi de notification', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifier une session spécifique
     */
    public function checkSpecificSession(string $sessionId): array
    {
        try {
            $session = $this->getSessionById($sessionId);
            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session non trouvée'
                ];
            }

            $shouldStop = $this->shouldStopSession($session);
            
            if ($shouldStop['should_stop']) {
                $stopResult = $this->stopSession($session, $shouldStop['reason']);
                return [
                    'success' => true,
                    'action' => 'stopped',
                    'reason' => $shouldStop['reason'],
                    'result' => $stopResult
                ];
            } else {
                return [
                    'success' => true,
                    'action' => 'continue',
                    'reason' => $shouldStop['reason'],
                    'remaining' => $shouldStop['remaining'] ?? null
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer une session par son ID
     */
    protected function getSessionById(string $sessionId): ?array
    {
        try {
            $url = $this->baseUrl . str_replace('{id}', $sessionId, $this->endpoints['session_status']);
            
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            } else {
                return null;
            }

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopService: Erreur lors de la récupération de la session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Obtenir les statistiques des sessions
     */
    public function getSessionStats(): array
    {
        try {
            $activeSessions = $this->getActiveChargingSessions();
            
            $stats = [
                'total_active_sessions' => count($activeSessions),
                'sessions_by_status' => [],
                'sessions_by_reservation_type' => [],
                'total_energy_delivered' => 0,
                'average_session_duration' => 0
            ];

            foreach ($activeSessions as $session) {
                // Statistiques par statut
                $status = $session['status'] ?? 'unknown';
                $stats['sessions_by_status'][$status] = ($stats['sessions_by_status'][$status] ?? 0) + 1;

                // Statistiques par type de réservation
                if (isset($session['reservation_id'])) {
                    $reservation = Reservation::find($session['reservation_id']);
                    if ($reservation) {
                        $type = $reservation->reservation_type;
                        $stats['sessions_by_reservation_type'][$type] = ($stats['sessions_by_reservation_type'][$type] ?? 0) + 1;
                    }
                }

                // Énergie totale
                $meterValues = $this->getMeterValues($session['id']);
                $stats['total_energy_delivered'] += $meterValues['energy_delivered'] ?? 0;
            }

            return [
                'success' => true,
                'stats' => $stats,
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ];
        }
    }
}
