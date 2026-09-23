<?php

namespace App\Support;

class LoginRolePolicy
{
    public const ADMIN_EMAIL_ROLES = [
        'admin',
        'Admin',
        'super_admin',
        'Super Admin',
        'Super-Admin',
        'integrator',
        'Integrator',
        'operator',
        'Operator',
        'partner',
        'Partner',
    ];

    public static function usesAdministrativeEmailLogin(mixed $user): bool
    {
        return $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(self::ADMIN_EMAIL_ROLES);
    }
}
