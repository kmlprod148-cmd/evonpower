<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Services\Authorization\PermissionService;

class PermissionRedirectService
{
    /**
     * Resource to permission mapping
     */
    protected array $resourcePermissions = [
        'charging-points' => [
            'view' => ['view_charging_points', 'view_integrator_charging_points', 'view_own_charging_points'],
            'create' => ['create_charging_points'],
            'edit' => ['edit_charging_points'],
            'delete' => ['delete_charging_points']
        ],
        'groups' => [
            'view' => ['view_groups', 'view_all_groups', 'view_integrator_groups', 'view_own_groups'],
            'create' => ['create_groups'],
            'edit' => ['edit_groups'],
            'delete' => ['delete_groups']
        ],
        'partners' => [
            'view' => ['view_partners', 'view_integrator_partners', 'view_own_partners'],
            'create' => ['create_partners'],
            'edit' => ['edit_partners'],
            'delete' => ['delete_partners']
        ],
        'integrators' => [
            'view' => ['view_integrators'],
            'create' => ['create_integrators'],
            'edit' => ['edit_integrators'],
            'delete' => ['delete_integrators']
        ],
        'transactions' => [
            'view' => ['view_transactions', 'view_own_transactions'],
            'create' => ['create_transactions'],
            'edit' => ['edit_transactions'],
            'delete' => ['delete_transactions']
        ],
        'business-profiles' => [
            'view' => ['view_business_profiles'],
            'create' => ['create_business_profiles'],
            'edit' => ['edit_business_profiles'],
            'delete' => ['delete_business_profiles']
        ],
        'users' => [
            'view' => ['view_users'],
            'create' => ['create_users'],
            'edit' => ['edit_users'],
            'delete' => ['delete_users']
        ],
        'plans' => [
            'view' => ['view_plans', 'view_pricing_plans'],
            'create' => ['create_plans', 'create_pricing_plans'],
            'edit' => ['edit_plans', 'edit_pricing_plans'],
            'delete' => ['delete_plans', 'delete_pricing_plans']
        ],
        'reports' => [
            'view' => ['view_reports', 'view_own_reports'],
            'create' => ['create_reports'],
            'edit' => ['edit_reports'],
            'delete' => ['delete_reports']
        ],
        'settings' => [
            'view' => ['view_settings', 'manage_settings'],
            'edit' => ['manage_settings']
        ]
    ];

    /**
     * Role-based route priorities
     */
    protected array $roleRoutePriorities = [
        'admin' => [
            'dashboard',
            'charging-points.index',
            'groups.index',
            'partners.index',
            'integrators.index',
            'transactions.index',
            'admin.business-profiles.index',
            'admin.users.index',
            'reports.index',
            'settings.index'
        ],
        'integrator' => [
            'charging-points.index',
            'partners.index',
            'groups.index',
            'transactions.index',
            'reports.index',
            'settings.profile',
            'dashboard'
        ],
        'partner' => [
            'charging-points.index',
            'groups.index',
            'transactions.index',
            'reports.index',
            'settings.profile',
            'dashboard'
        ],
        'operator' => [
            'charging-points.index',
            'groups.index',
            'transactions.index',
            'reports.index',
            'settings.profile',
            'dashboard'
        ],
        'user' => [
            'transactions.index',
            'charging-points.index',
            'settings.profile',
            'dashboard'
        ]
    ];

    /**
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get the best redirect route for a user based on their permissions
     */
    public function getRedirectRoute(User $user, string $fromRoute = null): string
    {
        // Log the redirect request
        Log::info('PermissionRedirectService: Finding redirect route', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->toArray(),
            'from_route' => $fromRoute
        ]);

        // Admin always goes to dashboard first via PermissionService
        if ($this->permissionService->hasRole('admin')) {
            return 'dashboard';
        }

        // Get user's primary role
        $primaryRole = $this->getUserPrimaryRole($user);
        
        // Get route priority for this role
        $routePriority = $this->roleRoutePriorities[$primaryRole] ?? $this->roleRoutePriorities['user'];

        // Find the first accessible route
        foreach ($routePriority as $route) {
            if ($this->canAccessRoute($user, $route)) {
                Log::info('PermissionRedirectService: Found accessible route', [
                    'user_id' => $user->id,
                    'route' => $route
                ]);
                return $route;
            }
        }

