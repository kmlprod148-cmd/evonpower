<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HierarchicalPermissionMiddleware
{
    /**
     * Handle an incoming request with strict hierarchical permissions
     * 
     * Admin: Full control over everything
     * Integrator: Only their own resources and their operators' resources
     * Operator: Only their own resources
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // ADMIN: Full control over everything
        if ($user->hasRole(['admin', 'super_admin'])) {
            Log::info('Admin access granted', [
                'user_id' => $user->id,
                'role' => $user->role,
                'permissions' => $permissions,
                'route' => $request->route()->getName()
            ]);
            return $next($request);
        }

        // INTEGRATOR: Strict isolation - only their own resources
        if ($user->hasRole('integrator')) {
            return $this->handleIntegratorAccess($request, $next, $user, $permissions);
        }

        // OPERATOR: Only their own resources
        if ($user->hasRole('operator')) {
            return $this->handleOperatorAccess($request, $next, $user, $permissions);
        }

        // PARTNER: Limited access to their partner resources
        if ($user->hasRole('partner')) {
            return $this->handlePartnerAccess($request, $next, $user, $permissions);
        }

        // USER: Basic access to public resources
        if ($user->hasRole('user')) {
            return $this->handleUserAccess($request, $next, $user, $permissions);
        }

        // Default deny
        Log::warning('Access denied - Unknown role', [
            'user_id' => $user->id,
            'role' => $user->role,
            'route' => $request->route()->getName()
        ]);

        return $this->denyAccess($request, 'Rôle non reconnu');
    }

    /**
     * Handle integrator access with strict isolation
     */
    private function handleIntegratorAccess(Request $request, Closure $next, $user, array $permissions): Response
    {
        $route = $request->route();
        $routeName = $route->getName();
        $resourceId = $this->extractResourceId($request);

        // Check if integrator is trying to access resources outside their scope
        if (!$this->isIntegratorAuthorized($user, $request, $resourceId)) {
            Log::warning('Integrator access denied - Outside scope', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id,
                'route' => $routeName,
                'resource_id' => $resourceId
            ]);
            
            return $this->denyAccess($request, 'Accès refusé. Vous ne pouvez accéder qu\'à vos propres ressources.');
        }

        // Verify specific permissions for integrators
        if (!$this->hasIntegratorPermissions($user, $permissions)) {
            Log::warning('Integrator permission denied', [
                'user_id' => $user->id,
                'permissions' => $permissions,
                'route' => $routeName
            ]);
            
            return $this->denyAccess($request, 'Permissions insuffisantes pour cette action.');
        }

        Log::info('Integrator access granted', [
            'user_id' => $user->id,
            'integrator_id' => $user->integrator_id,
            'route' => $routeName,
            'resource_id' => $resourceId
        ]);

        return $next($request);
    }

    /**
     * Handle operator access
     */
    private function handleOperatorAccess(Request $request, Closure $next, $user, array $permissions): Response
    {
        $route = $request->route();
        $routeName = $route->getName();
        $resourceId = $this->extractResourceId($request);

        // Check if operator is trying to access resources outside their scope
        if (!$this->isOperatorAuthorized($user, $request, $resourceId)) {
            Log::warning('Operator access denied - Outside scope', [
                'user_id' => $user->id,
                'route' => $routeName,
                'resource_id' => $resourceId
            ]);
            
            return $this->denyAccess($request, 'Accès refusé. Vous ne pouvez accéder qu\'à vos propres ressources.');
        }

        // Verify specific permissions for operators
        if (!$this->hasOperatorPermissions($user, $permissions)) {
            Log::warning('Operator permission denied', [
                'user_id' => $user->id,
                'permissions' => $permissions,
                'route' => $routeName
            ]);
            
            return $this->denyAccess($request, 'Permissions insuffisantes pour cette action.');
        }

        Log::info('Operator access granted', [
            'user_id' => $user->id,
            'route' => $routeName,
            'resource_id' => $resourceId
        ]);

        return $next($request);
    }

    /**
     * Handle partner access
     */
    private function handlePartnerAccess(Request $request, Closure $next, $user, array $permissions): Response
    {
        $route = $request->route();
        $routeName = $route->getName();
        $resourceId = $this->extractResourceId($request);

        // Check if partner is trying to access resources outside their scope
        if (!$this->isPartnerAuthorized($user, $request, $resourceId)) {
            Log::warning('Partner access denied - Outside scope', [
                'user_id' => $user->id,
                'partner_id' => $user->partner_id,
                'route' => $routeName,
                'resource_id' => $resourceId
            ]);
            
            return $this->denyAccess($request, 'Accès refusé. Vous ne pouvez accéder qu\'aux ressources de votre partenaire.');
        }

        Log::info('Partner access granted', [
            'user_id' => $user->id,
            'partner_id' => $user->partner_id,
            'route' => $routeName,
            'resource_id' => $resourceId
        ]);

        return $next($request);
    }

    /**
     * Handle user access (regular end users)
     * Simple clients (role 'user' only) can ONLY access:
     * - Their own reservations (view only)
     * - Credit balance management (credit-recharge)
     * - Profile settings
     * All other routes are denied
     */
    private function handleUserAccess(Request $request, Closure $next, $user, array $permissions): Response
    {
        $route = $request->route();
        $routeName = $route->getName();
        $resourceId = $this->extractResourceId($request);

        // Check if user has only 'user' role (simple client)
        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isClientOnly = count($userRoles) === 1 && in_array('user', $userRoles);
        
        if ($isClientOnly) {
            // For simple clients, only allow specific routes
            $allowedRoutes = [
                'reservations.index',
                'reservations.show',
                'credit-recharge.index',
                'credit-recharge.store',
                'credit-recharge.show',
                'credit-recharge.cancel',
                'credits.balance.api',
                'profile.edit',
                'profile.update',
                'logout',
                'dashboard', // Allow dashboard but it will redirect to reservations
            ];
            
            // Check if route is in allowed list
            $isAllowed = false;
            if ($routeName) {
                foreach ($allowedRoutes as $allowedRoute) {
                    if (strpos($routeName, $allowedRoute) !== false || $routeName === $allowedRoute) {
                        $isAllowed = true;
                        break;
                    }
                }
            }
            
            // Also allow CMI and Stripe callback routes for credit recharge
            if (!$isAllowed && $routeName) {
                if (strpos($routeName, 'credit-recharge.cmi') !== false || 
                    strpos($routeName, 'credit-recharge.stripe') !== false) {
                    $isAllowed = true;
                }
            }
            
            if (!$isAllowed) {
                Log::warning('Simple client attempted to access unauthorized route', [
                    'user_id' => $user->id,
                    'route' => $routeName,
                    'url' => $request->fullUrl()
                ]);
                
                // Redirect to reservations page with error message
                return redirect()->route('reservations.index')
                    ->with('error', 'Accès refusé. Vous n\'avez accès qu\'à vos réservations et à la gestion de votre solde de crédit.');
            }
            
            // Additional check for reservations - must be their own
            if (strpos($routeName, 'reservations') !== false && $resourceId) {
                $reservation = \App\Models\Reservation::find($resourceId);
                if ($reservation && $reservation->user_id !== $user->id) {
                    Log::warning('Client attempted to access another user\'s reservation', [
                        'user_id' => $user->id,
                        'reservation_id' => $resourceId,
                        'reservation_user_id' => $reservation->user_id
                    ]);
                    
                    return redirect()->route('reservations.index')
                        ->with('error', 'Accès refusé. Vous ne pouvez accéder qu\'à vos propres réservations.');
                }
            }
            
            // Deny access to any permission-protected routes for simple clients
            if (!empty($permissions)) {
                Log::warning('Client-only user attempted to access permission-protected route', [
                    'user_id' => $user->id,
                    'route' => $routeName,
                    'permissions' => $permissions
                ]);
                
                return redirect()->route('reservations.index')
                    ->with('error', 'Accès refusé. Cette fonctionnalité n\'est pas disponible pour les clients.');
            }
        }

        // Check if user is authorized to access the resource
        if (!$this->isUserAuthorized($user, $request, $resourceId)) {
            Log::warning('User access denied - Outside scope', [
                'user_id' => $user->id,
                'route' => $routeName,
                'resource_id' => $resourceId
            ]);

            return $this->denyAccess($request, 'Accès refusé. Vous n\'avez pas accès à cette ressource.');
        }

        // Verify specific permissions for users (only for non-client-only users)
        if (!$isClientOnly && !empty($permissions) && !$this->hasUserPermissions($user, $permissions)) {
            Log::warning('User permission denied', [
                'user_id' => $user->id,
                'permissions' => $permissions,
                'route' => $routeName
            ]);

            return $this->denyAccess($request, 'Permissions insuffisantes pour cette action.');
        }

        Log::info('User access granted', [
            'user_id' => $user->id,
            'route' => $routeName,
            'resource_id' => $resourceId,
            'is_client_only' => $isClientOnly
        ]);

        return $next($request);
    }

    /**
     * Check if integrator is authorized to access the resource
     */
    private function isIntegratorAuthorized($user, Request $request, $resourceId): bool
    {
        $routeName = $request->route()->getName();

        // Charging Points - only their own and their operators'
        if (strpos($routeName, 'charging-points') !== false) {
            if ($resourceId) {
                $chargingPoint = \App\Models\ChargingPoint::find($resourceId);
                if ($chargingPoint) {
                    // Can access if it belongs to their integrator or their operators
                    return $chargingPoint->integrator_id === $user->integrator_id ||
                           ($chargingPoint->user && $chargingPoint->user->integrator_id === $user->integrator_id);
                }
            }
            return true; // For listing, filtering will be applied in the controller
        }

        // Business Profiles - only their own
        if (strpos($routeName, 'business-profile') !== false) {
            if ($resourceId) {
                $businessProfile = \App\Models\BusinessProfile::find($resourceId);
                if ($businessProfile) {
                    return $businessProfile->user_id === $user->id ||
                           $businessProfile->integrator_id === $user->integrator_id;
                }
            }
            return true;
        }

        // Pricing Plans - only their own
        if (strpos($routeName, 'pricing-plan') !== false) {
            if ($resourceId) {
                $pricingPlan = \App\Models\PricingPlan::find($resourceId);
                if ($pricingPlan) {
                    return $pricingPlan->user_id === $user->id ||
                           $pricingPlan->integrator_id === $user->integrator_id;
                }
            }
            return true;
        }

        // Reservations - their own and their operators'/partners'
        if (strpos($routeName, 'reservation') !== false) {
            if ($resourceId) {
                $reservation = \App\Models\Reservation::with(['user', 'chargingPoint'])->find($resourceId);
                if ($reservation) {
                    // Access if: own reservation, operator's reservation, or reservation from their charging points
                    if ($reservation->user_id === $user->id) {
                        return true;
                    }
                    // Check if reservation belongs to an operator of this integrator
                    if ($reservation->user && $reservation->user->integrator_id === $user->integrator_id) {
                        return true;
                    }
                    // Check if reservation is for a charging point owned by integrator's operators/partners
                    if ($reservation->chargingPoint) {
                        $cp = $reservation->chargingPoint;
                        // Check via integrator_id directly
                        if ($cp->integrator_id === $user->integrator_id) {
                            return true;
                        }
                        // Check via group -> partner -> integrator
                        if ($cp->group && $cp->group->partner && $cp->group->partner->integrator_id === $user->integrator_id) {
                            return true;
                        }
                        // Check via user (operator) -> integrator
                        if ($cp->user && $cp->user->integrator_id === $user->integrator_id) {
                            return true;
                        }
                    }
                    return false;
                }
            }
            return true; // Allow listing, controller will filter
        }

        // Users/Operators - their operators and partners
        if (strpos($routeName, 'user') !== false || strpos($routeName, 'operator') !== false) {
            if ($resourceId) {
                $targetUser = \App\Models\User::find($resourceId);
                if ($targetUser) {
                    // Can manage operators under their integrator
                    if ($targetUser->hasRole('operator') && $targetUser->integrator_id === $user->integrator_id) {
                        return true;
                    }
                    // Can also manage partners if they belong to this integrator
                    if ($targetUser->hasRole('partner')) {
                        $partner = \App\Models\Partner::where('user_id', $targetUser->id)->first();
                        if ($partner && $partner->integrator_id === $user->integrator_id) {
                            return true;
                        }
                    }
                    return false;
                }
            }
            return true; // Allow listing, controller will filter
        }

        // Partners - only their own partners
        if (strpos($routeName, 'partners') !== false) {
            if ($resourceId) {
                $partner = \App\Models\Partner::find($resourceId);
                if ($partner) {
                    return $partner->integrator_id === $user->integrator_id;
                }
            }
            return true; // For listing, filtering will be applied in the controller
        }

        // Transactions - their own and their operators'/partners'
        if (strpos($routeName, 'transaction') !== false) {
            if ($resourceId) {
                // Try to find transaction by different models
                $transaction = \App\Models\Transaction::with(['user', 'chargingPoint', 'transactionDetail'])->find($resourceId);
                if ($transaction) {
                    // Check via charging point integrator
                    if ($transaction->chargingPoint && $transaction->chargingPoint->integrator_id === $user->integrator_id) {
                        return true;
                    }
                    // Check via user (operator) integrator
                    if ($transaction->user && $transaction->user->integrator_id === $user->integrator_id) {
                        return true;
                    }
                    // Check via transaction detail integrator_creator_id
                    if ($transaction->transactionDetail && $transaction->transactionDetail->integrator_creator_id === $user->id) {
                        return true;
                    }
                    // Check via charging point -> group -> partner -> integrator
                    if ($transaction->chargingPoint && $transaction->chargingPoint->group) {
                        $group = $transaction->chargingPoint->group;
                        if ($group->partner && $group->partner->integrator_id === $user->integrator_id) {
                            return true;
                        }
                        if ($group->user && $group->user->integrator_id === $user->integrator_id) {
                            return true;
                        }
                    }
                    return false;
                }
                // Try WalletTransaction
                $walletTransaction = \App\Models\WalletTransaction::with('wallet.owner')->find($resourceId);
                if ($walletTransaction && $walletTransaction->wallet) {
                    $owner = $walletTransaction->wallet->owner;
                    if ($owner instanceof \App\Models\User && $owner->integrator_id === $user->integrator_id) {
                        return true;
                    }
                    if ($owner instanceof \App\Models\Partner && $owner->integrator_id === $user->integrator_id) {
                        return true;
                    }
                    if ($owner instanceof \App\Models\Integrator && $owner->id === $user->integrator_id) {
                        return true;
                    }
                }
            }
            return true; // Allow listing, controller will filter
        }

        // Integrators - integrators cannot access integrator management routes (create/edit/delete other integrators)
        if (strpos($routeName, 'integrators') !== false) {
            // Allow viewing own integrator profile, but not managing other integrators
            if ($request->isMethod('GET') && $resourceId) {
                $integrator = \App\Models\Integrator::find($resourceId);
                if ($integrator && $integrator->user_id === $user->id) {
                    return true; // Can view own integrator profile
                }
            }
            // Deny create, edit, delete of integrators (only admin can do this)
            return $request->isMethod('GET'); // Only allow GET requests for viewing
        }

        // Default allow for general routes (dashboard, etc.)
        return true;
    }

    /**
     * Check if operator is authorized to access the resource
     */
    private function isOperatorAuthorized($user, Request $request, $resourceId): bool
    {
        $routeName = $request->route()->getName();

        // Charging Points - only their own
        if (strpos($routeName, 'charging-points') !== false) {
            if ($resourceId) {
                $chargingPoint = \App\Models\ChargingPoint::find($resourceId);
                if ($chargingPoint) {
                    return $chargingPoint->user_id === $user->id;
                }
            }
            return true; // For listing, filtering will be applied
        }

        // Reservations - only their own
        if (strpos($routeName, 'reservation') !== false) {
            if ($resourceId) {
                $reservation = \App\Models\Reservation::find($resourceId);
                if ($reservation) {
                    return $reservation->user_id === $user->id;
                }
            }
            return true;
        }

        // Business Profiles - read-only access to their inherited profile
        if (strpos($routeName, 'business-profile') !== false) {
            return $request->isMethod('GET'); // Only read access
        }

        // Pricing Plans - read-only access
        if (strpos($routeName, 'pricing-plan') !== false) {
            return $request->isMethod('GET'); // Only read access
        }

        // Users - cannot manage other users
        if (strpos($routeName, 'user') !== false && $resourceId && $resourceId != $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Check if partner is authorized to access the resource
     */
    private function isPartnerAuthorized($user, Request $request, $resourceId): bool
    {
        $routeName = $request->route()->getName();

        // Charging Points - only those associated with their partner
        if (strpos($routeName, 'charging-points') !== false) {
            if ($resourceId) {
                $chargingPoint = \App\Models\ChargingPoint::find($resourceId);
                if ($chargingPoint) {
                    return $chargingPoint->partner_id === $user->partner_id;
                }
            }
            return true;
        }

        // Reservations - only their own
        if (strpos($routeName, 'reservation') !== false) {
            if ($resourceId) {
                $reservation = \App\Models\Reservation::find($resourceId);
                if ($reservation) {
                    return $reservation->user_id === $user->id;
                }
            }
            return true;
        }

        return true;
    }

    /**
     * Check if user is authorized to access the resource
     */
    private function isUserAuthorized($user, Request $request, $resourceId): bool
    {
        $routeName = $request->route()->getName();

        // Users can access public charging points and offers
        if (strpos($routeName, 'charging-points') !== false && $request->isMethod('GET')) {
            return true; // Public access to view charging points
        }

        // Users can view public pricing plans
        if (strpos($routeName, 'pricing-plans') !== false && $request->isMethod('GET')) {
            return true;
        }

        // Users can create and manage their own reservations
        if (strpos($routeName, 'reservations') !== false) {
            if ($resourceId) {
                $reservation = \App\Models\Reservation::find($resourceId);
                if ($reservation) {
                    return $reservation->user_id === $user->id;
                }
            }
            // Allow creating new reservations and viewing own reservations
            return $request->isMethod('GET') || $request->isMethod('POST');
        }

        // Users can access their own transactions (read-only)
        if (strpos($routeName, 'transactions') !== false && $request->isMethod('GET')) {
            return true; // Controller will filter to user's own transactions
        }

        // Users can access their own profile
        if (strpos($routeName, 'profile') !== false) {
            return true;
        }

        // Users can access wallet recharge and credit management
        if (strpos($routeName, 'credit-recharge') !== false || 
            strpos($routeName, 'credits.balance') !== false ||
            strpos($routeName, 'wallet') !== false) {
            return true; // Allow access to wallet and recharge functionality
        }

        // Users can access dashboard and public offers
        if (strpos($routeName, 'dashboard') !== false || strpos($routeName, 'offer') !== false) {
            return true;
        }

        // Deny access to management routes
        if (strpos($routeName, 'integrators') !== false ||
            strpos($routeName, 'partners') !== false ||
            strpos($routeName, 'operators') !== false ||
            strpos($routeName, 'admin') !== false ||
            strpos($routeName, 'business-profiles') !== false) {
            return false;
        }

        // Default deny for unknown routes
        return false;
    }

    /**
     * Check integrator permissions
     */
    private function hasIntegratorPermissions($user, array $permissions): bool
    {
        // If no permissions required, allow access
        if (empty($permissions)) {
            return true;
        }

        $allowedPermissions = [
            // Charging Points
            'view-charging-points',
            'view_charging_points',
            'view_integrator_charging_points',
            'create-charging-points',
            'create_charging_points',
            'edit-charging-points',
            'edit_charging_points',
            'delete-charging-points',
            'delete_charging_points',
            'show-charging-points',
            'show_charging_points',
            'manage-charging-points',
            'manage_charging_points',
            
            // Business Profiles
            'view-business-profiles',
            'view_business_profiles',
            'create-business-profiles',
            'create_business_profiles',
            'edit-business-profiles',
            'edit_business_profiles',
            'delete-business-profiles',
            'delete_business_profiles',
            'show-business-profiles',
            'show_business_profiles',
            'manage-business-profiles',
            'manage_business_profiles',
            
            // Pricing Plans
            'view-pricing-plans',
            'view_pricing_plans',
            'create-pricing-plans',
            'create_pricing_plans',
            'edit-pricing-plans',
            'edit_pricing_plans',
            'delete-pricing-plans',
            'delete_pricing_plans',
            'manage-pricing-plans',
            'manage_pricing_plans',
            
            // Reservations
            'view-reservations',
            'view_reservations',
            'create-reservations',
            'create_reservations',
            'edit-reservations',
            'edit_reservations',
            'delete-reservations',
            'delete_reservations',
            'manage-reservations',
            'manage_reservations',
            
            // Operators/Users
            'view-operators',
            'view_users',
            'view-integrator-operators',
            'create-operators',
            'create_users',
            'edit-operators',
            'edit_users',
            'delete-operators',
            'delete_users',
            'activate-operators',
            'activate_users',
            'deactivate-operators',
            'deactivate_users',
            'show-operators',
            'show_users',
            'manage-operators',
            'manage_users',
            
            // Partners
            'view-partners',
            'view_partners',
            'view-integrator-partners',
            'create-partners',
            'create_partners',
            'edit-partners',
            'edit_partners',
            'delete-partners',
            'delete_partners',
            'activate-partners',
            'activate_partners',
            'deactivate-partners',
            'deactivate_partners',
            'show-partners',
            'show_partners',
            'manage-partners',
            'manage_partners',
            
            // Transactions
            'view-transactions',
            'view_transactions',
            'view-integrator-transactions',
            'view_integrator_transactions',
            'show-transactions',
            'show_transactions',
            'export-transactions',
            'export_transactions',
            'manage-transactions',
            'manage_transactions',
            'manage-financials',
            'manage_financials',
            
            // Groups
            'view-groups',
            'view_groups',
            'view-integrator-groups',
            'view_integrator_groups',
            'create-groups',
            'create_groups',
            'edit-groups',
            'edit_groups',
            'delete-groups',
            'delete_groups',
            'show-groups',
            'show_groups',
            'manage-groups',
            'manage_groups',
            
            // Dashboard & Reports
            'view-dashboard',
            'view_dashboard',
            'access-dashboard',
            'access_dashboard',
            'view-reports',
            'view_reports',
            'view-statistics',
            'view_statistics',
            'view-analytics',
            'view_analytics',
            'export-data',
            'export_data',
            'export-reports',
            'export_reports',
            'manage-reports',
            'manage_reports',
            
            // Settings
            'view-settings',
            'view_settings',
            'edit-settings',
            'edit_settings',
            'manage-settings',
            'manage_settings',
            
            // Wallet
            'view-wallet',
            'view_wallet',
            'manage-wallet',
            'manage_wallet',
            
            // Remote Control
            'manage-remote-control',
            'manage_remote_control',
        ];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $allowedPermissions)) {
                Log::warning('Integrator permission denied', [
                    'user_id' => $user->id,
                    'permission' => $permission,
                    'allowed_permissions_count' => count($allowedPermissions)
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Check operator permissions
     */
    private function hasOperatorPermissions($user, array $permissions): bool
    {
        $allowedPermissions = [
            'view-charging-points',
            'create-charging-points',
            'edit-charging-points',
            'view-business-profiles', // Read-only
            'view-pricing-plans', // Read-only
            'view-reservations',
            'create-reservations',
            'view-dashboard'
        ];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $allowedPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check user permissions
     */
    private function hasUserPermissions($user, array $permissions): bool
    {
        $allowedPermissions = [
            'view_user_dashboard',
            'view_public_charging_points',
            'view_charging_points_offer',
            'select_pricing_plan',
            'start_charging_session',
            'view_active_charges',
            'stop_charging_session',
            'view_charge_history',
            'view_charge_details',
            'generate_receipt',
            'calculate_price',
            'get_plan_for_charging_point',
            'get_public_offers',
            'view_profile',
            'update_profile',
            'manage_notification_preferences',
            'view_pricing_plans',
            'view_transactions',
            'view_connectors',
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'generate_report_pdf',
            'export_report_csv',
            'view_stations',
            'view_offers',
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'cancel_reservations'
        ];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $allowedPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract resource ID from request
     */
    private function extractResourceId(Request $request): ?int
    {
        $route = $request->route();
        
        // Try common parameter names
        $paramNames = ['id', 'chargingPoint', 'user', 'reservation', 'businessProfile', 'pricingPlan', 'partner'];
        
        foreach ($paramNames as $paramName) {
            if ($route->hasParameter($paramName)) {
                return (int) $route->parameter($paramName);
            }
        }

        return null;
    }

    /**
     * Deny access with appropriate response
     */
    private function denyAccess(Request $request, string $message = 'Accès refusé'): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 403);
        }

        abort(403, $message);
    }
}
