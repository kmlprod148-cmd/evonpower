<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission as SpatiePermission;

class AdvancedRoleService
{
    /**
     * Define comprehensive role hierarchy with specific permissions
     */
    protected const ROLE_PERMISSIONS = [
        'super_admin' => [
            'level' => 5,
            'permissions' => ['*'], // All permissions
            'description' => 'Full system access with all privileges'
        ],
        'admin' => [
            'level' => 4,
            'permissions' => [
                'users.manage',
                'roles.manage',
                'permissions.manage',
                'integrators.manage',
                'partners.manage',
                'operators.manage',
                'charging_points.manage',
                'transactions.view',
                'transactions.manage',
                'reports.generate',
                'settings.manage',
                'audit_logs.view'
            ],
            'description' => 'Administrative access to manage users and system settings'
        ],
        'integrator' => [
            'level' => 3,
            'permissions' => [
                'operators.manage',
                'partners.manage',
                'charging_points.manage',
                'charging_points.view',
                'transactions.view',
                'transactions.manage',
                'reports.generate',
                'wallet.manage',
                'business_profiles.manage'
            ],
            'description' => 'Manage operators, partners, and charging infrastructure'
        ],
        'partner' => [
            'level' => 2,
            'permissions' => [
                'charging_points.view',
                'charging_points.manage',
                'transactions.view',
                'reports.generate',
                'wallet.manage',
                'business_profiles.view'
            ],
            'description' => 'Manage charging points and view related transactions'
        ],
        'operator' => [
            'level' => 1,
            'permissions' => [
                'charging_points.view',
                'charging_points.manage',
                'transactions.view',
                'wallet.view',
                'sessions.manage'
            ],
            'description' => 'Operate charging points and manage charging sessions'
        ],
        'user' => [
            'level' => 0,
            'permissions' => [
                'charging_points.view',
                'transactions.view',
                'wallet.view',
                'sessions.create',
                'sessions.manage'
            ],
            'description' => 'Basic user access to charging services'
        ]
    ];

    /**
     * Create or update a role with specific permissions
     */
    public function createOrUpdateRole(string $roleName, array $permissions = [], array $metadata = []): SpatieRole
    {
        try {
            DB::beginTransaction();

            // Create or update the role
            $role = SpatieRole::updateOrCreate(
                ['name' => $roleName],
                array_merge([
                    'guard_name' => 'web',
                    'description' => $metadata['description'] ?? self::ROLE_PERMISSIONS[$roleName]['description'] ?? null,
                    'level' => $metadata['level'] ?? self::ROLE_PERMISSIONS[$roleName]['level'] ?? 0,
                    'is_active' => $metadata['is_active'] ?? true,
                    'created_by' => auth()->id(),
                ], $metadata)
            );

            // Sync permissions
            if (!empty($permissions)) {
                $permissionModels = [];
                foreach ($permissions as $permission) {
                    $permissionModels[] = SpatiePermission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web'
                    ]);
                }
                $role->syncPermissions($permissionModels);
            }

            // Clear cache
            $this->clearRoleCache($roleName);

            DB::commit();

            Log::info("Role created/updated successfully", [
                'role' => $roleName,
                'permissions_count' => count($permissions),
                'created_by' => auth()->id()
            ]);

