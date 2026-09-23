# Transaction System Documentation

This document describes the comprehensive transaction system that handles the full transaction chain from charging point identification through hierarchy processing to wallet debiting with rollback capabilities.

## Overview

The transaction system provides:
- **Complete hierarchy identification** for charging points
- **Business profile-based pricing** calculations
- **Automatic wallet debiting** with rollback on failure
- **Full transaction tracking** and audit trails
- **Comprehensive error handling** and logging

## Core Components

### 1. TransactionService

The main service that orchestrates the entire transaction process.

**Key Methods:**
- `processChargingTransaction()` - Main transaction processing
- `identifyHierarchy()` - Identify complete hierarchy for charging point
- `calculatePricing()` - Calculate pricing based on business profile
- `processWalletDebits()` - Process wallet operations with rollback
- `validateTransactionData()` - Validate transaction input data

### 2. Transaction Model

Tracks complete transaction information with hierarchy and pricing data.

**Key Features:**
- Complete transaction audit trail
- Hierarchy relationship tracking
- Pricing breakdown storage
- Debit results tracking
- Status management

### 3. Hierarchy Identification

Automatically identifies the complete organizational hierarchy for any charging point.

**Hierarchy Levels:**
- Charging Point
- Group
- Partner
- Integrator
- Operator (if exists)
- Business Profile

## Transaction Flow

### 1. Transaction Initiation

```php
$transactionData = [
    'charging_point_id' => 1,
    'user_id' => 123,
    'amount' => 100.00,
    'session_id' => 'SESS123',
    'energy_delivered' => 15.5,
    'duration_minutes' => 30,
];

$result = TransactionService::processChargingTransaction($transactionData);
```

### 2. Hierarchy Identification

The system automatically identifies the complete hierarchy:

```php
$hierarchy = TransactionService::identifyHierarchy($chargingPointId);

// Returns:
[
    'charging_point' => ChargingPoint,
    'group' => Group,
    'partner' => Partner,
    'integrator' => Integrator,
    'operator' => User (if exists),
    'business_profile' => BusinessProfile,
]
```

### 3. Pricing Calculation

Based on the business profile, the system calculates all fees:

```php
$pricing = TransactionService::calculatePricing($hierarchy, $transactionData);

// Returns:
[
    'base_amount' => 100.00,
    'admin_fee' => 5.00,
    'integrator_fee' => 10.00,
    'partner_fee' => 15.00,
    'operator_fee' => 0.00,
    'total_fees' => 30.00,
    'user_amount' => 70.00,
    'currency' => 'EUR',
]
```

### 4. Wallet Operations

The system processes wallet debits with automatic rollback:

```php
$debitResults = TransactionService::processWalletDebits($hierarchy, $pricing, $transactionData);

// Returns:
[
    'user' => [
        'wallet_id' => 1,
        'amount' => 100.00,
        'transaction' => WalletTransaction,
        'success' => true,
    ],
    'admin' => [
        'wallet_id' => 2,
        'amount' => 5.00,
        'transaction' => WalletTransaction,
        'success' => true,
    ],
    // ... other parties
]
```

## Business Profile Pricing

### Fee Calculation

The system supports both fixed and percentage-based fees:

```php
// Business Profile Configuration
$businessProfile = BusinessProfile::create([
    'admin_fee_percentage' => 5,      // 5% of base amount
    'admin_fee_fixed' => 1.00,        // Fixed €1.00
    'integrator_fee_percentage' => 10, // 10% of base amount
    'integrator_fee_fixed' => 2.00,   // Fixed €2.00
    'partner_fee_percentage' => 15,   // 15% of base amount
    'partner_fee_fixed' => 3.00,      // Fixed €3.00
]);

// For a €100 transaction:
// Admin fee: €1.00 + (€100 * 5%) = €6.00
// Integrator fee: €2.00 + (€100 * 10%) = €12.00
// Partner fee: €3.00 + (€100 * 15%) = €18.00
// Total fees: €36.00
// User amount: €100.00 - €36.00 = €64.00
```

### Custom Fee Logic

You can extend the fee calculation logic:

