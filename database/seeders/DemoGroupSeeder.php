<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;

class DemoGroupSeeder extends Seeder
{
    public function run()
    {
        // Get a demo partner and integrator
        $partner = Partner::first();
        $integrator = Integrator::first();
        
        // Find any admin user (try multiple possible emails, then any admin role)
        $adminUser = \App\Models\User::whereIn('email', [
            'admin@evoncharge.com',
            'admin@demo.com',
            'admin@evon.com',
            'admin@evonpower.com'
        ])->first();
        
        // If still not found, get any user with admin role
        if (!$adminUser) {
            $adminUser = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super-admin']);
            })->first();
        }
        
        if (!$adminUser) {
            throw new \Exception('Aucun utilisateur admin trouvé. Veuillez exécuter DemoUserSeeder avant DemoGroupSeeder.');
        }
        
        $partnerUser = \App\Models\User::where('email', 'partner@evoncharge.com')->first();
        $integratorUser = \App\Models\User::where('email', 'integrator@evoncharge.com')->first();

        // Un seul groupe de démo
        $groups = [
            [
                'name' => 'Downtown Charging Group',
                'description' => 'Group of stations in the city center',
                'type' => 'public',
                'partner_id' => $partner ? $partner->id : null,
                'integrator_id' => $integrator ? $integrator->id : null,
                'user_id' => $adminUser->id,
            ],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate(
                ['name' => $group['name']],
                [
                    'description' => $group['description'],
                    'type' => $group['type'],
                    'partner_id' => $group['partner_id'],
                    'integrator_id' => $group['integrator_id'],
                    'user_id' => $group['user_id'],
                ]
            );
        }

    }
}