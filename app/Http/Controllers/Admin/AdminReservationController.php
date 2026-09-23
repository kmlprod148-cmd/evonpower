<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AdminPermissionTrait;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationCostCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Throwable;

class AdminReservationController extends Controller
{
    use AdminPermissionTrait;
    public function index(Request $request)
    {
        // Admin can view all reservations
        Gate::authorize('view_admin_reservations');
        
        // Debug: Log the query for troubleshooting
        \Log::info('AdminReservationController: Starting reservation query', [
            'user_id' => auth()->id(),
            'user_roles' => auth()->user()->getRoleNames()->toArray()
        ]);
        
        $query = Reservation::with(['user', 'chargingPoint', 'pricingPlan', 'transactionDetails']);
        
        $user = auth()->user();
        
        // Filtrage hiérarchique selon le rôle
        // Admin voit TOUTES les réservations - pas de restriction hiérarchique
        if (!$user->hasRole(['admin', 'super_admin'])) {
            // Intégrateurs : voir leurs propres réservations et celles de leurs opérateurs
            $integratorId = $user->integrator_id ?? \App\Models\Integrator::where('user_id', $user->id)->value('id');
            if ($user->hasRole('integrator') && $integratorId) {
                $query->where(function($q) use ($user, $integratorId) {
                    // Own reservations
                    $q->where('user_id', $user->id)
                      // Reservations from operators
                      ->orWhereHas('user', function($userQuery) use ($integratorId) {
                          $userQuery->where('integrator_id', $integratorId)
                                   ->whereHas('roles', function($roleQuery) {
                                       $roleQuery->where('name', 'operator');
                                   });
                      })
                      // Reservations for charging points owned by integrator directly
                      ->orWhereHas('chargingPoint', function($cpQuery) use ($integratorId) {
                          $cpQuery->where('integrator_id', $integratorId);
                      })
                      // Reservations for charging points owned by integrator's operators
                      ->orWhereHas('chargingPoint', function($cpQuery) use ($integratorId) {
                          $cpQuery->whereHas('user', function($userQuery) use ($integratorId) {
                              $userQuery->where('integrator_id', $integratorId)
                                       ->whereHas('roles', function($roleQuery) {
                                           $roleQuery->where('name', 'operator');
                                       });
                          });
                      })
                      // Reservations for charging points via groups -> partners
                      ->orWhereHas('chargingPoint.group', function($groupQuery) use ($integratorId) {
                          $groupQuery->whereHas('partner', function($partnerQuery) use ($integratorId) {
                              $partnerQuery->where('integrator_id', $integratorId);
                          });
                      })
                      // Reservations for charging points via groups -> operators
                      ->orWhereHas('chargingPoint.group', function($groupQuery) use ($integratorId) {
                          $groupQuery->whereHas('user', function($userQuery) use ($integratorId) {
                              $userQuery->where('integrator_id', $integratorId)
                                       ->whereHas('roles', function($roleQuery) {
                                           $roleQuery->where('name', 'operator');
                                       });
                          });
                      });
                });
            } elseif ($user->hasRole('operator')) {
                // Les opérateurs voient TOUTES les réservations sur les bornes qu'ils gèrent
                // Utiliser whereHas pour une recherche plus flexible et robuste
                $query->where(function($q) use ($user) {
                    // Réservations personnelles de l'opérateur
                    $q->where('user_id', $user->id);
                    
                    // Réservations sur les points de charge directement assignés à l'opérateur
                    $q->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                        $cpQuery->withTrashed()
                            ->where(function($cpSubQuery) use ($user) {
                                // Points de charge avec user_id = opérateur
                                $cpSubQuery->where('user_id', $user->id)
                                    // OU créés par l'opérateur
                                    ->orWhere('created_by', $user->id)
                                    ->orWhere('created_by_id', $user->id);
                            });
                    });
                    
                    // Réservations sur les points de charge dans les groupes de l'opérateur
                    $q->orWhereHas('chargingPoint.group', function($groupQuery) use ($user) {
                        $groupQuery->where('user_id', $user->id);
                    });
                    
                    // Si l'opérateur a un intégrateur, inclure TOUTES les réservations 
                    // sur les points de charge de cet intégrateur (même si l'opérateur n'est pas le propriétaire direct)
                    if ($user->integrator_id) {
                        $q->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                            $cpQuery->withTrashed()
                                ->where('integrator_id', $user->integrator_id);
                        });
                    }
                });
                
                // Log pour débogage
                $chargingPointIds = \App\Models\ChargingPoint::withTrashed()
                    ->where(function($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->orWhere('created_by', $user->id)
                          ->orWhere('created_by_id', $user->id);
                    })
                    ->pluck('id')
                    ->toArray();
                
                $groupIds = \App\Models\Group::where('user_id', $user->id)->pluck('id')->toArray();
                
                \Log::info('AdminReservationController: Operator filtering (using whereHas)', [
                    'operator_id' => $user->id,
                    'operator_integrator_id' => $user->integrator_id,
                    'charging_point_ids' => $chargingPointIds,
                    'group_ids' => $groupIds,
                    'charging_point_ids_count' => count($chargingPointIds),
                    'group_count' => count($groupIds),
                    'total_reservations_count' => \App\Models\Reservation::where('user_id', $user->id)->count(),
                    'reservations_on_operator_cps' => \App\Models\Reservation::whereIn('charging_point_id', $chargingPointIds)->count()
                ]);
            } elseif ($user->hasRole('partner')) {
                // Les partenaires voient les réservations sur leurs bornes
                $partnerId = $user->partner_id;
                if ($partnerId) {
                    $query->where(function($q) use ($user, $partnerId) {
                        // Réservations sur les bornes directement liées au partenaire
                        $q->whereHas('chargingPoint', function($cpQuery) use ($partnerId) {
                            $cpQuery->where('partner_id', $partnerId);
                        })
                        // Réservations sur les bornes dans des groupes du partenaire
                        ->orWhereHas('chargingPoint.group', function($groupQuery) use ($partnerId) {
                            $groupQuery->where('partner_id', $partnerId);
                        })
                        // Réservations personnelles du partenaire (fallback)
                        ->orWhere('user_id', $user->id);
                    });
                } else {
                    $query->where('user_id', $user->id);
                }
            } else {
                // Autres rôles : seulement leurs propres réservations
                $query->where('user_id', $user->id);
            }
        }
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%")
                  ->orWhere('guest_phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%")
                               ->orWhere('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($search) {
                      $cpQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }
        
        // Admin voit toutes les réservations - pas de restriction sur user_id ou charging_point_id
        // Cela permet de voir les réservations invitées et toutes autres réservations
        $query->orderBy('created_at', 'desc');
        
        // Debug: Log SQL query before pagination
        \Log::info('AdminReservationController: SQL query before pagination', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray()
        ]);
        
        $reservations = $query->paginate(15)->appends($request->query());
        
        // Debug: Log the results
        \Log::info('AdminReservationController: Found reservations', [
            'total' => $reservations->total(),
            'count' => $reservations->count(),
            'current_page' => $reservations->currentPage(),
            'per_page' => $reservations->perPage()
        ]);
        
        return view('admin.reservations.index', compact('reservations'));
    }

    public function create(Request $request)
    {
        $duplicateReservation = null;
        
        // Vérifier s'il y a une réservation à dupliquer
        if ($request->has('duplicate') && $request->duplicate) {
            $duplicateReservation = Reservation::with(['user', 'chargingPoint', 'pricingPlan'])
                ->find($request->duplicate);
        }
        
        $selectedChargingPointId = old('charging_point_id', $duplicateReservation?->charging_point_id);

        $chargingPoints = ChargingPoint::query()
            ->select(['id', 'name', 'address', 'city', 'postal_code', 'pricing_plan_id', 'is_active'])
            ->where(function ($query) use ($selectedChargingPointId) {
                $query->where('is_active', true);

                if ($selectedChargingPointId) {
                    $query->orWhereKey($selectedChargingPointId);
                }
            })
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values();

        return view('admin.reservations.create', compact('duplicateReservation', 'chargingPoints', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'user_id' => 'nullable|exists:users,id',
            'guest_email' => 'nullable|email|required_without_all:user_id,guest_phone',
            'guest_phone' => 'nullable|string|max:30|required_without_all:user_id,guest_email',
            'reservation_type' => 'required|string|in:kwh,minute,minutes',
            'reservation_value' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $reservation = Reservation::create($this->buildReservationPayload($validated));

        $message = 'Réservation créée avec succès.';
        if ($request->has('duplicate_from')) {
            $message = 'Réservation dupliquée avec succès.';
        }

        return redirect()->route('admin.reservations.show', $reservation)->with('success', $message);
    }

    /**
     * Vérifier si l'utilisateur actuel peut accéder à cette réservation
     * Pour les opérateurs : vérifie que la réservation est liée à un de leurs points de charge
     * Utilise la même logique que dans index() pour garantir la cohérence
     */
    private function canAccessReservation($reservation, $user)
    {
        // Les admins peuvent accéder à toutes les réservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Pour les opérateurs : vérifier que la réservation est liée à un de leurs points de charge
        if ($user->hasRole('operator')) {
            // Toujours autoriser les réservations personnelles de l'opérateur
            if ($reservation->user_id === $user->id) {
                return true;
            }

            $chargingPoint = $reservation->chargingPoint;
            
            if (!$chargingPoint) {
                return false;
            }

            // Vérifier si le point de charge est directement assigné à l'opérateur
            if ($chargingPoint->user_id === $user->id || 
                $chargingPoint->created_by === $user->id || 
                $chargingPoint->created_by_id === $user->id) {
                return true;
            }

            // Vérifier si le point de charge est dans un groupe géré par l'opérateur
            if ($chargingPoint->group_id) {
                $group = \App\Models\Group::find($chargingPoint->group_id);
                if ($group && $group->user_id === $user->id) {
                    return true;
                }
            }

            // Si l'opérateur a un intégrateur, vérifier si le point de charge appartient à cet intégrateur
            if ($user->integrator_id && $chargingPoint->integrator_id === $user->integrator_id) {
                return true;
            }

            return false;
        }

        // Pour les intégrateurs : utiliser la même logique que dans index()
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id;
            if (!$integratorId) {
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator ? $integrator->id : null;
            }
            
            if ($integratorId) {
                $chargingPoint = $reservation->chargingPoint;
                if ($chargingPoint) {
                    // Vérifier les différentes relations possibles
                    if ($chargingPoint->integrator_id === $integratorId) {
                        return true;
                    }
                    if ($chargingPoint->user_id && $chargingPoint->user && $chargingPoint->user->integrator_id === $integratorId) {
                        return true;
                    }
                    if ($chargingPoint->group && $chargingPoint->group->partner && $chargingPoint->group->partner->integrator_id === $integratorId) {
                        return true;
                    }
                }
            }
            return false;
        }

        // Pour les partenaires
        if ($user->hasRole('partner') && $user->partner_id) {
            $chargingPoint = $reservation->chargingPoint;
            if ($chargingPoint) {
                if ($chargingPoint->partner_id === $user->partner_id) {
                    return true;
                }
                if ($chargingPoint->group && $chargingPoint->group->partner_id === $user->partner_id) {
                    return true;
                }
            }
            return false;
        }

        // Par défaut, refuser l'accès
        return false;
    }

    public function show(Reservation $reservation)
    {
        // Admin can view all reservation details
        Gate::authorize('view_admin_reservation_details');
        
        $user = auth()->user();
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        $reservation->load(['user', 'chargingPoint', 'pricingPlan', 'transaction']);
        $reservation->ensureTransactionConfirmedIfPaid();
        $reservation->refresh();
        $reservation->load(['user', 'chargingPoint', 'pricingPlan', 'transaction']);
        return view('admin.reservations.show', compact('reservation'));
    }

    public function showConfirmForm(Reservation $reservation)
    {
        // Utiliser la policy pour vérifier l'autorisation de confirmation
        Gate::authorize('confirm', $reservation);
        
        $reservation->load(['user', 'chargingPoint', 'pricingPlan']);
        return view('admin.reservations.confirm', compact('reservation'));
    }

    public function confirm(Request $request, Reservation $reservation)
    {
        Gate::authorize('confirm', $reservation);
        
        $paymentMethod = $reservation->payment_method ?? $reservation->payment_type;
        if (in_array($paymentMethod, ['credit', 'offline', 'prepaid_credit']) && $reservation->user_id) {
            try {
                \DB::beginTransaction();
                $creditPaymentService = app(\App\Services\CreditPaymentService::class);
                $paymentResult = $creditPaymentService->processPrepaidPayment($reservation);
                if (!$paymentResult['success']) {
                    \DB::rollBack();
                    return redirect()->back()->with('error', $paymentResult['error'] ?? 'Solde client insuffisant.');
                }
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error('Erreur paiement crédit lors confirmation admin', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
                return redirect()->back()->with('error', 'Erreur paiement par crédit : ' . $e->getMessage());
            }
        } else {
            $reservation->update([
                'status' => \App\Enums\ReservationStatus::CONFIRMED,
                'confirmed_at' => now(),
                'payment_status' => 'PAID',
            ]);
            $this->processReservationTransactionCalculation($reservation);
            // Synchroniser le statut transaction (completed) après création de la transaction
            $reservation->ensureTransactionConfirmedIfPaid();
        }

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Réservation confirmée avec succès.');
    }
    
    /**
     * Traite le calcul de transaction pour une réservation confirmée
     */
    private function processReservationTransactionCalculation(Reservation $reservation): void
    {
        try {
            $costService = app(\App\Services\ReservationCostCalculationService::class);
            $estimatedCost = $costService->calculateReservationCost($reservation);
            if ($estimatedCost <= 0) {
                $estimatedCost = $costService->calculateCostWithDefaults($reservation, 1.0);
            }
            if ($estimatedCost <= 0) {
                throw new \Exception("Impossible de calculer un montant valide pour la réservation #{$reservation->id}");
            }
            
            $reservation->update([
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $estimatedCost,
                'amount' => $estimatedCost,
            ]);
            
            $reservationTransactionService = app(\App\Services\ReservationTransactionService::class);
            $reservationTransactionService->processReservationTransaction($reservation);
        } catch (\Exception $e) {
            \Log::error("Erreur processReservationTransactionCalculation", [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function edit(Reservation $reservation)
    {
        $user = auth()->user();
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        $reservation->load(['user', 'chargingPoint', 'pricingPlan']);
        return view('admin.reservations.edit', compact('reservation'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $user = auth()->user();

        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }

        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'reservation_type' => 'required|string|in:kwh,minute,minutes',
            'reservation_value' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $reservation->update($this->buildReservationPayload([
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'guest_email' => $reservation->guest_email,
            'guest_phone' => $reservation->guest_phone,
            'status' => $reservation->status instanceof ReservationStatus
                ? $reservation->status->value
                : $reservation->status,
            ...$validated,
        ], $reservation));

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Réservation mise à jour avec succès.');
    }

    /**
     * Confirmer une réservation avec des frais personnalisés
     */
    /**
     * Normalise et enrichit les données du formulaire admin avant persistance.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildReservationPayload(array $validated, ?Reservation $existingReservation = null): array
    {
        $chargingPointId = $validated['charging_point_id'] ?? $existingReservation?->charging_point_id;
        $chargingPoint = $chargingPointId
            ? ChargingPoint::query()->with('pricingPlan.vatRate')->find($chargingPointId)
            : null;
        $pricingPlan = $chargingPoint?->getActivePricingPlan() ?? $existingReservation?->pricingPlan;

        $reservationType = $this->normalizeReservationType($validated['reservation_type'] ?? $existingReservation?->reservation_type);
        $reservationValue = round((float) ($validated['reservation_value'] ?? $existingReservation?->reservation_value ?? 0), 2);

        $startTime = isset($validated['start_time'])
            ? Carbon::parse($validated['start_time'])
            : ($existingReservation?->start_time ?? now());
        $endTime = isset($validated['end_time'])
            ? Carbon::parse($validated['end_time'])
            : ($existingReservation?->end_time ?? $startTime->copy()->addHours(2));

        $payload = [
            'user_id' => $validated['user_id'] ?? $existingReservation?->user_id,
            'charging_point_id' => $chargingPointId,
            'pricing_plan_id' => $validated['pricing_plan_id'] ?? $pricingPlan?->id ?? $existingReservation?->pricing_plan_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reservation_type' => $reservationType,
            'reservation_value' => $reservationValue,
            'notes' => $validated['notes'] ?? $existingReservation?->notes,
            'guest_email' => $validated['guest_email'] ?? $existingReservation?->guest_email,
            'guest_phone' => $validated['guest_phone'] ?? $existingReservation?->guest_phone,
            'status' => $validated['status']
                ?? ($existingReservation?->status instanceof ReservationStatus
                    ? $existingReservation->status->value
                    : ($existingReservation?->status ?? ReservationStatus::PENDING->value)),
            'payment_status' => $existingReservation?->payment_status ?? 'PENDING',
        ];

        if ($reservationType === 'minute') {
            $durationMinutes = max((int) round($reservationValue), 1);

            $payload['duration_minutes'] = $durationMinutes;
            $payload['estimated_duration'] = $durationMinutes;
            $payload['energy_kwh'] = null;
            $payload['estimated_energy'] = null;
        } else {
            $estimatedDuration = max((int) $startTime->diffInMinutes($endTime, false), 0);

            $payload['energy_kwh'] = $reservationValue;
            $payload['estimated_energy'] = $reservationValue;
            $payload['duration_minutes'] = $estimatedDuration > 0
                ? $estimatedDuration
                : ($existingReservation?->duration_minutes ?? null);
            $payload['estimated_duration'] = $estimatedDuration > 0
                ? $estimatedDuration
                : ($existingReservation?->estimated_duration ?? null);
        }

        $payload['is_guest'] = empty($payload['user_id']);
        $payload['guest_info'] = $payload['is_guest']
            ? array_filter([
                'email' => $payload['guest_email'],
                'phone' => $payload['guest_phone'],
            ])
            : null;

        if ($pricingPlan && $reservationValue > 0) {
            try {
                $payload['estimated_cost'] = app(ReservationCostCalculationService::class)->calculateEstimatedCostFromData([
                    'charging_point_id' => $payload['charging_point_id'],
                    'reservation_type' => $payload['reservation_type'],
                    'reservation_value' => $payload['reservation_value'],
                    'duration_minutes' => $payload['duration_minutes'] ?? null,
                    'energy_kwh' => $payload['energy_kwh'] ?? null,
                    'start_time' => $payload['start_time'],
                ], $pricingPlan);
            } catch (Throwable $e) {
                \Log::warning('Impossible de calculer le coût estimé de la réservation admin', [
                    'charging_point_id' => $chargingPointId,
                    'pricing_plan_id' => $pricingPlan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $payload;
    }

    private function normalizeReservationType(?string $reservationType): string
    {
        return match ($reservationType) {
            'minutes' => 'minute',
            'kwh' => 'kwh',
            default => 'minute',
        };
    }

    public function confirmWithCustomFees(Request $request, Reservation $reservation)
    {
        // Utiliser la policy pour vérifier l'autorisation de confirmation
        Gate::authorize('confirm', $reservation);

        $validated = $request->validate([
            'custom_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $reservation->status = \App\Enums\ReservationStatus::CONFIRMED;
        $reservation->confirmed_at = now();
        if (!empty($validated['custom_fees'])) {
            $reservation->actual_cost = $validated['custom_fees'];
        }
        if (!empty($validated['notes'])) {
            $reservation->notes = $validated['notes'];
        }
        $reservation->save();

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Réservation confirmée avec des frais personnalisés.');
    }

    /**
     * Rejeter une réservation
     */
    public function reject(Request $request, Reservation $reservation)
    {
        // Utiliser la policy pour vérifier l'autorisation de rejet
        Gate::authorize('reject', $reservation);
        
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $reservation->status = \App\Enums\ReservationStatus::CANCELED;
        if (!empty($validated['rejection_reason'])) {
            $reservation->notes = $validated['rejection_reason'];
        }
        $reservation->save();

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Réservation rejetée.');
    }

    /**
     * Afficher le formulaire de confirmation avec coût
     */
    public function showConfirmWithCostForm(Reservation $reservation)
    {
        // Utiliser la policy pour vérifier l'autorisation de confirmation
        Gate::authorize('confirm', $reservation);
        
        $reservation->load(['user', 'chargingPoint', 'pricingPlan']);
        return view('admin.reservations.confirm_with_cost', compact('reservation'));
    }

    /**
     * Confirmer avec coût personnalisé
     */
    public function confirmWithCost(Request $request, Reservation $reservation)
    {
        // Utiliser la policy pour vérifier l'autorisation de confirmation
        Gate::authorize('confirm', $reservation);
        
        $validated = $request->validate([
            'actual_cost' => 'required|numeric|min:0',
            'actual_energy' => 'nullable|numeric|min:0',
            'actual_duration' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $reservation->status = \App\Enums\ReservationStatus::CONFIRMED;
        $reservation->confirmed_at = now();
        $reservation->actual_cost = $validated['actual_cost'];
        if (!empty($validated['actual_energy'])) {
            $reservation->actual_energy = $validated['actual_energy'];
        }
        if (!empty($validated['actual_duration'])) {
            $reservation->actual_duration = $validated['actual_duration'];
        }
        if (!empty($validated['notes'])) {
            $reservation->notes = $validated['notes'];
        }
        $reservation->save();

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Réservation confirmée avec coût personnalisé.');
    }
}
