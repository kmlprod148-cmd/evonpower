<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\ChargingSession;
use App\Models\SubscriptionUsageLog;
use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionPlanType;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service de gestion des sessions d'abonnement avec limites configurables
 * 
 * Fonctionnalités:
 * - Limites de sessions configurables par plan
 * - Comptage précis des sessions utilisées
 * - Blocage automatique à l'épuisement des quotas
 * - Journalisation détaillée de l'utilisation
 */
class SubscriptionSessionService
{
    /**
     * Seuil d'alerte avant épuisement des quotas (en pourcentage)
     */
    public const WARNING_THRESHOLD = 0.2; // 20%

    /**
     * Nombre de sessions maximum qu'un utilisateur peut avoir simultanément
     */
    public const MAX_CONCURRENT_SESSIONS = 1;

    public function __construct(
        protected MoneyService $moneyService
    ) {}

    /**
     * Vérifier si l'utilisateur peut démarrer une session avec son abonnement
     * 
     * @param User $user
     * @param int|null $chargingPointId
     * @return array
     */
    public function canStartSession(User $user, ?int $chargingPointId = null): array
    {
        $subscription = $this->getActiveSubscriptionForUser($user);
        
        if (!$subscription) {
            return [
                'can_start' => false,
                'reason' => 'no_active_subscription',
                'message' => 'Aucun abonnement actif',
            ];
        }

        // Vérifier le statut de l'abonnement
        if (!$subscription->allowsUsage()) {
            return [
                'can_start' => false,
                'reason' => 'subscription_inactive',
                'message' => 'Abonnement non actif ou expiré',
                'subscription_status' => $subscription->status,
            ];
        }

        // Vérifier les limites de sessions
        $sessionLimitCheck = $this->checkSessionLimit($subscription);
        if (!$sessionLimitCheck['allowed']) {
            return [
                'can_start' => false,
                'reason' => 'session_limit_reached',
                'message' => $sessionLimitCheck['message'],
                'sessions_used' => $subscription->sessions_used,
                'sessions_max' => $subscription->subscriptionPlan->max_sessions,
            ];
        }

        // Vérifier les sessions concurrentes
        $concurrentCheck = $this->checkConcurrentSessions($user);
        if (!$concurrentCheck['allowed']) {
            return [
                'can_start' => false,
                'reason' => 'concurrent_session_limit',
                'message' => $concurrentCheck['message'],
            ];
        }

        return [
            'can_start' => true,
            'subscription' => [
                'id' => $subscription->id,
                'plan_name' => $subscription->subscriptionPlan->name,
                'sessions_used' => $subscription->sessions_used,
                'sessions_remaining' => $subscription->getRemainingSessions(),
            ],
        ];
    }

