<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class EnhancedDashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test que le dashboard nécessite une authentification
     */
    public function test_dashboard_requires_authentication()
    {
        $response = $this->get('/dashboard/enhanced');
        
        $response->assertRedirect('/login');
    }

    /**
     * Test que le dashboard s'affiche pour un utilisateur authentifié
     */
    public function test_dashboard_displays_for_authenticated_user()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard-enhanced');
    }

    /**
     * Test que les statistiques sont passées à la vue
     */
    public function test_dashboard_passes_stats_to_view()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced');
        
        $response->assertViewHas('stats');
        $response->assertViewHas('chargingPoints');
        $response->assertViewHas('recentTransactions');
        $response->assertViewHas('chartData');
        $response->assertViewHas('topStations');
        $response->assertViewHas('revenueStats');
    }

    /**
     * Test que l'API temps réel retourne du JSON
     */
    public function test_realtime_api_returns_json()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced/realtime');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'activeChargingPoints',
            'timestamp',
        ]);
    }

    /**
     * Test que les statistiques sont calculées correctement
     */
    public function test_stats_calculation()
    {
        $user = User::factory()->create();
        
        // Créer des données de test
        ChargingPoint::factory()->count(5)->create([
            'status' => 'online',
            'user_id' => $user->id,
        ]);
        
        ChargingPoint::factory()->count(2)->create([
            'status' => 'offline',
            'user_id' => $user->id,
        ]);
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced/realtime');
        
        $data = $response->json();
        
        $this->assertEquals(7, $data['stats']['totalPoints']);
        $this->assertEquals(5, $data['stats']['onlinePoints']);
    }

    /**
     * Test que les clients simples sont redirigés
     */
    public function test_simple_clients_are_redirected()
    {
        $user = User::factory()->create();
        $user->assignRole('user'); // Rôle client simple
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced');
        
        $response->assertRedirect(route('reservations.index'));
    }

    /**
     * Test que l'admin voit toutes les données
     */
    public function test_admin_sees_all_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        // Créer des bornes de différents utilisateurs
        ChargingPoint::factory()->count(3)->create(['user_id' => $admin->id]);
        ChargingPoint::factory()->count(2)->create(); // Autres utilisateurs
        
        $response = $this->actingAs($admin)->get('/dashboard/enhanced/realtime');
        
        $data = $response->json();
        
        // L'admin doit voir toutes les 5 bornes
        $this->assertEquals(5, $data['stats']['totalPoints']);
    }

    /**
     * Test que l'opérateur voit uniquement ses bornes
     */
    public function test_operator_sees_only_own_charging_points()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        // Créer des bornes pour l'opérateur
        ChargingPoint::factory()->count(3)->create(['user_id' => $operator->id]);
        
        // Créer des bornes pour d'autres utilisateurs
        ChargingPoint::factory()->count(2)->create();
        
        $response = $this->actingAs($operator)->get('/dashboard/enhanced/realtime');
        
        $data = $response->json();
        
        // L'opérateur doit voir uniquement ses 3 bornes
        $this->assertEquals(3, $data['stats']['totalPoints']);
    }

    /**
     * Test que les données de graphique sont correctement formatées
     */
    public function test_chart_data_is_properly_formatted()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced');
        
        $chartData = $response->viewData('chartData');
        
        $this->assertArrayHasKey('hourly', $chartData);
        $this->assertArrayHasKey('weekly', $chartData);
        $this->assertArrayHasKey('monthly', $chartData);
        
        $this->assertArrayHasKey('labels', $chartData['weekly']);
        $this->assertArrayHasKey('data', $chartData['weekly']);
    }

    /**
     * Test que les transactions récentes sont limitées
     */
    public function test_recent_transactions_are_limited()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['user_id' => $user->id]);
        
        // Créer 15 transactions
        Transaction::factory()->count(15)->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced');
        
        $transactions = $response->viewData('recentTransactions');
        
        // Doit être limité à 10
        $this->assertLessThanOrEqual(10, $transactions->count());
    }

    /**
     * Test que les bornes actives affichent les bonnes données
     */
    public function test_active_charging_points_display_correct_data()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create([
            'user_id' => $user->id,
            'status' => 'online',
        ]);
        
        // Créer une transaction en cours
        Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'status' => 'in_progress',
            'energy_consumed_wh' => 25000, // 25 kWh
        ]);
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced/realtime');
        
        $data = $response->json();
        
        $this->assertGreaterThan(0, count($data['activeChargingPoints']));
        $this->assertEquals(25, $data['activeChargingPoints'][0]['energy']);
    }

    /**
     * Test que les revenus sont calculés correctement
     */
    public function test_revenue_calculation()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['user_id' => $user->id]);
        
        // Créer des transactions pour aujourd'hui
        Transaction::factory()->count(3)->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'amount' => 100,
            'processed_at' => now(),
        ]);
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced/realtime');
        
        $data = $response->json();
        
        $this->assertEquals(300, $data['stats']['todayRevenue']);
    }

    /**
     * Test que le taux de disponibilité est calculé
     */
    public function test_availability_rate_calculation()
    {
        $user = User::factory()->create();
        
        // 8 bornes en ligne sur 10 = 80%
        ChargingPoint::factory()->count(8)->create([
            'user_id' => $user->id,
            'status' => 'online',
        ]);
        
        ChargingPoint::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'offline',
        ]);
        
        $response = $this->actingAs($user)->get('/dashboard/enhanced/realtime');
        
        $data = $response->json();
        
        $this->assertEquals(80, $data['stats']['availabilityRate']);
    }
}

