<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        Log::info('AdminReservationController: Starting reservation query', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray()
        ]);
        
        $query = Reservation::with(['user', 'chargingPoint', 'pricingPlan']);

        // Appliquer le filtrage hiérarchique selon le rôle
        if ($user->hasRole('integrator')) {
            // Get integrator ID
            $integratorId = $user->integrator_id;
            if (!$integratorId) {
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator ? $integrator->id : null;
            }
            
            if ($integratorId) {
                // Les intégrateurs voient leurs réservations, celles de leurs opérateurs, 
                // et celles liées à leurs charging points (via opérateurs ou partenaires)
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
            } else {
                // No integrator ID found, show only own reservations
                $query->where('user_id', $user->id);
            }
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
            
            Log::info('ReservationController: Operator filtering (using whereHas)', [
                'operator_id' => $user->id,
                'operator_integrator_id' => $user->integrator_id,
                'charging_point_ids' => $chargingPointIds,
                'group_ids' => $groupIds,
                'charging_point_ids_count' => count($chargingPointIds),
                'group_count' => count($groupIds),
                'total_reservations_count' => \App\Models\Reservation::where('user_id', $user->id)->count(),
                'reservations_on_operator_cps' => !empty($chargingPointIds) ? \App\Models\Reservation::whereIn('charging_point_id', $chargingPointIds)->count() : 0
            ]);
        } elseif ($user->hasRole('partner')) {
            // Les partenaires voient les réservations sur leurs bornes
            $partnerId = $user->partner_id;
            if ($partnerId) {
                // Récupérer les IDs des bornes liées au partenaire
                $chargingPointIds = \App\Models\ChargingPoint::where('partner_id', $partnerId)
                    ->pluck('id')
                    ->toArray();
                
                // Récupérer les IDs des groupes du partenaire
                $groupIds = \App\Models\Group::where('partner_id', $partnerId)
                    ->pluck('id')
                    ->toArray();
                
                // Récupérer les IDs des bornes dans ces groupes
                if (!empty($groupIds)) {
                    $groupChargingPointIds = \App\Models\ChargingPoint::whereIn('group_id', $groupIds)
                        ->pluck('id')
                        ->toArray();
                    $chargingPointIds = array_merge($chargingPointIds, $groupChargingPointIds);
                }
                
                if (empty($chargingPointIds)) {
                    // Si aucune borne n'est liée, montrer uniquement les réservations personnelles
                    $query->where('user_id', $user->id);
                } else {
                    $query->where(function($q) use ($user, $chargingPointIds) {
                        // Réservations sur les bornes du partenaire
                        $q->whereIn('charging_point_id', $chargingPointIds)
                          // Réservations personnelles du partenaire (fallback)
                          ->orWhere('user_id', $user->id);
                    });
                }
            } else {
                $query->where('user_id', $user->id);
            }
        }
        // Les admins voient tout (pas de filtrage supplémentaire)

        // Filtres de recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%")
                               ->orWhere('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($search) {
                      $cpQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        // Tri par défaut
        $query->orderBy('created_at', 'desc');

        // Pagination
        $reservations = $query->paginate(15)->appends($request->query());

        return view('admin.reservations.index', compact('reservations'));
    }

    public function create()
    {
        return view('admin.reservations.create');
    }

    public function store(Request $request)
    {
        // Logique de création de réservation
        return redirect()->route('admin.reservations.index');
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

    public function show($id)
    {
        $user = auth()->user();
        $reservation = \App\Models\Reservation::with(['user', 'chargingPoint', 'pricingPlan'])->findOrFail($id);
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        return view('admin.reservations.show', compact('reservation'));
    }

    public function edit($id)
    {
        $user = auth()->user();
        $reservation = \App\Models\Reservation::with(['user', 'chargingPoint', 'pricingPlan'])->findOrFail($id);
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        return view('admin.reservations.edit', compact('reservation'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $reservation = \App\Models\Reservation::with(['chargingPoint'])->findOrFail($id);
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        // Logique de mise à jour de réservation
        return redirect()->route('admin.reservations.index');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $reservation = \App\Models\Reservation::with(['chargingPoint'])->findOrFail($id);
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        // Logique de suppression de réservation
        return redirect()->route('admin.reservations.index');
    }

    public function confirmShow($id)
    {
        $user = auth()->user();
        $reservation = \App\Models\Reservation::with(['user', 'chargingPoint', 'pricingPlan'])->findOrFail($id);
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        return view('admin.reservations.confirm', compact('reservation'));
    }

    public function confirm(Request $request, $id)
    {
        $user = auth()->user();
        $reservation = \App\Models\Reservation::with(['chargingPoint'])->findOrFail($id);
        
        // Vérifier l'accès pour les non-admins
        if (!$this->canAccessReservation($reservation, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette réservation.');
        }
        
        // Valider la confirmation
        $request->validate([
            'confirmation_notes' => 'nullable|string|max:500'
        ]);

        // Mettre à jour le statut de la réservation
        $reservation->update([
            'status' => \App\Enums\ReservationStatus::CONFIRMED,
            'notes' => $request->confirmation_notes ?? $reservation->notes
        ]);

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Réservation confirmée avec succès.');
    }
}
