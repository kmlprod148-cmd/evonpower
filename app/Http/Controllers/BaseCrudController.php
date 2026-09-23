<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\CrudException;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

abstract class BaseCrudController extends Controller
{
    protected $service;
    protected $resource;
    protected $viewPrefix;
    protected $routePrefix;

    /**
     * Map of resources to their view permissions
     */
    protected array $permissionMap = [
        'charging-points' => ['view_charging_points', 'view_integrator_charging_points', 'view_own_charging_points'],
        'groups' => ['view_groups', 'view_all_groups', 'view_integrator_groups', 'view_own_groups'],
        'partners' => ['view_partners', 'view_integrator_partners', 'view_own_partners'],
        'integrators' => ['view_integrators'],
        'transactions' => ['view_transactions', 'view_own_transactions'],
        'business-profiles' => ['view_business_profiles'],
        'users' => ['view_users'],
        'plans' => ['view_plans'],
        'reports' => ['view_reports', 'view_own_reports'],
    ];

    public function index(Request $request)
    {
        try {
            // Check if user has permission to view this resource
            if (!$this->canViewResource()) {
                return $this->handleUnauthorizedAccess('index');
            }

            $items = $this->service->getFilteredForUser(
                auth()->user(),
                $request->all()
            );

            return view("{$this->viewPrefix}.index", [
                $this->resource => $items
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'index');
        }
    }

    public function show($id)
    {
        try {
            $item = $this->service->findOrFail($id);
            
            // Check authorization
            try {
                $this->authorize('view', $item);
            } catch (AuthorizationException $e) {
                return $this->handleUnauthorizedAccess('show', $item);
            }

            return view("{$this->viewPrefix}.show", [
                substr($this->resource, 0, -1) => $item
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'show');
        }
    }

    public function create()
    {
        try {
            // Check if user has permission to create this resource
            if (!$this->canCreateResource()) {
                return $this->handleUnauthorizedAccess('create');
            }

            $data = $this->getCreateFormData();
            return view("{$this->viewPrefix}.create", $data);
        } catch (\Exception $e) {
            return $this->handleError($e, 'create');
        }
    }

    public function store($request)
    {
        try {
            // Check if user has permission to create this resource
            if (!$this->canCreateResource()) {
                return $this->handleUnauthorizedAccess('store');
            }

            $item = $this->service->create($request->validated());

            return redirect()
                ->route("{$this->routePrefix}.show", $item)
                ->with('success', ucfirst($this->resource) . ' created successfully');
        } catch (CrudException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $item = $this->service->findOrFail($id);
            
            // Check authorization
            try {
                $this->authorize('update', $item);
            } catch (AuthorizationException $e) {
                return $this->handleUnauthorizedAccess('edit', $item);
            }

            $data = $this->getEditFormData($item);
            return view("{$this->viewPrefix}.edit", $data);
        } catch (\Exception $e) {
            return $this->handleError($e, 'edit');
        }
    }

    public function update($request, $id)
    {
        try {
            $item = $this->service->findOrFail($id);
            
            // Check authorization
            try {
                $this->authorize('update', $item);
            } catch (AuthorizationException $e) {
                return $this->handleUnauthorizedAccess('update', $item);
            }

            $this->service->update($item, $request->validated());

            return redirect()
                ->route("{$this->routePrefix}.show", $item)
                ->with('success', ucfirst($this->resource) . ' updated successfully');
        } catch (CrudException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $item = $this->service->findOrFail($id);
            
            // Check authorization
            try {
                $this->authorize('delete', $item);
            } catch (AuthorizationException $e) {
                return $this->handleUnauthorizedAccess('delete', $item);
            }

            $this->service->delete($item);

            return redirect()
                ->route("{$this->routePrefix}.index")
                ->with('success', ucfirst($this->resource) . ' deleted successfully');
        } catch (CrudException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Check if user can view this resource type
     */
    protected function canViewResource(): bool
    {
        $user = auth()->user();
        
        // Admin can view everything
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check specific permissions for this resource
        $permissions = $this->permissionMap[$this->routePrefix] ?? [];
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can create this resource type
     */
    protected function canCreateResource(): bool
    {
        $user = auth()->user();
        
        // Admin can create everything
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check create permission
        return $user->can("create_{$this->routePrefix}");
    }

    /**
     * Handle unauthorized access with smart redirects
     */
    protected function handleUnauthorizedAccess(string $action, $item = null)
    {
        $user = auth()->user();
        
        Log::warning("Unauthorized access attempt", [
            'user_id' => $user->id,
            'resource' => $this->routePrefix,
            'action' => $action,
            'item_id' => $item?->id
        ]);

        // Determine the best redirect
        $redirect = $this->determineRedirect($user, $action);

        $message = match($action) {
            'index' => "You don't have permission to view {$this->resource}.",
            'show' => "You don't have permission to view this " . substr($this->resource, 0, -1) . ".",
            'create', 'store' => "You don't have permission to create {$this->resource}.",
            'edit', 'update' => "You don't have permission to edit this " . substr($this->resource, 0, -1) . ".",
            'destroy' => "You don't have permission to delete this " . substr($this->resource, 0, -1) . ".",
            default => "You don't have permission to access this resource."
        };

        return redirect()->route($redirect)->with('error', $message);
    }

    /**
     * Determine the best redirect route for the user
     */
    protected function determineRedirect($user, string $action): string
    {
        // If user can view the index of this resource, redirect there
        if ($this->canViewResource() && in_array($action, ['show', 'edit', 'update', 'destroy'])) {
            return "{$this->routePrefix}.index";
        }

        // Check what the user CAN access
        $accessibleRoutes = $this->getAccessibleRoutes($user);
        
        // Return the first accessible route
        return $accessibleRoutes[0] ?? 'dashboard';
    }

    /**
     * Get list of routes the user can access
     */
    protected function getAccessibleRoutes($user): array
    {
        $routes = [];

        // Define route priority order
        $routePriority = [
            'charging-points.index',
            'groups.index',
            'partners.index',
            'transactions.index',
            'integrators.index',
            'reports.index',
            'plans.index',
            'admin.business-profiles.index',
            'admin.users.index',
            'settings.index',
            'dashboard'
        ];

        foreach ($routePriority as $route) {
            $resource = explode('.', $route)[0];
            $permissions = $this->permissionMap[$resource] ?? [];
            
            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    $routes[] = $route;
                    break;
                }
            }
        }

        // Always include dashboard as fallback
        if (!in_array('dashboard', $routes)) {
            $routes[] = 'dashboard';
        }

        return $routes;
    }

    /**
     * Get data for create form (override in child classes)
     */
    protected function getCreateFormData(): array
    {
        return [];
    }

    /**
     * Get data for edit form (override in child classes)
     */
    protected function getEditFormData($item): array
    {
        return [substr($this->resource, 0, -1) => $item];
    }

    protected function handleError(\Exception $e, string $action)
    {
        Log::error("Error in {$this->routePrefix}.{$action}", [
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        // For not found errors, redirect to index
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return redirect()
                ->route("{$this->routePrefix}.index")
                ->with('error', ucfirst(substr($this->resource, 0, -1)) . ' not found');
        }

        // For other errors, determine best redirect
        $redirect = $this->canViewResource() ? "{$this->routePrefix}.index" : 'dashboard';
        
        return redirect()
            ->route($redirect)
            ->with('error', 'An error occurred');
    }
}