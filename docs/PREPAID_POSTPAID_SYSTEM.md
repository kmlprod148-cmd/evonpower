# Prepaid vs Postpaid Charging Sessions

This document describes the implementation of prepaid and postpaid charging sessions in the EVON charging system.

## Overview

The system supports two charging modes:

- **Prepaid**: Users pay upfront for estimated charging cost, with refunds for unused balance
- **Postpaid**: Users pay after charging based on actual consumption, with minimum threshold validation

## Database Schema

### ChargingSession Model Extensions

The `charging_sessions` table has been extended with the following fields:

```sql
ALTER TABLE charging_sessions ADD COLUMN mode ENUM('prepaid', 'postpaid') DEFAULT 'postpaid';
ALTER TABLE charging_sessions ADD COLUMN estimated_cost DECIMAL(10,2) NULL;
ALTER TABLE charging_sessions ADD COLUMN prepaid_amount DECIMAL(10,2) NULL;
ALTER TABLE charging_sessions ADD COLUMN refund_amount DECIMAL(10,2) NULL;
ALTER TABLE charging_sessions ADD COLUMN min_threshold DECIMAL(10,2) NULL;
ALTER TABLE charging_sessions ADD COLUMN wallet_validation_passed BOOLEAN DEFAULT FALSE;
ALTER TABLE charging_sessions ADD COLUMN wallet_validated_at TIMESTAMP NULL;
```

## Prepaid Sessions

### How Prepaid Works

1. **Session Creation**: User selects prepaid mode and provides estimated cost
2. **Wallet Validation**: System checks if user's wallet has sufficient balance for estimated cost
3. **Payment Processing**: If validation passes, estimated cost is debited from wallet
4. **Charging**: Session proceeds with charging
5. **Cost Calculation**: Actual cost is calculated based on energy consumption
6. **Refund Processing**: If actual cost < estimated cost, difference is refunded to wallet

### Prepaid Validation Logic

```php
// Check if wallet has sufficient balance for estimated cost
$hasSufficientBalance = $wallet->hasSufficientBalance($session->estimated_cost);

// Check minimum balance constraint
$newBalance = $wallet->balance - $session->estimated_cost;
if ($wallet->min_balance && $newBalance < $wallet->min_balance) {
    // Validation fails
}
```

### Prepaid Payment Processing

```php
// Debit estimated cost from wallet
$transaction = $wallet->debit(
    $session->estimated_cost,
    "Prepaid charging session - {$session->session_id}",
    ['mode' => 'prepaid']
);

// Update session
$session->update([
    'prepaid_amount' => $session->estimated_cost,
    'payment_status' => 'paid',
]);
```

### Prepaid Refund Processing

```php
// Calculate refund amount
$refundAmount = $session->prepaid_amount - $session->cost;

if ($refundAmount > 0) {
    // Credit refund to wallet
    $transaction = $wallet->credit(
        $refundAmount,
        "Refund for prepaid session - {$session->session_id}",
        ['mode' => 'prepaid_refund']
    );
    
    $session->update([
        'refund_amount' => $refundAmount,
        'payment_status' => 'refunded',
    ]);
}
```

## Postpaid Sessions

### How Postpaid Works

1. **Session Creation**: User selects postpaid mode
2. **Threshold Validation**: System checks if user's wallet has sufficient balance for minimum threshold
3. **Charging**: Session proceeds with charging (no upfront payment)
4. **Cost Calculation**: Actual cost is calculated based on energy consumption
5. **Payment Processing**: Actual cost is debited from wallet at session end

### Postpaid Validation Logic

```php
// Get minimum threshold
$threshold = $session->min_threshold ?? config('charging.postpaid_min_threshold', 10.00);

// Check if wallet has sufficient balance for threshold
$hasSufficientBalance = $wallet->hasSufficientBalance($threshold);
```

### Postpaid Payment Processing

```php
// Debit actual cost from wallet
$transaction = $wallet->debit(
    $session->cost,
    "Postpaid charging session - {$session->session_id}",
    ['mode' => 'postpaid']
);

$session->update([
    'payment_status' => 'paid',
]);
```

