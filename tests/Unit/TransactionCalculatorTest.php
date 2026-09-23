<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\TransactionCalculator;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use Mockery;

class TransactionCalculatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

#[Test]
    public function it_calculates_admin_only_scenario_with_fixed_fees()
    {
        $transaction = Mockery::mock(Transaction::class);
        $chargingPoint = Mockery::mock(ChargingPoint::class);
        $businessProfile = Mockery::mock(BusinessProfile::class);

        $transaction->shouldReceive('offsetExists')->with('amount')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('chargingPoint')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('integrator')->andReturn(true);
        $transaction->shouldReceive('getAttribute')->with('amount')->andReturn(100.00);
        $transaction->shouldReceive('getAttribute')->with('chargingPoint')->andReturn($chargingPoint);
        $transaction->shouldReceive('getAttribute')->with('integrator')->andReturn(null); // Admin-only scenario

        $chargingPoint->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $chargingPoint->shouldReceive('getAttribute')->with('businessProfile')->andReturn($businessProfile);

        $businessProfile->shouldReceive('offsetExists')->with('transaction_fee')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('recharge_fee')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('other_fees')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('admin_percentage')->andReturn(true);
        $businessProfile->shouldReceive('getAttribute')->with('transaction_fee')->andReturn(5.00);
        $businessProfile->shouldReceive('getAttribute')->with('recharge_fee')->andReturn(2.00);
        $businessProfile->shouldReceive('getAttribute')->with('other_fees')->andReturn(1.00);
        $businessProfile->shouldReceive('getAttribute')->with('admin_percentage')->andReturn(null); // Fixed fees

        $calculator = new TransactionCalculator();
        $result = $calculator->calculate($transaction);

        $this->assertEquals(100.00, $result['total']);
        $this->assertEquals(8.00, $result['admin_amount']); // 5 + 2 + 1
        $this->assertEquals(0.00, $result['integrator_amount']);
        $this->assertEquals(92.00, $result['operator_amount']); // 100 - 8
    }

#[Test]
    public function it_calculates_admin_only_scenario_with_percentage_fees()
    {
        $transaction = Mockery::mock(Transaction::class);
        $chargingPoint = Mockery::mock(ChargingPoint::class);
        $businessProfile = Mockery::mock(BusinessProfile::class);

        $transaction->shouldReceive('offsetExists')->with('amount')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('chargingPoint')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('integrator')->andReturn(true);
        $transaction->shouldReceive('getAttribute')->with('amount')->andReturn(100.00);
        $transaction->shouldReceive('getAttribute')->with('chargingPoint')->andReturn($chargingPoint);
        $transaction->shouldReceive('getAttribute')->with('integrator')->andReturn(null); // Admin-only scenario

        $chargingPoint->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $chargingPoint->shouldReceive('getAttribute')->with('businessProfile')->andReturn($businessProfile);

        $businessProfile->shouldReceive('offsetExists')->with('transaction_fee')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('recharge_fee')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('other_fees')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('admin_percentage')->andReturn(true);
        $businessProfile->shouldReceive('getAttribute')->with('transaction_fee')->andReturn(5.00);
        $businessProfile->shouldReceive('getAttribute')->with('recharge_fee')->andReturn(2.00);
        $businessProfile->shouldReceive('getAttribute')->with('other_fees')->andReturn(1.00);
        $businessProfile->shouldReceive('getAttribute')->with('admin_percentage')->andReturn(10.00); // 10% of totalPaid

        $calculator = new TransactionCalculator();
        $result = $calculator->calculate($transaction);

        $this->assertEquals(100.00, $result['total']);
        $this->assertEquals(10.00, $result['admin_amount']); // 10% of 100
        $this->assertEquals(0.00, $result['integrator_amount']);
        $this->assertEquals(90.00, $result['operator_amount']); // 100 - 10
    }

#[Test]
    public function it_calculates_integrator_and_operator_scenario()
    {
        $transaction = Mockery::mock(Transaction::class);
        $chargingPoint = Mockery::mock(ChargingPoint::class);
        $adminBusinessProfile = Mockery::mock(BusinessProfile::class);
        $integrator = Mockery::mock(Integrator::class);
        $integratorBusinessProfile = Mockery::mock(BusinessProfile::class);

        $transaction->shouldReceive('offsetExists')->with('amount')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('chargingPoint')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('integrator')->andReturn(true);
        $transaction->shouldReceive('getAttribute')->with('amount')->andReturn(200.00);
        $transaction->shouldReceive('getAttribute')->with('chargingPoint')->andReturn($chargingPoint);
        $transaction->shouldReceive('getAttribute')->with('integrator')->andReturn($integrator);

        $chargingPoint->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $chargingPoint->shouldReceive('getAttribute')->with('businessProfile')->andReturn($adminBusinessProfile);
        
        $integrator->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $integrator->shouldReceive('getAttribute')->with('businessProfile')->andReturn($integratorBusinessProfile);

        // Admin fees (fixed)
        $adminBusinessProfile->shouldReceive('offsetExists')->with('transaction_fee')->andReturn(true);
        $adminBusinessProfile->shouldReceive('offsetExists')->with('recharge_fee')->andReturn(true);
        $adminBusinessProfile->shouldReceive('offsetExists')->with('other_fees')->andReturn(true);
        $adminBusinessProfile->shouldReceive('offsetExists')->with('admin_percentage')->andReturn(true);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('transaction_fee')->andReturn(5.00);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('recharge_fee')->andReturn(2.00);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('other_fees')->andReturn(1.00);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('admin_percentage')->andReturn(null);

        // Integrator percentage
        $integratorBusinessProfile->shouldReceive('offsetExists')->with('integrator_percentage')->andReturn(true);
        $integratorBusinessProfile->shouldReceive('getAttribute')->with('integrator_percentage')->andReturn(15.00); // 15% of totalPaid

        $calculator = new TransactionCalculator();
        $result = $calculator->calculate($transaction);

        $this->assertEquals(200.00, $result['total']);
        $this->assertEquals(8.00, $result['admin_amount']); // 5 + 2 + 1
        $this->assertEquals(30.00, $result['integrator_amount']); // 15% of 200
        $this->assertEquals(162.00, $result['operator_amount']); // 200 - 8 - 30
    }

