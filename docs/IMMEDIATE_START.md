# Immediate Start Documentation

This document describes the immediate start functionality for charging points, which allows users to start charging immediately after successful payment without scheduling.

## Overview

The immediate start feature simplifies the charging process by:
- Removing the need for time selection
- Starting charging immediately after payment
- Integrating with Steve API for automatic charging initiation
- Providing real-time charging session management

## Key Features

### 1. Simplified User Experience
- **No Time Selection**: Users no longer need to select start times
- **Immediate Start**: Charging begins immediately after payment
- **Real-time Status**: Live updates on charging progress
- **Automatic Management**: Session management handled automatically

### 2. Steve API Integration
- **Automatic Start**: Charging starts via Steve API after payment
- **Fallback Support**: Multiple API endpoints for reliability
- **Status Monitoring**: Real-time charging status updates
- **Error Handling**: Graceful handling of API failures

### 3. Payment Integration
- **Wallet Integration**: Uses existing wallet system
- **Balance Validation**: Checks sufficient balance before starting
- **Automatic Debit**: Debits wallet on successful start
- **Rollback Support**: Refunds on charging failure

## Database Schema

### Charging Sessions Table
```sql
CREATE TABLE charging_sessions (
    id BIGINT PRIMARY KEY,
    charging_point_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    status ENUM('starting', 'charging', 'completed', 'failed') NOT NULL,
    mode ENUM('immediate', 'scheduled') NOT NULL,
    estimated_cost DECIMAL(10,2) NULL,
    prepaid_amount DECIMAL(10,2) NULL,
    refund_amount DECIMAL(10,2) NULL,
    min_threshold DECIMAL(10,2) NULL,
    wallet_validation_passed BOOLEAN DEFAULT FALSE,
    wallet_validated_at TIMESTAMP NULL,
    steve_session_id VARCHAR(255) NULL,
    connector_id INTEGER DEFAULT 1,
    id_tag VARCHAR(255) NULL,
    reservation_id BIGINT NULL,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## API Reference

### ImmediateStartController

#### Get Status
```http
GET /api/immediate-start/status/{chargingPoint}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "available": true,
        "status": "online",
        "active_session": null,
        "steve_connection": {
            "connected": true,
            "message": "Connected to Steve API"
        }
    }
}
```

#### Start Charging
```http
POST /api/immediate-start/start/{chargingPoint}
```

**Request Body:**
```json
{
    "connector_id": 1,
    "id_tag": "user123",
    "reservation_id": null,
    "estimated_cost": 10.00,
    "prepaid_amount": 10.00,
    "min_threshold": 5.00
}
```

**Response:**
```json
{
    "success": true,
    "message": "Charging started successfully",
    "data": {
        "session": {
            "id": 1,
            "status": "charging",
            "started_at": "2024-01-01T10:00:00Z"
        },
        "steve_response": {
            "session_id": "steve-session-123",
            "success": true
        }
    }
}
```

#### Process Payment and Start
```http
POST /api/immediate-start/process-payment/{chargingPoint}
```

**Request Body:**
```json
{
    "amount": 10.00,
    "connector_id": 1,
    "id_tag": "user123",
    "estimated_cost": 10.00,
    "prepaid_amount": 10.00,
    "min_threshold": 5.00
}
```

**Response:**
```json
{
    "success": true,
    "message": "Payment processed and charging started successfully",
    "data": {
        "payment": {
            "success": true,
            "transaction_id": 123,
            "amount": 10.00,
            "balance_after": 40.00
        },
        "charging": {
            "success": true,
            "session": {
                "id": 1,
                "status": "charging"
            }
        }
    }
}
```

#### Check Wallet Balance
```http
GET /api/immediate-start/check-balance/{chargingPoint}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_balance": 50.00,
        "estimated_cost": 10.00,
        "sufficient_balance": true,
        "formatted_balance": "€50.00",
        "formatted_estimated_cost": "€10.00"
    }
}
```

#### Get Active Sessions
```http
GET /api/immediate-start/active-sessions
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "charging_point": {
                "id": 1,
                "name": "Charging Point 1",
                "location": "123 Main St"
            },
            "status": "charging",
            "started_at": "2024-01-01T10:00:00Z",
            "duration": 30,
            "connector_id": 1
        }
    ]
}
```

#### Stop Charging
```http
POST /api/immediate-start/stop/{session}
```

**Response:**
```json
{
    "success": true,
    "message": "Charging session stopped successfully"
}
```

#### Get Session Status
```http
GET /api/immediate-start/session-status/{session}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "session_id": 1,
        "status": "charging",
        "steve_status": {
            "success": true,
            "data": {
                "power": 7.2,
                "energy": 2.5
            }
        },
        "started_at": "2024-01-01T10:00:00Z",
        "ended_at": null,
        "duration": 30
    }
}
```

## Service Layer

### ImmediateStartService

#### Core Methods

##### Start Charging Immediately
```php
$result = $immediateStartService->startChargingImmediately($chargingPoint, $sessionData);

