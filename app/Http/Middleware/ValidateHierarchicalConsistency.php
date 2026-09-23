<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidateHierarchicalConsistency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only validate on POST requests (creation)
        if ($request->isMethod('POST')) {
            $this->validateRequestData($request);
        }

        return $next($request);
    }

    /**
     * Validate request data for hierarchical consistency
     */
    protected function validateRequestData(Request $request)
    {
        $routeName = $request->route()->getName();
        
        switch ($routeName) {
            case 'integrator.operators.store':
                $this->validateOperatorCreation($request);
                break;
            case 'groups.store':
                $this->validateGroupCreation($request);
                break;
            case 'charging-points.store':
                $this->validateChargingPointCreation($request);
                break;
        }
    }

    /**
     * Validate operator creation
     */
    protected function validateOperatorCreation(Request $request)
    {
        $user = auth()->user();
        
        // Admin can specify integrator_id, integrator uses their own
        if ($user->hasRole('admin')) {
            if (empty($request->integrator_id)) {
                throw ValidationException::withMessages([
                    'integrator_id' => ['Missing Integrator: Vous devez sélectionner un intégrateur.']
                ]);
            }
            
            // Verify integrator exists
            $integrator = \App\Models\Integrator::find($request->integrator_id);
            if (!$integrator || !$integrator->is_active) {
                throw ValidationException::withMessages([
                    'integrator_id' => ['Invalid Integrator: L\'intégrateur sélectionné n\'existe pas ou n\'est pas actif.']
                ]);
            }
        } else if ($user->hasRole('integrator')) {
            // Integrator must have an integrator profile
            if (!$user->integrator || !$user->integrator->is_active) {
                throw ValidationException::withMessages([
                    'integrator_id' => ['Invalid Integrator: Votre profil intégrateur n\'est pas valide.']
                ]);
            }
        }
    }

    /**
     * Validate group creation
     */
    protected function validateGroupCreation(Request $request)
    {
        if (empty($request->partner_id)) {
            throw ValidationException::withMessages([
                'partner_id' => ['Missing Partner: Un groupe doit être lié à un partenaire.']
            ]);
        }

        // Verify partner exists and is active
        $partner = \App\Models\Partner::find($request->partner_id);
        if (!$partner || !$partner->is_active) {
            throw ValidationException::withMessages([
                'partner_id' => ['Invalid Partner: Le partenaire spécifié n\'existe pas ou n\'est pas actif.']
            ]);
        }

        // Verify partner belongs to an integrator
        if (empty($partner->integrator_id)) {
            throw ValidationException::withMessages([
                'partner_id' => ['Invalid Partner: Le partenaire doit être lié à un intégrateur.']
            ]);
        }

        // Verify integrator exists and is active
        $integrator = \App\Models\Integrator::find($partner->integrator_id);
        if (!$integrator || !$integrator->is_active) {
            throw ValidationException::withMessages([
                'partner_id' => ['Invalid Integrator: L\'intégrateur du partenaire n\'existe pas ou n\'est pas actif.']
            ]);
        }
    }

    /**
     * Validate charging point creation
     */
    protected function validateChargingPointCreation(Request $request)
    {
        if (empty($request->group_id)) {
            throw ValidationException::withMessages([
                'group_id' => ['Missing Group: Un point de recharge doit être lié à un groupe.']
            ]);
        }

        // Verify group exists and is active
        $group = \App\Models\Group::find($request->group_id);
        if (!$group || !$group->is_active) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Group: Le groupe spécifié n\'existe pas ou n\'est pas actif.']
            ]);
        }

        // Verify group belongs to a partner
        if (empty($group->partner_id)) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Group: Le groupe doit être lié à un partenaire.']
            ]);
        }

        // Verify partner exists and is active
        $partner = \App\Models\Partner::find($group->partner_id);
        if (!$partner || !$partner->is_active) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Partner: Le partenaire du groupe n\'existe pas ou n\'est pas actif.']
            ]);
        }

        // Verify partner belongs to an integrator
        if (empty($partner->integrator_id)) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Integrator: Le partenaire du groupe doit être lié à un intégrateur.']
            ]);
        }

        // Verify integrator exists and is active
        $integrator = \App\Models\Integrator::find($partner->integrator_id);
        if (!$integrator || !$integrator->is_active) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Integrator: L\'intégrateur du partenaire n\'existe pas ou n\'est pas actif.']
            ]);
        }
    }
}
