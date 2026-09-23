<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculatorService;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $transactionCalculatorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionCalculatorService = new TransactionCalculatorService();
    }

    /**
     * Helper to create a full hierarchy for testing.
     */
    protected function createHierarchy($adminFeePercentage = 0,
                                       $integratorFeePercentage = 0,
                                       $operatorBalance = 0, $integratorBalance = 0, $adminBalance = 0)
    {
        // Admin
        $admin = User::factory()->create(['balance' => $adminBalance]);
        $adminBp = BusinessProfile::factory()->create([
            'admin_fee_percentage' => $adminFeePercentage,
            'owner_commission' => 0,
        ]);

        // Integrator
        $integrator = User::factory()->create(['created_by' => $admin->id, 'balance' => $integratorBalance]);
        $integrator->businessProfile()->save($adminBp); // Admin's BP applies to Integrator

        // Operator
        $operator = User::factory()->create(['created_by' => $integrator->id, 'balance' => $operatorBalance]);
        $operatorBp = BusinessProfile::factory()->create([
            'integrator_fee_percentage' => $integratorFeePercentage,
            'integrator_commission' => 0,
        ]);
        $operator->businessProfile()->save($operatorBp);

        // Group
        $group = Group::factory()->create(['operator_id' => $operator->id]);

        // ChargingPoint
        $chargingPoint = ChargingPoint::factory()->create(['group_id' => $group->id]);

        return compact('admin', 'integrator', 'operator', 'chargingPoint', 'adminBp', 'operatorBp');
    }

    /** @test */
    public function it_verifies_admin_integrator_operator_full_chain_calculation_and_balance_updates()
    {
        $initialOperatorBalance = 1000;
        $initialIntegratorBalance = 500;
        $initialAdminBalance = 200;

        extract($this->createHierarchy(
            $adminFeePercentage = 5, // Admin fees from business profile
            $integratorFeePercentage = 10, // Integrator fees from business profile
            $initialOperatorBalance, $initialIntegratorBalance, $initialAdminBalance
        ));

        $transactionAmountHT = 100; // Transaction amount hors taxes

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount_ht' => $transactionAmountHT,
            'amount_total' => $transactionAmountHT, // Assuming no tax for simplicity in HT calculation
        ]);

        // Expected Integrator Fee (applied to operator)
        // Using integrator_fee_percentage from business profile: 10% of 100 = 10
        $expectedIntegratorFee = $transactionAmountHT * 0.10; // 10

        // Expected Admin Fee (applied to integrator)
        // Using admin_fee_percentage from business profile: 5% of integratorFee (10) = 0.5
        $expectedAdminFee = $expectedIntegratorFee * 0.05; // 0.5

        // Expected Shares
        $expectedOperatorShare = $transactionAmountHT - $expectedIntegratorFee; // 100 - 10 = 90
        $expectedIntegratorShare = $expectedIntegratorFee - $expectedAdminFee; // 10 - 0.5 = 9.5
        $expectedAdminShare = $expectedAdminFee; // 0.5

        $result = $this->transactionCalculatorService->process($transaction);

        // Assert service returns success
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('calculation', $result);
        $this->assertArrayHasKey('repartition', $result);

        // Refresh models to get updated balances
        $operator->refresh();
        $integrator->refresh();
        $admin->refresh();
        $transaction->refresh();

        // Assert balances
        $this->assertEquals($initialOperatorBalance - $transactionAmountHT, $operator->balance); // Operator pays total transaction amount
        $this->assertEquals($initialIntegratorBalance + $expectedIntegratorShare, $integrator->balance);
        $this->assertEquals($initialAdminBalance + $expectedAdminShare, $admin->balance);

        // Assert Transaction Repartition record
        $this->assertDatabaseHas('transaction_repartitions', [
            'transaction_id' => $transaction->id,
            'admin_amount' => $expectedAdminShare,
            'integrator_amount' => $expectedIntegratorShare,
            'operator_amount' => $expectedOperatorShare,
        ]);

        // Assert calculation details
        $this->assertEquals($expectedIntegratorFee, $result['calculation']['integrator_fee']);
        $this->assertEquals($expectedAdminFee, $result['calculation']['admin_fee']);
        $this->assertEquals($expectedOperatorShare, $result['calculation']['operator_share']);
        $this->assertEquals($expectedIntegratorShare, $result['calculation']['integrator_share']);
        $this->assertEquals($expectedAdminShare, $result['calculation']['admin_share']);
    }

    /** @test */
    public function it_handles_operator_created_directly_by_admin_only_admin_fees_applied()
    {
        $initialOperatorBalance = 1000;
        $initialAdminBalance = 200;

        // Admin
        $admin = User::factory()->create(['balance' => $initialAdminBalance]);
        $adminBp = BusinessProfile::factory()->create([
            'admin_fee_percentage' => 5,
            'owner_commission' => 0,
        ]);

        // Operator created directly by Admin (no Integrator in between)
        $operator = User::factory()->create(['created_by' => $admin->id, 'balance' => $initialOperatorBalance]);
        $operator->businessProfile()->save($adminBp); // Admin's BP applies directly to Operator

        // Group
        $group = Group::factory()->create(['operator_id' => $operator->id]);

        // ChargingPoint
        $chargingPoint = ChargingPoint::factory()->create(['group_id' => $group->id]);

        $transactionAmountHT = 100;
        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount_ht' => $transactionAmountHT,
            'amount_total' => $transactionAmountHT,
        ]);

        // In this scenario, the IntegratorFee calculation will use the Operator's business profile,
        // which is the Admin's business profile in this case.
        // So, $bpIntegrator will be $adminBp.
        // The $bpAdmin will be null, as there's no Integrator with an Admin Business Profile.

        // Expected Integrator Fee (which is effectively Admin's fee applied to Operator)
        // Using admin_fee_percentage from business profile: 5% of 100 = 5
        $expectedIntegratorFee = $transactionAmountHT * 0.05; // 5

        // Expected Admin Fee (applied to integrator - but no integrator, so this should be 0)
        // The service logic will try to get $integrator->admin and $integrator->businessProfile.
        // If $operator->integrator is null, then $integrator will be null.
        // If $integrator is null, then $integrator->admin will be null, and $integrator->businessProfile will be null.
        // So $bpAdmin will be null, and $adminFee will be 0.
        $expectedAdminFee = 0;

        // Expected Shares
        $expectedOperatorShare = $transactionAmountHT - $expectedIntegratorFee; // 100 - 5 = 95
        $expectedIntegratorShare = $expectedIntegratorFee - $expectedAdminFee; // 5 - 0 = 5
        $expectedAdminShare = $expectedAdminFee; // 0

        $result = $this->transactionCalculatorService->process($transaction);

        // Assert service returns success
        $this->assertTrue($result['success']);

        $operator->refresh();
        $admin->refresh();
        $transaction->refresh();

        $this->assertEquals($initialOperatorBalance - $transactionAmountHT, $operator->balance);
        $this->assertEquals($initialAdminBalance + $expectedIntegratorShare, $admin->balance); // Admin gets the integrator fee
        $this->assertDatabaseHas('transaction_repartitions', [
            'transaction_id' => $transaction->id,
            'admin_amount' => $expectedIntegratorShare, // Admin gets the fee that would normally go to integrator
            'integrator_amount' => 0, // No integrator in this chain
            'operator_amount' => $expectedOperatorShare,
        ]);
    }

    /** @test */
    public function it_handles_integrator_creates_charging_point_directly_admin_fees_still_apply()
    {
        $initialIntegratorBalance = 500;
        $initialAdminBalance = 200;

        // Admin
        $admin = User::factory()->create(['balance' => $initialAdminBalance]);
        $adminBp = BusinessProfile::factory()->create([
            'admin_fee_percentage' => 5,
            'owner_commission' => 0,
        ]);

        // Integrator
        $integrator = User::factory()->create(['created_by' => $admin->id, 'balance' => $initialIntegratorBalance]);
        $integrator->businessProfile()->save($adminBp); // Admin's BP applies to Integrator

        // Group created by Integrator (no Operator in between)
        $group = Group::factory()->create(['created_by' => $integrator->id]);

        // ChargingPoint created by Integrator
        $chargingPoint = ChargingPoint::factory()->create(['group_id' => $group->id]);

        $transactionAmountHT = 100;
        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount_ht' => $transactionAmountHT,
            'amount_total' => $transactionAmountHT,
        ]);

        // In this scenario, $cp->group->operator will be null.
        // So $operator will be null.
        // $bpIntegrator will be null, and $integratorFee will be 0.

        // Expected Integrator Fee (applied to operator - but no operator, so this should be 0)
        $expectedIntegratorFee = 0;

        // Expected Admin Fee (applied to integrator)
        // Using admin_fee_percentage from business profile: 5% of integratorFee (0) = 0
        $expectedAdminFee = $expectedIntegratorFee * 0.05; // 0

        // Expected Shares
        $expectedOperatorShare = $transactionAmountHT - $expectedIntegratorFee; // 100 - 0 = 100
        $expectedIntegratorShare = $expectedIntegratorFee - $expectedAdminFee; // 0 - 0 = 0
        $expectedAdminShare = $expectedAdminFee; // 0

        $result = $this->transactionCalculatorService->process($transaction);

        // Assert service returns success
        $this->assertTrue($result['success']);

        $integrator->refresh();
        $admin->refresh();
        $transaction->refresh();

        $this->assertEquals($initialIntegratorBalance + $expectedIntegratorShare, $integrator->balance);
        $this->assertEquals($initialAdminBalance + $expectedAdminShare, $admin->balance);
        $this->assertDatabaseHas('transaction_repartitions', [
            'transaction_id' => $transaction->id,
            'admin_amount' => $expectedAdminShare,
            'integrator_amount' => $expectedIntegratorShare,
            'operator_amount' => $expectedOperatorShare,
        ]);
    }

    /** @test */
    public function it_handles_rounding_and_precision_for_two_decimal_places()
    {
        $initialOperatorBalance = 1000;
        $initialIntegratorBalance = 500;
        $initialAdminBalance = 200;

        extract($this->createHierarchy(
            $adminFeePercentage = 3.33,
            $integratorFeePercentage = 6.66,
            $initialOperatorBalance, $initialIntegratorBalance, $initialAdminBalance
        ));

        $transactionAmountHT = 123.45;

        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount_ht' => $transactionAmountHT,
            'amount_total' => $transactionAmountHT,
        ]);

        // Expected Integrator Fee (applied to operator)
        // Using integrator_fee_percentage from business profile
        $integratorFeePercentage = 6.66 / 100;
        $integratorFee = round($transactionAmountHT * $integratorFeePercentage, 2); // 8.22

        // Expected Admin Fee (applied to integrator)
        // Using admin_fee_percentage from business profile
        $adminFeePercentage = 3.33 / 100;
        $adminFee = round($integratorFee * $adminFeePercentage, 2); // 0.27

        // Expected Shares
        $expectedOperatorShare = round($transactionAmountHT - $integratorFee, 2); // 123.45 - 8.22 = 115.23
        $expectedIntegratorShare = round($integratorFee - $adminFee, 2); // 8.22 - 0.27 = 7.95
        $expectedAdminShare = round($adminFee, 2); // 0.27

        $result = $this->transactionCalculatorService->process($transaction);

        // Assert service returns success
        $this->assertTrue($result['success']);

        $operator->refresh();
        $integrator->refresh();
        $admin->refresh();
        $transaction->refresh();

        $this->assertEquals($initialOperatorBalance - $transactionAmountHT, $operator->balance);
        $this->assertEquals($initialIntegratorBalance + $expectedIntegratorShare, $integrator->balance);
        $this->assertEquals($initialAdminBalance + $expectedAdminShare, $admin->balance);

        $this->assertDatabaseHas('transaction_repartitions', [
            'transaction_id' => $transaction->id,
            'admin_amount' => $expectedAdminShare,
            'integrator_amount' => $expectedIntegratorShare,
            'operator_amount' => $expectedOperatorShare,
        ]);

        // Assert calculation details with proper rounding
        $this->assertEquals($integratorFee, $result['calculation']['integrator_fee']);
        $this->assertEquals($adminFee, $result['calculation']['admin_fee']);
        $this->assertEquals($expectedOperatorShare, $result['calculation']['operator_share']);
        $this->assertEquals($expectedIntegratorShare, $result['calculation']['integrator_share']);
        $this->assertEquals($expectedAdminShare, $result['calculation']['admin_share']);
    }

    /** @test */
    public function it_ensures_db_transaction_rollback_on_failure()
    {
        $initialOperatorBalance = 1000;
        $initialIntegratorBalance = 500;
        $initialAdminBalance = 200;

        extract($this->createHierarchy(
            $adminFeePercentage = 5,
            $integratorFeePercentage = 10,
            $initialOperatorBalance, $initialIntegratorBalance, $initialAdminBalance
        ));

        $transactionAmountHT = 100;
        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount_ht' => $transactionAmountHT,
            'amount_total' => $transactionAmountHT,
        ]);

        // Mock TransactionRepartition to throw an exception
        $this->mock(TransactionRepartition::class, function ($mock) {
            $mock->shouldReceive('create')
                ->andThrow(new Exception("Simulated DB failure during repartition creation."));
        });

        // Expect an exception to be thrown and caught by the DB::transaction block,
        // leading to a rollback.
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Simulated DB failure during repartition creation.");

        try {
            $this->transactionCalculatorService->process($transaction);
        } finally {
            // Assert that balances remain unchanged due to rollback
            $operator->refresh();
            $integrator->refresh();
            $admin->refresh();

            $this->assertEquals($initialOperatorBalance, $operator->balance);
            $this->assertEquals($initialIntegratorBalance, $integrator->balance);
            $this->assertEquals($initialAdminBalance, $admin->balance);

            // Assert that no repartition record was created
            $this->assertDatabaseMissing('transaction_repartitions', [
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /** @test */
    public function it_handles_missing_hierarchy_gracefully()
    {
        $transaction = Transaction::factory()->create([
            'charging_point_id' => null, // No charging point
            'amount_ht' => 100,
            'amount_total' => 100,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to retrieve hierarchy from ChargingPoint');

        $this->transactionCalculatorService->process($transaction);
    }

    /** @test */
    public function it_handles_missing_operator_gracefully()
    {
        // Create charging point without operator
        $group = Group::factory()->create(['operator_id' => null]);
        $chargingPoint = ChargingPoint::factory()->create(['group_id' => $group->id]);
        
        $transaction = Transaction::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'amount_ht' => 100,
            'amount_total' => 100,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to retrieve hierarchy from ChargingPoint');

        $this->transactionCalculatorService->process($transaction);
    }
}