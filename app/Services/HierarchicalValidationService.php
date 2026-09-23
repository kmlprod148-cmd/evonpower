<?php

namespace App\Services;

use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\DB;

class HierarchicalValidationService
{
    /**
     * Validate the complete hierarchy for a model
     */
    public static function validateHierarchy($model, $data = [])
    {
        $errors = [];

        // Validate based on model type
        switch (get_class($model)) {
            case User::class:
                $errors = array_merge($errors, self::validateUserHierarchy($model, $data));
                break;
            case Integrator::class:
                $errors = array_merge($errors, self::validateIntegratorHierarchy($model, $data));
                break;
            case Partner::class:
                $errors = array_merge($errors, self::validatePartnerHierarchy($model, $data));
                break;
            case Group::class:
                $errors = array_merge($errors, self::validateGroupHierarchy($model, $data));
                break;
            case ChargingPoint::class:
                $errors = array_merge($errors, self::validateChargingPointHierarchy($model, $data));
                break;
        }

        return $errors;
    }

    /**
     * Validate User hierarchy (Admin -> Integrator -> Operator)
     */
    private static function validateUserHierarchy($user, $data = [])
    {
        $errors = [];

        // If user is an operator, validate integrator relationship
        if ($user->hasRole('operator')) {
            if (!$user->integrator_id) {
                $errors[] = 'Operator must belong to an integrator.';
            } else {
                $integrator = Integrator::find($user->integrator_id);
                if (!$integrator) {
                    $errors[] = 'Operator must belong to a valid integrator.';
                }
            }

            // Validate creator is integrator
            if ($user->created_by) {
                $creator = User::find($user->created_by);
                if (!$creator || !$creator->hasRole('integrator')) {
                    $errors[] = 'Operator must be created by an integrator.';
                }
            }
        }

        // If user is an integrator, validate admin relationship
        if ($user->hasRole('integrator')) {
            if ($user->created_by) {
                $creator = User::find($user->created_by);
                if (!$creator || !$creator->hasRole('admin')) {
                    $errors[] = 'Integrator must be created by an admin.';
                }
            }
        }

        return $errors;
    }

    /**
     * Validate Integrator hierarchy (Admin -> Integrator)
     */
    private static function validateIntegratorHierarchy($integrator, $data = [])
    {
        $errors = [];

        if ($integrator->created_by) {
            $admin = User::find($integrator->created_by);
            if (!$admin || !$admin->hasRole('admin')) {
                $errors[] = 'Integrator must be created by an admin user.';
            }
        }

        return $errors;
    }

    /**
     * Validate Partner hierarchy (Admin -> Integrator -> Partner)
     */
    private static function validatePartnerHierarchy($partner, $data = [])
    {
        $errors = [];

        if ($partner->integrator_id) {
            $integrator = Integrator::find($partner->integrator_id);
            if (!$integrator) {
                $errors[] = 'Partner must belong to a valid integrator.';
            }
        }

        if ($partner->created_by) {
            $creator = User::find($partner->created_by);
            if (!$creator) {
                $errors[] = 'Partner must be created by a valid user.';
            } else {
                // Partner can be created by admin or integrator
                if (!$creator->hasRole('admin') && !$creator->hasRole('integrator')) {
                    $errors[] = 'Partner must be created by an admin or integrator.';
                }
            }
        }

        return $errors;
    }

    /**
     * Validate Group hierarchy (Partner -> Group)
     */
    private static function validateGroupHierarchy($group, $data = [])
    {
        $errors = [];

        if ($group->partner_id) {
            $partner = Partner::find($group->partner_id);
            if (!$partner) {
                $errors[] = 'Group must belong to a valid partner.';
            }
        }

        return $errors;
    }

    /**
     * Validate ChargingPoint hierarchy (Group -> ChargingPoint)
     */
    private static function validateChargingPointHierarchy($chargingPoint, $data = [])
    {
        $errors = [];

        if ($chargingPoint->group_id) {
            $group = Group::find($chargingPoint->group_id);
            if (!$group) {
                $errors[] = 'ChargingPoint must belong to a valid group.';
            }
        }

        return $errors;
    }

    /**
     * Validate complete hierarchy chain
     */
    public static function validateCompleteHierarchy($model)
    {
        $errors = [];

        // Validate the model itself
        $errors = array_merge($errors, self::validateHierarchy($model));

        // Validate parent relationships
        if ($model instanceof ChargingPoint && $model->group_id) {
            $group = Group::find($model->group_id);
            if ($group) {
                $errors = array_merge($errors, self::validateHierarchy($group));
            }
        }

        if ($model instanceof Group && $model->partner_id) {
            $partner = Partner::find($model->partner_id);
            if ($partner) {
                $errors = array_merge($errors, self::validateHierarchy($partner));
            }
        }

        if ($model instanceof Partner && $model->integrator_id) {
            $integrator = Integrator::find($model->integrator_id);
            if ($integrator) {
                $errors = array_merge($errors, self::validateHierarchy($integrator));
            }
        }

        return $errors;
    }

    /**
     * Get hierarchy path for a model
     */
    public static function getHierarchyPath($model)
    {
        $path = [];

        if ($model instanceof ChargingPoint) {
            $path[] = 'ChargingPoint';
            if ($model->group) {
                $path[] = 'Group: ' . $model->group->name;
                if ($model->group->partner) {
                    $path[] = 'Partner: ' . $model->group->partner->name;
                    if ($model->group->partner->integrator) {
                        $path[] = 'Integrator: ' . $model->group->partner->integrator->name;
                        if ($model->group->partner->integrator->admin) {
                            $path[] = 'Admin: ' . $model->group->partner->integrator->admin->name;
                        }
                    }
                }
            }
        }

        return array_reverse($path);
    }

    /**
     * Check if user can manage a resource based on hierarchy
     */
    public static function canUserManageResource($user, $resource)
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('integrator')) {
            // Integrator can manage their own resources and their partners' resources
            if (method_exists($resource, 'integrator_id') && $resource->integrator_id === $user->integrator_id) {
                return true;
            }
            if (method_exists($resource, 'partner') && $resource->partner && $resource->partner->integrator_id === $user->integrator_id) {
                return true;
            }
        }

        if ($user->hasRole('partner')) {
            // Partner can manage their own resources
            if (method_exists($resource, 'partner_id') && $resource->partner_id === $user->partner_id) {
                return true;
            }
            if (method_exists($resource, 'group') && $resource->group && $resource->group->partner_id === $user->partner_id) {
                return true;
            }
        }

        if ($user->hasRole('operator')) {
            // Operator can manage resources in their integrator's hierarchy
            if (method_exists($resource, 'integrator_id') && $resource->integrator_id === $user->integrator_id) {
                return true;
            }
        }

        return false;
    }
}
