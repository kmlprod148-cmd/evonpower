# Offline Transaction Confirmation System

## Overview

This system allows administrators to confirm offline transactions for reservations made by guests and complete the transaction logic with fee calculations based on commission plans applied to charging points.

## Features

### 1. Admin Confirmation Interface
- **Reservation Management**: Admins can view and manage all offline reservations
- **Fee Calculation**: Automatic calculation of fees based on commission plans
- **Custom Fee Application**: Ability to apply custom fixed and percentage fees
- **Transaction Completion**: Complete transaction processing with commission distribution

### 2. Fee Structure
The system supports two types of fees:

#### Fixed Fees (€)
- One-time amount per transaction
- Applied regardless of transaction value
- Configurable per entity (Admin, Integrator, Partner)

#### Percentage Fees (%)
- Calculated as a percentage of transaction value
- Applied to the total transaction amount
- Configurable per entity (Admin, Integrator, Partner)

### 3. Commission Distribution
Fees are distributed among three entities:
- **Admin**: Platform administrator fees
- **Integrator**: Charging point integrator fees
- **Partner**: Charging point owner/partner fees

## Database Schema

### Business Profiles Table
New fee fields added to `business_profiles` table:

```sql
-- Admin fees
admin_fee_fixed DECIMAL(10,2) DEFAULT 0
admin_fee_percentage DECIMAL(5,2) DEFAULT 0

-- Integrator fees
integrator_fee_fixed DECIMAL(10,2) DEFAULT 0
integrator_fee_percentage DECIMAL(5,2) DEFAULT 0

-- Partner fees
partner_fee_fixed DECIMAL(10,2) DEFAULT 0
partner_fee_percentage DECIMAL(5,2) DEFAULT 0
```

### Reservation Status Flow
```
PENDING → PENDING_CONFIRMATION → CONFIRMED/COMPLETED
                ↓
            CANCELED
```

## API Endpoints

### Admin Reservation Management

#### 1. List Offline Reservations
```
GET /admin/reservations
```
- Shows all offline reservations pending confirmation
- Includes filtering by status, charging point, and date range
- Displays statistics for different reservation states

#### 2. View Reservation Details
```
GET /admin/reservations/{reservation}
```
- Detailed view of reservation with suggested fees
- Shows user information, charging point details, and pricing plan
- Displays transaction information if exists

#### 3. Confirm Reservation (Default Fees)
```
POST /admin/reservations/{reservation}/confirm
```
- Confirms reservation with default commission plan fees
- Creates or updates associated transaction
- Applies standard fee calculations

#### 4. Confirm Reservation (Custom Fees)
```
POST /admin/reservations/{reservation}/confirm-with-custom-fees
```
- Confirms reservation with custom fee structure
- Allows modification of actual costs and fees
- Supports both fixed and percentage fees

#### 5. Reject Reservation
```
POST /admin/reservations/{reservation}/reject
```
- Cancels reservation and associated transaction
- Updates status to CANCELED

## Fee Calculation Logic

### Default Fee Calculation
```php
// Based on commission plan
$adminCommission = $totalAmount * ($commissionPlan->admin_percentage / 100);
$integratorCommission = $totalAmount * ($commissionPlan->integrator_percentage / 100);
$partnerCommission = $totalAmount * ($commissionPlan->partner_percentage / 100);
```

### Custom Fee Calculation
```php
// Fixed + Percentage fees
$adminCommission = $adminFeeFixed + ($totalAmount * ($adminFeePercentage / 100));
$integratorCommission = $integratorFeeFixed + ($totalAmount * ($integratorFeePercentage / 100));
$partnerCommission = $partnerFeeFixed + ($totalAmount * ($partnerFeePercentage / 100));
```

### Business Profile Override
If a charging point has a business profile with specific fees:
- Business profile fees override commission plan defaults
- Fixed fees are added to percentage calculations
- Total commission = Fixed + (Amount × Percentage)

## Services

### ReservationFeeService
Main service handling fee calculations and application:

#### Methods:
- `calculateFees(Reservation $reservation, float $actualCost = null)`: Calculate fees based on commission plan
- `calculateCustomFees(float $cost, array $customRates)`: Calculate fees with custom rates
- `applyFeesToTransaction(Transaction $transaction, array $fees)`: Apply calculated fees to transaction
- `getSuggestedFees(Reservation $reservation)`: Get suggested fees for display
- `validateFees(array $fees)`: Validate fee structure
- `calculateReservationCost(Reservation $reservation)`: Calculate reservation cost based on type and value

## Admin Interface

### Reservation Index Page
- **Statistics Dashboard**: Shows counts for different reservation states
- **Filtering Options**: Filter by status, charging point, date range
- **Quick Actions**: Confirm, reject, or customize fees for each reservation
- **Real-time Updates**: Auto-refresh for pending confirmations

### Reservation Detail Page
- **Basic Information**: User details, charging point, pricing plan
- **Reservation Details**: Type, value, estimated costs, dates
- **Suggested Fees**: Display of recommended fee structure
- **Action Buttons**: Confirm with default fees, custom fees, or reject
- **Transaction Information**: Associated transaction details if exists