## Cost Calculation

### ChargingSessionCostService

The `ChargingSessionCostService` handles all cost calculations:

```php
// Calculate estimated cost
$estimatedCost = ChargingSessionCostService::calculateEstimatedCost($session);

// Calculate actual cost
$actualCost = ChargingSessionCostService::calculateActualCost($session);

// Get cost breakdown
$breakdown = ChargingSessionCostService::getCostBreakdown($session);
```

### Cost Components

1. **Base Cost**:
   - Energy cost: `energy_consumed * energy_price_per_kwh`
   - Time cost: `duration * time_price_per_minute`
   - Session cost: `session_price`

2. **Business Profile Fees**:
   - Admin fee: `base_cost * admin_fee_percentage + admin_fee_fixed`
   - Integrator fee: `base_cost * integrator_fee_percentage + integrator_fee_fixed`
   - Partner fee: `base_cost * partner_fee_percentage + partner_fee_fixed`

## Session Validation

### ChargingSessionValidationService

The `ChargingSessionValidationService` handles all validation logic:

```php
// Validate session start
$validation = ChargingSessionValidationService::validateSessionStart($session);

// Validate session completion
$validation = ChargingSessionValidationService::validateSessionCompletion($session);

// Check user eligibility
$eligibility = ChargingSessionValidationService::getUserSessionEligibility($user);
```

### Validation Rules

1. **Prepaid Validation**:
   - User must have sufficient balance for estimated cost
   - Estimated cost must be positive
   - Wallet must be active

2. **Postpaid Validation**:
   - User must have sufficient balance for minimum threshold
   - Minimum threshold must be positive
   - Wallet must be active

## Session Lifecycle

### Starting a Session

```php
// Create session
$session = ChargingSession::create([
    'session_id' => 'SESS123',
    'charging_point_id' => $chargingPoint->id,
    'user_id' => $user->id,
    'mode' => 'prepaid', // or 'postpaid'
    'estimated_cost' => 50.00, // for prepaid
    'min_threshold' => 10.00, // for postpaid
]);

// Validate and start
if ($session->canBeStarted()) {
    $session->startSession();
}
```

### Ending a Session

```php
// Update session with actual consumption
$session->update([
    'energy_consumed' => 15.5,
    'duration' => 30,
    'cost' => 25.00,
]);

// End session
$session->endSession();
```

## API Endpoints

### Session Management

```http
POST /api/charging-sessions
{
    "charging_point_id": 1,
    "user_id": 1,
    "mode": "prepaid",
    "estimated_cost": 50.00
}

GET /api/charging-sessions/{session}
PUT /api/charging-sessions/{session}
DELETE /api/charging-sessions/{session}
```

### Session Operations

```http
POST /api/charging-sessions/{session}/start
POST /api/charging-sessions/{session}/end
POST /api/charging-sessions/{session}/validate
GET /api/charging-sessions/{session}/cost-breakdown
```

## Configuration

### Environment Variables

```env
# Postpaid settings
CHARGING_POSTPAID_MIN_THRESHOLD=10.00
CHARGING_POSTPAID_MAX_THRESHOLD=1000.00

# Prepaid settings
CHARGING_PREPAID_MIN_ESTIMATED_COST=1.00
CHARGING_PREPAID_MAX_ESTIMATED_COST=500.00

# Validation settings
CHARGING_REQUIRE_WALLET_VALIDATION=true
CHARGING_ALLOW_NEGATIVE_BALANCE=false

# Refund settings
CHARGING_AUTO_PROCESS_REFUNDS=true
CHARGING_REFUND_THRESHOLD=0.01
```

### Configuration File

The `config/charging.php` file contains all configuration options:

```php
return [
    'postpaid' => [
        'min_threshold' => env('CHARGING_POSTPAID_MIN_THRESHOLD', 10.00),
        'max_threshold' => env('CHARGING_POSTPAID_MAX_THRESHOLD', 1000.00),
    ],
    'prepaid' => [
        'min_estimated_cost' => env('CHARGING_PREPAID_MIN_ESTIMATED_COST', 1.00),
        'max_estimated_cost' => env('CHARGING_PREPAID_MAX_ESTIMATED_COST', 500.00),
    ],
    // ... more configuration
];
```

