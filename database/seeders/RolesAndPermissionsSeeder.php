<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Authentication & Authorization
            'view_profile',
            'update_profile',
            'manage_roles',
            'manage_permissions',
            'assign_roles',
            'assign_permissions',

            // Dashboard
            'view_admin_dashboard',
            'view_integrator_dashboard',
            'view_partner_dashboard',
            'view_user_dashboard',
            'view_dashboard_stats',
            'view_dashboard_chart_data',

            // Integrators Management
            'view_integrators', // Scoped by role
            'create_integrators',
            'edit_integrators', // Scoped by role
            'delete_integrators', // Scoped by role
            'activate_integrators',
            'deactivate_integrators',
            'export_integrators',
            'view_integrator_charging_points', // Scoped to integrator's points
            'view_integrator_stats', // Scoped to integrator's stats

            // Partners Management
            'view_partners', // Scoped by role
            'create_partners', // Scoped by role
            'edit_partners', // Scoped by role
            'delete_partners', // Scoped by role
            'bulk_update_partners', // Scoped by role
            'export_partners', // Scoped by role
            'view_partner_charging_points', // Scoped to partner's points
            'view_partner_stats', // Scoped to partner's stats

            // Groups Management
            'view_groups', // Scoped by role
            'create_groups', // Scoped by role
            'edit_groups', // Scoped by role
            'delete_groups', // Scoped by role
            'assign_charging_points_to_group', // Scoped by role
            'manage_group_location', // Scoped by role
            'bulk_update_groups', // Scoped by role

            // Charging Points Management
            'view_charging_points', // Scoped by role
            'create_charging_points', // Scoped by role
            'edit_charging_points', // Scoped by role
            'delete_charging_points', // Scoped by role
            'generate_qr_code', // Scoped by role
            'regenerate_qr_code', // Scoped by role
            'download_qr_code', // Scoped by role
            'bulk_generate_qr_code', // Scoped by role
            'manage_charging_point_status', // Scoped by role
            'manage_connectors', // Scoped by role
            'assign_pricing_plan_to_charging_point', // Scoped by role
            'manage_geolocation', // Scoped by role
            'schedule_maintenance', // Scoped by role
            'simulate_remote_control', // Scoped by role
            'view_public_offer_page', // Public access
            'batch_assign_pricing_plan', // Scoped by role

            // Offers Management
            'view_offers',
            'create_offers',
            'edit_offers',
            'delete_offers',

            // Pricing Plans Management
            'view_pricing_plans', // Scoped by role (public for users)
            'create_pricing_plans', // Scoped by role
            'edit_pricing_plans', // Scoped by role
            'delete_pricing_plans', // Scoped by role
            'activate_pricing_plans', // Scoped by role
            'set_default_pricing_plan', // Scoped by role
            'calculate_price', // Public access (API)
            'get_plan_for_charging_point', // Public access (API)
            'get_public_offers', // Public access (API)

            // Business Profiles Management
            'view_business_profiles', // Scoped by role
            'create_business_profiles',
            'edit_business_profiles', // Scoped by role
            'delete_business_profiles', // Scoped by role
            'manage_partner_rates', // Scoped by role

            // Transactions Management
            'view_transactions', // Scoped by role
            'start_charging_session', // Public access (API)
            'stop_charging_session', // Public access (API)
            'view_active_charges', // Scoped by user/guest
            'view_charge_history', // Scoped by user
            'view_charge_details', // Scoped by role
            'generate_receipt', // Scoped by user
            'export_transactions', // Scoped by role
            'manage_refunds',

            // Commission Management
            'view_commission_dashboard', // Scoped by role
            'view_commission_plans', // Scoped by role
            'create_commission_plans',
            'edit_commission_plans',
            'delete_commission_plans',
            'toggle_commission_plan_active',
            'set_default_commission_plan',
            'recalculate_commissions',
            'view_transaction_commissions', // Scoped by role
            'mark_commission_as_paid',
            'mark_multiple_commissions_as_paid',
            'view_commission_reports', // Scoped by role
            'export_commissions', // Scoped by role

            // Connectors Module
            'view_connectors', // Scoped by role
            'create_connectors', // Scoped by role
            'edit_connectors', // Scoped by role
            'delete_connectors', // Scoped by role
            'manage_connector_status', // Scoped by role
            'view_meter_reading', // Scoped by role

            // Reports Module
            'view_reports', // Scoped by role
            'create_reports', // Scoped by role
            'edit_reports', // Scoped by role
            'delete_reports', // Scoped by role
            'generate_report_pdf', // Scoped by role
            'export_report_csv', // Scoped by role

            // Notifications Module
            'view_admin_notifications', // Scoped by role
            'mark_notification_as_read', // Scoped by user
            'mark_all_notifications_as_read', // Scoped by user
            'delete_notifications',
            'manage_notification_preferences', // Scoped by user

            // Settings Module
            'view_settings',
            'manage_general_settings',
            'manage_security_settings',
            'manage_api_settings',
            'manage_commission_settings',
            'manage_email_settings',

            // Station Management Module
            'view_stations', // Scoped by role
            'create_stations', // Scoped by role
            'edit_stations', // Scoped by role
            'delete_stations', // Scoped by role
            'manage_station_location', // Scoped by role

            // User Management Module
            'view_users', // Scoped by role
            'create_users', // Scoped by role
            'edit_users', // Scoped by role
            'delete_users', // Scoped by role
            'manage_user_roles',
            'manage_user_password', // Scoped by role
            'activate_users', // Scoped by role
            'deactivate_users', // Scoped by role
            'manage_user_balance',
            'manage_user_preferences', // Scoped by user

            // Permissions utilisateur final (public/user)
            'view_user_dashboard',
            'view_public_charging_points',
            'view_charging_points_offer',
            'select_pricing_plan',
            'start_charging_session',
            'view_active_charges',
            'stop_charging_session',
            'view_charge_history',
            'view_charge_details',
            'generate_receipt',
            'calculate_price',
            'get_plan_for_charging_point',
            'get_public_offers',
            'view_profile',
            'update_profile',
            'manage_notification_preferences',
            'view_pricing_plans', // Can view public pricing plans
            'view_transactions', // Can view their own transactions
            'view_connectors', // Can view public connector info
            'view_reports', // Can view their own usage reports
            'create_reports', // Can create their own usage reports
            'edit_reports', // Can edit their own usage reports
            'delete_reports', // Can delete their own usage reports
            'generate_report_pdf', // Scoped
            'export_report_csv', // Scoped
            'view_stations', // Can view public stations
            'view_offers', // Can view public offers
            
            // Reservations
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'manage_reservations',
            
            // Admin Reservations Management
            'view_admin_reservations',
            'edit_admin_reservations',
            'delete_admin_reservations',
            'view_admin_reservation_details',
            'confirm_admin_reservations',
            'reject_admin_reservations',
            'manage_admin_reservations',
            
            // Admin Transactions Management
            'view_admin_transactions',
            'edit_admin_transactions',
            'delete_admin_transactions',
            'view_admin_transaction_details',
            'manage_admin_transactions',
            'export_admin_transactions',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create roles and assign permissions
        $adminPermissions = $permissions; // Admin gets all permissions

        $integratorPermissions = [
            'view_integrator_dashboard',
            'view_integrators', // Can view their own integrator profile
            'edit_integrators', // Can edit their own integrator profile
            'view_partners', // Can view their partners
            'create_partners',
            'edit_partners', // Can edit their partners
            'delete_partners', // Can delete their partners
            'bulk_update_partners',
            'export_partners',
            'view_groups', // Can view groups under their partners
            'create_groups',
            'edit_groups', // Can edit groups under their partners
            'delete_groups', // Can delete groups under their partners
            'assign_charging_points_to_group', // Scoped
            'manage_group_location', // Scoped
            'bulk_update_groups', // Scoped
            'view_charging_points', // Can view charging points under their partners/groups
            'create_charging_points',
            'edit_charging_points', // Can edit charging points under their partners/groups
            'delete_charging_points', // Can delete charging points under their partners/groups
            'generate_qr_code', // Scoped
            'regenerate_qr_code', // Scoped
            'download_qr_code', // Scoped
            'bulk_generate_qr_code', // Scoped
            'manage_charging_point_status', // Scoped
            'manage_connectors', // Scoped
            'assign_pricing_plan_to_charging_point', // Scoped
            'manage_geolocation', // Scoped
            'schedule_maintenance', // Scoped
            'simulate_remote_control', // Scoped
            'batch_assign_pricing_plan', // Scoped
            'view_pricing_plans', // Can view pricing plans applicable to their network
            'create_pricing_plans', // Can create pricing plans for their network
            'edit_pricing_plans', // Can edit pricing plans for their network
            'delete_pricing_plans', // Can delete pricing plans for their network
            'activate_pricing_plans', // Scoped
            'set_default_pricing_plan', // Scoped
            'view_business_profiles', // Can view business profiles applicable to their network
            'edit_business_profiles', // Can edit business profiles they own
            'manage_partner_rates', // Scoped
            'view_transactions', // Can view transactions within their network
            'export_transactions', // Scoped
            'view_commission_dashboard', // Scoped
            'view_commission_plans', // Can view commission plans applicable to their network
            'create_commission_plans', // Can create commission plans for their network
            'edit_commission_plans', // Can edit commission plans for their network
            'delete_commission_plans', // Can delete commission plans for their network
            'toggle_commission_plan_active', // Scoped
            'set_default_commission_plan', // Scoped
            'recalculate_commissions', // Scoped
            'view_transaction_commissions', // Scoped
            'export_commissions', // Scoped
            'view_connectors', // Scoped
            'create_connectors', // Scoped
            'edit_connectors', // Scoped
            'delete_connectors', // Scoped
            'manage_connector_status', // Scoped
            'view_meter_reading', // Scoped
            'view_reports', // Scoped
            'create_reports', // Scoped
            'edit_reports', // Scoped
            'delete_reports', // Scoped
            'generate_report_pdf', // Scoped
            'export_report_csv', // Scoped
            'view_admin_notifications', // Can view notifications relevant to their role
            'mark_notification_as_read',
            'mark_all_notifications_as_read',
            'view_stations', // Can view stations under their partners/groups
            'create_stations',
            'edit_stations', // Can edit stations under their partners/groups
            'delete_stations', // Can delete stations under their partners/groups
            'view_reservations', // Can view reservations in their network
            'manage_reservations', // Can manage reservations in their network
            'manage_station_location', // Scoped
            'view_users', // Can view users under their integrator
            'create_users', // Can create users under their integrator
            'edit_users', // Can edit users under their integrator
            'delete_users', // Can delete users under their integrator
            'manage_user_password', // Scoped
            'activate_users', // Scoped
            'deactivate_users', // Scoped
            'view_profile',
            'update_profile',
            'manage_notification_preferences',
            'view_offers',
            'create_offers',
            'edit_offers',
            'delete_offers',
        ];

        $partnerPermissions = [
            'view_partner_dashboard',
            'view_partners', // Can view their own partner profile
            'edit_partners', // Can edit their own partner profile
            'view_groups', // Can view groups under their partner
            'create_groups',
            'edit_groups', // Can edit groups under their partner
            'delete_groups', // Can delete groups under their partner
            'assign_charging_points_to_group', // Scoped
            'manage_group_location', // Scoped
            'bulk_update_groups', // Scoped
            'view_charging_points', // Can view charging points under their partner/groups
            'create_charging_points',
            'edit_charging_points', // Can edit charging points under their partner/groups
            'delete_charging_points', // Can delete charging points under their partner/groups
            'generate_qr_code', // Scoped
            'regenerate_qr_code', // Scoped
            'download_qr_code', // Scoped
            'bulk_generate_qr_code', // Scoped
            'manage_charging_point_status', // Scoped
            'manage_connectors', // Scoped
            'assign_pricing_plan_to_charging_point', // Scoped
            'manage_geolocation', // Scoped
            'schedule_maintenance', // Scoped
            'simulate_remote_control', // Scoped
            'batch_assign_pricing_plan', // Scoped
            'view_pricing_plans', // Can view pricing plans applicable to their network
            'create_pricing_plans', // Can create pricing plans for their network
            'edit_pricing_plans', // Can edit pricing plans for their network
            'delete_pricing_plans', // Can delete pricing plans for their network
            'activate_pricing_plans', // Scoped
            'set_default_pricing_plan', // Scoped
            'view_business_profiles', // Can view business profiles applicable to their network
            'edit_business_profiles', // Can edit business profiles they own
            'view_transactions', // Can view transactions within their network
            'export_transactions', // Scoped
            'view_commission_dashboard', // Scoped
            'view_commission_plans', // Can view commission plans applicable to their network
            'create_commission_plans', // Can create commission plans for their network
            'edit_commission_plans', // Can edit commission plans for their network
            'delete_commission_plans', // Can delete commission plans for their network
            'toggle_commission_plan_active', // Scoped
            'set_default_commission_plan', // Scoped
            'recalculate_commissions', // Scoped
            'view_transaction_commissions', // Scoped
            'export_commissions', // Scoped
            'view_connectors', // Scoped
            'create_connectors', // Scoped
            'edit_connectors', // Scoped
            'delete_connectors', // Scoped
            'manage_connector_status', // Scoped
            'view_meter_reading', // Scoped
            'view_reports', // Scoped
            'create_reports', // Scoped
            'edit_reports', // Scoped
            'delete_reports', // Scoped
            'generate_report_pdf', // Scoped
            'export_report_csv', // Scoped
            'view_admin_notifications', // Can view notifications relevant to their role
            'mark_notification_as_read',
            'mark_all_notifications_as_read',
            'view_stations', // Can view stations under their partner/groups
            'create_stations',
            'edit_stations', // Can edit stations under their partner/groups
            'delete_stations', // Can delete stations under their partner/groups
            'manage_station_location', // Scoped
            'view_reservations', // Can view reservations in their network
            'manage_reservations', // Can manage reservations in their network
            'view_users', // Can view users under their partner
            'create_users', // Can create users under their partner
            'edit_users', // Can edit users under their partner
            'delete_users', // Can delete users under their partner
            'manage_user_password', // Scoped
            'activate_users', // Scoped
            'deactivate_users', // Scoped
            'view_profile',
            'update_profile',
            'manage_notification_preferences',
            'view_offers',
            'create_offers',
            'edit_offers',
            'delete_offers',
        ];

        $userPermissions = [
            'view_user_dashboard',
            'view_public_charging_points',
            'view_charging_points_offer',
            'select_pricing_plan',
            'start_charging_session',
            'view_active_charges',
            'stop_charging_session',
            'view_charge_history',
            'view_charge_details',
            'generate_receipt',
            'calculate_price',
            'get_plan_for_charging_point',
            'get_public_offers',
            'view_profile',
            'update_profile',
            'manage_notification_preferences',
            'view_pricing_plans', // Can view public pricing plans
            'view_transactions', // Can view their own transactions
            'view_connectors', // Can view public connector info
            'view_reports', // Can view their own usage reports
            'create_reports', // Can create their own usage reports
            'edit_reports', // Can edit their own usage reports
            'delete_reports', // Can delete their own usage reports
            'generate_report_pdf', // Scoped
            'export_report_csv', // Scoped
            'view_stations', // Can view public stations
            'view_offers', // Can view public offers
            'view_reservations', // Can view their own reservations
            'create_reservations', // Can create reservations
            'edit_reservations', // Can edit their own reservations
            'cancel_reservations', // Can cancel their own reservations
        ];

        // Charger les permissions d'opérateur depuis la config si disponibles
        $operatorConfigPermissions = config('permissions.roles.operator.permissions', []);
        $operatorPermissions = is_array($operatorConfigPermissions) ? $operatorConfigPermissions : [];
        
        // Filtrer les permissions d'opérateur pour ne garder que celles qui existent
        $operatorPermissions = array_intersect($operatorPermissions, $permissions);

        // Admin permissions include all permissions plus specific admin permissions
        $adminPermissionsExtended = array_merge($adminPermissions, [
            'view_admin_reservations',
            'edit_admin_reservations', 
            'delete_admin_reservations',
            'view_admin_reservation_details',
            'confirm_admin_reservations',
            'reject_admin_reservations',
            'manage_admin_reservations',
            'view_admin_transactions',
            'edit_admin_transactions',
            'delete_admin_transactions',
            'view_admin_transaction_details',
            'manage_admin_transactions',
            'export_admin_transactions',
        ]);

        $roles = [
            'super_admin' => $adminPermissionsExtended,
            'admin' => $adminPermissionsExtended, // Add admin role with all permissions including admin-specific ones
            'integrator' => $integratorPermissions,
            'partner' => $partnerPermissions,
            'user' => $userPermissions,
            'operator' => $operatorPermissions,
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            
            // Filtrer les permissions pour ne garder que celles qui existent
            $validPermissions = array_intersect($rolePermissions, $permissions);
            
            $role->givePermissionTo($validPermissions);
        }
    }
}