// Returns: array
// [
//     'success' => true,
//     'session' => ChargingSession,
//     'steve_response' => array,
//     'message' => 'Charging started successfully'
// ]
```

##### Process Payment and Start
```php
$result = $immediateStartService->processPaymentAndStart($chargingPoint, $paymentData, $sessionData);

// Returns: array
// [
//     'success' => true,
//     'payment' => array,
//     'charging' => array,
//     'message' => 'Payment processed and charging started successfully'
// ]
```

##### Get Immediate Start Status
```php
$status = $immediateStartService->getImmediateStartStatus($chargingPoint);

// Returns: array
// [
//     'available' => true,
//     'status' => 'online',
//     'active_session' => null,
//     'steve_connection' => array
// ]
```

##### Stop Charging Session
```php
$result = $immediateStartService->stopChargingSession($session);

// Returns: array
// [
//     'success' => true,
//     'message' => 'Charging session stopped successfully'
// ]
```

##### Get Charging Session Status
```php
$status = $immediateStartService->getChargingSessionStatus($session);

// Returns: array
// [
//     'session_id' => 1,
//     'status' => 'charging',
//     'steve_status' => array,
//     'started_at' => '2024-01-01T10:00:00Z',
//     'ended_at' => null,
//     'duration' => 30
// ]
```

## Frontend Integration

### JavaScript Functions

#### Check Wallet Balance
```javascript
async function checkWalletBalance() {
    const response = await fetch(`/api/immediate-start/check-balance/${chargingPointId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    const data = await response.json();
    return data.data;
}
```

#### Start Immediate Charging
```javascript
async function startImmediateCharging() {
    const formData = new FormData();
    formData.append('amount', calculateEstimatedCost());
    formData.append('connector_id', 1);
    formData.append('id_tag', userId);
    formData.append('estimated_cost', calculateEstimatedCost());
    formData.append('prepaid_amount', calculateEstimatedCost());
    formData.append('min_threshold', 5.00);

    const response = await fetch(`/api/immediate-start/process-payment/${chargingPointId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    });

    const data = await response.json();
    return data;
}
```

#### Calculate Estimated Cost
```javascript
function calculateEstimatedCost() {
    const baseRate = 0.25; // €0.25 per kWh
    const estimatedEnergy = (duration / 60) * 7; // 7 kW average power
    return baseRate * estimatedEnergy;
}
```

## Usage Examples

### Basic Immediate Start

#### Start Charging Immediately
```php
use App\Services\ImmediateStartService;

$immediateStartService = app(ImmediateStartService::class);

$sessionData = [
    'connector_id' => 1,
    'id_tag' => $user->id,
    'estimated_cost' => 10.00,
    'prepaid_amount' => 10.00,
    'min_threshold' => 5.00,
];

$result = $immediateStartService->startChargingImmediately($chargingPoint, $sessionData);

if ($result['success']) {
    echo "Charging started successfully!";
    echo "Session ID: " . $result['session']->id;
} else {
    echo "Failed to start charging: " . $result['message'];
}
```

#### Process Payment and Start
```php
$paymentData = [
    'amount' => 10.00,
    'charging_point_id' => $chargingPoint->id,
];

$sessionData = [
    'connector_id' => 1,
    'id_tag' => $user->id,
    'estimated_cost' => 10.00,
    'prepaid_amount' => 10.00,
    'min_threshold' => 5.00,
];

$result = $immediateStartService->processPaymentAndStart($chargingPoint, $paymentData, $sessionData);

if ($result['success']) {
    echo "Payment processed and charging started!";
    echo "Transaction ID: " . $result['payment']['transaction_id'];
    echo "Session ID: " . $result['charging']['session']->id;
} else {
    echo "Failed: " . $result['message'];
}
```

### API Usage

#### Check Status
```bash
curl -X GET "https://api.example.com/api/immediate-start/status/1" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Start Charging
```bash
curl -X POST "https://api.example.com/api/immediate-start/start/1" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "connector_id": 1,
    "id_tag": "user123",
    "estimated_cost": 10.00,
    "prepaid_amount": 10.00,
    "min_threshold": 5.00
  }'
```

#### Process Payment and Start
```bash
curl -X POST "https://api.example.com/api/immediate-start/process-payment/1" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10.00,
    "connector_id": 1,
    "id_tag": "user123",
    "estimated_cost": 10.00,
    "prepaid_amount": 10.00,
    "min_threshold": 5.00
  }'
```

## Error Handling

### Common Errors

#### Insufficient Balance
```json
{
    "success": false,
    "message": "Insufficient wallet balance for immediate start",
    "error": "Insufficient wallet balance for immediate start"
}
```

#### Charging Point Not Available
```json
{
    "success": false,
    "message": "Charging point is not available for immediate start",
    "error": "Charging point is not available for immediate start"
}
```

#### Steve API Failure
```json
{
    "success": false,
    "message": "Failed to start charging via Steve API: Connection timeout",
    "error": "Failed to start charging via Steve API: Connection timeout"
}
```

