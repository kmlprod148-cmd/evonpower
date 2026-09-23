<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use App\Exceptions\PolicyException;

trait PolicyEnforcement
{
    /**
     * Authorize a policy action with custom error handling
     */
    protected function authorizePolicy($action, $model, $user = null)
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            throw new AuthorizationException('You must be logged in to perform this action.');
        }

        if (!$user->can($action, $model)) {
            throw PolicyException::denied(
                class_basename($model) . 'Policy',
                $action,
                $model,
                $user
            );
        }

        return true;
    }

    /**
     * Authorize viewing a model
     */
    protected function authorizeView($model, $user = null)
    {
        return $this->authorizePolicy('view', $model, $user);
    }

    /**
     * Authorize creating a model
     */
    protected function authorizeCreate($model, $user = null)
    {
        return $this->authorizePolicy('create', $model, $user);
    }

    /**
     * Authorize updating a model
     */
    protected function authorizeUpdate($model, $user = null)
    {
        return $this->authorizePolicy('update', $model, $user);
    }

    /**
     * Authorize deleting a model
     */
    protected function authorizeDelete($model, $user = null)
    {
        return $this->authorizePolicy('delete', $model, $user);
    }

    /**
     * Authorize managing a model
     */
    protected function authorizeManage($model, $user = null)
    {
        return $this->authorizePolicy('manage', $model, $user);
    }

    /**
     * Get filtered models based on user's hierarchical access
     */
    protected function getFilteredModels($modelClass, $user = null)
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return collect();
        }

        $query = $modelClass::query();

        // Apply hierarchical filtering based on user role
        if ($user->hasRole('admin')) {
            // Admin can see all
            return $query;
        }

        if ($user->hasRole('integrator')) {
            // Integrator can see their own resources
            if (method_exists($modelClass, 'scopeForIntegrator')) {
                return $query->forIntegrator($user->integrator_id);
            }
            if (property_exists($modelClass, 'integrator_id')) {
                return $query->where('integrator_id', $user->integrator_id);
            }
        }

        if ($user->hasRole('partner')) {
            // Partner can see their own resources
            if (method_exists($modelClass, 'scopeForPartner')) {
                return $query->forPartner($user->partner_id);
            }
            if (property_exists($modelClass, 'partner_id')) {
                return $query->where('partner_id', $user->partner_id);
            }
        }

        if ($user->hasRole('operator')) {
            // Operator can see their integrator's resources
            if (method_exists($modelClass, 'scopeForIntegrator')) {
                return $query->forIntegrator($user->integrator_id);
            }
            if (property_exists($modelClass, 'integrator_id')) {
                return $query->where('integrator_id', $user->integrator_id);
            }
        }

        return $query;
    }

    /**
     * Check if user can perform action on model
     */
    protected function canPerformAction($action, $model, $user = null)
    {
        $user = $user ?? auth()->user();
        return $user ? $user->can($action, $model) : false;
    }

    /**
     * Get user's accessible models with pagination
     */
    protected function getAccessibleModels($modelClass, $perPage = 15, $user = null)
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return collect();
        }

        $query = $this->getFilteredModels($modelClass, $user);
        
        return $query->paginate($perPage);
    }

    /**
     * Handle policy authorization in controllers
     */
    protected function handlePolicyAuthorization($action, $model, $user = null)
    {
        try {
            return $this->authorizePolicy($action, $model, $user);
        } catch (PolicyException $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Access Denied',
                    'message' => $e->getMessage(),
                    'policy' => $e->getPolicy(),
                    'action' => $e->getAction(),
                ], 403);
            }
            
            throw $e;
        }
    }
}
