# Money Service Documentation

This document describes the MoneyService class and its integration with the wallet system for currency standardization and conversion.

## Overview

The MoneyService provides a centralized way to handle currency operations across the application, ensuring all monetary values are stored internally as EUR with consistent decimal precision.

## Key Features

### 1. Currency Standardization
- **Internal Storage**: All amounts stored as EUR in database
- **Display Conversion**: Convert to user's preferred currency for display
- **Consistent Precision**: All monetary values use decimal(10,2) format

### 2. Exchange Rate Management
- **Real-time Rates**: Fetch from external APIs (exchangerates-api.io, fixer.io)
- **Fallback Rates**: Static rates when APIs are unavailable
- **Caching**: Exchange rates cached for 60 minutes
- **Multiple APIs**: Redundancy for reliability

### 3. Currency Support
- **EUR**: Euro (default internal currency)
- **USD**: US Dollar
- **GBP**: British Pound
- **MAD**: Moroccan Dirham
- **CAD**: Canadian Dollar

## Database Schema

### Wallet Table
```sql
CREATE TABLE wallets (
    id BIGINT PRIMARY KEY,
    owner_type VARCHAR(255),
    owner_id BIGINT,
    balance DECIMAL(10,2) DEFAULT 0.00,  -- Always in EUR
    currency VARCHAR(3) DEFAULT 'EUR',   -- Display currency
    min_balance DECIMAL(10,2) NULL,
    max_balance DECIMAL(10,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    name VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Wallet Transactions Table
```sql
CREATE TABLE wallet_transactions (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT,
    type ENUM('credit', 'debit'),
    amount DECIMAL(10,2),               -- Always in EUR
    currency VARCHAR(3) DEFAULT 'EUR', -- Display currency
    current_balance DECIMAL(10,2),      -- Balance after transaction
    description TEXT NULL,
    metadata JSON NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
    reference VARCHAR(255) NULL,
    external_id VARCHAR(255) NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## API Reference

### Core Methods

#### Currency Conversion
```php
// Convert to EUR (internal storage)
$eurAmount = MoneyService::toEur(100.00, 'USD');

// Convert from EUR (display)
$displayAmount = MoneyService::fromEur(100.00, 'USD');

// Get exchange rate
$rate = MoneyService::getExchangeRate('USD', 'EUR');
```

#### Formatting
```php
// Format for display
$formatted = MoneyService::format(100.00, 'USD');
// Returns: "100.00 $"

// Format with symbol prefix
$formatted = MoneyService::formatWithSymbol(100.00, 'USD');
// Returns: "$100.00"

// Convert and format
$formatted = MoneyService::convertAndFormat(100.00, 'USD', 'EUR');
// Returns: "93.00 €"
```

#### Utility Methods
```php
// Round to precision
$rounded = MoneyService::roundToPrecision(100.123456);
// Returns: 100.12

// Round for calculations
$rounded = MoneyService::roundForCalculation(100.123456);
// Returns: 100.1235

// Validate currency
$isValid = MoneyService::isValidCurrency('USD');
// Returns: true

// Get currency symbol
$symbol = MoneyService::getCurrencySymbol('USD');
// Returns: "$"

// Get currency name
$name = MoneyService::getCurrencyName('USD');
// Returns: "US Dollar"
```

#### Mathematical Operations
```php
// Calculate percentage
$percentage = MoneyService::calculatePercentage(100.00, 10);
// Returns: 10.00

// Add percentage
$newAmount = MoneyService::addPercentage(100.00, 10);
// Returns: 110.00

// Subtract percentage
$newAmount = MoneyService::subtractPercentage(100.00, 10);
// Returns: 90.00

// Compare amounts
$comparison = MoneyService::compare(100.00, 50.00);
// Returns: 1 (first is greater)

// Get min/max
$min = MoneyService::min(100.00, 50.00);
$max = MoneyService::max(100.00, 50.00);
```

#### Amount Validation
```php
// Check if positive
$isPositive = MoneyService::isPositive(100.00);
// Returns: true

// Check if negative
$isNegative = MoneyService::isNegative(-100.00);
// Returns: true

// Check if zero
$isZero = MoneyService::isZero(0.00);
// Returns: true

// Get absolute value
$abs = MoneyService::abs(-100.00);
// Returns: 100.00
```

### Cache Management
```php
// Clear exchange rate cache
MoneyService::clearExchangeRateCache();

// Get all exchange rates
$rates = MoneyService::getAllExchangeRates('EUR');
// Returns: ['USD' => 1.08, 'GBP' => 0.85, ...]
```

## Model Integration

### Wallet Model
```php
class Wallet extends Model
{
    // Credit with currency conversion
    public function credit(float $amount, string $description = null, array $metadata = [], string $currency = MoneyService::DEFAULT_CURRENCY): WalletTransaction
    {
        // Convert to EUR for internal storage
        $eurAmount = MoneyService::toEur($amount, $currency);
        
        // Store transaction in EUR
        $transaction = $this->transactions()->create([
            'type' => 'credit',
            'amount' => $eurAmount,
            'currency' => MoneyService::DEFAULT_CURRENCY,
            'current_balance' => $newBalance,
            'metadata' => array_merge($metadata, [
                'original_amount' => $amount,
                'original_currency' => $currency,
                'exchange_rate' => MoneyService::getExchangeRate($currency, MoneyService::DEFAULT_CURRENCY)
            ]),
        ]);
        
        return $transaction;
    }
    
    // Get formatted balance
    public function getFormattedBalance(string $displayCurrency = null): string
    {
        $currency = $displayCurrency ?? $this->currency ?? MoneyService::DEFAULT_CURRENCY;
        return MoneyService::format($this->balance, $currency);
    }
}
```

### WalletTransaction Model
```php
class WalletTransaction extends Model
{
    // Get formatted amount
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->type === 'credit' ? '+' : '-';
        $currency = $this->currency ?? MoneyService::DEFAULT_CURRENCY;
        $displayAmount = MoneyService::fromEur($this->amount, $currency);
        return $sign . MoneyService::format($displayAmount, $currency, false);
    }
    
    // Get formatted current balance
    public function getFormattedCurrentBalanceAttribute(): string
    {
        $currency = $this->currency ?? MoneyService::DEFAULT_CURRENCY;
        $displayAmount = MoneyService::fromEur($this->current_balance, $currency);
        return MoneyService::format($displayAmount, $currency);
    }
}
```

## Blade Integration

### Blade Directives
```blade
{{-- Format amount --}}
@money(100.00, 'USD')
{{-- Output: 100.00 $ --}}

{{-- Format with symbol prefix --}}
@moneyWithSymbol(100.00, 'USD')
{{-- Output: $100.00 --}}

{{-- Format with color --}}
@moneyWithColor(100.00, 'USD')
{{-- Output: <span class="text-success">100.00 $</span> --}}

{{-- Format with badge --}}
@moneyWithBadge(100.00, 'USD', 'credit')
{{-- Output: <span class="badge badge-success">100.00 $</span> --}}

{{-- Format for table --}}
@moneyForTable(100.00, 'USD', 'credit')
{{-- Output: <span class="text-success">+100.00 $</span> --}}

{{-- Format for card --}}
@moneyForCard(100.00, 'USD')
{{-- Output: <span class="h4 text-success">100.00 $</span> --}}

{{-- Currency symbol --}}
@currencySymbol('USD')
{{-- Output: $ --}}

{{-- Currency name --}}
@currencyName('USD')
{{-- Output: US Dollar --}}
```

### Helper Functions
```php
// In Blade templates
{{ MoneyHelper::format(100.00, 'USD') }}
{{ MoneyHelper::formatWithColor(100.00, 'USD') }}
{{ MoneyHelper::formatForTable(100.00, 'USD', 'credit') }}
{{ MoneyHelper::getCurrencySymbol('USD') }}
```

## Configuration

### Exchange Rate APIs
```php
// config/services.php
'fixer' => [
    'api_key' => env('FIXER_API_KEY'),
],

'exchangerates' => [
    'api_key' => env('EXCHANGERATES_API_KEY'),
],
```

### Environment Variables
```env
# Exchange Rate APIs
FIXER_API_KEY=your_fixer_api_key
EXCHANGERATES_API_KEY=your_exchangerates_api_key

# Default Currency
MONEY_DEFAULT_CURRENCY=EUR
MONEY_DECIMAL_PRECISION=2
MONEY_CALCULATION_PRECISION=4
```

## Usage Examples

### Basic Currency Conversion
```php
// Convert USD to EUR for storage
$eurAmount = MoneyService::toEur(100.00, 'USD');
// Returns: 93.00 (using fallback rate)

// Convert EUR to USD for display
$usdAmount = MoneyService::fromEur(100.00, 'USD');
// Returns: 108.00 (using fallback rate)

// Format for display
$formatted = MoneyService::format(100.00, 'USD');
// Returns: "100.00 $"
```

### Wallet Operations
```php
$user = User::find(1);
$wallet = $user->getOrCreateWallet();

// Credit 100 USD
$transaction = $wallet->credit(100.00, 'USD payment', [], 'USD');
// Stores: 93.00 EUR in database
// Metadata: original_amount=100.00, original_currency=USD

// Debit 50 USD
$transaction = $wallet->debit(50.00, 'USD payment', [], 'USD');
// Stores: 46.50 EUR in database

// Get formatted balance in USD
$formatted = $wallet->getFormattedBalance('USD');
// Returns: "108.00 $" (converted from EUR)
```

### Transaction Display
```php
$transaction = WalletTransaction::find(1);

// Get formatted amount
$formatted = $transaction->formatted_amount;
// Returns: "+100.00 $" (for USD transaction)

// Get formatted current balance
$balance = $transaction->formatted_current_balance;
// Returns: "108.00 $" (converted from EUR)
```

### Blade Template Usage
```blade
{{-- Display wallet balance --}}
<div class="wallet-balance">
    <h3>Current Balance</h3>
    <p>@moneyForCard($wallet->balance, $wallet->currency)</p>
</div>

{{-- Display transaction list --}}
@foreach($transactions as $transaction)
    <tr>
        <td>@moneyForTable($transaction->amount, $transaction->currency, $transaction->type)</td>
        <td>{{ $transaction->description }}</td>
        <td>@money($transaction->current_balance, $transaction->currency)</td>
    </tr>
@endforeach

{{-- Display statistics --}}
<div class="stats">
    <div class="stat">
        <h4>Total Credits</h4>
        <p>@moneyWithColor($totalCredits, 'USD')</p>
    </div>
    <div class="stat">
        <h4>Total Debits</h4>
        <p>@moneyWithColor($totalDebits, 'USD')</p>
    </div>
</div>
```

## Error Handling

### Invalid Currency
```php
try {
    $amount = MoneyService::toEur(100.00, 'INVALID');
} catch (\Exception $e) {
    // Handle invalid currency
    $amount = 100.00; // Fallback to same currency
}
```

### API Failures
```php
// Exchange rate API fails
$rate = MoneyService::getExchangeRate('USD', 'EUR');
// Returns: 0.93 (fallback rate)

// Log API failures
Log::warning('Exchange rate API failed', [
    'from' => 'USD',
    'to' => 'EUR',
    'error' => $e->getMessage()
]);
```

### Insufficient Balance
```php
try {
    $wallet->debit(100.00, 'Payment', [], 'USD');
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Insufficient balance')) {
        // Handle insufficient balance
        return redirect()->back()->with('error', 'Insufficient balance');
    }
}
```

## Performance Considerations

### Caching Strategy
- **Exchange Rates**: Cached for 60 minutes
- **Fallback Rates**: Static rates for reliability
- **API Timeouts**: 5-second timeout for external APIs

### Database Optimization
- **Decimal Precision**: Consistent decimal(10,2) format
- **Indexing**: Proper indexing on currency and amount fields
- **Batch Operations**: Efficient batch currency conversions

### Memory Management
- **Lazy Loading**: Exchange rates loaded on demand
- **Cache Cleanup**: Automatic cache expiration
- **API Limits**: Respect API rate limits

## Testing

### Unit Tests
```php
class MoneyServiceTest extends TestCase
{
    public function test_currency_conversion()
    {
        $result = MoneyService::toEur(100.00, 'USD');
        $this->assertEquals(93.00, $result);
    }
    
    public function test_amount_formatting()
    {
        $result = MoneyService::format(100.00, 'USD');
        $this->assertEquals('100.00 $', $result);
    }
}
```

### Integration Tests
```php
class MoneyServiceIntegrationTest extends TestCase
{
    public function test_wallet_credit_with_currency_conversion()
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        
        $transaction = $wallet->credit(100.00, 'Test', [], 'USD');
        
        $this->assertEquals(93.00, $transaction->amount);
        $this->assertEquals('EUR', $transaction->currency);
    }
}
```

## Security Considerations

### Input Validation
- **Currency Codes**: Validated against supported currencies
- **Amount Values**: Positive values only for credits/debits
- **Precision**: Rounded to prevent floating-point errors

### API Security
- **API Keys**: Stored securely in environment variables
- **Rate Limiting**: Respect API rate limits
- **Error Handling**: Graceful fallback to static rates

### Data Integrity
- **Atomic Operations**: Database transactions for consistency
- **Locking**: Row-level locking for concurrent operations
- **Validation**: Model-level validation for amounts

## Troubleshooting

### Common Issues
1. **Exchange Rate API Failures**: Check API keys and network connectivity
2. **Currency Conversion Errors**: Verify currency codes are valid
3. **Precision Issues**: Use MoneyService rounding methods
4. **Cache Issues**: Clear exchange rate cache if needed

### Debug Commands
```bash
# Clear exchange rate cache
php artisan cache:clear

# Test currency conversion
php artisan tinker
>>> MoneyService::toEur(100.00, 'USD')

# Check supported currencies
php artisan tinker
>>> MoneyService::getSupportedCurrencies()
```

### Monitoring
- **API Calls**: Monitor exchange rate API usage
- **Cache Hit Rate**: Monitor cache performance
- **Conversion Errors**: Log currency conversion failures
- **Balance Discrepancies**: Monitor wallet balance accuracy

## Conclusion

The MoneyService provides a robust, scalable solution for currency handling across the application. With proper configuration and monitoring, it ensures accurate financial operations while maintaining performance and reliability.

For more information, see the related documentation:
- [Wallet System Documentation](WALLET_SYSTEM.md)
- [User Transaction View Documentation](USER_TRANSACTION_VIEW.md)
- [Admin Transaction View Documentation](ADMIN_TRANSACTION_VIEW.md)
