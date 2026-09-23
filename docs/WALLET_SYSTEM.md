# Wallet System Documentation

This document describes the unified wallet system that provides credit/debit functionality with automatic balance updates and polymorphic relationships.

## Overview

The wallet system provides:
- **Unified wallet management** for all entities (users, integrators, partners, etc.)
- **Automatic balance updates** on every transaction
- **Polymorphic relationships** allowing any model to have a wallet
- **Transaction history** with full audit trail
- **Advanced features** like auto-recharge, minimum balance constraints
- **Bulk operations** for efficient processing

## Core Components

### 1. Wallet Model

The main wallet model with polymorphic relationships and transaction methods.

**Key Features:**
- Polymorphic relationship to any model
- Automatic balance tracking
- Credit/debit methods with validation
- Transfer functionality between wallets
- Auto-recharge capabilities
- Minimum/maximum balance constraints

### 2. WalletTransaction Model

Tracks all wallet transactions with full audit trail.

**Key Features:**
- Credit/debit transaction types
- Balance before/after tracking
- Metadata storage for additional information
- Status tracking (pending, completed, failed)
- Reference and external ID support

### 3. WalletService

Service class providing high-level wallet operations.

**Key Features:**
- Wallet creation and management
- Transaction processing
- Bulk operations
- Statistics and reporting
- Auto-recharge processing

## Database Schema

### Wallets Table

```sql
CREATE TABLE wallets (
    id BIGINT PRIMARY KEY,
    owner_type VARCHAR(255),
    owner_id BIGINT,
    balance DECIMAL(15,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'EUR',
    is_active BOOLEAN DEFAULT true,
    name VARCHAR(255),
    description TEXT,
    min_balance DECIMAL(15,2),
    max_balance DECIMAL(15,2),
    auto_recharge BOOLEAN DEFAULT false,
    auto_recharge_threshold DECIMAL(15,2),
    auto_recharge_amount DECIMAL(15,2),
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
    amount DECIMAL(15,2),
    balance_before DECIMAL(15,2),
    balance_after DECIMAL(15,2),
    description TEXT,
    metadata JSON,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    reference VARCHAR(255),
    external_id VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Usage Examples

### Basic Wallet Operations

```php
use App\Models\User;
use App\Services\WalletService;

// Create a user
$user = User::factory()->create();

// Create wallet for user
$wallet = WalletService::createWallet($user, [
    'name' => 'Main Wallet',
    'currency' => 'EUR',
    'min_balance' => 10.00,
]);

// Credit amount to wallet
$transaction = $wallet->credit(100.50, 'Initial deposit');
echo $wallet->balance; // 100.50

// Debit amount from wallet
$transaction = $wallet->debit(25.75, 'Payment for service');
echo $wallet->balance; // 74.75

// Check balance
$balance = $wallet->balance;
$formatted = $wallet->getFormattedBalance(); // "74.75 EUR"
```

### User Wallet Methods

```php
$user = User::find(1);

// Credit to user's wallet
$transaction = $user->creditWallet(50, 'Bonus credit');

// Debit from user's wallet
$transaction = $user->debitWallet(20, 'Service fee');

// Get wallet balance
$balance = $user->getWalletBalance();
$formatted = $user->getFormattedWalletBalance();

// Check sufficient balance
$hasEnough = $user->hasSufficientWalletBalance(100);
```

### Transfer Between Wallets

```php
$user1 = User::find(1);
$user2 = User::find(2);

$wallet1 = $user1->getOrCreateWallet();
$wallet2 = $user2->getOrCreateWallet();

// Transfer between wallets
$result = $wallet1->transferTo($wallet2, 50, 'Transfer payment');

// Or using service
$result = WalletService::transfer($wallet1, $wallet2, 50, 'Transfer payment');

// Result contains both transactions
$sourceTransaction = $result['source_transaction'];
$destTransaction = $result['destination_transaction'];
```

### Advanced Features

#### Auto-Recharge

```php
$wallet = WalletService::createWallet($user, [
    'balance' => 5,
    'auto_recharge' => true,
    'auto_recharge_threshold' => 10,
    'auto_recharge_amount' => 50,
]);

// Check if auto-recharge is needed
if ($wallet->needsAutoRecharge()) {
    $transaction = $wallet->performAutoRecharge();
}

// Process all wallets needing auto-recharge
$processedCount = WalletService::processAutoRecharges();
```

#### Minimum Balance Constraints

```php
$wallet = WalletService::createWallet($user, [
    'balance' => 100,
    'min_balance' => 20,
]);

// This will succeed (balance would be 30)
$wallet->debit(70, 'Valid debit');

// This will fail (balance would be 10, below minimum)
try {
    $wallet->debit(20, 'Invalid debit');
} catch (Exception $e) {
    echo $e->getMessage(); // "Transaction would violate minimum balance constraint"
}
```

### Service Operations

#### Processing Payments

```php
// Process charging session payment
$transaction = WalletService::processChargingPayment($wallet, 25.50, [
    'session_id' => 'SESS123',
    'charging_point_id' => 1,
    'energy_delivered' => 15.5,
]);

// Process commission payment
$transaction = WalletService::processCommissionPayment($wallet, 5.00, 'Commission from session', [
    'commission_type' => 'charging_session',
    'rate' => 0.05,
]);

// Process refund
$transaction = WalletService::processRefund($wallet, 15.00, 'Session cancelled', [
    'original_transaction_id' => 'TXN123',
]);
```

#### Bulk Operations

```php
// Bulk credit multiple wallets
$walletCredits = [
    1 => 100,  // Wallet ID 1 gets 100
    2 => 200,  // Wallet ID 2 gets 200
    3 => 300,  // Wallet ID 3 gets 300
];

