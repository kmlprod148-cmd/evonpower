<?php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\ChargingPoint;
use App\Services\Authorization\PermissionService;

class ValidChargingPointAccess implements Rule
{
    protected PermissionService $permissionService;

    public function __construct()
    {
        $this->permissionService = app(PermissionService::class);
    }

    public function passes($attribute, $value)
    {
        $user = auth()->user();
        $chargingPoint = ChargingPoint::find($value);

        if (!$chargingPoint) {
            return false;
        }

        // Admin bypass using PermissionService
        if ($this->permissionService->hasRole('admin')) {
            return true;
        }

        // Integrator check using PermissionService
        if ($this->permissionService->hasRole('integrator')) {
            return $chargingPoint->integrator_id === $user->integrator_id;
        }

        // Partner check using PermissionService
        if ($this->permissionService->hasRole('partner')) {
            return $chargingPoint->partner_id === $user->partner_id;
        }

        return false;
    }

    public function message()
    {
        return 'You do not have access to this charging point.';
    }
}