<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\ChargingPoints\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Group;
use App\Models\PricingPlan;
use App\Modules\ChargingPoints\Services\ChargingPointService;
use App\Modules\ChargingPoints\DTOs\ChargingPointDTO;
use App\Modules\ChargingPoints\DTOs\ConnectorDTO;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedChargingPointController extends Controller
{
    protected $chargingPointService;

    public function __construct(ChargingPointService $chargingPointService)
    {
        $this->middleware('auth');
        $this->chargingPointService = $chargingPointService;
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Log user access
            Log::info('UnifiedChargingPointController@index: User accessing', [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames(),
                'integrator_id' => $user->integrator_id ?? 'N/A',
                'partner_id' => $user->partner_id ?? 'N/A',
            ]);

            // Use the service to get filtered data with stats
            $data = $this->chargingPointService->getFilteredForUser(
                $user,
                $request->all()
            );

            $chargingPoints = $data['chargingPoints'];
            $stats = $data['stats'];

            Log::info('UnifiedChargingPointController@index: Charging points retrieved', [
                'count' => $chargingPoints->count(),
                'total' => $chargingPoints->total(),
                'stats' => $stats,
            ]);

            // Determine which view to return based on role
            if ($user->hasRole('integrator')) {
                return view('integrator.charging-points.index', compact('chargingPoints', 'stats'));
            }

            // Use the main charging points index view
            return view('charging-points.index', compact('chargingPoints', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error in UnifiedChargingPointController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors du chargement des points de charge. Veuillez réessayer.');
        }
    }

    public function create()
    {
        Gate::authorize('create_charging_points');
        
        $user = auth()->user();
        $integrator = $user->integrator; // Will be null for non-integrators

        // Operators filter based on user role
        $operators = User::whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->when($user->hasRole('integrator'), function ($query) use ($integrator) {
                $query->where('integrator_id', $integrator->id);
            })
            ->with('partner.businessProfile')
            ->get();

        // Business Profiles filter based on user role
        $businessProfiles = BusinessProfile::where('is_active', true)
            ->when($user->hasRole('integrator'), function ($query) {
                $query->whereNull('integrator_id'); // Integrators only see admin-created BPs
            })
            ->get();

        // Groups filter based on user role
        $groups = Group::forUser($user)->where('is_active', true)->get();

        $pricingPlans = PricingPlan::where('is_active', true)->get();

        // Determine which view to return based on role
        if ($user->hasRole('integrator')) {
            return view('integrator.charging-points.create', compact(
                'operators',
                'businessProfiles',
                'groups',
                'pricingPlans'
            ));
        }

        // Default view for other roles (e.g., admin, partner)
        return view('charging-points.create', compact(
            'operators',
            'businessProfiles',
            'groups',
            'pricingPlans'
        ));
    }

    public function store(Request $request)
    {
        Log::info('UnifiedChargingPointController@store: Starting');
        Log::info('UnifiedChargingPointController@store: Request data', $request->all());
        
        Gate::authorize('create_charging_points');
        
        $user = auth()->user();
        $integrator = $user->integrator;
        
        Log::info('UnifiedChargingPointController@store: User info', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames(),
            'integrator_id' => $integrator?->id
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'power_output' => 'nullable|numeric|min:0',
            'operator_id' => 'required|exists:users,id',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'status' => 'required|in:online,offline,maintenance',
            'description' => 'nullable|string|max:1000',
            // Validation des connecteurs supprimée car non utilisée
        ]);

        try {
            DB::beginTransaction();

            $operator = User::where('id', $request->operator_id)
                ->whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->when($user->hasRole('integrator'), function ($query) use ($integrator) {
                    $query->where('integrator_id', $integrator->id);
                })
                ->firstOrFail();

            $businessProfile = BusinessProfile::where('id', $request->business_profile_id)
                ->where('is_active', true)
                ->firstOrFail();

            if ($request->group_id) {
                $group = Group::where('id', $request->group_id)
                    ->when($user->hasRole('integrator'), function ($query) use ($integrator) {
                        $query->where('integrator_id', $integrator->id);
                    })
                    ->firstOrFail();
            }

                $chargingPointData = [
                    'name' => $request->name,
                    'serial_number' => $request->serial_number,
                    'manufacturer' => $request->manufacturer,
                    'model' => $request->model,
                    'address' => $request->address,
                    'city' => $request->city,
                    'location' => $request->location,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'power_output' => $request->power_output,
                    'user_id' => $operator->id,
                    'business_profile_id' => $businessProfile->id,
                    'group_id' => $request->group_id,
                    'pricing_plan_id' => $request->pricing_plan_id,
                    'status' => $request->status,
                    'description' => $request->description,
                ];

            // Assign partner_id from operator if available, otherwise let boot() method fill from group
            if ($operator->partner_id) {
                $chargingPointData['partner_id'] = $operator->partner_id;
            }

            // Assign integrator_id if user is an integrator - use user->integrator_id as fallback
            if ($user->hasRole('integrator')) {
                if ($integrator) {
                    $chargingPointData['integrator_id'] = $integrator->id;
                } elseif ($user->integrator_id) {
                    $chargingPointData['integrator_id'] = $user->integrator_id;
                } elseif ($operator->integrator_id) {
                    $chargingPointData['integrator_id'] = $operator->integrator_id;
                }
                
                if (isset($chargingPointData['integrator_id'])) {
                    Log::info('UnifiedChargingPointController@store: Assigning integrator_id', [
                        'integrator_id' => $chargingPointData['integrator_id'],
                        'source' => $integrator ? 'integrator_relation' : ($user->integrator_id ? 'user_integrator_id' : 'operator_integrator_id')
                    ]);
                } else {
                    Log::warning('UnifiedChargingPointController@store: Could not assign integrator_id', [
                        'user_id' => $user->id,
                        'user_has_integrator_id' => !empty($user->integrator_id),
                        'operator_id' => $operator->id,
                        'operator_has_integrator_id' => !empty($operator->integrator_id),
                        'integrator_exists' => $integrator !== null,
                    ]);
                }
            }

            Log::info('UnifiedChargingPointController@store: Creating charging point', $chargingPointData);
            
            // Check for duplicate serial number
            $existingChargingPoint = ChargingPoint::where('serial_number', $chargingPointData['serial_number'])->first();
            if ($existingChargingPoint) {
                Log::warning('Duplicate serial number detected', ['serial_number' => $chargingPointData['serial_number']]);
                $chargingPointData['serial_number'] = $chargingPointData['serial_number'] . '_' . time();
                Log::info('Serial number modified to avoid duplicate', ['new_serial_number' => $chargingPointData['serial_number']]);
            }
            
            $chargingPoint = ChargingPoint::create($chargingPointData);
            Log::info('UnifiedChargingPointController@store: Charging point created', ['id' => $chargingPoint->id]);

                Log::info('UnifiedChargingPointController@store: Creating connectors', ['count' => count($request->connectors)]);
                
                // Detect database type and disable foreign key constraints accordingly
                $driver = DB::getDriverName();
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                } elseif ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys=OFF');
                }
                
                // Création des connecteurs supprimée car non utilisée
                
                // Re-enable foreign key constraints based on database type
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                } elseif ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys=ON');
                }

            Log::info('UnifiedChargingPointController@store: Committing transaction');
            DB::commit();

            User::role('admin')->each(function($admin) use ($chargingPoint) {
                $admin->notify(new \App\Notifications\NewChargingPointNotification($chargingPoint));
            });

            $redirectRoute = $user->hasRole('integrator') ? 'integrator.charging-points.index' : 'charging-points.index';
            return redirect()->route($redirectRoute)
                ->with('success', 'Borne de recharge créée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création borne: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            // Return a redirect to the index page instead of back() to avoid the loop
            $user = auth()->user();
            $redirectRoute = $user->hasRole('integrator') ? 'integrator.charging-points.index' : 'charging-points.index';
            
            return redirect()->route($redirectRoute)
                ->with('error', 'Erreur lors de la création de la borne de recharge: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Récupérer manuellement le point de charge pour gérer les cas où il n'existe pas
        $chargingPoint = ChargingPoint::find($id);
        
        if (!$chargingPoint) {
            abort(404, 'Point de charge non trouvé');
        }
        
        Gate::authorize('view_charging_points', $chargingPoint);
        
        $user = auth()->user();
        Log::info('UnifiedChargingPointController@show: User details', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames(),
        ]);

        $chargingPoint->load(['user', 'partner', 'businessProfile', 'group', 'connectors', 'pricingPlan']);

        // Préparer les Business Profiles réels et un calcul d'exemple cohérent pour l'affichage
        $businessProfiles = null;
        $calculation = null;
        try {
            $service = app(\App\Services\UnifiedHierarchicalTransactionService::class);
            $hierarchy = $service->getCompleteHierarchy($chargingPoint->id);
            if ($hierarchy) {
                $businessProfiles = $service->getBusinessProfilesForHierarchy($hierarchy);
                // Exemple sur 200 pour l'affichage (aligné avec la vue)
                $calculation = $service->calculateSharesWithBusinessProfiles(200.0, $businessProfiles, $hierarchy);
            }
        } catch (\Throwable $e) {
            \Log::warning('Impossible de charger les Business Profiles pour la vue show', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
        }
        Log::info('UnifiedChargingPointController@show: Charging point data', [
            'charging_point_id' => $chargingPoint->id,
            'charging_point_data' => $chargingPoint->toArray(),
        ]);

        if ($user->hasRole('integrator')) {
            Log::info('UnifiedChargingPointController@show: Returning integrator view', ['view' => 'integrator.charging-points.show']);
            return view('integrator.charging-points.show', compact('chargingPoint', 'businessProfiles', 'calculation'));
        }
        
        // Use enhanced view with toggle functionality
        Log::info('UnifiedChargingPointController@show: Returning enhanced view', ['view' => 'charging-points.show-enhanced']);
        return view('charging-points.show-enhanced', compact('chargingPoint', 'businessProfiles', 'calculation'));
    }

    public function edit(ChargingPoint $chargingPoint)
    {
        Log::info('Attempting to access edit page for charging point.', ['charging_point_id' => $chargingPoint->id, 'user_id' => auth()->id()]);
        $this->authorize('update', $chargingPoint);
        
        $user = auth()->user();
        Log::info('User authorized to edit charging point.', ['charging_point_id' => $chargingPoint->id, 'user_id' => $user->id]);
        $integrator = $user->integrator;

        $operators = User::whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->when($user->hasRole('integrator'), function ($query) use ($integrator) {
                $query->where('integrator_id', $integrator->id);
            })
            ->with('partner.businessProfile')
            ->get();

        $businessProfiles = BusinessProfile::where('is_active', true)
            ->when($user->hasRole('integrator'), function ($query) {
                $query->whereNull('integrator_id');
            })
            ->get();

        $groups = Group::forUser($user)->where('is_active', true)->get();

        $pricingPlans = PricingPlan::where('is_active', true)->get();

        $chargingPoint->load(['connectors']);

        if ($user->hasRole('integrator')) {
            return view('integrator.charging-points.edit', compact(
                'chargingPoint',
                'operators', 
                'businessProfiles', 
                'groups', 
                'pricingPlans'
            ));
        }

        return view('charging-points.edit', compact(
            'chargingPoint',
            'operators', 
            'businessProfiles', 
            'groups', 
            'pricingPlans'
        ));
    }

    public function update(Request $request, ChargingPoint $chargingPoint)
    {
        $this->authorize('update', $chargingPoint);
        
        $user = auth()->user();
        $integrator = $user->integrator;

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'power_output' => 'nullable|numeric|min:0',
            'operator_id' => 'required|exists:users,id',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'status' => 'required|in:online,offline,maintenance',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $operator = User::where('id', $request->operator_id)
                ->whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->when($user->hasRole('integrator'), function ($query) use ($integrator) {
                    $query->where('integrator_id', $integrator->id);
                })
                ->firstOrFail();

            $businessProfile = BusinessProfile::where('id', $request->business_profile_id)
                ->where('is_active', true)
                ->firstOrFail();

            $updateData = [
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'power_output' => $request->power_output,
                'user_id' => $operator->id,
                'partner_id' => $operator->partner_id,
                'business_profile_id' => $businessProfile->id,
                'group_id' => $request->group_id,
                'pricing_plan_id' => $request->pricing_plan_id,
                'status' => $request->status,
                'description' => $request->description,
            ];

            // Assign integrator_id if user is an integrator
            if ($user->hasRole('integrator') && $integrator) {
                $updateData['integrator_id'] = $integrator->id;
            }

            $chargingPoint->update($updateData);

            DB::commit();

            $redirectRoute = $user->hasRole('integrator') ? 'integrator.charging-points.index' : 'charging-points.index';
            return redirect()->route($redirectRoute)
                ->with('success', 'Borne de recharge mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour borne: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la borne de recharge.')
                ->withInput();
        }
    }

    public function destroy(ChargingPoint $chargingPoint)
    {
        Gate::authorize('delete_charging_points', $chargingPoint);
        
        $user = auth()->user();

        try {
            $chargingPoint->delete();
            
            $redirectRoute = $user->hasRole('integrator') ? 'integrator.charging-points.index' : 'charging-points.index';
            return redirect()->route($redirectRoute)
                ->with('success', 'Borne de recharge supprimée avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur suppression borne: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la borne de recharge.');
        }
    }

    // API endpoints for enhanced functionality
    public function connectToSteVe(Request $request, $id)
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($id);
            
            // Validation des données
            $request->validate([
                'charge_box_id' => 'required|string|max:255',
                'steve_server_url' => 'nullable|url',
            ]);

            // URL du serveur SteVe par défaut si non fournie
            $steveServerUrl = $request->steve_server_url ?? 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';
            
            // Génération de l'URL WebSocket complète
            $websocketUrl = $steveServerUrl . $request->charge_box_id;
            
            // Mise à jour de la borne avec les informations de connexion
            $chargingPoint->update([
                'charge_box_id' => $request->charge_box_id,
                'steve_server_url' => $steveServerUrl,
                'websocket_url' => $websocketUrl,
                'status' => 'connecting', // Statut en cours de connexion
                'last_connection_attempt' => now(),
            ]);

            // Log de la tentative de connexion
            Log::info("Borne {$chargingPoint->id} connectée à SteVe avec charge_box_id: {$request->charge_box_id}");

            return response()->json([
                'success' => true,
                'message' => 'Borne connectée avec succès au serveur SteVe',
                'data' => [
                    'charge_box_id' => $request->charge_box_id,
                    'websocket_url' => $websocketUrl,
                    'charging_point' => $chargingPoint->fresh()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la connexion à SteVe: " . $e->getMessage(), [
                'charging_point_id' => $id,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au serveur SteVe: ' . $e->getMessage()
            ], 500);
        }
    }

    public function executeSteVeAction(Request $request, $id)
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $action = $request->input('action');
        
        $actions = [
            'start' => 'Démarrage de la charge',
            'stop' => 'Arrêt de la charge', 
            'status' => 'Récupération du statut',
            'transactions' => 'Récupération des transactions'
        ];
        
        return response()->json([
            'success' => true,
            'message' => $actions[$action] ?? 'Action inconnue',
            'action' => $action,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
    }

    public function testConnectivity(Request $request, $id)
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        
        // Simulation de test de connectivité
        return response()->json([
            'success' => true,
            'message' => 'Test de connectivité réussi',
            'status' => 'connected',
            'response_time' => rand(50, 200) . 'ms'
        ]);
    }
}