<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Partner;
use App\Models\User;
use App\Models\BusinessProfile;

class DemoPartnerSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();
        // Récupérer un intégrateur existant pour lier au partner
        $integrator = \App\Models\Integrator::first();
        // Get the demo partner user and a business profile
        $user = User::where('email', 'partner@evoncharge.com')->first();
        $businessProfile = BusinessProfile::where('name', 'GreenCharge Partner')->first();

        // Create a demo partner
        $partner = Partner::create([
            'name' => $faker->company,
            'contact_name' => $faker->name,
            'type' => $faker->randomElement(['Exploitant', 'Propriétaire', 'Intégrateur']),
            'email' => $faker->unique()->safeEmail,
            'phone' => $faker->phoneNumber,
            'city' => $faker->city,
            'address' => $faker->address,
            'postal_code' => $faker->postcode,
            'country' => $faker->country,
            'website' => $faker->url,
            'logo' => null,
            'description' => $faker->sentence,
            'is_active' => true,
            'integrator_id' => $integrator->id,
            // Champs supprimés car absents de la table : contact_title, contact_email, contact_phone, notes, tax_id, company_registration, language
        ]);

    }
}