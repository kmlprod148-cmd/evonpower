<?php

namespace App\Services;

use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Auth;

class AutoAssignmentService
{
    /**
     * Auto-assign relationships for a User (Operator)
     */
    public static function assignUserRelationships(User $user, array $data = [])
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return $data;
        }

        // Auto-assign based on authenticated user's role
        if ($authenticatedUser->hasRole('admin')) {
            // Admin can assign to any integrator
            if (isset($data['integrator_id']) && $data['integrator_id']) {
                $data['created_by'] = $authenticatedUser->id;
                $data['created_by_role'] = 'admin';
            }
        } elseif ($authenticatedUser->hasRole('integrator')) {
            // Integrator can only assign to their own integrator
            $data['integrator_id'] = $authenticatedUser->integrator_id;
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'integrator';
        }

        return $data;
    }

    /**
     * Auto-assign relationships for a ChargingPoint
     */
    public static function assignChargingPointRelationships(ChargingPoint $chargingPoint, array $data = [])
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return $data;
        }

        // Auto-assign based on authenticated user's role and hierarchy
        if ($authenticatedUser->hasRole('admin')) {
            // Admin can assign to any hierarchy
            if (isset($data['group_id']) && $data['group_id']) {
                $group = Group::find($data['group_id']);
                if ($group) {
                    $data['partner_id'] = $group->partner_id;
                    if ($group->partner) {
                        $data['integrator_id'] = $group->partner->integrator_id;
                    }
                }
            }
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'admin';
        } elseif ($authenticatedUser->hasRole('integrator')) {
            // Integrator can assign to their integrator's hierarchy
            $data['integrator_id'] = $authenticatedUser->integrator_id;
            
            if (isset($data['group_id']) && $data['group_id']) {
                $group = Group::find($data['group_id']);
                if ($group && $group->partner && $group->partner->integrator_id === $authenticatedUser->integrator_id) {
                    $data['partner_id'] = $group->partner_id;
                }
            }
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'integrator';
        } elseif ($authenticatedUser->hasRole('partner')) {
            // Partner can assign to their partner's hierarchy
            $data['partner_id'] = $authenticatedUser->partner_id;
            
            if ($authenticatedUser->integrator_id) {
                $data['integrator_id'] = $authenticatedUser->integrator_id;
            }
            
            if (isset($data['group_id']) && $data['group_id']) {
                $group = Group::find($data['group_id']);
                if ($group && $group->partner_id === $authenticatedUser->partner_id) {
                    // Group belongs to the partner, proceed
                } else {
                    // Auto-assign to partner's first group if no group specified
                    $firstGroup = Group::where('partner_id', $authenticatedUser->partner_id)->first();
                    if ($firstGroup) {
                        $data['group_id'] = $firstGroup->id;
                    }
                }
            } else {
                // Auto-assign to partner's first group
                $firstGroup = Group::where('partner_id', $authenticatedUser->partner_id)->first();
                if ($firstGroup) {
                    $data['group_id'] = $firstGroup->id;
                }
            }
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'partner';
        } elseif ($authenticatedUser->hasRole('operator')) {
            // Operator can assign to their integrator's hierarchy
            $data['integrator_id'] = $authenticatedUser->integrator_id;
            
            if (isset($data['group_id']) && $data['group_id']) {
                $group = Group::find($data['group_id']);
                if ($group && $group->partner && $group->partner->integrator_id === $authenticatedUser->integrator_id) {
                    $data['partner_id'] = $group->partner_id;
                }
            }
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'operator';
        }

        return $data;
    }

    /**
     * Auto-assign relationships for a Group
     */
    public static function assignGroupRelationships(Group $group, array $data = [])
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return $data;
        }

        if ($authenticatedUser->hasRole('admin')) {
            // Admin can assign to any partner
            if (isset($data['partner_id']) && $data['partner_id']) {
                $partner = Partner::find($data['partner_id']);
                if ($partner) {
                    $data['integrator_id'] = $partner->integrator_id;
                }
            }
            $data['user_id'] = $authenticatedUser->id;
        } elseif ($authenticatedUser->hasRole('integrator')) {
            // Integrator can assign to their integrator's partners
            if (isset($data['partner_id']) && $data['partner_id']) {
                $partner = Partner::find($data['partner_id']);
                if ($partner && $partner->integrator_id === $authenticatedUser->integrator_id) {
                    $data['integrator_id'] = $authenticatedUser->integrator_id;
                }
            }
            $data['user_id'] = $authenticatedUser->id;
        } elseif ($authenticatedUser->hasRole('partner')) {
            // Partner can only assign to themselves
            $data['partner_id'] = $authenticatedUser->partner_id;
            $data['integrator_id'] = $authenticatedUser->integrator_id;
            $data['user_id'] = $authenticatedUser->id;
        }

        return $data;
    }

    /**
     * Auto-assign relationships for a Partner
     */
    public static function assignPartnerRelationships(Partner $partner, array $data = [])
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return $data;
        }

        if ($authenticatedUser->hasRole('admin')) {
            // Admin can assign to any integrator
            if (isset($data['integrator_id']) && $data['integrator_id']) {
                // Partner belongs to specified integrator
            }
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'admin';
        } elseif ($authenticatedUser->hasRole('integrator')) {
            // Integrator can only assign to themselves
            $data['integrator_id'] = $authenticatedUser->integrator_id;
            $data['created_by'] = $authenticatedUser->id;
            $data['created_by_role'] = 'integrator';
        }

        return $data;
    }

    /**
     * Get available options for the authenticated user
     */
    public static function getAvailableOptions($modelType, $field = null)
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return collect();
        }

        switch ($modelType) {
            case 'integrators':
                if ($authenticatedUser->hasRole('admin')) {
                    return Integrator::all();
                }
                return collect();

            case 'partners':
                if ($authenticatedUser->hasRole('admin')) {
                    return Partner::all();
                } elseif ($authenticatedUser->hasRole('integrator')) {
                    return Partner::where('integrator_id', $authenticatedUser->integrator_id)->get();
                }
                return collect();

            case 'groups':
                if ($authenticatedUser->hasRole('admin')) {
                    return Group::all();
                } elseif ($authenticatedUser->hasRole('integrator')) {
                    return Group::whereHas('partner', function ($query) use ($authenticatedUser) {
                        $query->where('integrator_id', $authenticatedUser->integrator_id);
                    })->get();
                } elseif ($authenticatedUser->hasRole('partner')) {
                    return Group::where('partner_id', $authenticatedUser->partner_id)->get();
                }
                return collect();

            case 'operators':
                if ($authenticatedUser->hasRole('admin')) {
                    return User::whereHas('roles', function ($query) {
                        $query->where('name', 'operator');
                    })->get();
                } elseif ($authenticatedUser->hasRole('integrator')) {
                    return User::where('integrator_id', $authenticatedUser->integrator_id)
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'operator');
                        })->get();
                }
                return collect();

            default:
                return collect();
        }
    }

    /**
     * Validate that the user can assign to the specified relationships
     */
    public static function validateAssignment($modelType, $data)
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return false;
        }

        switch ($modelType) {
            case 'user':
                if (isset($data['integrator_id'])) {
                    if ($authenticatedUser->hasRole('admin')) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('integrator') && 
                             $data['integrator_id'] === $authenticatedUser->integrator_id) {
                        return true;
                    }
                }
                return false;

            case 'charging_point':
                if (isset($data['group_id'])) {
                    $group = Group::find($data['group_id']);
                    if (!$group) {
                        return false;
                    }

                    if ($authenticatedUser->hasRole('admin')) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('integrator') && 
                             $group->partner && 
                             $group->partner->integrator_id === $authenticatedUser->integrator_id) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('partner') && 
                             $group->partner_id === $authenticatedUser->partner_id) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('operator') && 
                             $group->partner && 
                             $group->partner->integrator_id === $authenticatedUser->integrator_id) {
                        return true;
                    }
                }
                return false;

            case 'group':
                if (isset($data['partner_id'])) {
                    $partner = Partner::find($data['partner_id']);
                    if (!$partner) {
                        return false;
                    }

                    if ($authenticatedUser->hasRole('admin')) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('integrator') && 
                             $partner->integrator_id === $authenticatedUser->integrator_id) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('partner') && 
                             $partner->id === $authenticatedUser->partner_id) {
                        return true;
                    }
                }
                return false;

            case 'partner':
                if (isset($data['integrator_id'])) {
                    if ($authenticatedUser->hasRole('admin')) {
                        return true;
                    } elseif ($authenticatedUser->hasRole('integrator') && 
                             $data['integrator_id'] === $authenticatedUser->integrator_id) {
                        return true;
                    }
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * Get the default values for a model based on authenticated user
     */
    public static function getDefaultValues($modelType)
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser) {
            return [];
        }

        $defaults = [
            'created_by' => $authenticatedUser->id,
            'created_by_role' => $authenticatedUser->getRoleNames()->first(),
        ];

        switch ($modelType) {
            case 'user':
                if ($authenticatedUser->hasRole('integrator')) {
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                }
                break;

            case 'charging_point':
                if ($authenticatedUser->hasRole('integrator')) {
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                } elseif ($authenticatedUser->hasRole('partner')) {
                    $defaults['partner_id'] = $authenticatedUser->partner_id;
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                    
                    // Auto-assign to first group if available
                    $firstGroup = Group::where('partner_id', $authenticatedUser->partner_id)->first();
                    if ($firstGroup) {
                        $defaults['group_id'] = $firstGroup->id;
                    }
                } elseif ($authenticatedUser->hasRole('operator')) {
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                }
                break;

            case 'group':
                if ($authenticatedUser->hasRole('integrator')) {
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                } elseif ($authenticatedUser->hasRole('partner')) {
                    $defaults['partner_id'] = $authenticatedUser->partner_id;
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                }
                break;

            case 'partner':
                if ($authenticatedUser->hasRole('integrator')) {
                    $defaults['integrator_id'] = $authenticatedUser->integrator_id;
                }
                break;
        }

        return $defaults;
    }
}
