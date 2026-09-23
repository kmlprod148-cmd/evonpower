<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DemoUserSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks to allow truncation (database-agnostic)
        $driver = DB::getDriverName();
        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }
        } catch (\Exception $e) {
            // Ignore if foreign key checks are not supported
            \Log::warning('Could not disable foreign key checks: ' . $e->getMessage());
        }
        
        // Ne pas truncate pour préserver les données existantes, juste créer/mettre à jour les comptes de démo
        
        // Créer l'admin d'abord (nécessaire pour créer les autres)
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin Démo',
                'password' => Hash::make('demo123'),
                'is_active' => true,
                'email_verified_at' => now(),
                'balance' => 10000.00,
                'currency' => 'EUR',
            ]
        );
        
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        // Créer l'intégrateur (nécessite un admin comme créateur)
        $integratorUser = User::updateOrCreate(
            ['email' => 'integrateur@demo.com'],
            [
                'name' => 'Intégrateur Démo',
                'password' => Hash::make('demo123'),
                'is_active' => true,
                'email_verified_at' => now(),
                'balance' => 5000.00,
                'currency' => 'EUR',
                'created_by' => $adminUser->id,
            ]
        );
        
        if (!$integratorUser->hasRole('integrator')) {
            $integratorUser->assignRole('integrator');
        }

        // Créer l'enregistrement Integrator pour l'intégrateur
        // Utiliser uniquement DB::table pour contourner complètement les validations du modèle
        $integratorData = DB::table('integrators')
            ->where('user_id', $integratorUser->id)
            ->first();
        
        if (!$integratorData) {
            // Créer directement via DB pour éviter les validations du modèle
            $integratorId = DB::table('integrators')->insertGetId([
                'user_id' => $integratorUser->id,
                'name' => 'Intégrateur Démo',
                'email' => 'integrateur@demo.com',
                'is_active' => true,
                'created_by' => $adminUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Mettre à jour si nécessaire
            DB::table('integrators')
                ->where('id', $integratorData->id)
                ->update([
                    'name' => 'Intégrateur Démo',
                    'email' => 'integrateur@demo.com',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                    'updated_at' => now(),
                ]);
            $integratorId = $integratorData->id;
        }

        // Mettre à jour l'integrator_id de l'utilisateur intégrateur
        $integratorUser->update(['integrator_id' => $integratorId]);
        
        // Rafraîchir l'intégrateur pour s'assurer que tout est à jour
        $integratorUser->refresh();
        
        // Vérifier que l'intégrateur a bien son rôle
        if (!$integratorUser->hasRole('integrator')) {
            $integratorUser->assignRole('integrator');
        }

        // Créer l'opérateur (nécessite un intégrateur comme créateur)
        $operatorUser = User::updateOrCreate(
            ['email' => 'operateur@demo.com'],
            [
                'name' => 'Opérateur Démo',
                'password' => Hash::make('demo123'),
                'is_active' => true,
                'email_verified_at' => now(),
                'balance' => 2000.00,
                'currency' => 'EUR',
                'integrator_id' => $integratorId,
                'created_by' => $integratorUser->id,
            ]
        );
        
        if (!$operatorUser->hasRole('operator')) {
            $operatorUser->assignRole('operator');
        }

        // Re-enable foreign key checks (database-agnostic)
        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        } catch (\Exception $e) {
            // Ignore if foreign key checks are not supported
            \Log::warning('Could not enable foreign key checks: ' . $e->getMessage());
        }
        
        \Log::info('Comptes de démo créés avec succès :');
        \Log::info('- Admin: admin@demo.com / demo123');
        \Log::info('- Intégrateur: integrateur@demo.com / demo123');
        \Log::info('- Opérateur: operateur@demo.com / demo123');
    }
}