$results = WalletService::bulkCredit($walletCredits, 'Bulk credit');

foreach ($results as $walletId => $result) {
    if ($result['success']) {
        echo "Wallet {$walletId} credited successfully";
    } else {
        echo "Wallet {$walletId} failed: " . $result['error'];
    }
}
```

#### Statistics and Reporting

```php
// Get wallet statistics
$stats = WalletService::getWalletStatistics($wallet, $startDate, $endDate);

echo "Current Balance: " . $stats['current_balance'];
echo "Total Credits: " . $stats['total_credits'];
echo "Total Debits: " . $stats['total_debits'];
echo "Net Change: " . $stats['net_change'];
echo "Transaction Count: " . $stats['transaction_count'];

// Get transaction history
$history = WalletService::getTransactionHistory($wallet, 50, 0);

// Get wallets needing attention
$attention = WalletService::getWalletsNeedingAttention();
$lowBalanceWallets = $attention['low_balance'];
$needingRechargeWallets = $attention['needing_recharge'];
$inactiveWallets = $attention['inactive'];
```

### Transaction Management

#### Transaction Queries

```php
// Get all transactions for a wallet
$transactions = $wallet->transactions;

// Get credit transactions only
$credits = $wallet->creditTransactions;

// Get debit transactions only
$debits = $wallet->debitTransactions;

// Get transactions in date range
$history = $wallet->getBalanceHistory($startDate, $endDate);

// Get total credits/debits for period
$totalCredits = $wallet->getTotalCredits($startDate, $endDate);
$totalDebits = $wallet->getTotalDebits($startDate, $endDate);
```

#### Transaction Information

```php
$transaction = $wallet->transactions->first();

// Basic information
echo $transaction->type; // 'credit' or 'debit'
echo $transaction->amount; // 100.50
echo $transaction->description; // 'Payment description'

// Formatted values
echo $transaction->formatted_amount; // '+100.50 EUR' or '-100.50 EUR'
echo $transaction->formatted_balance_before; // '50.00 EUR'
echo $transaction->formatted_balance_after; // '150.50 EUR'

// Status checks
$transaction->isCredit(); // true/false
$transaction->isDebit(); // true/false
$transaction->isCompleted(); // true/false

// Badge colors for UI
echo $transaction->type_badge_color; // 'green' or 'red'
echo $transaction->status_badge_color; // 'green', 'yellow', or 'red'
```

## API Integration

### Controller Example

```php
class WalletController extends Controller
{
    public function show(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        
        $transactions = $wallet->transactions()->latest()->paginate(20);
        $statistics = WalletService::getWalletStatistics($wallet);
        
        return view('wallets.show', compact('wallet', 'transactions', 'statistics'));
    }
    
    public function credit(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);
        
        $transaction = $wallet->credit(
            $validated['amount'],
            $validated['description']
        );
        
        return response()->json([
            'message' => 'Amount credited successfully',
            'transaction' => $transaction,
            'new_balance' => $wallet->fresh()->balance,
        ]);
    }
}
```

### API Routes

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/wallets/{wallet}', [WalletController::class, 'show']);
    Route::post('/wallets/{wallet}/credit', [WalletController::class, 'credit']);
    Route::post('/wallets/{wallet}/debit', [WalletController::class, 'debit']);
    Route::post('/wallets/{wallet}/transfer', [WalletController::class, 'transfer']);
    Route::get('/wallets/{wallet}/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallets/{wallet}/statistics', [WalletController::class, 'statistics']);
});
```

## Testing

### Unit Tests

```php
class WalletSystemTest extends TestCase
{
    public function test_can_credit_wallet()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user);
        
        $transaction = WalletService::credit($wallet, 100.50, 'Test credit');
        
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(100.50, $wallet->fresh()->balance);
    }
    
    public function test_debit_fails_with_insufficient_balance()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user, ['balance' => 50]);
        
        $this->expectException(\Exception::class);
        WalletService::debit($wallet, 100, 'Test debit');
    }
}
```

## Best Practices

1. **Always use transactions** for wallet operations to ensure data consistency
2. **Validate amounts** before processing transactions
3. **Log all wallet operations** for audit purposes
4. **Use appropriate error handling** for insufficient balance scenarios
5. **Implement proper authorization** for wallet access
6. **Monitor wallet balances** and set up alerts for low balances
7. **Use bulk operations** for efficiency when processing multiple wallets
8. **Regularly process auto-recharges** for wallets that need it

## Security Considerations

1. **Validate all inputs** before processing transactions
2. **Use database transactions** to prevent race conditions
3. **Implement proper authorization** for wallet operations
4. **Log all transactions** for audit trails
5. **Use HTTPS** for all wallet-related API calls
6. **Implement rate limiting** for wallet operations
7. **Monitor for suspicious activity** in wallet transactions

## Troubleshooting

### Common Issues

1. **"Insufficient balance" errors**
   - Check wallet balance before debiting
   - Verify minimum balance constraints
   - Ensure proper error handling

2. **"Wallet not found" errors**
   - Ensure wallet exists before operations
   - Use `getOrCreateWallet()` for automatic creation
   - Check polymorphic relationships

3. **Transaction failures**
   - Check database constraints
   - Verify transaction amounts are positive
   - Ensure proper error handling and logging

### Debug Information

Enable debug logging to track wallet operations:

```php
// In your .env file
LOG_LEVEL=debug

// The system will log all wallet operations with details
```