    /**
     * Démarrer une session de charge avec un abonnement
     * 
     * @throws InsufficientBalanceException
     */
    public function startSessionWithSubscription(
        User $user,
        ChargingSession $session,
        ?int $subscriptionId = null
    ): ChargingSession {
        // Déterminer l'abonnement à utiliser
        $subscription = $this->resolveSubscription($user, $subscriptionId);
        
        if (!$subscription) {
            throw new InsufficientBalanceException('Aucun abonnement actif disponible');
        }

        // Vérifier une dernière fois avant le démarrage
        $canStart = $this->canStartSession($user, $session->charging_point_id);
        if (!$canStart['can_start']) {
            throw new InsufficientBalanceException($canStart['message']);
        }

        return DB::transaction(function () use ($subscription, $session, $user) {
            // Incrémenter le compteur de sessions
            $sessionsBefore = $subscription->sessions_used;
            $subscription->incrementSessions();
            $subscription->refresh();

            // Créer le log d'utilisation
            $this->logSessionStart($subscription, $session, $sessionsBefore);

            // Mettre à jour la session de charge
            $session->update([
                'user_subscription_id' => $subscription->id,
                'subscription_session_counted' => true,
            ]);

            // Vérifier si on est proche de la limite et envoyer une notification
            $this->checkAndNotifyThreshold($subscription);

            Log::info('SubscriptionSessionService: Session démarrée avec abonnement', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'subscription_id' => $subscription->id,
                'sessions_before' => $sessionsBefore,
                'sessions_after' => $subscription->sessions_used,
            ]);

            return $session;
        });
    }

    /**
     * Finaliser une session de charge et enregistrer l'utilisation
     */
    public function finalizeSession(
        ChargingSession $session,
        float $energyKwh,
        int $durationMinutes
    ): array {
        return DB::transaction(function () use ($session, $energyKwh, $durationMinutes) {
            $subscription = $session->userSubscription;
            
            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'Aucune subscription associée',
                ];
            }

            $plan = $subscription->subscriptionPlan;

            // Enregistrer les其他的 utilisations (kWh, durée)
            $this->logSessionUsage($subscription, $session, $energyKwh, $durationMinutes);

            // Mettre à jour les compteurs
            if ($plan->max_kwh !== null) {
                $subscription->incrementKwh($energyKwh);
            }
            
            if ($plan->max_duration_minutes !== null) {
                $subscription->incrementDuration($durationMinutes);
            }

            $subscription->refresh();

            // Vérifier si les quotas sont épuisés après cette session
            $quotaStatus = $this->checkQuotaStatus($subscription);
            
            if ($quotaStatus['exhausted']) {
                $this->handleQuotaExhausted($subscription);
            }

            Log::info('SubscriptionSessionService: Session finalisée', [
                'session_id' => $session->id,
                'subscription_id' => $subscription->id,
                'energy_kwh' => $energyKwh,
                'duration_minutes' => $durationMinutes,
                'quota_exhausted' => $quotaStatus['exhausted'],
            ]);

            return [
                'success' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'sessions_used' => $subscription->sessions_used,
                    'sessions_remaining' => $subscription->getRemainingSessions(),
                    'kwh_used' => $subscription->kwh_used,
                    'kwh_remaining' => $subscription->getRemainingKwh(),
                    'duration_used' => $subscription->duration_minutes_used,
                    'duration_remaining' => $subscription->getRemainingDuration(),
                ],
                'quota_exhausted' => $quotaStatus['exhausted'],
                'quota_status' => $quotaStatus,
            ];
        });
    }

    /**
     * Obtenir l'abonnement actif pour un utilisateur
     */
    public function getActiveSubscriptionForUser(User $user): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('end_date', '>=', now())
            ->with('subscriptionPlan')
            ->orderBy('end_date', 'desc')
            ->first();
    }

    /**
     * Résoudre l'abonnement à utiliser (explicit ou automatique)
     */
    protected function resolveSubscription(User $user, ?int $subscriptionId): ?UserSubscription
    {
        if ($subscriptionId) {
            return UserSubscription::where('id', $subscriptionId)
                ->where('user_id', $user->id)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->first();
        }

        // Trouver automatiquement le meilleur abonnement
        return $this->findBestSubscription($user);
    }

    /**
     * Trouver le meilleur abonnement (celui avec le plus de sessions restantes)
     */
    protected function findBestSubscription(User $user): ?UserSubscription
    {
        $subscriptions = UserSubscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('end_date', '>=', now())
            ->with('subscriptionPlan')
            ->get();

        if ($subscriptions->isEmpty()) {
            return null;
        }

        // Trier par nombre de sessions restantes (desc)
        return $subscriptions
            ->sortByDesc(fn($s) => $s->getRemainingSessions() ?? PHP_INT_MAX)
            ->first();
    }

    /**
     * Vérifier la limite de sessions
     */
    protected function checkSessionLimit(UserSubscription $subscription): array
    {
        $plan = $subscription->subscriptionPlan;

        // Si pas de limite de sessions, c'est OK
        if ($plan->max_sessions === null) {
            return ['allowed' => true];
        }

        $remaining = $subscription->getRemainingSessions();
        $allowed = $remaining !== null && $remaining > 0;

        return [
            'allowed' => $allowed,
            'message' => $allowed 
                ? "{$remaining} session(s) restante(s)"
                : 'Nombre de sessions atteint',
            'sessions_used' => $subscription->sessions_used,
            'sessions_max' => $plan->max_sessions,
            'sessions_remaining' => $remaining,
        ];
    }

    /**
     * Vérifier les sessions concurrentes
     */
    protected function checkConcurrentSessions(User $user): array
    {
        $activeCount = ChargingSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('subscription_session_counted', true)
            ->count();

        $allowed = $activeCount < self::MAX_CONCURRENT_SESSIONS;

        return [
            'allowed' => $allowed,
            'message' => $allowed 
                ? 'OK'
                : 'Session déjà en cours',
            'active_sessions' => $activeCount,
        ];
    }

    /**
     * Vérifier le statut des quotas
     */
    protected function checkQuotaStatus(UserSubscription $subscription): array
    {
        $plan = $subscription->subscriptionPlan;
        $status = [
            'exhausted' => false,
            'sessions_exhausted' => false,
            'kwh_exhausted' => false,
            'duration_exhausted' => false,
            'warnings' => [],
        ];

        // Vérifier les sessions
        if ($plan->max_sessions !== null) {
            $remaining = $subscription->getRemainingSessions();
            if ($remaining !== null && $remaining <= 0) {
                $status['exhausted'] = true;
                $status['sessions_exhausted'] = true;
            } elseif ($remaining !== null && $remaining / $plan->max_sessions <= self::WARNING_THRESHOLD) {
                $status['warnings'][] = 'sessions';
            }
        }

        // Vérifier les kWh
        if ($plan->max_kwh !== null) {
            $remaining = $subscription->getRemainingKwh();
            if ($remaining !== null && $remaining <= 0) {
                $status['exhausted'] = true;
                $status['kwh_exhausted'] = true;
            } elseif ($remaining !== null && $remaining / $plan->max_kwh <= self::WARNING_THRESHOLD) {
                $status['warnings'][] = 'kwh';
            }
        }

        // Vérifier la durée
        if ($plan->max_duration_minutes !== null) {
            $remaining = $subscription->getRemainingDuration();
            if ($remaining !== null && $remaining <= 0) {
                $status['exhausted'] = true;
                $status['duration_exhausted'] = true;
            } elseif ($remaining !== null && $remaining / $plan->max_duration_minutes <= self::WARNING_THRESHOLD) {
                $status['warnings'][] = 'duration';
            }
        }

        return $status;
    }

    /**
     * Gérer l'épuisement des quotas
     */
    protected function handleQuotaExhausted(UserSubscription $subscription): void
    {
        // Marquer l'abonnement comme expiré si les quotas sont épuisés
        // (pour les abonnements basés sur les quotas)
        $plan = $subscription->subscriptionPlan;
        
        if ($plan->planType->isQuotaBased()) {
            $subscription->update([
                'status' => SubscriptionStatus::EXPIRED->value,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'quota_exhausted_at' => now()->toISOString(),
                    'quota_exhausted_reason' => 'sessions_or_usage_limit_reached',
                ]),
            ]);

            Log::warning('SubscriptionSessionService: Abonnement désactivé - quotas épuisés', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
        }
    }

    /**
     * Vérifier et notifier le seuil d'alerte
     */
    protected function checkAndNotifyThreshold(UserSubscription $subscription): void
    {
        $quotaStatus = $this->checkQuotaStatus($subscription);
        
        if (!empty($quotaStatus['warnings'])) {
            // Ici on pourrait déclencher une notification
            // à l'utilisateur pour le prévenir
            Log::info('SubscriptionSessionService: Seuil d\'alerte atteint', [
                'subscription_id' => $subscription->id,
                'warnings' => $quotaStatus['warnings'],
            ]);
        }
    }

    /**
     * Logger le début d'une session
     */
    protected function logSessionStart(
        UserSubscription $subscription,
        ChargingSession $session,
        int $sessionsBefore
    ): SubscriptionUsageLog {
        return SubscriptionUsageLog::create([
            'user_subscription_id' => $subscription->id,
            'charging_session_id' => $session->id,
            'usage_type' => 'session',
            'sessions_consumed' => 1,
            'sessions_before' => $sessionsBefore,
            'sessions_after' => $sessionsBefore + 1,
            'metadata' => [
                'action' => 'session_start',
                'charging_point_id' => $session->charging_point_id,
                'started_at' => $session->start_timestamp,
            ],
        ]);
    }

    /**
     * Logger l'utilisation de la session
     */
    protected function logSessionUsage(
        UserSubscription $subscription,
        ChargingSession $session,
        float $energyKwh,
        int $durationMinutes
    ): SubscriptionUsageLog {
        return SubscriptionUsageLog::create([
            'user_subscription_id' => $subscription->id,
            'charging_session_id' => $session->id,
            'usage_type' => 'session_complete',
            'kwh_consumed' => $energyKwh,
            'duration_minutes_consumed' => $durationMinutes,
            'kwh_before' => $subscription->kwh_used - $energyKwh,
            'kwh_after' => $subscription->kwh_used,
            'duration_before' => $subscription->duration_minutes_used - $durationMinutes,
            'duration_after' => $subscription->duration_minutes_used,
            'metadata' => [
                'action' => 'session_complete',
                'charged_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Obtenir le résumé d'utilisation pour un utilisateur
     */
    public function getUsageSummary(User $user): array
    {
        $subscriptions = UserSubscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->with('subscriptionPlan')
            ->get();

        return $subscriptions->map(function ($sub) {
            $plan = $sub->subscriptionPlan;
            $quotaStatus = $this->checkQuotaStatus($sub);
            
            return [
                'subscription_id' => $sub->id,
                'plan_name' => $plan->name,
                'plan_type' => $plan->type,
                'status' => $sub->status,
                'end_date' => $sub->end_date,
                'sessions' => [
                    'used' => $sub->sessions_used,
                    'max' => $plan->max_sessions,
                    'remaining' => $sub->getRemainingSessions(),
                    'exhausted' => $quotaStatus['sessions_exhausted'],
                ],
                'kwh' => [
                    'used' => $sub->kwh_used,
                    'max' => $plan->max_kwh,
                    'remaining' => $sub->getRemainingKwh(),
                    'exhausted' => $quotaStatus['kwh_exhausted'],
                ],
                'duration' => [
                    'used' => $sub->duration_minutes_used,
                    'max' => $plan->max_duration_minutes,
                    'remaining' => $sub->getRemainingDuration(),
                    'exhausted' => $quotaStatus['duration_exhausted'],
                ],
                'is_exhausted' => $quotaStatus['exhausted'],
            ];
        })->toArray();
    }

    /**
     * Obtenir les sessions restantes pour un utilisateur
     */
    public function getRemainingSessions(User $user): int
    {
        $subscription = $this->getActiveSubscriptionForUser($user);
        
        if (!$subscription) {
            return 0;
        }

        $remaining = $subscription->getRemainingSessions();
        
        // Si pas de limite, retourner -1 (illimité)
        return $remaining ?? -1;
    }

    /**
     * Vérifier si un utilisateur peut accéder à une borne avec son abonnement
     */
    public function canAccessChargingPoint(User $user, int $chargingPointId): array
    {
        $subscription = $this->getActiveSubscriptionForUser($user);
        
        if (!$subscription) {
            return [
                'can_access' => false,
                'reason' => 'no_subscription',
            ];
        }

        $plan = $subscription->subscriptionPlan;

        // Vérifier si le plan permet l'accès à cette borne
        if (!$plan->isAccessibleAtChargingPointId($chargingPointId)) {
            return [
                'can_access' => false,
                'reason' => 'plan_restriction',
                'message' => 'Cette borne n\'est pas incluse dans votre abonnement',
            ];
        }

        // Vérifier les quotas restants
        if (!$subscription->canStartSession()) {
            return [
                'can_access' => false,
                'reason' => 'quota_exhausted',
                'message' => 'Vos sessions sont épuisées',
            ];
        }

        return [
            'can_access' => true,
            'subscription' => [
                'id' => $subscription->id,
                'plan_name' => $plan->name,
            ],
        ];
    }
}
