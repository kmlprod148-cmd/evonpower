<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\CommissionTransaction;

class OrderTransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function une_commande_et_les_commissions_sont_enregistrees_via_api()
    {
        // Préparation des données
        $user = User::factory()->create();
        $plan = PricingPlan::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => 100.00,
            'commissions' => [
                'admin' => 10.00,
                'integrator' => 5.00,
                'partner' => 2.50
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'order' => ['id', 'user_id', 'plan_id', 'charging_point_id', 'amount', 'status', 'created_at', 'updated_at'],
                'transaction' => ['id', 'order_id', 'user_id', 'pricing_plan_id', 'charging_point_id', 'price_total', 'admin_commission', 'integrator_commission', 'partner_commission', 'status', 'start_timestamp', 'stop_timestamp', 'created_at', 'updated_at']
            ]);

        // Vérifie la présence en base
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => 100.00
        ]);

        $transaction = Transaction::where('order_id', $response['order']['id'])->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(10.00, $transaction->admin_commission);
        $this->assertEquals(5.00, $transaction->integrator_commission);
        $this->assertEquals(2.50, $transaction->partner_commission);

        // Vérifie les commissions individuelles
        $this->assertDatabaseHas('commission_transactions', [
            'transaction_id' => $transaction->id,
            'type' => 'admin',
            'amount' => 10.00
        ]);
        $this->assertDatabaseHas('commission_transactions', [
            'transaction_id' => $transaction->id,
            'type' => 'integrator',
            'amount' => 5.00
        ]);
        $this->assertDatabaseHas('commission_transactions', [
            'transaction_id' => $transaction->id,
            'type' => 'partner',
            'amount' => 2.50
        ]);
    }
} 