## Error Handling

### Common Errors

1. **Insufficient Balance**: User's wallet doesn't have enough funds
2. **Invalid Session Mode**: Session mode is not 'prepaid' or 'postpaid'
3. **Missing Parameters**: Required parameters are missing
4. **Wallet Not Found**: User doesn't have a wallet
5. **Charging Point Unavailable**: Charging point is not online

### Error Responses

```json
{
    "error": "Insufficient balance for prepaid session",
    "message": "Required: 50.00 EUR, Available: 25.00 EUR",
    "code": "INSUFFICIENT_BALANCE",
    "session_id": "SESS123"
}
```

## Testing

### Test Coverage

The system includes comprehensive tests:

- Prepaid session validation
- Postpaid session validation
- Payment processing
- Refund processing
- Cost calculation
- Session lifecycle
- Error handling

### Running Tests

```bash
# Run all charging session tests
php artisan test tests/Feature/ChargingSessionPrepaidPostpaidTest.php

# Run specific test
php artisan test --filter test_prepaid_session_validates_wallet_balance
```

## Monitoring and Logging

### Log Events

The system logs all important events:

- Session creation
- Wallet validation
- Payment processing
- Refund processing
- Error occurrences

### Log Levels

- `info`: Normal operations
- `warning`: Validation failures
- `error`: Payment failures
- `debug`: Detailed debugging information

## Security Considerations

### Wallet Security

1. **Balance Validation**: Always validate wallet balance before processing
2. **Transaction Locking**: Use database transactions for atomic operations
3. **Audit Trail**: Log all wallet transactions
4. **Authorization**: Verify user permissions for wallet operations

### Session Security

1. **Authentication**: Require user authentication for all operations
2. **Authorization**: Verify user can access charging point
3. **Session Locking**: Prevent concurrent session modifications
4. **Timeout Handling**: Handle session timeouts gracefully

## Performance Considerations

### Optimization

1. **Caching**: Cache frequently accessed data
2. **Batch Processing**: Process refunds in batches
3. **Database Indexing**: Index frequently queried fields
4. **Connection Pooling**: Use connection pooling for database operations

### Scalability

1. **Horizontal Scaling**: Design for horizontal scaling
2. **Load Balancing**: Distribute load across multiple servers
3. **Queue Processing**: Use queues for background processing
4. **Database Sharding**: Consider database sharding for large datasets

## Troubleshooting

### Common Issues

1. **Session Not Starting**: Check wallet balance and validation
2. **Payment Failures**: Verify wallet status and permissions
3. **Refund Issues**: Check refund calculations and processing
4. **Cost Calculation Errors**: Verify pricing plan configuration

### Debug Commands

```bash
# Check session status
php artisan charging:session:status SESS123

# Validate wallet balance
php artisan charging:wallet:validate USER123

# Process pending refunds
php artisan charging:refunds:process

# Check system health
php artisan charging:health:check
```

## Future Enhancements

### Planned Features

1. **Dynamic Pricing**: Real-time pricing based on demand
2. **Loyalty Programs**: Discounts for frequent users
3. **Subscription Plans**: Monthly/yearly charging plans
4. **Multi-Currency**: Support for multiple currencies
5. **Advanced Analytics**: Detailed usage analytics

### Integration Opportunities

1. **Payment Gateways**: Integration with external payment systems
2. **Mobile Apps**: Mobile application integration
3. **IoT Devices**: Integration with IoT charging devices
4. **Third-Party Services**: Integration with external services

## Conclusion

The prepaid/postpaid charging session system provides a robust, secure, and scalable solution for managing charging sessions with wallet integration. The system supports both payment modes with comprehensive validation, cost calculation, and refund processing.

For more information, see the related documentation:
- [Wallet System Documentation](WALLET_SYSTEM.md)
- [Transaction System Documentation](TRANSACTION_SYSTEM.md)
- [API Documentation](API_DOCUMENTATION.md)
