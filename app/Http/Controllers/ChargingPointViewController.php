<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\PricingPlan;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session; // Import Session facade
use Illuminate\Support\Facades\Validator; // Import Validator facade
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Arr; // Import Arr facade for array_except
use Illuminate\Support\Facades\App; // Import App facade
use Illuminate\Support\Facades\Cache; // Import Cache facade
use Illuminate\Support\Facades\Schema; // Import Schema facade
use Illuminate\Support\Facades\DB; // Import DB facade
use App\Services\QRCodeService;
use App\Models\BusinessProfile;
use App\Exceptions\SteVeConfigurationException;
use App\Exceptions\SteVeProvisioningFailedException;

class ChargingPointViewController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Authorize the action
        $this->authorize('viewAny', \App\Models\ChargingPoint::class);
        
        // Debug: Log user info and check all charging points
        $allChargingPointsCount = ChargingPoint::count();
        $userChargingPointsCount = ChargingPoint::where('integrator_id', $user->integrator_id)->count();
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isIntegrator = in_array('integrator', $userRolesLower) && $user->integrator_id;
        
        Log::info('CHARGING POINT INDEX - Debug info', [
            'user_id' => $user->id,
            'user_integrator_id' => $user->integrator_id,
            'user_roles' => $userRolesLower,
            'is_integrator' => $isIntegrator,
            'all_charging_points_count' => $allChargingPointsCount,
            'user_charging_points_count' => $userChargingPointsCount,
            'charging_points_with_integrator_id' => ChargingPoint::whereNotNull('integrator_id')->count(),
            'recent_charging_points' => ChargingPoint::latest()->take(5)->get(['id', 'name', 'integrator_id', 'group_id', 'created_at'])->toArray()
        ]);
        
        // Start with base query filtered by user permissions
        $query = ChargingPoint::visibleToUser($user);
        
        // Debug: Log the query SQL before applying filters
        $queryBeforeFilters = (clone $query);
        Log::info('CHARGING POINT INDEX - Query before filters', [
            'sql' => $queryBeforeFilters->toSql(),
            'bindings' => $queryBeforeFilters->getBindings(),
            'count' => $queryBeforeFilters->count()
        ]);

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Apply status filter if provided (prioritize 'filter' parameter over 'status')
        if ($request->has('filter') && !empty($request->filter)) {
            switch ($request->filter) {
                case 'online':
                    $query->where('status', 'online');
                    break;
                case 'maintenance':
                    $query->where('status', 'maintenance');
                    break;
                case 'offline':
                    $query->where('status', 'offline');
                    break;
                // 'all' or any other value will show all charging points
            }
        } elseif ($request->has('status') && !empty($request->status)) {
            // Fallback to 'status' parameter if 'filter' is not provided
            $query->where('status', $request->status);
        }

        // Apply group filter if provided
        if ($request->has('group_id') && !empty($request->group_id)) {
            $query->where('group_id', $request->group_id);
        }

        // Apply partner filter if provided
        if ($request->has('partner_id') && !empty($request->partner_id)) {
            $query->where('partner_id', $request->partner_id);
        }

        // Apply pagination
        $chargingPoints = $query->select('id', 'name', 'serial_number', 'status', 'city', 'address', 'manufacturer', 'model', 'group_id', 'partner_id', 'integrator_id')
                               ->with(['group:id,name'])
                               ->paginate(15);
        
        // Debug: Log final results
        Log::info('CHARGING POINT INDEX - Final results', [
            'total_results' => $chargingPoints->total(),
            'current_page' => $chargingPoints->currentPage(),
            'per_page' => $chargingPoints->perPage(),
            'charging_point_ids' => $chargingPoints->pluck('id')->toArray(),
            'charging_point_names' => $chargingPoints->pluck('name')->toArray(),
            'charging_points_integrator_ids' => $chargingPoints->pluck('integrator_id')->toArray()
        ]);

        // Load connectors séparément à supprimer
        // $chargingPointIds = $chargingPoints->pluck('id')->toArray();
        // $connectors = Connector::whereIn('charging_point_id', $chargingPointIds)
        //                      ->get()
        //                      ->groupBy('charging_point_id');

        // Attach connectors to charging points à supprimer
        // foreach($chargingPoints as $chargingPoint) {
        //     $chargingPoint->connectors = $connectors[$chargingPoint->id] ?? collect([]);
        // }

        // Get statistics for dashboard - filtered by user permissions
        $userRole = $user->getRoleNames()->first();
        $cacheKey = 'dashboard_charging_point_stats_' . $userRole . '_' . ($user->integrator_id ?? $user->id);
        $stats = Cache::remember($cacheKey, 300, function () use ($user) {
            $baseQuery = ChargingPoint::visibleToUser($user);
            return [
                'activeChargingPoints' => (clone $baseQuery)->where('status', 'online')->count(),
                'inactiveChargingPoints' => (clone $baseQuery)->where('status', 'offline')->count(),
                'maintenanceChargingPoints' => (clone $baseQuery)->where('status', 'maintenance')->count(),
                'totalChargingPoints' => $baseQuery->count(),
            ];
        });

        // Get required data for filters - filtered by user permissions
        $groups = collect();
        $partners = collect();
        
        // Check if user is integrator (case-insensitive)
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isIntegrator = in_array('integrator', $userRolesLower) && $user->integrator_id;
        
        try {
            $groupsQuery = Group::select('id', 'name')->orderBy('name');
            
            // Filtrer par is_active seulement si la colonne existe
            if (Schema::hasColumn('groups', 'is_active')) {
                $groupsQuery->where(function($q) {
                    $q->where('is_active', true)
                      ->orWhereNull('is_active');
                });
            }
            
            // Filter groups for integrators - only show their own groups
            if ($isIntegrator) {
                $groupsQuery->where('integrator_id', $user->integrator_id);
            }
            $groups = $groupsQuery->get();
        } catch (\Exception $e) {
            Log::warning('Error loading groups: ' . $e->getMessage());
        }
        
        try {
            $partnersQuery = Partner::select('id', 'name')->orderBy('name');
            // Filter partners for integrators - only show their own partners
            if ($isIntegrator) {
                $partnersQuery->where('integrator_id', $user->integrator_id);
            }
            $partners = $partnersQuery->get();
        } catch (\Exception $e) {
            Log::warning('Error loading partners: ' . $e->getMessage());
        }

        $isClientOnly = $user->can('view_public_charging_points') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']);
        return view('charging-points.index', compact('chargingPoints', 'stats', 'groups', 'partners', 'isClientOnly'));
    }

    public function show(ChargingPoint $charging_point)
    {
        $chargingPoint = $charging_point;
        Log::info('Attempting to show charging point', ['id' => $chargingPoint->id]);

        try {
            // Authorize the action
            $this->authorize('view', $chargingPoint);
            
            Log::info('After finding charging point.', ['id' => $chargingPoint->id, 'name' => $chargingPoint->name]);

            // Load relations one by one with logging
            $chargingPoint->load('partner:id,name,type,business_profile_id');
            Log::info('Partner relation loaded.');
            
            // Load partner's business profile if partner exists
            if ($chargingPoint->partner) {
                $chargingPoint->partner->load('businessProfile');
                Log::info('Partner business profile relation loaded.');
            }

            $chargingPoint->load('group:id,name');
            Log::info('Group relation loaded.');

            $chargingPoint->load('integrator:id,name,business_profile_id');
            Log::info('Integrator relation loaded.');

            // Load integrator's business profile if integrator exists
            if ($chargingPoint->integrator) {
                $chargingPoint->integrator->load('businessProfile');
                Log::info('Integrator business profile relation loaded.');
            }

            $chargingPoint->load('pricingPlan');
            Log::info('PricingPlan relation loaded.');

            $chargingPoint->load('businessProfile');
            Log::info('BusinessProfile relation loaded.');

            // Load partner's business profile if partner exists
            if ($chargingPoint->partner) {
                $chargingPoint->partner->load('businessProfile');
                Log::info('Partner business profile relation loaded.');
            }

            Log::info('All relations loaded.', ['id' => $chargingPoint->id, 'name' => $chargingPoint->name, 'pricing_plan_loaded' => !is_null($chargingPoint->pricingPlan)]);

            // Extract the pricing plan and pass it as $pricingPlan
            $pricingPlan = $chargingPoint->pricingPlan;

            // Add logging for the pricing plan data
            Log::info('Active pricing plan data:', ['pricing_plan' => $pricingPlan]);

            // Calculate fee display data (avec fallback en cas d'erreur)
            $feeCalculationService = app(\App\Services\DetailedFeeCalculationService::class);
            try {
                $feeDisplayData = $feeCalculationService->getChargingPointFeeDisplay($chargingPoint);
                $sampleAmounts = [50, 100, 200, 500];
                $sampleCalculations = [];
                foreach ($sampleAmounts as $amount) {
                    $sampleCalculations[$amount] = $feeCalculationService->calculateChargingPointFees($chargingPoint, $amount);
                }
            } catch (\Throwable $e) {
                Log::warning('Fee calculation failed for charging point show', ['id' => $chargingPoint->id, 'error' => $e->getMessage()]);
                $feeDisplayData = ['has_business_profile' => false, 'has_pricing_plan' => false];
                $sampleCalculations = [50 => [], 100 => [], 200 => [], 500 => []];
            }

                        // Déterminer les types de réservation disponibles
            $availableTypes = [];
            if ($pricingPlan) {
                if ($pricingPlan->price_per_kwh > 0) {
                    $availableTypes[] = 'kwh';
                }
                if ($pricingPlan->price_per_minute > 0) {
                    $availableTypes[] = 'minute';
                }
            }
            
            // Si aucun type n'est disponible, permettre les deux par défaut
            if (empty($availableTypes)) {
                $availableTypes = ['kwh', 'minute'];
            }

            // Variables attendues par la vue (statut temps réel Steve)
            $realtimeStatus = $chargingPoint->status ?? 'unknown';
            $steveStatusSuccess = false;

            // QR Code en data URL (base64) pour éviter les 404 sur les fichiers stockés
            $qrCodeUrl = null;
            try {
                $qrCodeService = app(QRCodeService::class);
                $qrCodeUrl = $qrCodeService->generateAsDataUrl($chargingPoint->id);
            } catch (\Throwable $e) {
                Log::debug('QR code not available for show page', ['id' => $chargingPoint->id, 'error' => $e->getMessage()]);
            }

            return view('charging-points.show', compact('chargingPoint', 'pricingPlan', 'feeDisplayData', 'sampleCalculations', 'availableTypes', 'realtimeStatus', 'steveStatusSuccess', 'qrCodeUrl'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found.', ['id' => $chargingPoint->id ?? null, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'Aucun point de charge trouvé.');
        } catch (\Throwable $e) {
            Log::error('Error showing charging point.', [
                'id' => $chargingPoint->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $errorMessage = 'Une erreur est survenue lors de l\'affichage du point de charge.';
            if (config('app.debug')) {
                $errorMessage .= ' ' . $e->getMessage() . ' (ligne ' . $e->getLine() . ')';
            }
            return redirect()->route('charging-points.index')->with('error', $errorMessage);
        }
    }

    /**
     * Show the form for editing the specified charging point.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($id);
            
            // Authorize the action
            $this->authorize('update', $chargingPoint);
            
            // Load necessary relations
            $chargingPoint->load(['partner:id,name', 'group:id,name', 'integrator:id,name', 'pricingPlan:id,name']);
            
            $user = Auth::user();
            $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isPartner    = in_array('partner', $userRolesLower);
            $isIntegrator = in_array('integrator', $userRolesLower) && $user->integrator_id;
            $partnerIdForEdit = $user->partner_id ?? optional($user->partner)->id ?? 0;

            // Groups — scoped by role
            $groupsQuery = Group::forUser($user)->select('id', 'name')->orderBy('name');
            if (Schema::hasColumn('groups', 'is_active')) {
                $groupsQuery->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                });
            }
            $groups = $groupsQuery->get();

            // Partners — scoped by role
            $partnersQuery = Partner::select('id', 'name')->orderBy('name');
            if ($isPartner) {
                $partnersQuery->where('id', $partnerIdForEdit);
            } elseif ($isIntegrator) {
                $partnersQuery->where('integrator_id', $user->integrator_id);
            }
            $partners = $partnersQuery->get();

            $integrators = \App\Models\Integrator::select('id', 'name')->orderBy('name')->get();

            // Pricing plans — scoped by role
            $pricingPlans = PricingPlan::where('is_active', true)
                ->forUser($user)
                ->orderBy('name')
                ->get();
            
            return view('charging-points.edit', compact('chargingPoint', 'groups', 'partners', 'integrators', 'pricingPlans'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found for editing.', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'Aucun point de charge trouvé.');
        } catch (\Exception $e) {
            Log::error('Error loading charging point for editing.', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'Une erreur est survenue lors du chargement du point de charge.');
        }
    }

    /**
     * Update the specified charging point in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($id);
            
            // Authorize the action
            $this->authorize('update', $chargingPoint);
            
            // Validate the request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'serial_number' => 'required|string|max:255|unique:charging_points,serial_number,' . $id,
                'manufacturer' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'status' => 'required|string|in:online,offline,maintenance,error',
                'group_id' => 'nullable|exists:groups,id',
                'partner_id' => 'nullable|exists:partners,id',
                'integrator_id' => 'nullable|exists:integrators,id',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'power_output' => 'nullable|numeric|min:0|max:100000',
                'connection_type' => 'nullable|string|max:255',
                'communication_protocol' => 'nullable|string|max:255',
                'installation_date' => 'nullable|date',
                'access_type' => 'nullable|string|in:public,private,restricted',
                'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
                'authentication_required' => 'nullable|boolean',
                'ip_address' => 'nullable|ipv4',
                'firmware_version' => 'nullable|string|max:255',
                'mac_address' => [
                    'nullable',
                    'string',
                    'max:17',
                    // enforce proper MAC address format (six pairs of hex digits separated by : or -)
                    'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'
                ],
                'server_url' => 'nullable|url|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);
            
            // Update the charging point
            $chargingPoint->update($validatedData);
            
            Log::info('Charging point updated successfully.', ['id' => $id, 'name' => $chargingPoint->name]);
            
            return redirect()->route('charging-points.show', $chargingPoint->id)
                           ->with('success', 'Point de charge mis à jour avec succès.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found for updating.', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'Aucun point de charge trouvé.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error while updating charging point.', ['id' => $id, 'errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating charging point.', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'Une erreur est survenue lors de la mise à jour du point de charge.');
        }
    }

    public function create()
    {
        Log::info('Attempting to fetch pricing plans for charging point create form.');
        try {
            $user = Auth::user();

            $pricingPlans = \App\Models\PricingPlan::where('is_active', true)
                ->forUser($user)
                ->orderBy('name', 'asc')
                ->get();
            Log::info('Fetched pricing plans.', ['count' => $pricingPlans->count()]);

            return view('charging-points.create', compact('pricingPlans'));
        } catch (\Exception $e) {
            Log::error('Error fetching pricing plans for charging point create form.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // Remove the basic store method as we'll use the multi-step process
    // public function store(Request $request)
    // {
    //     // Basic implementation for now
    //     // You will need to add validation and logic to save the charging point
    //     return redirect()->route('charging-points.index')->with('success', 'Charging point created successfully.');
    // }

    /**
     * Show the form for creating a new charging point - Step 1.
     *
     * @return \Illuminate\View\View
     */
    public function createStep1()
    {
        // Nettoyer les sessions invalides seulement si on n'est pas en train de créer (pas de step2 ou step3)
        // Cela évite de supprimer les sessions lors d'une redirection après erreur
        if (!Session::has('charging_point_step2') && !Session::has('charging_point_step3')) {
            $this->cleanInvalidSessions();
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur est un intégrateur, partenaire ou opérateur
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isIntegrator = in_array('integrator', $userRolesLower) && $user->integrator_id;
        $isPartner = in_array('partner', $userRolesLower);
        $isOperator = in_array('operator', $userRolesLower);
        $partnerIdForUser = $user->partner_id ?? optional($user->partner)->id ?? 0;
        
        Log::info('CREATE STEP1 - User info', [
            'user_id' => $user->id,
            'user_roles' => $userRolesLower,
            'is_integrator' => $isIntegrator,
            'integrator_id' => $user->integrator_id ?? null
        ]);
        
        // Récupérer tous les groupes disponibles
        // D'abord, récupérer tous les groupes sans filtre de statut pour voir ce qui existe
        $allGroupsCount = Group::count();
        Log::info('CREATE STEP1 - Total groups in database', [
            'total_count' => $allGroupsCount,
            'is_integrator' => $isIntegrator,
            'user_integrator_id' => $user->integrator_id ?? null
        ]);
        
        $groupsQuery = Group::query();

        // Filtrer les groupes selon le rôle
        if ($isPartner || $isOperator) {
            // Les partenaires et opérateurs ne voient que les groupes de leur partenaire
            $groupsQuery->where('partner_id', $partnerIdForUser);
        } elseif ($isIntegrator) {
            // Vérifier d'abord si l'intégrateur a des groupes spécifiques
            $integratorGroupsCount = Group::where('integrator_id', $user->integrator_id)
                                          ->whereNotNull('integrator_id')
                                          ->count();
            
            Log::info('CREATE STEP1 - Groups for integrator', [
                'integrator_id' => $user->integrator_id,
                'integrator_groups_count' => $integratorGroupsCount,
                'all_groups_count' => $allGroupsCount
            ]);
            
            // Si l'intégrateur a des groupes spécifiques, ne retourner que ceux-là
            // Sinon, inclure aussi les groupes globaux (integrator_id = null)
            if ($integratorGroupsCount > 0) {
                $groupsQuery->where('integrator_id', $user->integrator_id)
                           ->whereNotNull('integrator_id');
            } else {
                // Si l'intégrateur n'a pas de groupes spécifiques, inclure les groupes globaux
                $groupsQuery->where(function($q) use ($user) {
                    $q->where('integrator_id', $user->integrator_id)
                      ->orWhereNull('integrator_id');
                });
                Log::info('CREATE STEP1 - No integrator groups found, including global groups');
            }
        }
        
        // Filtrer uniquement les groupes actifs (comme dans les autres contrôleurs)
        // Vérifier si la colonne is_active existe avant de filtrer
        if (Schema::hasColumn('groups', 'is_active')) {
            // D'abord, essayer de récupérer les groupes actifs ou NULL
            $activeGroupsCount = (clone $groupsQuery)
                ->where(function($q) {
                    $q->where('is_active', true)
                      ->orWhereNull('is_active');
                })
                ->count();
            
            // Si aucun groupe actif ou NULL n'est trouvé, inclure tous les groupes
            // pour éviter un formulaire vide (les groupes inactifs peuvent être activés plus tard)
            if ($activeGroupsCount > 0) {
                $groupsQuery->where(function($q) {
                    $q->where('is_active', true)
                      ->orWhereNull('is_active');
                });
            }
            // Si $activeGroupsCount == 0, on ne filtre pas et on retourne tous les groupes
        }
        // Si la colonne n'existe pas, on retourne tous les groupes
        
        // Trier par nom pour un affichage cohérent
        $groupsQuery->orderBy('name', 'asc');
        
        $groups = $groupsQuery->get();
        
        Log::info('CREATE STEP1 - Groups retrieved', [
            'groups_count' => $groups->count(),
            'group_ids' => $groups->pluck('id')->toArray(),
            'group_names' => $groups->pluck('name')->toArray(),
            'groups_data' => $groups->map(function($g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'integrator_id' => $g->integrator_id ?? null,
                    'is_active' => $g->is_active ?? null,
                    'status' => $g->status ?? null
                ];
            })->toArray(),
            'is_integrator' => $isIntegrator,
            'user_integrator_id' => $user->integrator_id ?? null
        ]);
        
        // Filtrer les partenaires selon le rôle
        $partnersQuery = Partner::query();
        if ($isPartner) {
            // Un partenaire ne voit que son propre profil
            $partnersQuery->where('id', $partnerIdForUser);
        } elseif ($isIntegrator) {
            $partnersQuery->where('integrator_id', $user->integrator_id);
        }
        $partners = $partnersQuery->get();

        $integrators = \App\Models\Integrator::all();

        return view('charging-points.create-step1', compact('groups', 'partners', 'integrators'));
    }

    /**
     * Store Step 1 data in session and redirect to Step 2.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeStep1(Request $request)
    {
        $user = Auth::user();
        
        // Vérifier si l'utilisateur est un intégrateur
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isIntegrator = in_array('integrator', $userRolesLower) && $user->integrator_id;
        
        Log::info('STORE STEP1 - User info', [
            'user_id' => $user->id,
            'user_roles' => $userRolesLower,
            'is_integrator' => $isIntegrator,
            'integrator_id' => $user->integrator_id ?? null
        ]);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:charging_points,serial_number',
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'status' => 'required|string|in:online,offline,maintenance,error',
            'group_id' => 'required|exists:groups,id',
            'integrator_id' => 'nullable|exists:integrators,id',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // On retire partner_id du tableau si jamais il est présent
        unset($validatedData['partner_id']);
        
        // Pour les intégrateurs : vérifier que le groupe est valide
        if ($isIntegrator && !empty($validatedData['group_id'])) {
            $group = Group::find($validatedData['group_id']);
            if ($group) {
                // Vérifier si l'intégrateur a des groupes spécifiques
                $integratorGroupsCount = Group::where('integrator_id', $user->integrator_id)
                                              ->whereNotNull('integrator_id')
                                              ->count();
                
                // Si l'intégrateur a des groupes spécifiques, il ne peut utiliser que ceux-là
                // Sinon, il peut utiliser les groupes globaux (integrator_id = null)
                if ($integratorGroupsCount > 0) {
                    // L'intégrateur a des groupes spécifiques, il ne peut utiliser que ceux-là
                    if ($group->integrator_id !== $user->integrator_id) {
                        Log::warning('STORE STEP1 - Integrator attempted to use another integrator\'s group', [
                            'user_id' => $user->id,
                            'user_integrator_id' => $user->integrator_id,
                            'group_id' => $validatedData['group_id'],
                            'group_integrator_id' => $group->integrator_id
                        ]);
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['group_id' => 'Vous ne pouvez sélectionner que vos propres groupes.']);
                    }
                } else {
                    // L'intégrateur n'a pas de groupes spécifiques, il peut utiliser les groupes globaux
                    // Mais il ne peut pas utiliser les groupes d'autres intégrateurs
                    if ($group->integrator_id !== null && $group->integrator_id !== $user->integrator_id) {
                        Log::warning('STORE STEP1 - Integrator attempted to use another integrator\'s group', [
                            'user_id' => $user->id,
                            'user_integrator_id' => $user->integrator_id,
                            'group_id' => $validatedData['group_id'],
                            'group_integrator_id' => $group->integrator_id
                        ]);
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['group_id' => 'Vous ne pouvez sélectionner que vos propres groupes ou les groupes globaux.']);
                    }
                }
            }
        }
        
        // Auto-assigner integrator_id si l'utilisateur est un intégrateur et qu'il n'est pas fourni
        if ($isIntegrator && empty($validatedData['integrator_id']) && $user->integrator_id) {
            $validatedData['integrator_id'] = $user->integrator_id;
            Log::info('STORE STEP1 - Auto-assigned integrator_id', [
                'integrator_id' => $user->integrator_id
            ]);
        }

        Session::put('charging_point_step1', $validatedData);
        
        // S'assurer que la session est sauvegardée
        Session::save();

        Log::info('Step 1 data stored in session', [
            'keys' => array_keys($validatedData),
            'session_id' => Session::getId(),
            'step1_exists' => Session::has('charging_point_step1'),
            'count' => count($validatedData),
            'session_data' => Session::get('charging_point_step1')
        ]);

        return redirect()->route('charging-points.create.step2')->with('success', 'Étape 1 enregistrée avec succès.');
    }

    /**
     * Show the form for creating a new charging point - Step 2.
     *
     * @return \Illuminate\View\View
     */
    public function createStep2()
    {
        try {
            if (!Session::has('charging_point_step1')) {
                return redirect()->route('charging-points.create.step1')
                    ->with('error', 'Veuillez compléter l\'étape 1 d\'abord.');
            }

            $user = Auth::user();

            $pricingPlans = collect();

            try {
                if (Schema::hasTable('pricing_plans')) {
                    $pricingPlans = PricingPlan::where('is_active', true)
                        ->forUser($user)
                        ->orderBy('name', 'asc')
                        ->get();
                } else {
                    Log::warning('Table pricing_plans does not exist');
                }
            } catch (\Exception $e) {
                Log::error('Error fetching pricing plans in createStep2', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $pricingPlans = collect();
            }

            return view('charging-points.create-step2', compact('pricingPlans'));
            
        } catch (\Exception $e) {
            Log::error('Error in createStep2', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('charging-points.create.step1')
                ->with('error', 'Une erreur est survenue lors du chargement de la page. Veuillez réessayer.');
        }
    }

    /**
     * Store Step 2 data in session and redirect to Step 3.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeStep2(Request $request)
    {
        $step1Data = Session::get('charging_point_step1');
        if ($this->isStepDataMissing($step1Data)) {
            Log::warning('STORE STEP2 - Step 1 data missing, redirecting to step1', [
                'session_id' => Session::getId(),
                'all_session_keys' => array_keys(Session::all()),
            ]);

            return redirect()->route('charging-points.create.step1')
                ->with('error', 'Veuillez completer l\'etape 1 avant de continuer.');
        }

        $validatedData = $request->validate([
            'power_output' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === 'other') {
                        return; // Allow 'other' value
                    }
                    if (!is_numeric($value) || $value < 0 || $value > 100000) {
                        $fail('La puissance doit être un nombre entre 0 et 100000 ou "Autre".');
                    }
                },
            ],
            'connector_type' => 'required|string|max:255',
            'connection_type' => 'required|string|max:255',
            'communication_protocol' => 'required|string|max:255',
            'installation_date' => 'nullable|date',
            'accessibility' => 'nullable|string|in:public,private,restricted',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'authentication_required' => 'nullable|boolean',
        ]);

        // Convert power_output from step1 if needed (manufacturer/model might be in step1)
        if (isset($step1Data['manufacturer'])) {
            $validatedData['manufacturer'] = $step1Data['manufacturer'];
        }
        if (isset($step1Data['model'])) {
            $validatedData['model'] = $step1Data['model'];
        }

        // If power_output is 'other', convert to numeric 0 or handle separately
        if ($validatedData['power_output'] === 'other') {
            $validatedData['power_output'] = 0; // or set a default, or handle in UI
        } else {
            $validatedData['power_output'] = (float) $validatedData['power_output'];
        }

        // Map accessibility to access_type for consistency
        if (isset($validatedData['accessibility'])) {
            $validatedData['access_type'] = $validatedData['accessibility'];
        }

        $step2Data = $validatedData;
        Session::put('charging_point_step2', $step2Data);

        // Fallback: stocker aussi dans le cache (TTL 5 min) pour pallier les problèmes
        // de persistance de session entre redirect et requête suivante (load balancer, etc.)
        $sessionId = Session::getId();
        Cache::put("charging_point_step2_{$sessionId}", $step2Data, now()->addMinutes(5));

        // S'assurer que la session est sauvegardée
        Session::save();

        Log::info('Step 2 data stored in session and cache', [
            'keys' => array_keys($step2Data),
            'session_id' => $sessionId,
            'step1_exists' => Session::has('charging_point_step1'),
            'step2_exists' => Session::has('charging_point_step2'),
        ]);

        return redirect()->route('charging-points.create.step3')->with('success', 'Étape 2 enregistrée avec succès.');
    }

    /**
     * Show the form for creating a new charging point - Step 3.
     *
     * @return \Illuminate\View\View
     */
    public function createStep3()
    {
        $step1Data = Session::get('charging_point_step1');
        $step2Data = Session::get('charging_point_step2');

        // Fallback: si step2 manque en session (problème de persistance), tenter de le récupérer depuis le cache
        $this->restoreStep2FromCacheIfMissing();
        $step2Data = Session::get('charging_point_step2');

        if ($this->isStepDataMissing($step1Data)) {
            return redirect()->route('charging-points.create.step1')
                ->with('error', 'Veuillez completer l\'etape 1 avant de continuer.');
        }

        if ($this->isStepDataMissing($step2Data)) {
            Log::warning('CREATE STEP3 - Step 2 data missing, rebuilding defaults and redirecting to step2', [
                'session_id' => Session::getId(),
                'all_session_keys' => array_keys(Session::all()),
            ]);

            Session::put('charging_point_step2', $this->buildDefaultStep2Data($step1Data));
            Session::save();

            return redirect()->route('charging-points.create.step2')
                ->with('error', 'Les specifications techniques (etape 2) etaient manquantes. Veuillez les verifier.');
        }

        return view('charging-points.create-step3');
    }

    /**
     * Store Step 3 data in session and redirect to Confirmation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeStep3(Request $request)
    {
        $validatedData = $request->validate([
            'connection_type' => 'required|string|max:255',
            'ip_address' => 'nullable|ipv4',
            'communication_protocol' => 'required|string|max:255',
            'firmware_version' => 'nullable|string|max:255',
            'mac_address' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'
            ],
            'server_url' => 'nullable|url|max:255',
            'access_type' => 'nullable|string|in:public,private,restricted',
            'public_access' => 'nullable|boolean',
            'access_code' => 'nullable|string|max:255',
            'authentication_required' => 'nullable|boolean',
        ]);

        // Set default values if not provided
        if (!isset($validatedData['access_type'])) {
            $validatedData['access_type'] = 'public';
        }
        if (!isset($validatedData['public_access'])) {
            $validatedData['public_access'] = ($validatedData['access_type'] === 'public');
        }

        Session::put('charging_point_step3', $validatedData);
        
        // S'assurer que la session est sauvegardée
        Session::save();

        Log::info('Step 3 data stored in session', [
            'keys' => array_keys($validatedData),
            'session_id' => Session::getId(),
            'step3_data' => $validatedData,
            'all_session_keys' => array_keys(Session::all())
        ]);

        // Fallback: restaurer step2 depuis le cache si manquant en session
        $this->restoreStep2FromCacheIfMissing();

        // Vérifier que step1 et step2 sont toujours présents
        $step1Check = Session::has('charging_point_step1');
        $step2Check = Session::has('charging_point_step2');
        $step3Check = Session::has('charging_point_step3');
        
        Log::info('Step 3 - Session verification before redirect', [
            'step1_exists' => $step1Check,
            'step2_exists' => $step2Check,
            'step3_exists' => $step3Check,
            'session_id' => Session::getId(),
            'step1_data' => Session::get('charging_point_step1'),
            'step2_data' => Session::get('charging_point_step2'),
            'step3_data' => Session::get('charging_point_step3')
        ]);

        // Double verification: redirect to the step that is missing
        if (!$step1Check || !$step2Check || !$step3Check) {
            Log::warning('Step 3 - Session data incomplete, redirecting to the appropriate step', [
                'step1_exists' => $step1Check,
                'step2_exists' => $step2Check,
                'step3_exists' => $step3Check
            ]);

            if (!$step1Check) {
                return redirect()->route('charging-points.create.step1')
                    ->with('error', 'Les donnees de l\'etape 1 sont manquantes. Veuillez recommencer.');
            }

            if (!$step2Check) {
                return redirect()->route('charging-points.create.step2')
                    ->with('error', 'Les donnees de l\'etape 2 sont manquantes. Veuillez les completer.');
            }

            return redirect()->route('charging-points.create.step3')
                ->with('error', 'Les donnees de l\'etape 3 sont invalides. Veuillez reessayer.');
        }

        return redirect()->route('charging-points.create.confirm')->with('success', 'Étape 3 enregistrée avec succès.');
    }

    /**
     * Show the confirmation page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function confirm()
    {
        try {
            // Log immédiat pour confirmer que la méthode est appelée
            $routeName = null;
            try {
                $route = request()->route();
                $routeName = $route ? $route->getName() : 'unknown';
            } catch (\Exception $routeException) {
                Log::warning('CONFIRM - Could not get route name', ['error' => $routeException->getMessage()]);
            }
            
            Log::info('CONFIRM METHOD CALLED', [
                'method' => 'confirm',
                'url' => request()->url(),
                'route' => $routeName,
                'session_id' => Session::getId(),
                'user_id' => auth()->id()
            ]);
            
            // S'assurer que la session est sauvegardée avant de vérifier
            Session::save();

            // Fallback: restaurer step2 depuis le cache si manquant en session
            $this->restoreStep2FromCacheIfMissing();
            
            // Récupérer toutes les données de session directement
            $step1Data = Session::get('charging_point_step1', null);
            $step2Data = Session::get('charging_point_step2', null);
            $step3Data = Session::get('charging_point_step3', null);

            // Log détaillé pour le débogage
            Log::info('CONFIRM - Checking session data', [
                'step1_exists' => !is_null($step1Data),
                'step1_is_array' => is_array($step1Data),
                'step1_count' => is_array($step1Data) ? count($step1Data) : 0,
                'step1_keys' => is_array($step1Data) ? array_keys($step1Data) : [],
                'step2_exists' => !is_null($step2Data),
                'step2_is_array' => is_array($step2Data),
                'step2_count' => is_array($step2Data) ? count($step2Data) : 0,
                'step2_keys' => is_array($step2Data) ? array_keys($step2Data) : [],
                'step3_exists' => !is_null($step3Data),
                'step3_is_array' => is_array($step3Data),
                'step3_count' => is_array($step3Data) ? count($step3Data) : 0,
                'step3_keys' => is_array($step3Data) ? array_keys($step3Data) : [],
                'session_id' => Session::getId(),
                'all_session_keys' => array_keys(Session::all())
            ]);

            // Vérifier que toutes les étapes sont complétées
            if (is_null($step1Data) || !is_array($step1Data) || empty($step1Data)) {
                Log::warning('CONFIRM - Step 1 data missing or invalid', [
                    'step1_data' => $step1Data,
                    'is_null' => is_null($step1Data),
                    'is_array' => is_array($step1Data),
                    'empty' => empty($step1Data)
                ]);
                return redirect()->route('charging-points.create.step1')
                    ->with('error', 'Veuillez compléter l\'étape 1 d\'abord.');
            }
            
            if (is_null($step2Data) || !is_array($step2Data) || empty($step2Data)) {
                Log::warning('CONFIRM - Step 2 data missing or invalid', [
                    'step2_data' => $step2Data,
                    'is_null' => is_null($step2Data),
                    'is_array' => is_array($step2Data),
                    'empty' => empty($step2Data)
                ]);
                return redirect()->route('charging-points.create.step2')
                    ->with('error', 'Veuillez compléter l\'étape 2 d\'abord.');
            }
            
            if ($this->isStepDataMissing($step3Data)) {
                Log::warning('CONFIRM - Step 3 data missing or invalid, generating defaults', [
                    'step3_data' => $step3Data,
                    'is_null' => is_null($step3Data),
                    'is_array' => is_array($step3Data),
                    'empty' => empty($step3Data)
                ]);

                $step3Data = $this->buildDefaultStep3Data($step2Data ?? []);
                Session::put('charging_point_step3', $step3Data);
                Session::save();
                Session::flash('warning', 'Les informations de connectivité n\'étaient pas complétées. Des valeurs par défaut ont été appliquées.');
            }

            // Merge all data for display on confirmation page
            $chargingPointData = array_merge($step1Data, $step2Data, $step3Data);

            Log::info('CONFIRM - Displaying confirmation page successfully', [
                'step1_keys' => array_keys($step1Data),
                'step2_keys' => array_keys($step2Data),
                'step3_keys' => array_keys($step3Data),
                'merged_keys_count' => count($chargingPointData)
            ]);

            return view('charging-points.create-confirm', compact('chargingPointData'));
            
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $e) {
            Log::error('CONFIRM - Route not found', [
                'error' => $e->getMessage(),
                'url' => request()->url(),
                'session_all' => Session::all()
            ]);
            
            return redirect()->route('charging-points.create.step1')
                ->with('error', 'Route non trouvée. Veuillez recommencer depuis le début.');
                
        } catch (\Exception $e) {
            Log::error('CONFIRM - Error displaying confirmation page', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_all' => Session::all()
            ]);
            
            return redirect()->route('charging-points.create.step1')
                ->with('error', 'Une erreur est survenue lors du chargement de la page de confirmation. Veuillez réessayer.');
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Log immédiat pour confirmer que la méthode est appelée
        Log::info('STORE METHOD CALLED', [
            'method' => 'store',
            'url' => request()->url(),
            'route' => request()->route()->getName(),
            'session_id' => Session::getId(),
            'user_id' => auth()->id(),
            'request_method' => request()->method()
        ]);
        
        // Fallback: restaurer step2 depuis le cache si manquant en session
        $this->restoreStep2FromCacheIfMissing();

        // Retrieve all data from the session
        $step1Data = Session::get('charging_point_step1');
        $step2Data = Session::get('charging_point_step2');
        $step3Data = Session::get('charging_point_step3');
        // Suppression de connectorsData

        \Log::info('STORE CHARGING POINT - step1Data', $step1Data ?? []);
        \Log::info('STORE CHARGING POINT - step2Data', $step2Data ?? []);
        \Log::info('STORE CHARGING POINT - step3Data', $step3Data ?? []);
        
        // Log détaillé de la session
        \Log::info('STORE CHARGING POINT - Session details', [
            'session_id' => Session::getId(),
            'step1_exists' => !is_null($step1Data),
            'step1_is_array' => is_array($step1Data),
            'step1_count' => is_array($step1Data) ? count($step1Data) : 0,
            'step2_exists' => !is_null($step2Data),
            'step2_is_array' => is_array($step2Data),
            'step2_count' => is_array($step2Data) ? count($step2Data) : 0,
            'step3_exists' => !is_null($step3Data),
            'step3_is_array' => is_array($step3Data),
            'step3_count' => is_array($step3Data) ? count($step3Data) : 0,
            'all_session_keys' => array_keys(Session::all())
        ]);

        // Check if all session data exists
        if (!$step1Data || !$step2Data || $this->isStepDataMissing($step3Data)) {
            Log::warning('STORE CHARGING POINT - Step 3 data missing, attempting auto-fill', [
                'step1_exists' => !is_null($step1Data),
                'step2_exists' => !is_null($step2Data),
                'step3_exists' => !is_null($step3Data),
                'step3_is_array' => is_array($step3Data),
                'step3_empty' => empty($step3Data)
            ]);

            if ($this->isStepDataMissing($step2Data) && !$this->isStepDataMissing($step1Data)) {
                $step2Data = $this->buildDefaultStep2Data($step1Data);
                Session::put('charging_point_step2', $step2Data);
                Session::save();
                Session::flash('warning', 'Les informations techniques ont ete completees automatiquement.');
            }

            if ($step2Data) {
                $step3Data = $this->buildDefaultStep3Data($step2Data);
                Session::put('charging_point_step3', $step3Data);
                Session::save();
                Session::flash('warning', 'Les informations de connectivité ont été complétées automatiquement.');
            }
        }

        if (!$step1Data || !$step2Data || $this->isStepDataMissing($step3Data)) {
            \Log::error('STORE CHARGING POINT - Session data missing', [
                'step1' => $step1Data,
                'step2' => $step2Data,
                'step3' => $step3Data,
                'session_id' => Session::getId(),
                'all_session' => Session::all()
            ]);
            // Rediriger vers la confirmation au lieu de step1 pour permettre à l'utilisateur de voir l'erreur
            return redirect()->route('charging-points.create.confirm')
                ->with('error', 'Les données de session sont manquantes. Veuillez recommencer depuis le début.');
        }

        // Merge all data for final validation and saving
        $allData = array_merge($step1Data, $step2Data, $step3Data);

        // Perform final validation on merged data
        $validator = Validator::make($allData, [
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:charging_points,serial_number',
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'status' => 'required|string|in:online,offline,maintenance,error',
            'group_id' => 'required|exists:groups,id',
            'partner_id' => 'nullable|exists:partners,id',
            'integrator_id' => 'nullable|exists:integrators,id',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'power_output' => 'nullable|numeric|min:0|max:100000',
            'connector_type' => 'required|string|max:255',
            'connection_type' => 'required|string|max:255',
            'communication_protocol' => 'required|string|max:255',
            'installation_date' => 'nullable|date',
            'accessibility' => 'nullable|string|in:public,private,restricted',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'authentication_required' => 'nullable|boolean',
            'ip_address' => 'nullable|ipv4',
            'firmware_version' => 'nullable|string|max:255',
            'mac_address' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'
            ],
            'server_url' => 'nullable|url|max:255',
            'access_type' => 'nullable|string|in:public,private,restricted',
            'public_access' => 'nullable|boolean',
            'access_code' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            \Log::error('STORE CHARGING POINT - Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'all_data' => $allData,
                'missing_fields' => array_keys($validator->errors()->toArray())
            ]);
            // If validation fails, redirect back to the confirmation page with errors
            // Conserver les données de session pour permettre à l'utilisateur de corriger
            return redirect()->route('charging-points.create.confirm')
                             ->withErrors($validator->errors())
                             ->with('error', 'Veuillez corriger les erreurs de validation ci-dessous.');
        }

        // Security & Authorization Validation
        $user = Auth::user();
        
        // Use Gate authorization to check permissions (respects ChargingPointPolicy::create)
        // This allows Admin, Integrator, Partner, and Operator roles
        if (!$user->can('create', \App\Models\ChargingPoint::class)) {
            \Log::warning('STORE CHARGING POINT - Unauthorized user attempted to create charging point', [
                'user_id' => $user->id,
                'user_role' => $user->roles->pluck('name')->toArray()
            ]);
            return redirect()->route('charging-points.index')
                ->with('error', 'Vous n\'êtes pas autorisé à créer des bornes de recharge.');
        }

        // Vérifier que le groupe existe et est actif
        if (!empty($allData['group_id'])) {
            $group = Group::find($allData['group_id']);
            if (!$group) {
                \Log::error('STORE CHARGING POINT - Group not found', [
                    'group_id' => $allData['group_id'],
                    'user_id' => $user->id,
                    'is_integrator' => $user->hasRole('integrator')
                ]);
                // Rediriger vers la confirmation au lieu de step1 pour préserver les données
                return redirect()->route('charging-points.create.confirm')
                    ->with('error', 'Le groupe sélectionné n\'existe pas. Veuillez retourner à l\'étape 1 pour sélectionner un autre groupe.');
            }
            
            // Validate group ownership for integrators
            if ($user->hasRole('integrator')) {
                if (!$user->integrator_id) {
                    \Log::error('STORE CHARGING POINT - Integrator user has no integrator_id', [
                        'user_id' => $user->id,
                        'user_roles' => $user->roles->pluck('name')->toArray()
                    ]);
                    return redirect()->route('charging-points.create.confirm')
                        ->with('error', 'Erreur: votre compte intégrateur n\'est pas correctement configuré. Veuillez contacter l\'administrateur.');
                }
                
                // Vérifier si l'intégrateur a des groupes spécifiques
                $integratorGroupsCount = Group::where('integrator_id', $user->integrator_id)
                                              ->whereNotNull('integrator_id')
                                              ->count();
                
                // Si l'intégrateur a des groupes spécifiques, il ne peut utiliser que ceux-là
                // Sinon, il peut utiliser les groupes globaux (integrator_id = null)
                if ($integratorGroupsCount > 0) {
                    // L'intégrateur a des groupes spécifiques, il ne peut utiliser que ceux-là
                    if ($group->integrator_id !== $user->integrator_id) {
                        \Log::warning('STORE CHARGING POINT - Integrator attempted to create CP for another integrator\'s group', [
                            'user_id' => $user->id,
                            'user_integrator_id' => $user->integrator_id,
                            'group_integrator_id' => $group->integrator_id,
                            'group_id' => $group->id
                        ]);
                        return redirect()->route('charging-points.create.confirm')
                            ->with('error', 'Vous ne pouvez créer des bornes que pour vos propres groupes. Veuillez retourner à l\'étape 1 pour sélectionner un autre groupe.');
                    }
                } else {
                    // L'intégrateur n'a pas de groupes spécifiques, il peut utiliser les groupes globaux
                    // Mais il ne peut pas utiliser les groupes d'autres intégrateurs
                    if ($group->integrator_id !== null && $group->integrator_id !== $user->integrator_id) {
                        \Log::warning('STORE CHARGING POINT - Integrator attempted to create CP for another integrator\'s group', [
                            'user_id' => $user->id,
                            'user_integrator_id' => $user->integrator_id,
                            'group_integrator_id' => $group->integrator_id,
                            'group_id' => $group->id
                        ]);
                        return redirect()->route('charging-points.create.confirm')
                            ->with('error', 'Vous ne pouvez créer des bornes que pour vos propres groupes ou les groupes globaux. Veuillez retourner à l\'étape 1 pour sélectionner un autre groupe.');
                    }
                    // Si le groupe a integrator_id = null, c'est un groupe global, l'intégrateur peut l'utiliser
                }
            }
            
            // Vérifier si le groupe est actif (compatible avec les deux structures de base de données)
            $isActive = false;
            if (isset($group->status) && $group->status === 'active') {
                $isActive = true;
            } elseif (isset($group->is_active) && $group->is_active) {
                $isActive = true;
            }
            
            if (!$isActive) {
                \Log::error('STORE CHARGING POINT - Group is not active', [
                    'group_id' => $allData['group_id'],
                    'group' => $group,
                    'user_id' => $user->id,
                    'is_integrator' => $user->hasRole('integrator')
                ]);
                return redirect()->route('charging-points.create.confirm')
                    ->with('error', 'Le groupe sélectionné n\'est pas actif. Veuillez retourner à l\'étape 1 pour sélectionner un autre groupe.');
            }
        } else {
            // Si aucun groupe n'est sélectionné, vérifier si c'est autorisé pour les intégrateurs
            if ($user->hasRole('integrator')) {
                \Log::warning('STORE CHARGING POINT - Integrator attempted to create CP without group', [
                    'user_id' => $user->id,
                    'integrator_id' => $user->integrator_id
                ]);
                // Permettre la création sans groupe pour les intégrateurs (selon les règles métier)
                // Si vous voulez forcer les intégrateurs à avoir un groupe, décommentez la ligne suivante:
                // return redirect()->route('charging-points.create.confirm')->with('error', 'Un groupe est requis pour créer un point de charge.');
            }
        }

        // Create the Charging Point
        \Log::info('STORE CHARGING POINT - Attempting to create charging point with data:', $allData);
        
        try {
            // Pour les intégrateurs, s'assurer que l'integrator_id est toujours défini
            $finalIntegratorId = $allData['integrator_id'] ?? null;
            if ($user->hasRole('integrator') && $user->integrator_id) {
                // Si l'intégrateur n'a pas spécifié d'integrator_id, utiliser le sien
                if (empty($finalIntegratorId)) {
                    $finalIntegratorId = $user->integrator_id;
                    \Log::info('STORE CHARGING POINT - Auto-assigned integrator_id for integrator', [
                        'user_id' => $user->id,
                        'integrator_id' => $finalIntegratorId
                    ]);
                }
            }
            
            // Créer un tableau de données en filtrant les colonnes qui n'existent pas
            $chargingPointData = [
                'name' => $allData['name'],
                'serial_number' => $allData['serial_number'],
                'manufacturer' => $allData['manufacturer'],
                'model' => $allData['model'],
                'status' => $allData['status'],
                'user_id' => auth()->id(),
                'integrator_id' => $finalIntegratorId,
                'partner_id' => $allData['partner_id'] ?? null,
                'group_id' => $allData['group_id'] ?? null,
                'pricing_plan_id' => $allData['pricing_plan_id'] ?? null,
                'location' => $allData['location'] ?? null,
                'address' => $allData['address'] ?? null,
                'city' => $allData['city'] ?? null,
                'postal_code' => $allData['postal_code'] ?? null,
                'country' => $allData['country'] ?? null,
                'latitude' => $allData['latitude'] ?? null,
                'longitude' => $allData['longitude'] ?? null,
                'connector_type' => $allData['connector_type'] ?? null,
                'connection_type' => $allData['connection_type'] ?? null,
                'communication_protocol' => $allData['communication_protocol'] ?? 'ocpp16',
                'installation_date' => isset($allData['installation_date']) ? $allData['installation_date'] : null,
                'ip_address' => $allData['ip_address'] ?? null,
                'firmware_version' => $allData['firmware_version'] ?? null,
                'mac_address' => $allData['mac_address'] ?? null,
                'access_type' => $allData['access_type'] ?? ($allData['accessibility'] ?? 'public'),
                'public_access' => isset($allData['public_access']) ? (bool)$allData['public_access'] : (($allData['access_type'] ?? 'public') === 'public'),
                'access_code' => $allData['access_code'] ?? null,
                'authentication_required' => isset($allData['authentication_required']) ? (bool) $allData['authentication_required'] : false,
            ];

            if (isset($allData['server_url']) && Schema::hasColumn('charging_points', 'steve_server_url')) {
                $chargingPointData['steve_server_url'] = $allData['server_url'];
            }
            
            // Ajouter power_output si présent (peut être dans step2)
            if (isset($allData['power_output'])) {
                // Vérifier si la colonne existe dans la table
                if (Schema::hasColumn('charging_points', 'power_output')) {
                    $chargingPointData['power_output'] = is_numeric($allData['power_output']) ? (float)$allData['power_output'] : null;
                }
            }
            
            // Ajouter 'notes' ou 'description' seulement si la colonne existe
            if (isset($allData['notes']) && Schema::hasColumn('charging_points', 'notes')) {
                $chargingPointData['notes'] = $allData['notes'];
            }
            if (isset($allData['description']) && Schema::hasColumn('charging_points', 'description')) {
                $chargingPointData['description'] = $allData['description'];
            }

            $chargingPointData = array_filter(
                $chargingPointData,
                static fn ($value, string $column): bool => Schema::hasColumn('charging_points', $column),
                ARRAY_FILTER_USE_BOTH
            );

            // Auto-assign integrator_id if user is integrator and not provided
            if ($user->hasRole('integrator') && empty($chargingPointData['integrator_id']) && $user->integrator_id) {
                $chargingPointData['integrator_id'] = $user->integrator_id;
            }
            
            // Use ChargingPointService to create with automatic Steve API registration.
            // The atomicity mode is config-driven (config/steve.php → STEVE_AUTO_PROVISION_ON_CREATE):
            //  - true  (default): strict atomic — SteVe failure rolls back the local row.
            //  - false:           skip SteVe; persist the row with awaiting_sync flag for later retry.
            $chargingPointService = app(\App\Services\ChargingPointService::class);
            $autoProvision = (bool) config('steve.auto_provision_on_create', true);

            $chargingPoint = $chargingPointService->createChargingPoint($chargingPointData, $autoProvision);
        
        \Log::info('STORE CHARGING POINT - Charging point created successfully with ID:', [
            'id' => $chargingPoint->id,
            'name' => $chargingPoint->name,
            'integrator_id' => $chargingPoint->integrator_id,
            'group_id' => $chargingPoint->group_id,
            'user_id' => $chargingPoint->user_id,
            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
            'created_by_user_id' => $user->id,
            'created_by_user_integrator_id' => $user->integrator_id,
            'created_by_role' => $user->roles->pluck('name')->toArray(),
            'charging_point_data_integrator_id' => $finalIntegratorId ?? null,
            'charging_point_data' => $chargingPointData['integrator_id'] ?? null
        ]);

        // Note: La création sur Steve API est déjà gérée par ChargingPointService::createChargingPoint
        // Pas besoin de double création ici

        // Suppression de la création des connecteurs

        // Notifier tous les admins (si la table notifications existe)
        try {
            \App\Models\User::role('admin')->each(function($admin) use ($chargingPoint) {
                $admin->notify(new \App\Notifications\SystemAdminNotification(
                    'Nouveau point de charge créé',
                    'Le point de charge "' . $chargingPoint->name . '" a été ajouté.'
                ));
            });
        } catch (\Exception $e) {
            \Log::warning('STORE CHARGING POINT - Could not send notifications: ' . $e->getMessage());
        }

        // Clear the session data and cache fallback
        $sessionId = Session::getId();
        Session::forget(['charging_point_step1', 'charging_point_step2', 'charging_point_step3']);
        Cache::forget("charging_point_step2_{$sessionId}");

        return redirect()->route('charging-points.index')->with('success', 'Charging point created successfully.');
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si c'est une exception de validation, rediriger vers la confirmation avec les erreurs
            \Log::error('STORE CHARGING POINT - Validation exception:', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);

            return redirect()->route('charging-points.create.confirm')
                ->withErrors($e->errors())
                ->with('error', 'Erreur de validation lors de la création du point de charge.');
        } catch (SteVeConfigurationException $e) {
            // SteVe isn't configured at all — surface a distinct, actionable
            // message instead of swallowing it into the generic 500 branch.
            \Log::error('STORE CHARGING POINT - SteVe configuration missing', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('charging-points.create.confirm')
                ->with('error', 'La connexion à SteVe n\'est pas configurée (STEVE_API_URL absent). Contactez l\'administrateur, ou définissez STEVE_AUTO_PROVISION_ON_CREATE=false pour créer la borne localement et la synchroniser plus tard.');
        } catch (SteVeProvisioningFailedException $e) {
            // The wrapping DB transaction in ChargingPointService has already
            // rolled back, so no half-created CP is left behind. Session data
            // is preserved so the operator can re-submit once SteVe is back.
            \Log::error('STORE CHARGING POINT - SteVe rejected provisioning, rolled back local row', [
                'message'         => $e->getMessage(),
                'upstream_status' => $e->httpStatus,
                'upstream'        => $e->upstreamError,
            ]);
            return redirect()->route('charging-points.create.confirm')
                ->with('error', 'La création sur SteVe a échoué (' . $e->getMessage() . '). Vérifiez la disponibilité du serveur SteVe, ou définissez STEVE_AUTO_PROVISION_ON_CREATE=false pour créer la borne en mode différé.');
        } catch (\Exception $e) {
            \Log::error('STORE CHARGING POINT - Error creating charging point:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_data' => [
                    'step1_exists' => Session::has('charging_point_step1'),
                    'step2_exists' => Session::has('charging_point_step2'),
                    'step3_exists' => Session::has('charging_point_step3')
                ]
            ]);

            // Conserver les données de session en cas d'erreur pour permettre à l'utilisateur de réessayer
            return redirect()->route('charging-points.create.confirm')
                ->with('error', 'Erreur lors de la création du point de charge: ' . $e->getMessage());
        }
    }

    /**
     * Show the charging point's QR code.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function qrCode(ChargingPoint $chargingPoint)
    {
        try {
            // Check if user can view QR codes for the charging point
            try {
                $this->authorize('viewQrCode', $chargingPoint);
            } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
                Log::warning('QR Code authorization failed for user ' . auth()->id() . ' on charging point ' . $chargingPoint->id . ': ' . $authException->getMessage());
                return redirect()->back()
                    ->with('error', 'Vous n\'avez pas la permission de voir le QR code pour cette borne.')
                    ->withInput();
            }
            
            // Générer l'URL de l'offre de réservation
            try {
                $offerUrl = route('public.charging-point.offer.reservation', $chargingPoint->id);
            } catch (\Exception $routeException) {
                Log::error('Failed to generate offer URL for charging point ' . $chargingPoint->id . ': ' . $routeException->getMessage());
                return redirect()->back()
                    ->with('error', 'Erreur lors de la génération de l\'URL de réservation.')
                    ->withInput();
            }
            
            // Service QR Code - génération en data URL (pas de fichier stocké, évite les 404)
            try {
                $qrCodeService = app(QRCodeService::class);
                $qrCodeUrl = $qrCodeService->generateAsDataUrl($chargingPoint->id);
            } catch (\RuntimeException $runtimeException) {
                Log::error('Runtime error generating QR code for charging point ' . $chargingPoint->id . ': ' . $runtimeException->getMessage());
                return redirect()->back()
                    ->with('error', 'Erreur lors de la génération du QR code: ' . $runtimeException->getMessage())
                    ->withInput();
            } catch (\Exception $serviceException) {
                Log::error('Failed to generate QR code: ' . $serviceException->getMessage());
                return redirect()->back()
                    ->with('error', 'Erreur lors de la génération du QR code.')
                    ->withInput();
            }
            
            return view('charging-points.qr-code', compact('chargingPoint', 'qrCodeUrl', 'offerUrl'));
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            Log::warning('QR Code authorization failed: ' . $authException->getMessage());
            return redirect()->back()
                ->with('error', 'Vous n\'avez pas la permission de voir le QR code pour cette borne.')
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error in QR Code generation for charging point ' . $chargingPoint->id . ': ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Erreur inattendue lors de la génération du QR code: ' . $e->getMessage())
                ->withInput();
        }
    }
/**
     * Remove the specified charging point from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        Log::info('Attempting to delete charging point with ID: ' . $id);

        try {
            $chargingPoint = ChargingPoint::findOrFail($id);

            // Authorize the action
            $this->authorize('delete', $chargingPoint);

            // Supprimer dans SteVe API si le point de charge a un steve_charging_point_id
            if (!empty($chargingPoint->steve_charging_point_id)) {
                try {
                    $steveService = app(\App\Services\SteveService::class);
                    $deletedInSteve = $steveService->deleteChargePoint($chargingPoint->steve_charging_point_id);
                    
                    if ($deletedInSteve) {
                        Log::info('Charging point deleted from SteVe API successfully', [
                            'charging_point_id' => $chargingPoint->id,
                            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id
                        ]);
                    } else {
                        Log::warning('Failed to delete charging point from SteVe API, continuing with local deletion', [
                            'charging_point_id' => $chargingPoint->id,
                            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id
                        ]);
                        // Continue avec la suppression locale même si SteVe échoue
                    }
                } catch (\Exception $e) {
                    Log::error('Error deleting charging point from SteVe API, continuing with local deletion', [
                        'charging_point_id' => $chargingPoint->id,
                        'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue avec la suppression locale même si SteVe échoue
                }
            } else {
                Log::info('Charging point has no steve_charging_point_id, skipping SteVe deletion', [
                    'charging_point_id' => $chargingPoint->id
                ]);
            }

            // Delete associated connectors first
            $chargingPoint->connectors()->delete();
            Log::info('Deleted associated connectors for charging point ID: ' . $id);

            // Delete the charging point
            $chargingPoint->delete();
            Log::info('Successfully deleted charging point with ID: ' . $id);

            return redirect()->route('charging-points.index')->with('success', 'Charging point deleted successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found for deletion.', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'Charging point not found.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error deleting charging point ' . $id . ': ' . $e->getMessage());
            return redirect()->route('charging-points.index')->with('error', 'You are not authorized to delete this charging point.');
        } catch (\Exception $e) {
            Log::error('Error deleting charging point.', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('charging-points.index')->with('error', 'An error occurred while deleting the charging point: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page d'offre pour une borne de recharge
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showOffer($id)
    {
        try {
            // Récupérer la borne avec ses relations
            $chargingPoint = \App\Models\ChargingPoint::with([
                'connectors', 
                'pricingPlan', 
                'partner', 
                'integrator'
            ])->findOrFail($id);

            // Récupérer le plan tarifaire actif
            $pricingPlan = null;
            
            // 1. Vérifier si la borne a un plan assigné
            if ($chargingPoint->pricingPlan) {
                $pricingPlan = $chargingPoint->pricingPlan;
            } else {
                // 2. Sinon, récupérer le plan par défaut via le service
                $pricingPlanService = app(\App\Services\PricingPlanService::class);
                $pricingPlan = $pricingPlanService->getDefaultPlanForChargingPoint($chargingPoint->id);
                
                // 3. Si aucun plan par défaut, créer un plan temporaire
                if (!$pricingPlan) {
                    $pricingPlan = new \App\Models\PricingPlan([
                        'name' => 'Plan Standard',
                        'rate_type' => 'mixed',
                        'price_per_kwh' => 0.30,
                        'price_per_minute' => 0.10,
                        'activation_fee' => 1.00,
                        'currency' => 'EUR',
                        'is_active' => true
                    ]);
                }
            }

            // Récupérer tous les plans tarifaires disponibles
            $pricingPlans = \App\Models\PricingPlan::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('name', 'asc')
                ->get();

            // Déterminer le type de carousel selon le rate_type du plan
            $carouselType = 'minute'; // Par défaut
            if ($pricingPlan->rate_type === 'kwh' || $pricingPlan->rate_type === 'energy') {
                $carouselType = 'kwh';
            } elseif ($pricingPlan->rate_type === 'minute' || $pricingPlan->rate_type === 'time') {
                $carouselType = 'minute';
            } elseif ($pricingPlan->rate_type === 'mixed') {
                // Pour le type mixte, on peut laisser l'utilisateur choisir
                $carouselType = 'both';
            }
            
            // Préparer les options pour le carousel
            $carouselOptions = [];
            if ($carouselType === 'minute' || $carouselType === 'both') {
                $carouselOptions['minute'] = [15, 30, 45, 60, 90, 120]; // minutes
            }
            if ($carouselType === 'kwh' || $carouselType === 'both') {
                $carouselOptions['kwh'] = [5, 10, 15, 20, 25, 30]; // kWh
            }
            
            // Calculer les options de tarification
            $pricingOptions = [];
            foreach ($carouselOptions as $type => $values) {
                foreach ($values as $value) {
                    $price = $this->calculatePrice($pricingPlan, $value, $type);
                    $pricingOptions[$type][] = [
                        'value' => $value,
                        'price' => $price,
                        'unit' => $type === 'minute' ? 'min' : 'kWh',
                        'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR')
                    ];
                }
            }

            // Générer le QR code
            $qrCode = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode(route('public.charging-point.offer.view.public', $id));

            // Pas de créneaux horaires - recharge immédiate après paiement
            $timeSlots = [];

            // Préparer les données pour la vue
            $viewData = [
                'chargingPoint' => $chargingPoint,
                'pricingPlan' => $pricingPlan,
                'pricingPlans' => $pricingPlans,
                'carouselType' => $carouselType,
                'pricingOptions' => $pricingOptions,
                'qrCode' => $qrCode,
                'station' => $chargingPoint->station,
                'currency' => $pricingPlan->currency ?? 'EUR',
                'min_duration' => $pricingPlan->min_charge_duration ?? 1,
                'min_kwh' => $pricingPlan->min_energy ?? 1,
                'timeSlots' => $timeSlots,
            ];

            return view('charging-points.offer-reservation', $viewData);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found for offer view: ' . $id);
            return redirect()->route('charging-points.index')
                ->with('error', 'Borne de recharge non trouvée.');
        } catch (\Exception $e) {
            Log::error('Error showing charging offer for charging point ' . $id . ': ' . $e->getMessage());
            return redirect()->route('charging-points.index')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de l\'offre.');
        }
    }

    /**
     * Traite la confirmation de recharge depuis le formulaire de confirmation d'offre.
     */
    public function startOffer(Request $request, $id)
    {
        // Exemple de logique : vérifier les données reçues
        // Ici, vous pouvez créer une session de recharge, enregistrer un log, etc.
        // Pour l'instant, on redirige simplement avec un message de succès
        return redirect()->route('charging-points.index')->with('success', 'Recharge démarrée avec succès !');
    }

    /**
     * Calcule le prix pour une valeur donnée selon le type (minute ou kwh)
     */
    private function calculatePrice($pricingPlan, $value, $type)
    {
        if (!$pricingPlan) return 0;

        $basePrice = 0;

        // Frais d'activation
        $basePrice += $pricingPlan->activation_fee ?? 0;

        // Calcul selon le type
        if ($type === 'minute') {
            $pricePerMinute = $pricingPlan->price_per_minute ?? 0;
            // Pour les anciens modèles qui utilisent base_rate_per_minute
            if ($pricePerMinute == 0 && isset($pricingPlan->base_rate_per_minute)) {
                $pricePerMinute = $pricingPlan->base_rate_per_minute;
            }
            if ($pricePerMinute > 0) {
                $basePrice += $pricePerMinute * $value;
            }
        } else { // kwh
            $pricePerKwh = $pricingPlan->price_per_kwh ?? 0;
            // Pour les anciens modèles qui utilisent base_rate_per_kwh
            if ($pricePerKwh == 0 && isset($pricingPlan->base_rate_per_kwh)) {
                $pricePerKwh = $pricingPlan->base_rate_per_kwh;
            }
            if ($pricePerKwh > 0) {
                $basePrice += $pricePerKwh * $value;
            }
        }

        // Ajouter la TVA si applicable
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $basePrice *= (1 + ($pricingPlan->vatRate->rate / 100));
        } elseif (isset($pricingPlan->vat_rate) && $pricingPlan->vat_rate > 0) {
            // Pour la compatibilité avec l'ancien modèle
            $basePrice *= (1 + ($pricingPlan->vat_rate / 100));
        }

        return round($basePrice, 2);
    }

    /**
     * Calcule un exemple concret de transaction basé sur les données de la borne
     */
    private function calculateRealTransactionExample($chargingPoint)
    {
        // Montant de base pour l'exemple (peut être basé sur le plan tarifaire)
        $baseAmount = 200; // Montant de base pour l'exemple
        
        // Si la borne a un plan tarifaire, utiliser un montant plus réaliste
        if ($chargingPoint->pricingPlan) {
            $baseAmount = $chargingPoint->pricingPlan->price_per_kwh * 20; // 20 kWh d'exemple
            if ($chargingPoint->pricingPlan->activation_fee) {
                $baseAmount += $chargingPoint->pricingPlan->activation_fee;
            }
        }

        $example = [
            'base_amount' => round($baseAmount, 2),
                        'currency' => 'EUR',
            'scenarios' => []
        ];

        // Scénario 1: Borne créée par Opérateur (avec intégrateur)
        if ($chargingPoint->integrator && $chargingPoint->partner) {
            $integratorCommission = 0;
            $adminCommission = 0;
            $partnerCommission = 0;

            // Calculer les commissions basées sur les business profiles
            if ($chargingPoint->integrator->businessProfile) {
                $integratorCommission = $chargingPoint->integrator->businessProfile->integrator_commission ?? 17;
                $adminCommission = $chargingPoint->integrator->businessProfile->admin_fee_percentage ?? 10;
            }

            if ($chargingPoint->partner->businessProfile) {
                $partnerCommission = $chargingPoint->partner->businessProfile->partner_commission ?? 63;
            }

            // Calculer les montants
            $adminAmount = round(($baseAmount * $adminCommission) / 100, 2);
            $integratorAmount = round(($baseAmount * $integratorCommission) / 100, 2);
            $partnerAmount = round($baseAmount - $adminAmount - $integratorAmount, 2);

            $example['scenarios'][] = [
                'title' => 'Borne créée par Opérateur',
                'description' => 'Avec intégrateur et business profiles appliqués',
                'calculations' => [
                    [
                        'label' => 'Montant total de la transaction',
                        'amount' => $baseAmount,
                        'currency' => 'EUR',
                        'type' => 'total'
                    ],
                    [
                        'label' => "Part Admin ({$adminCommission}%)",
                        'amount' => $adminAmount,
                        'currency' => 'EUR',
                        'type' => 'admin'
                    ],
                    [
                        'label' => "Part Intégrateur ({$integratorCommission}%)",
                        'amount' => $integratorAmount,
                        'currency' => 'EUR',
                        'type' => 'integrator'
                    ],
                    [
                        'label' => "Part Opérateur/Partenaire",
                        'amount' => $partnerAmount,
                        'currency' => 'EUR',
                        'type' => 'partner'
                    ]
                ],
                'business_profile_applied' => $chargingPoint->integrator->businessProfile ? $chargingPoint->integrator->businessProfile->name : 'Aucun'
            ];
        }

        // Scénario 2: Borne créée directement par Admin (sans intégrateur)
        if (!$chargingPoint->integrator && $chargingPoint->partner) {
            $adminCommission = 15; // Commission par défaut pour admin direct
            $partnerCommission = 85;

            $adminAmount = round(($baseAmount * $adminCommission) / 100, 2);
            $partnerAmount = round($baseAmount - $adminAmount, 2);

            $example['scenarios'][] = [
                'title' => 'Borne créée directement par Admin',
                'description' => 'Sans intégrateur intermédiaire',
                'calculations' => [
                    [
                        'label' => 'Montant total de la transaction',
                        'amount' => $baseAmount,
                        'currency' => 'EUR',
                        'type' => 'total'
                    ],
                    [
                        'label' => "Part Admin ({$adminCommission}%)",
                        'amount' => $adminAmount,
                        'currency' => 'EUR',
                        'type' => 'admin'
                    ],
                    [
                        'label' => 'Part Intégrateur',
                        'amount' => 0,
                        'currency' => 'EUR',
                        'type' => 'integrator'
                    ],
                    [
                        'label' => "Part Opérateur/Partenaire ({$partnerCommission}%)",
                        'amount' => $partnerAmount,
                        'currency' => 'EUR',
                        'type' => 'partner'
                    ]
                ],
                'business_profile_applied' => 'Profil Admin Direct'
            ];
        }

        // Scénario 3: Borne avec business profile direct
        if ($chargingPoint->businessProfile) {
            $adminCommission = $chargingPoint->businessProfile->admin_fee_percentage ?? 12;
            $integratorCommission = $chargingPoint->businessProfile->integrator_commission ?? 0;
            $partnerCommission = $chargingPoint->businessProfile->partner_commission ?? 88;

            $adminAmount = round(($baseAmount * $adminCommission) / 100, 2);
            $integratorAmount = round(($baseAmount * $integratorCommission) / 100, 2);
            $partnerAmount = round($baseAmount - $adminAmount - $integratorAmount, 2);

            $example['scenarios'][] = [
                'title' => 'Borne avec Business Profile Direct',
                'description' => 'Business profile appliqué directement sur la borne',
                'calculations' => [
                    [
                        'label' => 'Montant total de la transaction',
                        'amount' => $baseAmount,
                        'currency' => 'EUR',
                        'type' => 'total'
                    ],
                    [
                        'label' => "Part Admin ({$adminCommission}%)",
                        'amount' => $adminAmount,
                        'currency' => 'EUR',
                        'type' => 'admin'
                    ],
                    [
                        'label' => "Part Intégrateur ({$integratorCommission}%)",
                        'amount' => $integratorAmount,
                        'currency' => 'EUR',
                        'type' => 'integrator'
                    ],
                    [
                        'label' => "Part Opérateur/Partenaire",
                        'amount' => $partnerAmount,
                        'currency' => 'EUR',
                        'type' => 'partner'
                    ]
                ],
                'business_profile_applied' => $chargingPoint->businessProfile->name
            ];
        }

        // Si aucun scénario n'a été créé, utiliser un exemple par défaut
        if (empty($example['scenarios'])) {
            $example['scenarios'][] = [
                'title' => 'Exemple Générique',
                'description' => 'Calcul basé sur les paramètres par défaut',
                'calculations' => [
                    [
                        'label' => 'Montant total de la transaction',
                        'amount' => $baseAmount,
                        'currency' => 'EUR',
                        'type' => 'total'
                    ],
                    [
                        'label' => 'Part Admin (10%)',
                        'amount' => round($baseAmount * 0.10, 2),
                        'currency' => 'EUR',
                        'type' => 'admin'
                    ],
                    [
                        'label' => 'Part Intégrateur (20%)',
                        'amount' => round($baseAmount * 0.20, 2),
                        'currency' => 'EUR',
                        'type' => 'integrator'
                    ],
                    [
                        'label' => 'Part Opérateur/Partenaire (70%)',
                        'amount' => round($baseAmount * 0.70, 2),
                        'currency' => 'EUR',
                        'type' => 'partner'
                    ]
                ],
                'business_profile_applied' => 'Configuration par défaut'
            ];
        }

        return $example;
    }

    /**
     * Récupère les business profiles appliqués pour une borne de recharge
     */
    public function getBusinessProfiles(Request $request, $id)
    {
        try {
            $chargingPoint = ChargingPoint::with([
                'integrator.businessProfile',
                'partner.businessProfile',
                'businessProfile'
            ])->find($id);

            if (!$chargingPoint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borne de recharge non trouvée'
                ], 404);
            }

            $adminAppliedProfiles = [];
            $integratorAppliedProfiles = [];

            // Helper function to format business profile data
            $formatProfile = function ($profile, $type, $appliedTo, $appliedBy = null, $appliedAt = null) {
                // Extraire les détails des frais
                $feesDetails = [
                    'admin_fees' => [
                        'fixed' => $profile->admin_fee_fixed ?? 0,
                        'percentage' => $profile->admin_fee_percentage ?? 0,
                        'type' => $profile->admin_fee_fixed ? 'fixe' : 'pourcentage'
                    ],
                    'integrator_fees' => [
                        'fixed' => $profile->integrator_fee_fixed ?? 0,
                        'percentage' => $profile->integrator_fee_percentage ?? 0,
                        'type' => $profile->integrator_fee_fixed ? 'fixe' : 'pourcentage'
                    ],
                    'partner_fees' => [
                        'fixed' => $profile->partner_fee_fixed ?? 0,
                        'percentage' => $profile->partner_fee_percentage ?? 0,
                        'type' => $profile->partner_fee_fixed ? 'fixe' : 'pourcentage'
                    ],
                    'commissions' => [
                        'operator' => $profile->operator_commission ?? 0,
                        'integrator' => $profile->integrator_commission ?? 0,
                        'owner' => $profile->owner_commission ?? 0,
                        'partner' => $profile->partner_commission ?? 0
                    ],
                    'maintenance' => [
                        'type' => $profile->maintenance_fee_type ?? 'aucun',
                        'amount' => $profile->maintenance_fee_amount ?? 0
                    ],
                    'transaction_config' => $profile->transaction_fee_config ?? null,
                    'charge_config' => $profile->charge_fee_config ?? null
                ];

                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'type' => $type,
                    'applied_to' => $appliedTo,
                    'applied_by' => $appliedBy,
                    'applied_at' => $appliedAt,
                    'description' => $profile->description ?? '',
                    'is_active' => $profile->is_active,
                    'fees_details' => $feesDetails
                ];
            };

            // Récupérer les business profiles appliqués par l'admin aux intégrateurs
            if ($chargingPoint->integrator && $chargingPoint->integrator->businessProfile) {
                $adminAppliedProfiles[] = $formatProfile(
                    $chargingPoint->integrator->businessProfile,
                    'Admin → Intégrateur',
                    $chargingPoint->integrator->name ?? 'Intégrateur'
                );
            }

            // Récupérer les business profiles appliqués par les intégrateurs aux opérateurs
            // Chercher le business profile créé par l'intégrateur pour ce partenaire
            if ($chargingPoint->partner && $chargingPoint->integrator) {
                // Chercher le profil créé par l'intégrateur pour ce partenaire
                // Utiliser l'ID de l'intégrateur (modèle), pas l'ID de l'utilisateur
                $integratorBP = \App\Models\BusinessProfile::where('created_by_id', $chargingPoint->integrator->id)
                    ->where('created_by_type', 'integrator')
                    ->where('partner_id', $chargingPoint->partner->id)
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                // Si pas trouvé, chercher un profil par défaut de l'intégrateur (sans partner_id spécifique)
                if (!$integratorBP) {
                    $integratorBP = \App\Models\BusinessProfile::where('created_by_id', $chargingPoint->integrator->id)
                        ->where('created_by_type', 'integrator')
                        ->where('is_active', true)
                        ->orderBy('created_at', 'desc')
                        ->first();
                }
                
                // Fallback: utiliser le business profile du partenaire si aucun profil intégrateur trouvé
                if (!$integratorBP && $chargingPoint->partner->businessProfile) {
                    $integratorBP = $chargingPoint->partner->businessProfile;
                }
                
                if ($integratorBP) {
                    $integratorAppliedProfiles[] = $formatProfile(
                        $integratorBP,
                        'Intégrateur → Opérateur',
                        $chargingPoint->partner->name ?? 'Opérateur'
                    );
                }
            }

            // Récupérer les business profiles directement appliqués à la borne
            if ($chargingPoint->businessProfile) {
                $integratorAppliedProfiles[] = $formatProfile(
                    $chargingPoint->businessProfile,
                    'Directement sur la borne',
                    $chargingPoint->name
                );
            }

            // Récupérer les business profiles appliqués via la table business_profile_applications
            // Note: Cette fonctionnalité sera ajoutée plus tard quand le système polymorphique sera implémenté
            // foreach ($chargingPoint->businessProfileApplications as $application) {
            //     $integratorAppliedProfiles[] = $formatProfile(
            //         $application->businessProfile,
            //         'Application via intégrateur',
            //         $application->operator->name ?? 'Opérateur inconnu',
            //         $application->integrator->name ?? 'Intégrateur inconnu',
            //         $application->applied_at
            //     );
            // }

            // Calculer les exemples concrets basés sur les données de la borne
            $realCalculationExample = $this->calculateRealTransactionExample($chargingPoint);

            return response()->json([
                'success' => true,
                'data' => [
                    'admin_applied_profiles' => $adminAppliedProfiles,
                    'integrator_applied_profiles' => $integratorAppliedProfiles,
                    'real_calculation_example' => $realCalculationExample
                ],
                'message' => 'Business profiles appliqués récupérés avec succès'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Borne de recharge non trouvée'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des business profiles appliqués', [
                'charging_point_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des business profiles appliqués',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche la page de remerciement après une réservation
     */
    public function thankYou(Reservation $reservation)
    {
        try {
            // Corriger le statut si payée par solde mais encore "pending" (sync)
            $reservation->ensureTransactionConfirmedIfPaid();
            $reservation->refresh();

            // Vérifier que la réservation existe et est confirmée (ou payée par solde = considérée confirmée)
            if (!$reservation || (!$reservation->confirmed_at && !$reservation->isPaidByBalance())) {
                return redirect()->route('charging-points.index')
                    ->with('error', 'Réservation non trouvée ou non confirmée.');
            }

            // Générer le QR code pour la borne
            $qrCode = null;
            if ($reservation->chargingPoint) {
                $qrCode = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . 
                         urlencode(route('public.charging-point.offer.view.public', $reservation->chargingPoint->id));
            }

            return view('charging-points.thank-you', [
                'reservation' => $reservation,
                'qrCode' => $qrCode
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la page de remerciement', [
                'reservation_id' => $reservation->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('charging-points.index')
                ->with('error', 'Erreur lors de l\'affichage de la page de remerciement.');
        }
    }

    /**
     * Build default data for step 2 to prevent blocking the flow.
     */
    private function buildDefaultStep2Data(array $step1Data = []): array
    {
        $manufacturer = $step1Data['manufacturer'] ?? 'Non specifie';
        $model = $step1Data['model'] ?? 'Non specifie';

        return [
            'manufacturer' => $manufacturer,
            'model' => $model,
            'power_output' => 0,
            'connector_type' => 'type2',
            'installation_date' => null,
            'pricing_plan_id' => null,
            'connection_type' => 'ethernet',
            'communication_protocol' => 'ocpp16',
            'accessibility' => 'public',
            'access_type' => 'public',
            'authentication_required' => false,
        ];
    }

    /**
     * Build default data for step 3 to prevent blocking the flow.
     */
    private function buildDefaultStep3Data(array $step2Data = []): array
    {
        $accessType = $step2Data['access_type']
            ?? $step2Data['accessibility']
            ?? 'public';

        $connectionType = $step2Data['connection_type'] ?? 'ethernet';
        $protocol = $step2Data['communication_protocol'] ?? 'ocpp16';

        return [
            'connection_type' => $connectionType,
            'ip_address' => $step2Data['ip_address'] ?? null,
            'communication_protocol' => $protocol,
            'firmware_version' => $step2Data['firmware_version'] ?? null,
            'mac_address' => $step2Data['mac_address'] ?? null,
            'server_url' => $step2Data['server_url'] ?? null,
            'access_type' => $accessType,
            'public_access' => $accessType === 'public',
            'access_code' => $step2Data['access_code'] ?? null,
            'authentication_required' => (bool)($step2Data['authentication_required'] ?? false),
        ];
    }

    /**
     * Determine if a step data array is missing or empty.
     */
    private function isStepDataMissing($data): bool
    {
        return is_null($data) || !is_array($data) || empty($data);
    }

    /**
     * Restaure les données step2 depuis le cache si manquantes en session.
     * Contourne les problèmes de persistance de session (load balancer, etc.).
     */
    private function restoreStep2FromCacheIfMissing(): void
    {
        $step2Data = Session::get('charging_point_step2');
        if (!$this->isStepDataMissing($step2Data)) {
            return;
        }
        $sessionId = Session::getId();
        $cachedStep2 = Cache::get("charging_point_step2_{$sessionId}");
        if (!$this->isStepDataMissing($cachedStep2)) {
            Session::put('charging_point_step2', $cachedStep2);
            Session::save();
            Log::info('Charging point step2 restored from cache fallback', ['session_id' => $sessionId]);
        }
    }

    /**
     * Nettoyer les sessions invalides (groupes inexistants ou inactifs)
     */
    private function cleanInvalidSessions()
    {
        $step1Data = Session::get('charging_point_step1');
        
        if ($step1Data && isset($step1Data['group_id'])) {
            $group = Group::find($step1Data['group_id']);
            
            // Si le groupe n'existe pas ou n'est pas actif, nettoyer la session
            if (!$group) {
                \Log::info('CLEAN SESSION - Group not found, clearing session', ['group_id' => $step1Data['group_id']]);
                Session::forget(['charging_point_step1', 'charging_point_step2', 'charging_point_step3']);
                return;
            }
            
            // Vérifier si le groupe est actif
            $status = strtolower((string)($group->status ?? ''));
            $isInactiveByStatus = in_array($status, ['inactive', 'disabled', 'archived'], true);
            $isInactiveByFlag = isset($group->is_active) && !is_null($group->is_active) && !(bool)$group->is_active;

            if ($isInactiveByStatus || $isInactiveByFlag) {
                \Log::info('CLEAN SESSION - Group is not active, clearing session', ['group_id' => $step1Data['group_id']]);
                Session::forget(['charging_point_step1', 'charging_point_step2', 'charging_point_step3']);
            }
        }
    }

    /**
     * Synchroniser les points de charge depuis l'API Steve
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function syncFromSteve(Request $request)
    {
        try {
            // Vérifier les permissions
            $user = auth()->user();
            if (!$user->can('create', ChargingPoint::class) && !$user->hasRole(['admin', 'super_admin'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas autorisé à synchroniser les points de charge.'
                    ], 403);
                }
                return redirect()->route('charging-points.index')
                    ->with('error', 'Vous n\'êtes pas autorisé à synchroniser les points de charge.');
            }

            // Exécuter la commande de synchronisation
            $steveService = app(\App\Services\SteveService::class);
            $chargePoints = $steveService->getChargePoints();

            if (!$chargePoints || !is_array($chargePoints)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Échec de la récupération des points de charge depuis l\'API Steve.'
                    ], 500);
                }
                return redirect()->route('charging-points.index')
                    ->with('error', 'Échec de la récupération des points de charge depuis l\'API Steve.');
            }

            $created = 0;
            $updated = 0;
            $errors = 0;

            DB::beginTransaction();
            try {
                foreach ($chargePoints as $cp) {
                    try {
                        $data = [
                            'steve_charging_point_id' => $cp['id'] ?? $cp['chargePointId'] ?? null,
                            'name' => $cp['chargeBoxId'] ?? $cp['name'] ?? null,
                            'ip_address' => $cp['endpointAddress'] ?? $cp['ipAddress'] ?? null,
                            'status' => $this->mapStatus($cp['status'] ?? 'Unknown'),
                            'notes' => $cp['notes'] ?? null,
                        ];

                        if (empty($data['steve_charging_point_id'])) {
                            $errors++;
                            continue;
                        }

                        $chargingPoint = ChargingPoint::updateOrCreate(
                            ['steve_charging_point_id' => $data['steve_charging_point_id']],
                            $data
                        );

                        if ($chargingPoint->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::error('Erreur lors de la synchronisation d\'un point de charge', [
                            'charge_point_data' => $cp,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Erreur lors de la synchronisation: ' . $e->getMessage());
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
                    ], 500);
                }
                return redirect()->route('charging-points.index')
                    ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
            }

            $message = "Synchronisation terminée ! Créés: {$created}, Mis à jour: {$updated}, Erreurs: {$errors}";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'created' => $created,
                        'updated' => $updated,
                        'errors' => $errors,
                        'total' => count($chargePoints)
                    ]
                ]);
            }

            return redirect()->route('charging-points.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation depuis Steve', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('charging-points.index')
                ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }

    /**
     * Convertir les statuts Steve vers notre système local.
     */
    private function mapStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'available', 'charging', 'occupied', 'ready', 'online' => 'online',
            'unavailable', 'faulted', 'disconnected', 'offline' => 'offline',
            'maintenance' => 'maintenance',
            'error', 'faulted' => 'error',
            'reserved' => 'reserved',
            default => 'unknown',
        };
    }

    /**
     * Créer un point de charge directement sur l'API Steve
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function createOnSteve(Request $request)
    {
        try {
            // Vérifier les permissions
            $user = auth()->user();
            if (!$user->can('create', ChargingPoint::class) && !$user->hasRole(['admin', 'super_admin'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas autorisé à créer des points de charge sur Steve.'
                    ], 403);
                }
                return redirect()->route('charging-points.index')
                    ->with('error', 'Vous n\'êtes pas autorisé à créer des points de charge sur Steve.');
            }

            // Valider les données
            $validated = $request->validate([
                'chargeBoxId' => 'required|string|max:255',
                'endpointAddress' => 'required|string|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            // Préparer les données pour l'API Steve
            $steveData = [
                'chargeBoxId' => $validated['chargeBoxId'],
                'endpointAddress' => $validated['endpointAddress'],
            ];

            if (!empty($validated['notes'])) {
                $steveData['notes'] = $validated['notes'];
            }

            // Créer sur l'API Steve
            $steveService = app(\App\Services\SteveService::class);
            $steveResponse = $steveService->createChargePoint($steveData);

            if (!$steveResponse) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Échec de la création du point de charge sur l\'API Steve.'
                    ], 500);
                }
                return redirect()->route('charging-points.index')
                    ->with('error', 'Échec de la création du point de charge sur l\'API Steve.');
            }

            // Optionnel : Créer aussi dans la base de données locale
            $createInLocalDb = $request->boolean('create_in_local_db', false);
            
            if ($createInLocalDb) {
                try {
                    $chargingPoint = ChargingPoint::create([
                        'steve_charging_point_id' => $steveResponse['id'] ?? $steveResponse['chargePointId'] ?? null,
                        'name' => $steveResponse['chargeBoxId'] ?? $validated['chargeBoxId'],
                        'ip_address' => $steveResponse['endpointAddress'] ?? $validated['endpointAddress'],
                        'status' => $this->mapStatus($steveResponse['status'] ?? 'Unknown'),
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    Log::info('Point de charge créé dans la base de données locale après création sur Steve', [
                        'charging_point_id' => $chargingPoint->id,
                        'steve_id' => $chargingPoint->steve_charging_point_id
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Échec de la création dans la base de données locale', [
                        'error' => $e->getMessage(),
                        'steve_response' => $steveResponse
                    ]);
                    // Ne pas échouer si la création locale échoue, car Steve a réussi
                }
            }

            $message = 'Point de charge créé avec succès sur l\'API Steve !';
            if ($createInLocalDb) {
                $message .= ' Le point a également été ajouté à votre base de données locale.';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'steve_response' => $steveResponse,
                        'created_in_local_db' => $createInLocalDb
                    ]
                ]);
            }

            return redirect()->route('charging-points.index')
                ->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création sur Steve', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('charging-points.index')
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }
}