### Custom Fee Modal
- **Cost Input**: Actual cost, energy, duration
- **Fee Configuration**: Fixed and percentage fees for each entity
- **Real-time Calculation**: Live fee calculation display
- **Validation**: Input validation for fee ranges

## Usage Examples

### 1. Standard Confirmation
```php
// Admin confirms reservation with default fees
$reservation = Reservation::find(1);
$adminController->confirm($reservation);

// Result: Transaction created with commission plan fees
```

### 2. Custom Fee Confirmation
```php
// Admin confirms with custom fees
$customFees = [
    'actual_cost' => 25.50,
    'admin_fee_fixed' => 1.00,
    'admin_fee_percentage' => 5.0,
    'integrator_fee_fixed' => 0.50,
    'integrator_fee_percentage' => 3.0,
    'partner_fee_fixed' => 0.25,
    'partner_fee_percentage' => 2.0
];

$adminController->confirmWithCustomFees($request, $reservation);
```

### 3. Fee Calculation Example
```php
$feeService = new ReservationFeeService();

// Calculate fees for a €25.50 transaction
$fees = $feeService->calculateCustomFees(25.50, [
    'admin_fee_fixed' => 1.00,
    'admin_fee_percentage' => 5.0,
    'integrator_fee_fixed' => 0.50,
    'integrator_fee_percentage' => 3.0,
    'partner_fee_fixed' => 0.25,
    'partner_fee_percentage' => 2.0
]);

// Result:
// Admin: €1.00 + (€25.50 × 5%) = €2.28
// Integrator: €0.50 + (€25.50 × 3%) = €1.27
// Partner: €0.25 + (€25.50 × 2%) = €0.76
// Total Commission: €4.31
// Net Amount: €21.19
```

## Configuration

### Commission Plans
Configure default commission percentages in the commission plans table:
```php
CommissionPlan::create([
    'name' => 'Standard Plan',
    'admin_percentage' => 5.0,
    'integrator_percentage' => 3.0,
    'partner_percentage' => 2.0,
    'is_default' => true
]);
```

### Business Profile Fees
Set specific fees for charging point owners:
```php
BusinessProfile::create([
    'name' => 'Premium Partner',
    'integrator_fee_fixed' => 1.00,
    'integrator_fee_percentage' => 4.0,
    'partner_fee_fixed' => 0.50,
    'partner_fee_percentage' => 3.0
]);
```

## Security

### Authorization
- Only users with admin role can access reservation management
- Reservation policies control access to individual reservations
- All actions are logged for audit purposes

### Validation
- Fee percentages must be between 0-100%
- Fixed fees must be non-negative
- Actual costs must be positive numbers
- Input sanitization for all user inputs

## Logging

All admin actions are logged with detailed information:
```php
Log::info('Admin confirmed offline reservation', [
    'reservation_id' => $reservation->id,
    'transaction_id' => $transaction->id,
    'admin_id' => auth()->id(),
    'custom_fees' => $request->only([...])
]);
```

## Error Handling

### Common Scenarios
1. **Invalid Fee Structure**: Validation errors for fee inputs
2. **Missing Commission Plan**: Fallback to default calculations
3. **Transaction Creation Failure**: Rollback of all changes
4. **Database Errors**: Proper error messages and logging

### Error Responses
```json
{
    "error": "Invalid fee structure",
    "details": [
        "Admin fee percentage must be between 0 and 100"
    ]
}
```

## Testing

### Unit Tests
- Fee calculation accuracy
- Commission distribution logic
- Validation rules
- Service method coverage

### Integration Tests
- Admin confirmation workflow
- Transaction creation process
- Database consistency
- Authorization checks

## Future Enhancements

### Planned Features
1. **Bulk Operations**: Confirm multiple reservations at once
2. **Fee Templates**: Save and reuse custom fee structures
3. **Advanced Reporting**: Detailed commission reports
4. **Automated Notifications**: Email/SMS notifications for confirmations
5. **Mobile Interface**: Admin app for mobile confirmation

### API Extensions
1. **Webhook Support**: Notify external systems of confirmations
2. **Batch Processing**: Process multiple transactions efficiently
3. **Audit Trail**: Complete history of all fee modifications
4. **Export Functionality**: Export transaction data for accounting

## Troubleshooting

### Common Issues

#### 1. Migration Errors
```bash
# If fee columns already exist
php artisan migrate:rollback
php artisan migrate
```

#### 2. Fee Calculation Errors
- Check commission plan exists and is active
- Verify business profile fee configuration
- Ensure all required fields are present

#### 3. Authorization Issues
- Verify user has admin role
- Check reservation policies
- Ensure proper middleware is applied

### Debug Commands
```bash
# Check reservation status
php artisan tinker
>>> App\Models\Reservation::where('payment_type', 'offline')->get()

# Verify fee calculations
>>> $feeService = new App\Services\ReservationFeeService();
>>> $reservation = App\Models\Reservation::find(1);
>>> $feeService->calculateFees($reservation);
```

## Support

For technical support or questions about the offline transaction confirmation system:
1. Check the logs for detailed error information
2. Verify database schema and migrations
3. Test with sample data to isolate issues
4. Contact the development team with specific error details