#### Validation Errors
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "amount": ["The amount field is required."],
        "connector_id": ["The connector id field must be an integer."]
    }
}
```

### Error Handling in JavaScript

```javascript
async function startImmediateCharging() {
    try {
        const response = await fetch(`/api/immediate-start/process-payment/${chargingPointId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showMessage('Charging started successfully!', 'success');
            // Redirect to charging session page
            window.location.href = `/charging-sessions/${data.data.charging.session.id}`;
        } else {
            showMessage(data.message || 'Failed to start charging', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    }
}
```

## Configuration

### Environment Variables
```env
# Steve API Configuration
STEVE_API_URL=http://158.69.27.239:8080
STEVE_TIMEOUT=30
STEVE_OCPP_ENABLED=true

# Immediate Start Configuration
IMMEDIATE_START_ENABLED=true
IMMEDIATE_START_DEFAULT_CONNECTOR=1
IMMEDIATE_START_MIN_THRESHOLD=5.00
```

### Configuration File
```php
// config/immediate_start.php
return [
    'enabled' => env('IMMEDIATE_START_ENABLED', true),
    'default_connector' => env('IMMEDIATE_START_DEFAULT_CONNECTOR', 1),
    'min_threshold' => env('IMMEDIATE_START_MIN_THRESHOLD', 5.00),
    'steve_api' => [
        'url' => env('STEVE_API_URL', 'http://158.69.27.239:8080'),
        'timeout' => env('STEVE_TIMEOUT', 30),
        'ocpp_enabled' => env('STEVE_OCPP_ENABLED', true),
    ],
    'fallback' => [
        'enabled' => true,
        'simulate_start' => true,
    ],
];
```

## Testing

### Unit Tests
```php
class ImmediateStartTest extends TestCase
{
    public function test_can_start_charging_immediately()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        $sessionData = [
            'connector_id' => 1,
            'id_tag' => $this->user->id,
        ];

        $response = $this->postJson("/api/immediate-start/start/{$chargingPoint->id}", $sessionData);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }
}
```

### Integration Tests
```php
public function test_process_payment_and_start_charging()
{
    $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
    $wallet = Wallet::factory()->create(['balance' => 50.00]);
    
    $paymentData = [
        'amount' => 10.00,
        'connector_id' => 1,
        'id_tag' => $this->user->id,
    ];

    $response = $this->postJson("/api/immediate-start/process-payment/{$chargingPoint->id}", $paymentData);
    
    $response->assertStatus(200)
            ->assertJson(['success' => true]);
    
    $this->assertEquals(40.00, $wallet->fresh()->balance);
}
```

## Security Considerations

### Authentication
- All immediate start endpoints require authentication
- Users can only access their own charging sessions
- Session ownership is validated before operations

### Authorization
- Users can only start charging on available charging points
- Session access is restricted to session owners
- Admin users have full access to all sessions

### Data Validation
- All input data is validated before processing
- Amount validation prevents negative values
- Connector ID validation ensures valid connectors

## Performance Considerations

### Caching
- Charging point status is cached for performance
- Steve API responses are cached when appropriate
- Session status is cached to reduce database queries

### Database Optimization
- Proper indexing on charging sessions table
- Eager loading of relationships
- Query optimization for status checks

### API Rate Limiting
- Rate limiting on immediate start endpoints
- Throttling for Steve API calls
- Circuit breaker pattern for API failures

## Monitoring

### Logging
- All immediate start operations are logged
- Steve API calls are logged with response times
- Error conditions are logged with full context

### Metrics
- Success rate of immediate starts
- Average response time for Steve API
- Number of active charging sessions
- Wallet balance distribution

### Alerts
- Steve API connection failures
- High error rates on immediate starts
- Insufficient balance warnings
- Charging point availability issues

## Troubleshooting

### Common Issues
1. **Steve API Connection Failed**: Check API URL and network connectivity
2. **Insufficient Balance**: Verify wallet balance and estimated costs
3. **Charging Point Not Available**: Check charging point status and active sessions
4. **Session Not Starting**: Verify Steve API response and session creation

### Debug Commands
```bash
# Check Steve API connection
php artisan tinker
>>> $service = app(\App\Services\ImmediateStartService::class);
>>> $status = $service->getImmediateStartStatus($chargingPoint);

# Check active sessions
php artisan tinker
>>> ChargingSession::where('status', 'charging')->count();

# Check wallet balances
php artisan tinker
>>> Wallet::where('balance', '<', 10)->count();
```

## Conclusion

The immediate start functionality provides a streamlined charging experience by removing the complexity of time selection and enabling instant charging after payment. With proper Steve API integration, wallet management, and comprehensive error handling, it ensures a reliable and user-friendly charging process.

For more information, see the related documentation:
- [Wallet System Documentation](WALLET_SYSTEM.md)
- [Money Service Documentation](MONEY_SERVICE.md)
- [Business Profile Auto-Link Documentation](BUSINESS_PROFILE_AUTO_LINK.md)
