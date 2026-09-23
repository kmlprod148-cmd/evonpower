<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TransactionCalculator;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Integrator;
use App\Models\TransactionRepartition;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TransactionCalculator();
    }

    /** @test */
    public function it_calculates_admin_only_scenario_when_operator_created_by_admin()
    {
        // Créer un business profile avec des frais admin
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee_config' => json_encode(['fixed' => 2.50]),
            'charge_fee_config' => json_encode(['fixed_amount' => 1.00]),
            'terminal_fee_amount' => 0.50,
            'base_fee_amount' => 0.00,
            'admin_fee_fixed' => 0.00, // Pas de frais fixes spécifiques
            'admin_fee_percentage' => 0.00, // Pas de pourcentage, utiliser les frais fixes de transaction/recharge
        ]);

        // Créer un point de charge avec ce business profile
        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => null, // Pas d'intégrateur
        ]);

        // Créer une transaction
        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 10.00,
        ]);

        // Calculer la répartition
        $result = $this->calculator->calculate($transaction);

        // Vérifications
        $this->assertEquals(10.00, $result['total']);
        $this->assertEquals(4.00, $result['admin']); // 2.50 + 1.00 + 0.50
        $this->assertEquals(0.00, $result['integrator']);
        $this->assertEquals(6.00, $result['operator']); // 10.00 - 4.00
    }

    /** @test */
    public function it_calculates_admin_percentage_scenario()
    {
        // Créer un business profile avec pourcentage admin
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee' => 1.00,
            'recharge_fee' => 0.50,
            'active_terminal_fee' => 0.25,
            'admin_fee_percentage' => 15.0, // 15%
        ]);

        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => null,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 20.00,
        ]);

        $result = $this->calculator->calculate($transaction);

        // Le pourcentage (15% de 20€ = 3€) est supérieur aux frais fixes (1.75€)
        $this->assertEquals(20.00, $result['total']);
        $this->assertEquals(3.00, $result['admin']); // max(3.00, 1.75) = 3.00
        $this->assertEquals(0.00, $result['integrator']);
        $this->assertEquals(17.00, $result['operator']);
    }

    /** @test */
    public function it_calculates_integrator_plus_operator_scenario()
    {
        // Créer un business profile pour l'intégrateur
        $integratorBp = BusinessProfile::factory()->create([
            'integrator_fee_percentage' => 20.0, // 20% pour l'intégrateur
        ]);

        // Créer un intégrateur
        $integrator = Integrator::factory()->create([
            'business_profile_id' => $integratorBp->id,
        ]);

        // Créer un business profile pour le point de charge
        $chargingPointBp = BusinessProfile::factory()->create([
            'transaction_fee' => 1.00,
            'recharge_fee' => 0.50,
            'other_fees' => 0.25,
        ]);

        // Créer un point de charge avec intégrateur
        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $chargingPointBp->id,
            'integrator_id' => $integrator->id,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 15.00,
        ]);

        $result = $this->calculator->calculate($transaction);

        $this->assertEquals(15.00, $result['total']);
        $this->assertEquals(1.75, $result['admin']); // 1.00 + 0.50 + 0.25
        $this->assertEquals(3.00, $result['integrator']); // 20% de 15€
        $this->assertEquals(10.25, $result['operator']); // 15.00 - 1.75 - 3.00
    }

    /** @test */
    public function it_calculates_charging_point_created_by_integrator_scenario()
    {
        // Créer un business profile pour l'intégrateur
        $integratorBp = BusinessProfile::factory()->create([
            'integrator_fee_percentage' => 25.0, // 25% pour l'intégrateur
        ]);

        // Créer un utilisateur intégrateur
        $integratorUser = User::factory()->create();
        $integratorUser->assignRole('integrator');

        // Créer un point de charge créé directement par l'intégrateur
        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $integratorBp->id,
            'user_id' => $integratorUser->id, // Créé par l'intégrateur
            'integrator_id' => null,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 12.00,
        ]);

        $result = $this->calculator->calculate($transaction);

        $this->assertEquals(12.00, $result['total']);
        $this->assertEquals(0.00, $result['admin']); // Pas de frais admin définis
        $this->assertEquals(3.00, $result['integrator']); // 25% de 12€
        $this->assertEquals(9.00, $result['operator']); // 12.00 - 3.00
    }

    /** @test */
    public function it_handles_missing_business_profile_gracefully()
    {
        // Créer un point de charge sans business profile
        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => null,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 8.00,
        ]);

        $result = $this->calculator->calculate($transaction);

        // Fallback: tout va à l'opérateur
        $this->assertEquals(8.00, $result['total']);
        $this->assertEquals(0.00, $result['admin']);
        $this->assertEquals(0.00, $result['integrator']);
        $this->assertEquals(8.00, $result['operator']);
    }

    /** @test */
    public function it_prevents_negative_operator_share()
    {
        // Scénario où les frais admin + intégrateur dépassent le montant total
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee' => 5.00,
            'recharge_fee' => 3.00,
            'active_terminal_fee' => 2.00, // Total admin: 10€
        ]);

        $integratorBp = BusinessProfile::factory()->create([
            'integrator_fee_percentage' => 50.0, // 50% = 5€ sur 10€
        ]);

        $integrator = Integrator::factory()->create([
            'business_profile_id' => $integratorBp->id,
        ]);

        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $integrator->id,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 10.00, // Total: 10€, Admin: 10€, Integrator: 5€ = -5€ pour operator
        ]);

        $result = $this->calculator->calculate($transaction);

        $this->assertEquals(10.00, $result['total']);
        $this->assertEquals(10.00, $result['admin']);
        $this->assertEquals(5.00, $result['integrator']);
        $this->assertEquals(0.00, $result['operator']); // Pas de montant négatif
    }

    /** @test */
    public function it_validates_repartition_consistency()
    {
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee' => 1.00,
            'recharge_fee' => 0.50,
        ]);

        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 5.00,
        ]);

        $result = $this->calculator->calculate($transaction);

        // Vérifier que la répartition est cohérente
        $this->assertTrue($this->calculator->validateRepartition($result, 5.00));
        
        // Vérifier que le total est correct
        $total = $result['admin'] + $result['integrator'] + $result['operator'];
        $this->assertEquals(5.00, $total);
    }

    /** @test */
    public function it_creates_and_saves_repartition()
    {
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee' => 2.00,
        ]);

        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 10.00,
        ]);

        // Créer la répartition
        $repartition = $this->calculator->createRepartition($transaction);

        $this->assertInstanceOf(TransactionRepartition::class, $repartition);
        $this->assertEquals($transaction->id, $repartition->transaction_id);
        $this->assertEquals(2.00, $repartition->admin_amount);
        $this->assertEquals(0.00, $repartition->integrator_amount);
        $this->assertEquals(8.00, $repartition->operator_amount);

        // Vérifier que la répartition est sauvegardée en base
        $this->assertDatabaseHas('transaction_repartitions', [
            'transaction_id' => $transaction->id,
            'admin_amount' => 2.00,
            'integrator_amount' => 0.00,
            'operator_amount' => 8.00,
        ]);
    }

    /** @test */
    public function it_updates_existing_repartition()
    {
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee' => 1.00,
        ]);

        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 5.00,
        ]);

        // Créer une répartition existante
        $existingRepartition = TransactionRepartition::create([
            'transaction_id' => $transaction->id,
            'admin_amount' => 0.50, // Ancienne valeur
            'integrator_amount' => 0.00,
            'operator_amount' => 4.50,
        ]);

        // Recalculer et mettre à jour
        $updatedRepartition = $this->calculator->createRepartition($transaction);

        $this->assertEquals($existingRepartition->id, $updatedRepartition->id);
        $this->assertEquals(1.00, $updatedRepartition->admin_amount); // Nouvelle valeur
        $this->assertEquals(0.00, $updatedRepartition->integrator_amount);
        $this->assertEquals(4.00, $updatedRepartition->operator_amount);
    }

    /** @test */
    public function it_calculates_detailed_breakdown()
    {
        $businessProfile = BusinessProfile::factory()->create([
            'transaction_fee' => 1.50,
            'recharge_fee' => 0.75,
            'active_terminal_fee' => 0.25,
            'admin_fee_percentage' => 10.0,
        ]);

        $integratorBp = BusinessProfile::factory()->create([
            'integrator_fee_percentage' => 15.0,
        ]);

        $integrator = Integrator::factory()->create([
            'business_profile_id' => $integratorBp->id,
        ]);

        $chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $integrator->id,
        ]);

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount' => 20.00,
        ]);

        $result = $this->calculator->calculateDetailed($transaction);

        // Vérifier la structure détaillée
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('admin_fees_breakdown', $result['details']);
        $this->assertArrayHasKey('integrator_details', $result['details']);
        $this->assertArrayHasKey('operator_details', $result['details']);
        $this->assertArrayHasKey('business_profile_info', $result['details']);

        // Vérifier les détails admin
        $adminBreakdown = $result['details']['admin_fees_breakdown'];
        $this->assertEquals(1.50, $adminBreakdown['transaction_fee']);
        $this->assertEquals(0.75, $adminBreakdown['recharge_fee']);
        $this->assertEquals(0.25, $adminBreakdown['other_fees']);
        $this->assertEquals(2.50, $adminBreakdown['total_fixed_fees']);
        $this->assertEquals(10.0, $adminBreakdown['admin_fee_percentage']);
        $this->assertEquals(2.00, $adminBreakdown['admin_percentage_amount']);

        // Vérifier les détails intégrateur
        $integratorDetails = $result['details']['integrator_details'];
        $this->assertEquals(15.0, $integratorDetails['integrator_fee_percentage']);
        $this->assertEquals(3.00, $integratorDetails['gross_amount']); // 15% de 20€
    }
}
