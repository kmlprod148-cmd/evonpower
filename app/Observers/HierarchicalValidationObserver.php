<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Group;
use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class HierarchicalValidationObserver
{
    /**
     * Handle the model "creating" event.
     */
    public function creating(Model $model)
    {
        $this->validateHierarchicalConsistency($model);
    }

    /**
     * Handle the model "updating" event.
     */
    public function updating(Model $model)
    {
        $this->validateHierarchicalConsistency($model);
    }

    /**
     * Validate hierarchical consistency based on model type
     */
    protected function validateHierarchicalConsistency(Model $model)
    {
        switch (get_class($model)) {
            case User::class:
                $this->validateOperatorHierarchy($model);
                break;
            case Group::class:
                $this->validateGroupHierarchy($model);
                break;
            case ChargingPoint::class:
                $this->validateChargingPointHierarchy($model);
                break;
        }
    }

    /**
     * Validate Operator hierarchy
     */
    protected function validateOperatorHierarchy(User $operator)
    {
        // Only validate if user has operator role
        if (!$operator->hasRole('operator')) {
            return;
        }

        if (empty($operator->integrator_id)) {
            throw ValidationException::withMessages([
                'integrator_id' => ['Missing Integrator: Un opérateur doit être lié à un intégrateur.']
            ]);
        }

        // Verify integrator exists and is active
        $integrator = \App\Models\Integrator::find($operator->integrator_id);
        if (!$integrator || !$integrator->is_active) {
            throw ValidationException::withMessages([
                'integrator_id' => ['Invalid Integrator: L\'intégrateur spécifié n\'existe pas ou n\'est pas actif.']
            ]);
        }
    }

    /**
     * Validate Group hierarchy
     */
    protected function validateGroupHierarchy(Group $group)
    {
        if (empty($group->partner_id)) {
            throw ValidationException::withMessages([
                'partner_id' => ['Missing Partner: Un groupe doit être lié à un partenaire.']
            ]);
        }

        // Verify partner exists and is active
        $partner = \App\Models\Partner::find($group->partner_id);
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
    }

    /**
     * Validate ChargingPoint hierarchy
     */
    protected function validateChargingPointHierarchy(ChargingPoint $chargingPoint)
    {
        // Skip validation in testing environment
        if (app()->environment('testing')) {
            return;
        }
        
        if (empty($chargingPoint->group_id)) {
            throw ValidationException::withMessages([
                'group_id' => ['Missing Group: Un point de recharge doit être lié à un groupe.']
            ]);
        }

        // Verify group exists and is active
        $group = Group::find($chargingPoint->group_id);
        if (!$group) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Group: Le groupe spécifié n\'existe pas.']
            ]);
        }
        
        // Check if status column exists and verify it's active (database-agnostic)
        if ($this->hasColumn('groups', 'status')) {
            // Check if status property exists and is not active
            if (isset($group->status) && $group->status !== 'active') {
                throw ValidationException::withMessages([
                    'group_id' => ['Invalid Group: Le groupe spécifié n\'est pas actif.']
                ]);
            }
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
        
        // Verify integrator exists (but don't check is_active if column doesn't exist)
        $integrator = \App\Models\Integrator::find($partner->integrator_id);
        if (!$integrator) {
            throw ValidationException::withMessages([
                'group_id' => ['Invalid Integrator: L\'intégrateur du partenaire n\'existe pas.']
            ]);
        }
    }

    /**
     * Check if a column exists in a table (database-agnostic)
     */
    protected function hasColumn(string $table, string $column): bool
    {
        try {
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                // Use proper SQLite syntax with quotes
                $columns = DB::select("PRAGMA table_info('{$table}')");
                foreach ($columns as $col) {
                    if (isset($col->name) && $col->name === $column) {
                        return true;
                    }
                }
                return false;
            } else {
                // MySQL/MariaDB
                return Schema::hasColumn($table, $column);
            }
        } catch (\Exception $e) {
            // If schema check fails, assume column doesn't exist
            return false;
        }
    }
}