```php
// In TransactionService::calculatePricing()
protected static function calculateAdminFee(BusinessProfile $businessProfile, float $baseAmount): float
{
    $fixedFee = $businessProfile->admin_fee_fixed ?? 0;
    $percentageFee = $businessProfile->admin_fee_percentage ?? 0;
    
    // Add custom logic here
    if ($baseAmount > 100) {
        $percentageFee += 2; // Additional 2% for large transactions
    }
    
    return $fixedFee + ($baseAmount * $percentageFee / 100);
}
```

## Rollback Mechanism

### Automatic Rollback

The system automatically rolls back all successful transactions if any part fails:

```php
try {
    // Process user debit
    $userTransaction = $userWallet->debit($amount, 'Payment');
    
    // Process admin credit
    $adminTransaction = $adminWallet->credit($adminFee, 'Admin fee');
    
    // Process integrator credit
    $integratorTransaction = $integratorWallet->credit($integratorFee, 'Integrator fee');
    
    // If this fails, all previous transactions are rolled back
    $partnerTransaction = $partnerWallet->credit($partnerFee, 'Partner fee');
    
} catch (\Exception $e) {
    // Automatic rollback of all successful transactions
    self::rollbackDebits([$userTransaction, $adminTransaction, $integratorTransaction]);
    throw $e;
}
```

### Manual Rollback

You can manually rollback transactions:

```php
$transactions = [$userTransaction, $adminTransaction];
TransactionService::rollbackDebits($transactions);
```

## Transaction Tracking

### Transaction Model

The Transaction model stores complete transaction information:

```php
$transaction = Transaction::create([
    'transaction_id' => 'TXN123',
    'charging_point_id' => 1,
    'user_id' => 123,
    'session_id' => 'SESS123',
    'amount' => 100.00,
    'currency' => 'EUR',
    'status' => 'completed',
    'hierarchy_data' => [
        'charging_point' => 1,
        'group' => 1,
        'partner' => 1,
        'integrator' => 1,
        'operator' => 2,
        'business_profile' => 1,
    ],
    'pricing_data' => [
        'base_amount' => 100.00,
        'admin_fee' => 5.00,
        'integrator_fee' => 10.00,
        'partner_fee' => 15.00,
        'total_fees' => 30.00,
        'user_amount' => 70.00,
    ],
    'debit_results' => [
        'user' => ['success' => true, 'amount' => 100.00],
        'admin' => ['success' => true, 'amount' => 5.00],
        'integrator' => ['success' => true, 'amount' => 10.00],
        'partner' => ['success' => true, 'amount' => 15.00],
    ],
]);
```

### Transaction Queries

```php
// Get completed transactions
$completedTransactions = Transaction::completed()->get();

// Get failed transactions
$failedTransactions = Transaction::failed()->get();

// Get transactions for a user
$userTransactions = Transaction::forUser($userId)->get();

// Get transactions for a charging point
$chargingPointTransactions = Transaction::forChargingPoint($chargingPointId)->get();

// Get transactions in date range
$recentTransactions = Transaction::inDateRange($startDate, $endDate)->get();
```

### Transaction Information

```php
$transaction = Transaction::find(1);

// Basic information
echo $transaction->formatted_amount; // "100.00 EUR"
echo $transaction->status_badge_color; // "green"
echo $transaction->summary; // "Transaction for John Doe - 100.00 EUR"

// Hierarchy information
echo $transaction->hierarchy_summary; // "Integrator: Test Integrator | Partner: Test Partner"

// Pricing information
echo $transaction->pricing_summary; // "Base: 100 EUR | Admin: 5 | Integrator: 10 | Partner: 15"

// Debit results
echo $transaction->debit_summary; // "User: 100 | Admin: 5 | Integrator: 10 | Partner: 15"

// Financial information
echo $transaction->total_fees; // 30.00
echo $transaction->user_amount; // 70.00
```

## Error Handling

### Validation Errors

```php
$transactionData = [
    'charging_point_id' => 1,
    'user_id' => 123,
    'amount' => 100,
];

$errors = TransactionService::validateTransactionData($transactionData);

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo $error; // "Field 'amount' is required", etc.
    }
}
```

### Transaction Failures

