<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\BusinessProfile;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class BusinessProfileCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    #[Test]
    public function test_admin_can_create_business_profile()
    {
        $this->actingAs($this->adminUser);
        // S’assurer que l’admin a la permission requise
        $this->adminUser->givePermissionTo('create_business_profiles');
        
        // Debug: vérifier que l'utilisateur a le rôle admin
        $this->assertTrue($this->adminUser->hasRole('admin'), 'User should have admin role');
        $this->assertTrue($this->adminUser->can('create_business_profiles'), 'User should have create_business_profiles permission');
        
        // Créer les dépendances nécessaires
        $partner = \App\Models\Partner::factory()->create();
        $integrator = \App\Models\Integrator::factory()->create();

        // Debug : vérifier la présence en base
        $this->assertDatabaseHas('partners', ['id' => $partner->id]);
        $this->assertDatabaseHas('integrators', ['id' => $integrator->id]);
        
        // Données simples pour le test - correspondant à BusinessProfileRequest
        $profileData = [
            'name' => 'Test Business Profile',
            'description' => 'Description de test',
            'is_public' => 1,
            'is_active' => 1,
            'target_audience' => ['pro', 'collectivite'],
            'maintenance_fee_type' => 'monthly',
            'maintenance_fee_amount' => 100,
            'transaction_fee_type' => ['fixed', 'percentage'],
            'transaction_fee_fixed_amount' => 2.5,
            'transaction_fee_percentage' => 5,
            'charge_fee_fixed_amount' => 1.5,
            'charge_fee_percentage' => 2,
            'terminal_fee_amount' => 10,
            'terminal_fee_period' => 'monthly',
            'base_fee_amount' => 5,
            'operator_commission' => 10,
            'integrator_commission' => 15,
            'owner_commission' => 20,
            'maintenance_fee' => 0,
            'maintenance_period' => 'monthly',
            'transaction_fee' => 0,
            'recharge_fee' => 0,
            'active_terminal_fee' => 0,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
        ];
        
        // Vérification explicite de l’authentification
        $this->assertTrue(auth()->check(), 'L’utilisateur doit être authentifié avant la requête POST');
        $response = $this->post(route('business-profiles.store'), $profileData);
        
        // Debug: vérifier le statut de la réponse
        if ($response->getStatusCode() !== 302) {
            // Si ce n'est pas une redirection, il y a un problème
            $this->fail('Expected redirect (302), got ' . $response->getStatusCode() . '. Content: ' . $response->getContent());
        }
        
        // Vérifier que la création a réussi
        $createdProfile = BusinessProfile::latest()->first();
        if ($createdProfile) {
            $response->assertRedirect(route('business-profiles.show', $createdProfile));
            $this->assertDatabaseHas('business_profiles', [
                'id' => $createdProfile->id,
                'name' => $profileData['name'],
            ]);
        } else {
            // Si la création a échoué, vérifier les erreurs de validation
            if ($response->getStatusCode() === 422) {
                $response->assertSessionHasErrors();
            } else {
                // Debug: afficher le contenu de la réponse pour comprendre
                $this->fail('Creation failed without errors. Response status: ' . $response->getStatusCode() . ', Content: ' . $response->getContent());
            }
        }
    }

    #[Test]
    public function admin_cannot_create_business_profile_with_missing_required_fields()
    {
        $this->actingAs($this->adminUser);
        $profileData = [
            // 'name' manquant
            'target_audience' => ['pro'],
            'transaction_fee_type' => ['fixed'],
        ];
        $response = $this->post(route('business-profiles.store'), $profileData);
        $response->assertSessionHasErrors(['name']);
    }

    #[Test]
    public function admin_can_update_business_profile()
    {
        $this->actingAs($this->adminUser);
        
        // Créer les dépendances et le profil
        $partner = \App\Models\Partner::factory()->create();
        $integrator = \App\Models\Integrator::factory()->create();
        $profile = BusinessProfile::factory()->create([
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
        ]);
        
        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Nouvelle description',
            'is_public' => 0,
            'is_active' => 0,
            'target_audience' => ['collectivite'],
            'maintenance_fee_type' => 'yearly',
            'maintenance_fee_amount' => 200,
            'transaction_fee_type' => ['percentage'],
            'transaction_fee_fixed_amount' => 0,
            'transaction_fee_percentage' => 10,
            'charge_fee_fixed_amount' => 0,
            'charge_fee_percentage' => 5,
            'terminal_fee_amount' => 20,
            'terminal_fee_period' => 'yearly',
        ];
        
        $response = $this->put(route('business-profiles.update', $profile), $updateData);
        $response->assertRedirect(route('business-profiles.show', $profile));
        $this->assertDatabaseHas('business_profiles', [
            'id' => $profile->id,
            'name' => 'Updated Name',
            'is_public' => 0,
            'is_active' => 0,
        ]);
    }

    #[Test]
    public function admin_can_delete_business_profile()
    {
        $this->actingAs($this->adminUser);
        $profile = BusinessProfile::factory()->create();
        $response = $this->delete(route('business-profiles.destroy', $profile));
        $response->assertRedirect(route('business-profiles.index'));
        $this->assertDatabaseMissing('business_profiles', ['id' => $profile->id]);
    }

    #[Test]
    public function admin_can_view_business_profile_details()
    {
        $this->actingAs($this->adminUser);
        
        // Créer les dépendances et le profil
        $partner = \App\Models\Partner::factory()->create();
        $integrator = \App\Models\Integrator::factory()->create();
        $profile = BusinessProfile::factory()->create([
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
        ]);
        
        // Debug: vérifier que le profil a été créé
        if (!$profile) {
            $this->fail('BusinessProfile factory failed to create profile');
        }
        
        $response = $this->get(route('business-profiles.show', $profile));
        $response->assertOk();
        $response->assertViewIs('business-profiles.show');
        $response->assertViewHas('businessProfile', function($viewProfile) use ($profile) {
            return $viewProfile->id === $profile->id;
        });
    }
} 