            return $role;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create/update role", [
                'role' => $roleName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assign role to user with additional metadata
     */
    public function assignRoleToUser(User $user, string $roleName, array $metadata = []): bool
    {
        try {
            $role = SpatieRole::where('name', $roleName)->first();
            
            if (!$role) {
                throw new \Exception("Role '{$roleName}' not found");
            }

            // Check if user already has this role
            if ($user->hasRole($roleName)) {
                Log::info("User already has role", [
                    'user_id' => $user->id,
                    'role' => $roleName
                ]);
                return true;
            }

            // Assign role
            $user->assignRole($role);

            // Store additional metadata if provided
            if (!empty($metadata)) {
                $user->roles()->updateExistingPivot($role->id, [
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'metadata' => json_encode($metadata)
                ]);
            }

            // Clear user permission cache
            app(PermissionService::class)->invalidateUserPermissionsCache($user->id);

            Log::info("Role assigned to user", [
                'user_id' => $user->id,
                'role' => $roleName,
                'assigned_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to assign role to user", [
                'user_id' => $user->id,
                'role' => $roleName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(User $user, string $roleName): bool
    {
        try {
            if (!$user->hasRole($roleName)) {
                Log::info("User does not have role to remove", [
                    'user_id' => $user->id,
                    'role' => $roleName
                ]);
                return true;
            }

            $user->removeRole($roleName);

            // Clear user permission cache
            app(PermissionService::class)->invalidateUserPermissionsCache($user->id);

            Log::info("Role removed from user", [
                'user_id' => $user->id,
                'role' => $roleName,
                'removed_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to remove role from user", [
                'user_id' => $user->id,
                'role' => $roleName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user's effective permissions (including role hierarchy)
     */
    public function getUserEffectivePermissions(User $user): array
    {
        $cacheKey = "user_effective_permissions:{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user) {
            $permissions = [];
            
            // Get direct permissions
            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
            $permissions = array_merge($permissions, $directPermissions);
            
            // Get role permissions
            foreach ($user->getRoles() as $role) {
                $rolePermissions = $role->permissions->pluck('name')->toArray();
                $permissions = array_merge($permissions, $rolePermissions);
                
                // Check for wildcard permissions
                if (in_array('*', $rolePermissions)) {
                    $permissions = ['*'];
                    break;
                }
            }
            
            return array_unique($permissions);
        });
    }

    /**
     * Check if user can perform action on resource
     */
    public function canUserPerformAction(User $user, string $action, $resource = null): bool
    {
        $effectivePermissions = $this->getUserEffectivePermissions($user);
        
        // Check for wildcard permission
        if (in_array('*', $effectivePermissions)) {
            return true;
        }
        
        // Check specific permission
        if (in_array($action, $effectivePermissions)) {
            return true;
        }
        
        // Check resource-specific permissions
        if ($resource) {
            $resourceClass = get_class($resource);
            $resourceAction = $action . '.' . strtolower(class_basename($resourceClass));
            
            if (in_array($resourceAction, $effectivePermissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get role hierarchy information
     */
    public function getRoleHierarchy(): array
    {
        return Cache::remember('role_hierarchy', now()->addHour(), function () {
            return self::ROLE_PERMISSIONS;
        });
    }

    /**
     * Initialize default roles and permissions
     */
    public function initializeDefaultRoles(): bool
    {
        try {
            DB::beginTransaction();

            foreach (self::ROLE_PERMISSIONS as $roleName => $roleData) {
                $this->createOrUpdateRole($roleName, $roleData['permissions'], [
                    'description' => $roleData['description'],
                    'level' => $roleData['level']
                ]);
            }

            DB::commit();

            Log::info("Default roles and permissions initialized successfully");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to initialize default roles", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear role-related cache
     */
    private function clearRoleCache(string $roleName = null): void
    {
        $cacheKeys = [
            'role_hierarchy',
            'user_effective_permissions:*'
        ];
        
        if ($roleName) {
            $cacheKeys[] = "role_permissions:{$roleName}";
        }
        
        foreach ($cacheKeys as $key) {
            if (str_contains($key, '*')) {
                // For wildcard keys, we'd need to implement pattern-based cache clearing
                // This depends on the cache driver being used
                Cache::flush();
                break;
            } else {
                Cache::forget($key);
            }
        }
    }

    /**
     * Get users by role with pagination
     */
    public function getUsersByRole(string $roleName, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $role = SpatieRole::where('name', $roleName)->first();
        
        if (!$role) {
            return collect()->paginate($perPage);
        }
        
        return $role->users()->paginate($perPage);
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(): array
    {
        return Cache::remember('role_statistics', now()->addMinutes(30), function () {
            $stats = [];
            
            foreach (self::ROLE_PERMISSIONS as $roleName => $roleData) {
                $role = SpatieRole::where('name', $roleName)->first();
                $stats[$roleName] = [
                    'name' => $roleName,
                    'level' => $roleData['level'],
                    'description' => $roleData['description'],
                    'user_count' => $role ? $role->users()->count() : 0,
                    'permission_count' => count($roleData['permissions']),
                    'is_active' => $role ? $role->is_active ?? true : false
                ];
            }
            
            return $stats;
        });
    }
}