```php
$result = TransactionService::processChargingTransaction($transactionData);

if (!$result['success']) {
    echo $result['error']; // "Insufficient balance in user wallet"
    
    if ($result['rollback_required']) {
        // Handle rollback scenario
        echo "Transaction rolled back due to failure";
    }
}
```

### Common Error Scenarios

1. **Insufficient Balance**
   - User wallet doesn't have enough funds
   - Automatic rollback of any successful debits

2. **Missing Business Profile**
   - Charging point has no business profile
   - Transaction fails before any wallet operations

3. **Invalid Hierarchy**
   - Charging point not properly linked to hierarchy
   - Transaction fails during hierarchy identification

4. **Wallet Creation Failure**
   - Unable to create wallet for hierarchy member
   - Transaction fails during wallet operations

## API Integration

### Controller Example

```php
class TransactionController extends Controller
{
    public function processChargingTransaction(Request $request)
    {
        $validated = $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'session_id' => 'nullable|string',
            'energy_delivered' => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer',
        ]);
        
        try {
            $result = TransactionService::processChargingTransaction($validated);
            
            if ($result['success']) {
                return response()->json([
                    'message' => 'Transaction processed successfully',
                    'transaction_id' => $result['transaction_id'],
                    'transaction' => $result['transaction'],
                ], 201);
            } else {
                return response()->json([
                    'error' => 'Transaction failed',
                    'message' => $result['error'],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Transaction processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### API Routes

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/transactions/charging', [TransactionController::class, 'processChargingTransaction']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::get('/transactions/user/{user}', [TransactionController::class, 'userTransactions']);
    Route::get('/transactions/charging-point/{chargingPoint}', [TransactionController::class, 'chargingPointTransactions']);
});
```

## Testing

### Unit Tests

```php
class TransactionServiceTest extends TestCase
{
    public function test_can_process_complete_charging_transaction()
    {
        // Create hierarchy
        $hierarchy = $this->createTestHierarchy();
        
        $user = User::factory()->create();
        $user->creditWallet(200, 'Initial credit');
        
        $transactionData = [
            'charging_point_id' => $hierarchy['charging_point']->id,
            'user_id' => $user->id,
            'amount' => 100,
            'session_id' => 'SESS123',
        ];
        
        $result = TransactionService::processChargingTransaction($transactionData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(100, $user->getWalletBalance()); // 200 - 100
    }
    
    public function test_transaction_fails_with_insufficient_balance()
    {
        $hierarchy = $this->createTestHierarchy();
        $user = User::factory()->create();
        // No wallet balance
        
        $transactionData = [
            'charging_point_id' => $hierarchy['charging_point']->id,
            'user_id' => $user->id,
            'amount' => 100,
        ];
        
        $result = TransactionService::processChargingTransaction($transactionData);
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('Insufficient balance', $result['error']);
    }
}
```

## Best Practices

1. **Always validate transaction data** before processing
2. **Use database transactions** for atomicity
3. **Implement proper error handling** for all scenarios
4. **Log all transaction activities** for audit purposes
5. **Test rollback scenarios** thoroughly
6. **Monitor transaction success rates** and failure reasons
7. **Implement rate limiting** for transaction processing
8. **Use appropriate authorization** for transaction access

## Security Considerations

1. **Validate all inputs** before processing
2. **Use HTTPS** for all transaction-related API calls
3. **Implement proper authentication** and authorization
4. **Log all transaction activities** for audit trails
5. **Use database transactions** to prevent partial updates
6. **Implement rate limiting** to prevent abuse
7. **Monitor for suspicious activity** in transaction patterns

## Troubleshooting

### Common Issues

1. **"Insufficient balance" errors**
   - Check user wallet balance before processing
   - Implement proper error handling and user feedback

2. **"No business profile found" errors**
   - Ensure charging point has associated business profile
   - Check hierarchy relationships

3. **"Transaction rolled back" errors**
   - Check individual wallet operations
   - Verify all hierarchy members have wallets
   - Check for database constraints

4. **"Hierarchy identification failed" errors**
   - Verify charging point relationships
   - Check group, partner, integrator associations
   - Ensure proper data integrity

### Debug Information

Enable debug logging to track transaction processing:

```php
// In your .env file
LOG_LEVEL=debug

// The system will log all transaction steps with details
```
