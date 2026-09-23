<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MoneyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MoneyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_converts_amounts_to_eur_correctly()
    {
        // Test same currency (EUR to EUR)
        $result = MoneyService::toEur(100.00, 'EUR');
        $this->assertEquals(100.00, $result);

        // Test USD to EUR (using fallback rate)
        $result = MoneyService::toEur(100.00, 'USD');
        $this->assertEquals(93.00, $result); // 100 * 0.93 (fallback rate)

        // Test GBP to EUR (using fallback rate)
        $result = MoneyService::toEur(100.00, 'GBP');
        $this->assertEquals(118.00, $result); // 100 * 1.18 (fallback rate)
    }

    /** @test */
    public function it_converts_amounts_from_eur_correctly()
    {
        // Test same currency (EUR from EUR)
        $result = MoneyService::fromEur(100.00, 'EUR');
        $this->assertEquals(100.00, $result);

        // Test EUR to USD (using fallback rate)
        $result = MoneyService::fromEur(100.00, 'USD');
        $this->assertEquals(108.00, $result); // 100 * 1.08 (fallback rate)

        // Test EUR to GBP (using fallback rate)
        $result = MoneyService::fromEur(100.00, 'GBP');
        $this->assertEquals(85.00, $result); // 100 * 0.85 (fallback rate)
    }

    /** @test */
    public function it_formats_amounts_correctly()
    {
        // Test EUR formatting
        $result = MoneyService::format(100.00, 'EUR');
        $this->assertEquals('100.00 €', $result);

        // Test USD formatting
        $result = MoneyService::format(100.00, 'USD');
        $this->assertEquals('100.00 $', $result);

        // Test formatting without symbol
        $result = MoneyService::format(100.00, 'EUR', false);
        $this->assertEquals('100.00', $result);
    }

    /** @test */
    public function it_formats_amounts_with_symbol_prefix()
    {
        $result = MoneyService::formatWithSymbol(100.00, 'EUR');
        $this->assertEquals('€100.00', $result);

        $result = MoneyService::formatWithSymbol(100.00, 'USD');
        $this->assertEquals('$100.00', $result);
    }

    /** @test */
    public function it_rounds_amounts_to_correct_precision()
    {
        // Test rounding to 2 decimal places
        $result = MoneyService::roundToPrecision(100.123456);
        $this->assertEquals(100.12, $result);

        // Test rounding to 4 decimal places for calculations
        $result = MoneyService::roundForCalculation(100.123456);
        $this->assertEquals(100.1235, $result);
    }

    /** @test */
    public function it_validates_currency_codes()
    {
        $this->assertTrue(MoneyService::isValidCurrency('EUR'));
        $this->assertTrue(MoneyService::isValidCurrency('USD'));
        $this->assertTrue(MoneyService::isValidCurrency('GBP'));
        $this->assertTrue(MoneyService::isValidCurrency('MAD'));
        $this->assertTrue(MoneyService::isValidCurrency('CAD'));

        $this->assertFalse(MoneyService::isValidCurrency('INVALID'));
        $this->assertFalse(MoneyService::isValidCurrency(''));
        $this->assertFalse(MoneyService::isValidCurrency('123'));
    }

    /** @test */
    public function it_gets_currency_symbols()
    {
        $this->assertEquals('€', MoneyService::getCurrencySymbol('EUR'));
        $this->assertEquals('$', MoneyService::getCurrencySymbol('USD'));
        $this->assertEquals('£', MoneyService::getCurrencySymbol('GBP'));
        $this->assertEquals('د.م.', MoneyService::getCurrencySymbol('MAD'));
        $this->assertEquals('C$', MoneyService::getCurrencySymbol('CAD'));
    }

    /** @test */
    public function it_gets_currency_names()
    {
        $this->assertEquals('Euro', MoneyService::getCurrencyName('EUR'));
        $this->assertEquals('US Dollar', MoneyService::getCurrencyName('USD'));
        $this->assertEquals('British Pound', MoneyService::getCurrencyName('GBP'));
        $this->assertEquals('Moroccan Dirham', MoneyService::getCurrencyName('MAD'));
        $this->assertEquals('Canadian Dollar', MoneyService::getCurrencyName('CAD'));
    }

    /** @test */
    public function it_gets_supported_currencies()
    {
        $currencies = MoneyService::getSupportedCurrencies();
        
        $this->assertArrayHasKey('EUR', $currencies);
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertArrayHasKey('GBP', $currencies);
        $this->assertArrayHasKey('MAD', $currencies);
        $this->assertArrayHasKey('CAD', $currencies);

        $this->assertEquals('€', $currencies['EUR']['symbol']);
        $this->assertEquals('Euro', $currencies['EUR']['name']);
    }

    /** @test */
    public function it_handles_exchange_rate_caching()
    {
        // Mock HTTP response for exchange rate API
        Http::fake([
            'api.exchangerates-api.io/*' => Http::response([
                'rates' => [
                    'USD' => 1.08,
                    'GBP' => 0.85
                ]
            ], 200)
        ]);

        // First call should fetch from API
        $rate1 = MoneyService::getExchangeRate('EUR', 'USD');
        
        // Second call should use cache
        $rate2 = MoneyService::getExchangeRate('EUR', 'USD');
        
        $this->assertEquals($rate1, $rate2);
        $this->assertEquals(1.08, $rate1);
    }

    /** @test */
    public function it_uses_fallback_rates_when_api_fails()
    {
        // Mock HTTP failure
        Http::fake([
            'api.exchangerates-api.io/*' => Http::response([], 500)
        ]);

        $rate = MoneyService::getExchangeRate('EUR', 'USD');
        
        // Should use fallback rate
        $this->assertEquals(1.08, $rate);
    }

    /** @test */
    public function it_calculates_percentages_correctly()
    {
        $result = MoneyService::calculatePercentage(100.00, 10);
        $this->assertEquals(10.00, $result);

        $result = MoneyService::calculatePercentage(100.00, 15.5);
        $this->assertEquals(15.50, $result);
    }

    /** @test */
    public function it_adds_percentages_correctly()
    {
        $result = MoneyService::addPercentage(100.00, 10);
        $this->assertEquals(110.00, $result);

        $result = MoneyService::addPercentage(100.00, 15.5);
        $this->assertEquals(115.50, $result);
    }

    /** @test */
    public function it_subtracts_percentages_correctly()
    {
        $result = MoneyService::subtractPercentage(100.00, 10);
        $this->assertEquals(90.00, $result);

        $result = MoneyService::subtractPercentage(100.00, 15.5);
        $this->assertEquals(84.50, $result);
    }

    /** @test */
    public function it_checks_amount_signs_correctly()
    {
        $this->assertTrue(MoneyService::isPositive(100.00));
        $this->assertFalse(MoneyService::isPositive(-100.00));
        $this->assertFalse(MoneyService::isPositive(0.00));

        $this->assertTrue(MoneyService::isNegative(-100.00));
        $this->assertFalse(MoneyService::isNegative(100.00));
        $this->assertFalse(MoneyService::isNegative(0.00));

        $this->assertTrue(MoneyService::isZero(0.00));
        $this->assertTrue(MoneyService::isZero(0.001)); // Within tolerance
        $this->assertFalse(MoneyService::isZero(0.01));
    }

    /** @test */
    public function it_gets_absolute_values()
    {
        $this->assertEquals(100.00, MoneyService::abs(100.00));
        $this->assertEquals(100.00, MoneyService::abs(-100.00));
        $this->assertEquals(0.00, MoneyService::abs(0.00));
    }

    /** @test */
    public function it_compares_amounts_correctly()
    {
        $this->assertEquals(0, MoneyService::compare(100.00, 100.00)); // Equal
        $this->assertEquals(1, MoneyService::compare(100.00, 50.00)); // First greater
        $this->assertEquals(-1, MoneyService::compare(50.00, 100.00)); // First smaller
    }

    /** @test */
    public function it_gets_min_and_max_amounts()
    {
        $this->assertEquals(50.00, MoneyService::min(100.00, 50.00));
        $this->assertEquals(100.00, MoneyService::max(100.00, 50.00));
    }

    /** @test */
    public function it_clears_exchange_rate_cache()
    {
        // Set some cache values
        Cache::put('exchange_rate_EUR_USD', 1.08, 60);
        Cache::put('exchange_rate_EUR_GBP', 0.85, 60);

        // Clear cache
        MoneyService::clearExchangeRateCache();

        // Cache should be cleared
        $this->assertFalse(Cache::has('exchange_rate_EUR_USD'));
        $this->assertFalse(Cache::has('exchange_rate_EUR_GBP'));
    }

    /** @test */
    public function it_gets_all_exchange_rates()
    {
        $rates = MoneyService::getAllExchangeRates('EUR');
        
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertArrayHasKey('MAD', $rates);
        $this->assertArrayHasKey('CAD', $rates);
        
        // Should not include base currency
        $this->assertArrayNotHasKey('EUR', $rates);
    }

    /** @test */
    public function it_converts_and_formats_amounts()
    {
        $result = MoneyService::convertAndFormat(100.00, 'USD', 'EUR', true);
        
        // Should convert USD to EUR and format
        $this->assertStringContainsString('€', $result);
        $this->assertStringContainsString('93.00', $result); // 100 * 0.93 (fallback rate)
    }

    /** @test */
    public function it_handles_edge_cases()
    {
        // Test with zero amount
        $result = MoneyService::format(0.00, 'EUR');
        $this->assertEquals('0.00 €', $result);

        // Test with very small amount
        $result = MoneyService::format(0.01, 'EUR');
        $this->assertEquals('0.01 €', $result);

        // Test with large amount
        $result = MoneyService::format(999999.99, 'EUR');
        $this->assertEquals('999,999.99 €', $result);
    }

    /** @test */
    public function it_handles_invalid_currencies_gracefully()
    {
        // Should fallback to EUR for invalid currency
        $result = MoneyService::format(100.00, 'INVALID');
        $this->assertEquals('100.00 INVALID', $result);

        // Should use fallback rate for invalid currency
        $result = MoneyService::toEur(100.00, 'INVALID');
        $this->assertEquals(100.00, $result); // Same currency fallback
    }
}