#[Test]
    public function it_handles_missing_business_profiles_gracefully()
    {
        $transaction = Mockery::mock(Transaction::class);
        $chargingPoint = Mockery::mock(ChargingPoint::class);

        $transaction->shouldReceive('offsetExists')->with('amount')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('chargingPoint')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('integrator')->andReturn(true);
        $transaction->shouldReceive('getAttribute')->with('amount')->andReturn(50.00);
        $transaction->shouldReceive('getAttribute')->with('chargingPoint')->andReturn($chargingPoint);
        $transaction->shouldReceive('getAttribute')->with('integrator')->andReturn(null); // No integrator

        $chargingPoint->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $chargingPoint->shouldReceive('getAttribute')->with('businessProfile')->andReturn(null); // Missing business profile for charging point

        $calculator = new TransactionCalculator();
        $result = $calculator->calculate($transaction);

        $this->assertEquals(50.00, $result['total']);
        $this->assertEquals(0.00, $result['admin_amount']); // No fees if BP is missing
        $this->assertEquals(0.00, $result['integrator_amount']);
        $this->assertEquals(50.00, $result['operator_amount']);
    }

#[Test]
    public function it_handles_missing_integrator_business_profile_gracefully()
    {
        $transaction = Mockery::mock(Transaction::class);
        $chargingPoint = Mockery::mock(ChargingPoint::class);
        $adminBusinessProfile = Mockery::mock(BusinessProfile::class);
        $integrator = Mockery::mock(Integrator::class);

        $transaction->shouldReceive('offsetExists')->with('amount')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('chargingPoint')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('integrator')->andReturn(true);
        $transaction->shouldReceive('getAttribute')->with('amount')->andReturn(100.00);
        $transaction->shouldReceive('getAttribute')->with('chargingPoint')->andReturn($chargingPoint);
        $transaction->shouldReceive('getAttribute')->with('integrator')->andReturn($integrator);

        $chargingPoint->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $chargingPoint->shouldReceive('getAttribute')->with('businessProfile')->andReturn($adminBusinessProfile);
        
        $integrator->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $integrator->shouldReceive('getAttribute')->with('businessProfile')->andReturn(null); // Missing integrator business profile

        // Admin fees (fixed)
        $adminBusinessProfile->shouldReceive('offsetExists')->with('transaction_fee')->andReturn(true);
        $adminBusinessProfile->shouldReceive('offsetExists')->with('recharge_fee')->andReturn(true);
        $adminBusinessProfile->shouldReceive('offsetExists')->with('other_fees')->andReturn(true);
        $adminBusinessProfile->shouldReceive('offsetExists')->with('admin_percentage')->andReturn(true);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('transaction_fee')->andReturn(5.00);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('recharge_fee')->andReturn(2.00);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('other_fees')->andReturn(1.00);
        $adminBusinessProfile->shouldReceive('getAttribute')->with('admin_percentage')->andReturn(null);

        $calculator = new TransactionCalculator();
        $result = $calculator->calculate($transaction);

        $this->assertEquals(100.00, $result['total']);
        $this->assertEquals(8.00, $result['admin_amount']); // Admin fees still apply
        $this->assertEquals(0.00, $result['integrator_amount']); // No integrator share if BP is missing
        $this->assertEquals(92.00, $result['operator_amount']);
    }

#[Test]
    public function it_handles_zero_amounts_and_fees()
    {
        $transaction = Mockery::mock(Transaction::class);
        $chargingPoint = Mockery::mock(ChargingPoint::class);
        $businessProfile = Mockery::mock(BusinessProfile::class);

        $transaction->shouldReceive('offsetExists')->with('amount')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('chargingPoint')->andReturn(true);
        $transaction->shouldReceive('offsetExists')->with('integrator')->andReturn(true);
        $transaction->shouldReceive('getAttribute')->with('amount')->andReturn(0.00);
        $transaction->shouldReceive('getAttribute')->with('chargingPoint')->andReturn($chargingPoint);
        $transaction->shouldReceive('getAttribute')->with('integrator')->andReturn(null);

        $chargingPoint->shouldReceive('offsetExists')->with('businessProfile')->andReturn(true);
        $chargingPoint->shouldReceive('getAttribute')->with('businessProfile')->andReturn($businessProfile);

        $businessProfile->shouldReceive('offsetExists')->with('transaction_fee')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('recharge_fee')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('other_fees')->andReturn(true);
        $businessProfile->shouldReceive('offsetExists')->with('admin_percentage')->andReturn(true);
        $businessProfile->shouldReceive('getAttribute')->with('transaction_fee')->andReturn(0.00);
        $businessProfile->shouldReceive('getAttribute')->with('recharge_fee')->andReturn(0.00);
        $businessProfile->shouldReceive('getAttribute')->with('other_fees')->andReturn(0.00);
        $businessProfile->shouldReceive('getAttribute')->with('admin_percentage')->andReturn(0.00);

        $calculator = new TransactionCalculator();
        $result = $calculator->calculate($transaction);

        $this->assertEquals(0.00, $result['total']);
        $this->assertEquals(0.00, $result['admin_amount']);
        $this->assertEquals(0.00, $result['integrator_amount']);
        $this->assertEquals(0.00, $result['operator_amount']);
    }
}