        // Default fallback
        return 'dashboard';
    }

    /**
     * Get redirect for unauthorized access
     */
    public function getUnauthorizedRedirect(User $user, string $attemptedResource, string $attemptedAction = 'view'): string
    {
        Log::warning('PermissionRedirectService: Handling unauthorized access', [
            'user_id' => $user->id,
            'resource' => $attemptedResource,
            'action' => $attemptedAction
        ]);

        // If user can view the index of the same resource, redirect there
        if ($attemptedAction !== 'view' && $this->canAccessResource($user, $attemptedResource, 'view')) {
            return "{$attemptedResource}.index";
        }

        // Otherwise, get the best general redirect
        return $this->getRedirectRoute($user, "{$attemptedResource}.{$attemptedAction}");
    }

    /**
     * Check if user can access a specific route
     */
    public function canAccessRoute(User $user, string $route): bool
    {
        // Extract resource from route (e.g., 'charging-points' from 'charging-points.index')
        $parts = explode('.', $route);
        $resource = $parts[0];
        $action = $parts[1] ?? 'index';

        // Map common route actions to permission actions
        $actionMap = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete'
        ];

        $permissionAction = $actionMap[$action] ?? 'view';

        return $this->canAccessResource($user, $resource, $permissionAction);
    }

    /**
     * Check if user can access a resource with a specific action
     */
    public function canAccessResource(User $user, string $resource, string $action = 'view'): bool
    {
        // Admin can access everything via PermissionService
        if ($this->permissionService->hasRole('admin')) {
            return true;
        }

        // Special handling for dashboard and settings
        if ($resource === 'dashboard') {
            return true; // Everyone can access dashboard
        }

        if ($resource === 'settings') {
            return true; // Everyone can access their own settings
        }

        // Check if we have permissions defined for this resource
        if (!isset($this->resourcePermissions[$resource][$action])) {
            Log::warning('PermissionRedirectService: No permissions defined', [
                'resource' => $resource,
                'action' => $action
            ]);
            return false;
        }

        // Check each permission
        // Check each permission using the PermissionService
        $permissions = $this->resourcePermissions[$resource][$action];
        if ($this->permissionService->hasAnyPermission($permissions)) {
            return true;
        }

        return false;
    }

    /**
     * Get user's primary role
     */
    protected function getUserPrimaryRole(User $user): string
    {
        $roles = $user->getRoleNames()->toArray();
        
        // Priority order for roles
        $rolePriority = ['super_admin', 'admin', 'integrator', 'partner', 'operator', 'user'];
        
        foreach ($rolePriority as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }

        return 'user'; // Default
    }

    /**
     * Get all accessible routes for a user
     */
    public function getAccessibleRoutes(User $user): array
    {
        $accessibleRoutes = [];
        
        // Get role-based routes
        $primaryRole = $this->getUserPrimaryRole($user);
        $potentialRoutes = $this->roleRoutePriorities[$primaryRole] ?? $this->roleRoutePriorities['user'];

        foreach ($potentialRoutes as $route) {
            if ($this->canAccessRoute($user, $route)) {
                $accessibleRoutes[] = $route;
            }
        }

        return $accessibleRoutes;
    }

    /**
     * Get menu items for user based on permissions
     */
    public function getMenuItems(User $user): array
    {
        $menuItems = [];
        
        $menuConfig = [
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home'
            ],
            [
                'title' => 'Charging Points',
                'route' => 'charging-points.index',
                'icon' => 'zap',
                'resource' => 'charging-points'
            ],
            [
                'title' => 'Groups',
                'route' => 'groups.index',
                'icon' => 'folder',
                'resource' => 'groups'
            ],
            [
                'title' => 'Partners',
                'route' => 'partners.index',
                'icon' => 'users',
                'resource' => 'partners'
            ],
            [
                'title' => 'Integrators',
                'route' => 'integrators.index',
                'icon' => 'briefcase',
                'resource' => 'integrators'
            ],
            [
                'title' => 'Transactions',
                'route' => 'transactions.index',
                'icon' => 'credit-card',
                'resource' => 'transactions'
            ],
            [
                'title' => 'Reports',
                'route' => 'reports.index',
                'icon' => 'file-text',
                'resource' => 'reports'
            ],
            [
                'title' => 'Plans',
                'route' => 'plans.index',
                'icon' => 'tag',
                'resource' => 'plans'
            ]
        ];

        // Add admin-only items via PermissionService
        if ($this->permissionService->hasRole('admin')) {
            $menuConfig[] = [
                'title' => 'Business Profiles',
                'route' => 'admin.business-profiles.index',
                'icon' => 'building',
                'resource' => 'business-profiles'
            ];
            $menuConfig[] = [
                'title' => 'Users',
                'route' => 'admin.users.index',
                'icon' => 'user',
                'resource' => 'users'
            ];
        }

        // Always add settings
        $menuConfig[] = [
            'title' => 'Settings',
            'route' => 'settings.index',
            'icon' => 'settings',
            'resource' => 'settings'
        ];

        // Filter menu items based on permissions
        foreach ($menuConfig as $item) {
            if (!isset($item['resource']) || $this->canAccessResource($user, $item['resource'])) {
                $menuItems[] = $item;
            }
        }

        return $menuItems;
    }
}