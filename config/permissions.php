<?php

return [
    'roles' => [
        'admin' => [
            'description' => 'Administrator with full access',
            'permissions' => ['*'], // All permissions
        ],
        'integrator' => [
            'description' => 'Integrator with admin-level access except for integrator management',
            'permissions' => [
                // Charging Points Management (Full access)
                'view_charging_points',
                'view_integrator_charging_points',
                'create_charging_points',
                'edit_charging_points',
                'delete_charging_points',
                'show_charging_points',
                'manage_charging_points',
                'create_integrator_charging_points',
                'edit_integrator_charging_points',
                'delete_integrator_charging_points',
                
                // Partners/Operators Management (Full access)
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
                'activate_partners',
                'deactivate_partners',
                'manage_partners',
                
                // Users/Operators Management (Full access)
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
                'show_users',
                'activate_users',
                'deactivate_users',
                'manage_users',
                
                // Groups Management (Full access)
                'view_integrator_groups',
                'create_groups',
                'edit_groups',
                'delete_groups',
                'show_groups',
                'manage_groups',
                
                // Business Profiles Management (Full access)
                'view_business_profiles',
                'create_business_profiles',
                'edit_business_profiles',
                'delete_business_profiles',
                'show_business_profiles',
                'manage_business_profiles',
                
                // Financial Management (Full access)
                'view_transactions',
                'view_integrator_transactions',
                'approve_withdrawal_requests',
                'manage_pricing_plans',
                'manage_financials',
                
                // Dashboard and Reports (Full access)
                'access_dashboard',
                'view_reports',
                'export_data',
                'manage_reports',

                // Remote Control (Full access)
                'manage_remote_control',

                // System Permissions (Full access)
                'manage_system_permissions',
                'view_system_audit',
                
                // Reservations Management (Full access)
                'view_reservations',
                'create_reservations',
                'edit_reservations',
                'delete_reservations',
                'manage_reservations',
                
                // Pricing Plans Management (Full access)
                'view_pricing_plans',
                'create_pricing_plans',
                'edit_pricing_plans',
                'delete_pricing_plans',
                'manage_pricing_plans',
                
                // Wallet Management (Full access)
                'view_wallet',
                'manage_wallet',
                'credit_wallet',
                'debit_wallet',
                
                // Settings Management (Full access)
                'view_settings',
                'edit_settings',
                'manage_settings',
                
                // Analytics and Statistics (Full access)
                'view_analytics',
                'view_statistics',
                'export_analytics',
                
                // NOT INCLUDED: Integrator management permissions
                // 'view_integrators', 'create_integrators', 'edit_integrators', 'delete_integrators'
            ],
        ],
        'operator' => [
            'description' => 'Operator with limited access to their own resources',
            'permissions' => [
                // Own Charging Points Management
                'view_charging_points',
                'view_own_charging_points',
                'create_charging_points',
                'edit_own_charging_points',
                'delete_own_charging_points',
                'show_own_charging_points',
                
                // Own Groups Management
                'view_own_groups',
                'create_groups',
                'edit_own_groups',
                'delete_own_groups',
                'show_own_groups',
                
                // Own Transactions
                'view_own_transactions',
                'export_own_transactions',
                
                // Profile Management
                'manage_own_profile',
                'view_own_dashboard',
                'access_basic_dashboard',
                
                // Limited Business Profile Access
                'view_assigned_business_profile',

                // Offline credit approvals (clients who reserved on operator's charging points)
                'approve_offline_credits',
            ],
        ],
        'user' => [
            'description' => 'Regular user with access to charging services',
            'permissions' => [
                'view_public_charging_points',
                'start_charging_session',
                'stop_charging_session',
                'view_own_history',
                'view_own_transactions',
                'manage_own_profile',
                'manage_credit_recharges',
            ],
        ],
    ],
    
    // Permission groups for easier management
    'permission_groups' => [
        'charging_point_management' => [
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_charging_points',
            'show_charging_points',
        ],
        'group_management' => [
            'create_groups',
            'edit_groups',
            'delete_groups',
            'view_groups',
            'show_groups',
        ],
        'partner_management' => [
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
        ],
        'operator_management' => [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'show_users',
            'activate_users',
            'deactivate_users',
        ],
        'business_profile_management' => [
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'show_business_profiles',
        ],
        'financial_management' => [
            'view_transactions',
            'view_integrator_transactions',
            'approve_withdrawals',
            'issue_refunds',
            'manage_pricing',
            'manage_pricing_plans',
            // Integrator Contract Management
            'view_integrator_contracts',
            'create_integrator_contracts',
            'edit_integrator_contracts',
            'delete_integrator_contracts',
            // Integrator Invoices
            'view_integrator_invoices',
            'create_integrator_invoices',
            'edit_integrator_invoices',
            'delete_integrator_invoices',
        ],
        'system_management' => [
            'manage_system_permissions',
            'view_system_audit',
            'access_dashboard',
            'view_reports',
            'export_data',
        ],
    ],
];