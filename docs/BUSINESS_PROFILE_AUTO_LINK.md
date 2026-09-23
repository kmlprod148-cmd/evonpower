# Business Profile Auto-Linking Documentation

This document describes the automatic business profile linking system for ChargingPoints based on their operator and integrator relationships.

## Overview

The BusinessProfileAutoLinkService automatically assigns the most appropriate business profile to a ChargingPoint based on its hierarchical relationships (operator, partner, integrator) and validates that the profile exists and is active.

## Key Features

### 1. Automatic Business Profile Linking
- **Priority Order**: Operator > Partner > Integrator > Default
- **Auto-Assignment**: Automatically links business profiles on ChargingPoint creation
- **Validation**: Ensures business profiles exist and are active
- **Error Handling**: Graceful fallback with clear error messages

### 2. Business Profile Hierarchy
- **Operator Level**: Specific business profiles for individual operators
- **Partner Level**: Business profiles for partner organizations
- **Integrator Level**: Business profiles for integrator organizations
- **Default Level**: System-wide default business profiles

### 3. Compatibility Validation
- **Profile Validation**: Ensures business profiles are active and accessible
- **Compatibility Check**: Validates profile compatibility with charging point hierarchy
- **Error Messages**: Clear error messages for validation failures

## Database Schema

### Business Profiles Table
```sql
CREATE TABLE business_profiles (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_type VARCHAR(255) NOT NULL,  -- User, Partner, Integrator
    owner_id BIGINT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    pricing_rules JSON NULL,
    commission_rates JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Charging Points Table
```sql
CREATE TABLE charging_points (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    business_profile_id BIGINT NULL,
    group_id BIGINT NULL,
    partner_id BIGINT NULL,
    integrator_id BIGINT NULL,
    user_id BIGINT NULL,  -- operator
    -- ... other fields
);
```

## API Reference

### BusinessProfileAutoLinkService

#### Core Methods

##### Auto-Link Business Profile
```php
// Auto-link business profile for a charging point
$businessProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);

// Returns: BusinessProfile instance
// Throws: Exception if no profile found
```

##### Find Business Profile
```php
// Find appropriate business profile for a charging point
$businessProfile = BusinessProfileAutoLinkService::findBusinessProfileForChargingPoint($chargingPoint);

// Returns: BusinessProfile|null
```

##### Get Available Business Profiles
```php
// Get all available business profiles for a charging point
$profiles = BusinessProfileAutoLinkService::getAvailableBusinessProfiles($chargingPoint);

// Returns: array of profile information
// [
//     [
//         'id' => 1,
//         'name' => 'Operator Profile',
//         'type' => 'operator',
//         'owner_name' => 'John Doe'
//     ],
//     // ... more profiles
// ]
```

##### Get Recommended Business Profile
```php
// Get recommended business profile for a charging point
$recommended = BusinessProfileAutoLinkService::getRecommendedBusinessProfile($chargingPoint);

// Returns: array|null
// [
//     'id' => 1,
//     'name' => 'Operator Profile',
//     'type' => 'operator',
//     'reason' => 'Recommended because this charging point is managed by operator John Doe'
// ]
```

##### Validate Business Profile
```php
// Validate that a business profile exists and is active
$businessProfile = BusinessProfileAutoLinkService::validateBusinessProfile($businessProfileId);

// Returns: BusinessProfile instance
// Throws: Exception if invalid
```

##### Check Compatibility
```php
// Check if a business profile is compatible with a charging point
$isCompatible = BusinessProfileAutoLinkService::isBusinessProfileCompatible($businessProfileId, $chargingPoint);

// Returns: boolean
```

##### Get Statistics
```php
// Get business profile statistics for a charging point
$stats = BusinessProfileAutoLinkService::getBusinessProfileStats($chargingPoint);

// Returns: array
// [
//     'total_available' => 4,
//     'by_type' => [
//         'operator' => 1,
//         'partner' => 1,
//         'integrator' => 1,
//         'default' => 1
//     ],
//     'has_recommended' => true,
//     'recommended_profile' => [...]
// ]
```

### ChargingPointService

#### Business Profile Methods

##### Create with Business Profile
```php
// Create charging point with business profile validation
$chargingPoint = $service->createWithBusinessProfile($data, $businessProfileId);

// Returns: ChargingPoint instance
// Throws: Exception if business profile invalid
```

##### Update Business Profile
```php
// Update business profile for a charging point
$businessProfile = $service->updateBusinessProfile($chargingPoint, $businessProfileId);

// Returns: BusinessProfile instance
// Throws: Exception if invalid
```

##### Remove Business Profile
```php
// Remove business profile from a charging point
$service->removeBusinessProfile($chargingPoint);

// Returns: void
```

##### Get Available Business Profiles
```php
// Get available business profiles for a charging point
$profiles = $service->getAvailableBusinessProfiles($chargingPoint);

// Returns: array of profile information
```

##### Get Recommended Business Profile
```php
// Get recommended business profile for a charging point
$recommended = $service->getRecommendedBusinessProfile($chargingPoint);

// Returns: array|null
```

##### Validate Compatibility
```php
// Validate business profile compatibility
$isCompatible = $service->validateBusinessProfileCompatibility($chargingPoint, $businessProfileId);

// Returns: boolean
```

##### Get Statistics
```php
// Get business profile statistics
$stats = $service->getBusinessProfileStats($chargingPoint);

// Returns: array
```

### ChargingPoint Model

#### Business Profile Methods

##### Auto-Link Business Profile
```php
// Auto-link business profile for this charging point
$businessProfile = $chargingPoint->autoLinkBusinessProfile();

// Returns: BusinessProfile instance
```

##### Get Available Business Profiles
```php
// Get available business profiles for this charging point
$profiles = $chargingPoint->getAvailableBusinessProfiles();

// Returns: array of profile information
```

##### Get Recommended Business Profile
```php
// Get recommended business profile for this charging point
$recommended = $chargingPoint->getRecommendedBusinessProfile();

// Returns: array|null
```

##### Check Compatibility
```php
// Check if business profile is compatible
$isCompatible = $chargingPoint->isBusinessProfileCompatible($businessProfileId);

// Returns: boolean
```

##### Get Statistics
```php
// Get business profile statistics
$stats = $chargingPoint->getBusinessProfileStats();

// Returns: array
```

##### Validate Business Profile
```php
// Validate business profile assignment
$businessProfile = $chargingPoint->validateBusinessProfile($businessProfileId);

// Returns: BusinessProfile instance
// Throws: Exception if invalid
```

##### Get Hierarchy Methods
```php
// Get the operator for this charging point
$operator = $chargingPoint->getOperator();

// Get the integrator for this charging point
$integrator = $chargingPoint->getIntegrator();

// Get the partner for this charging point
$partner = $chargingPoint->getPartner();
```

## Usage Examples

### Basic Auto-Linking

#### Create Charging Point with Auto-Linking
```php
// Create charging point (business profile auto-linked)
$chargingPoint = ChargingPoint::create([
    'name' => 'Charging Point 1',
    'group_id' => $group->id,
    'partner_id' => $partner->id,
    'integrator_id' => $integrator->id,
    'user_id' => $operator->id,
]);

// Business profile automatically linked based on hierarchy
$businessProfile = $chargingPoint->businessProfile;
```

#### Manual Auto-Linking
```php
// Auto-link business profile for existing charging point
$businessProfile = $chargingPoint->autoLinkBusinessProfile();

// Or using service
$service = new ChargingPointService();
$businessProfile = $service->autoLinkBusinessProfile($chargingPoint);
```

### Business Profile Management

#### Get Available Business Profiles
```php
// Get all available business profiles for a charging point
$availableProfiles = $chargingPoint->getAvailableBusinessProfiles();

foreach ($availableProfiles as $profile) {
    echo "Profile: {$profile['name']} ({$profile['type']}) - {$profile['owner_name']}\n";
}
```

#### Get Recommended Business Profile
```php
// Get recommended business profile
$recommended = $chargingPoint->getRecommendedBusinessProfile();

if ($recommended) {
    echo "Recommended: {$recommended['name']} - {$recommended['reason']}\n";
}
```

#### Update Business Profile
```php
// Update business profile for a charging point
$service = new ChargingPointService();
$businessProfile = $service->updateBusinessProfile($chargingPoint, $businessProfileId);

echo "Updated to: {$businessProfile->name}\n";
```

#### Validate Business Profile
```php
// Validate business profile before assignment
try {
    $businessProfile = $chargingPoint->validateBusinessProfile($businessProfileId);
    echo "Valid business profile: {$businessProfile->name}\n";
} catch (\Exception $e) {
    echo "Invalid business profile: {$e->getMessage()}\n";
}
```

### Service Usage

#### Create Charging Point with Business Profile
```php
$service = new ChargingPointService();

// Create with specific business profile
$chargingPoint = $service->createWithBusinessProfile($data, $businessProfileId);

// Create with auto-linking
$chargingPoint = $service->createWithBusinessProfile($data);
```

#### Get Form Data
```php
$service = new ChargingPointService();
$user = auth()->user();

// Get create form data
$formData = $service->getCreateFormData($user);

// Get edit form data
$formData = $service->getEditFormData($chargingPoint, $user);
```

#### Get Charging Points with Business Profiles
```php
$service = new ChargingPointService();

// Get single charging point with business profile
$chargingPoint = $service->getChargingPointWithBusinessProfile($id);

// Get multiple charging points with business profiles
$chargingPoints = $service->getChargingPointsWithBusinessProfiles([
    'status' => 'online',
    'partner_id' => $partner->id,
]);
```

## Business Profile Priority

### 1. Operator Level (Highest Priority)
```php
// Operator-specific business profile
$operatorProfile = BusinessProfile::create([
    'name' => 'Operator Profile',
    'owner_type' => User::class,
    'owner_id' => $operator->id,
    'is_active' => true,
]);
```

### 2. Partner Level
```php
// Partner-specific business profile
$partnerProfile = BusinessProfile::create([
    'name' => 'Partner Profile',
    'owner_type' => Partner::class,
    'owner_id' => $partner->id,
    'is_active' => true,
]);
```

### 3. Integrator Level
```php
// Integrator-specific business profile
$integratorProfile = BusinessProfile::create([
    'name' => 'Integrator Profile',
    'owner_type' => Integrator::class,
    'owner_id' => $integrator->id,
    'is_active' => true,
]);
```

### 4. Default Level (Lowest Priority)
```php
// Default business profile
$defaultProfile = BusinessProfile::create([
    'name' => 'Default Profile',
    'is_default' => true,
    'is_active' => true,
]);
```

## Error Handling

### Common Exceptions

#### No Business Profile Found
```php
try {
    $businessProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'No business profile found')) {
        // Handle no profile found
        echo "No business profile available for this charging point hierarchy.\n";
    }
}
```

#### Invalid Business Profile
```php
try {
    $businessProfile = BusinessProfileAutoLinkService::validateBusinessProfile($businessProfileId);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'not found')) {
        echo "Business profile not found.\n";
    } elseif (str_contains($e->getMessage(), 'not active')) {
        echo "Business profile is not active.\n";
    }
}
```

#### Incompatible Business Profile
```php
try {
    $service->updateBusinessProfile($chargingPoint, $businessProfileId);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'not compatible')) {
        echo "Business profile is not compatible with this charging point.\n";
    }
}
```

## Configuration

### Business Profile Settings
```php
// config/business_profiles.php
return [
    'auto_link_enabled' => env('BUSINESS_PROFILE_AUTO_LINK_ENABLED', true),
    'default_profile_required' => env('BUSINESS_PROFILE_DEFAULT_REQUIRED', true),
    'validation_strict' => env('BUSINESS_PROFILE_VALIDATION_STRICT', true),
    'fallback_to_default' => env('BUSINESS_PROFILE_FALLBACK_TO_DEFAULT', true),
];
```

### Environment Variables
```env
# Business Profile Auto-Linking
BUSINESS_PROFILE_AUTO_LINK_ENABLED=true
BUSINESS_PROFILE_DEFAULT_REQUIRED=true
BUSINESS_PROFILE_VALIDATION_STRICT=true
BUSINESS_PROFILE_FALLBACK_TO_DEFAULT=true
```

## Testing

### Unit Tests
```php
class BusinessProfileAutoLinkTest extends TestCase
{
    public function test_auto_links_operator_business_profile()
    {
        // Create test data
        $integrator = Integrator::factory()->create();
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Test auto-linking
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($operatorProfile->id, $linkedProfile->id);
        $this->assertEquals($operatorProfile->id, $chargingPoint->fresh()->business_profile_id);
    }
}
```

### Integration Tests
```php
class BusinessProfileIntegrationTest extends TestCase
{
    public function test_charging_point_creation_with_auto_linking()
    {
        // Create test data
        $integrator = Integrator::factory()->create();
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        // Create charging point (should auto-link business profile)
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Check that business profile was auto-linked
        $this->assertEquals($operatorProfile->id, $chargingPoint->fresh()->business_profile_id);
    }
}
```

## Performance Considerations

### Caching Strategy
- **Business Profile Cache**: Cache business profiles for faster lookup
- **Hierarchy Cache**: Cache charging point hierarchy relationships
- **Compatibility Cache**: Cache business profile compatibility results

### Database Optimization
- **Indexing**: Proper indexing on business profile fields
- **Eager Loading**: Load relationships efficiently
- **Query Optimization**: Minimize database queries

### Memory Management
- **Lazy Loading**: Load business profiles on demand
- **Cache Cleanup**: Automatic cache expiration
- **Memory Limits**: Respect memory limits for large datasets

## Security Considerations

### Access Control
- **Role-Based Access**: Only authorized users can manage business profiles
- **Hierarchy Validation**: Ensure users can only access profiles within their hierarchy
- **Profile Validation**: Validate business profile ownership and access

### Data Integrity
- **Atomic Operations**: Database transactions for consistency
- **Validation Rules**: Strict validation of business profile data
- **Audit Trail**: Log all business profile changes

## Troubleshooting

### Common Issues
1. **No Business Profile Found**: Check if business profiles exist and are active
2. **Incompatible Profile**: Verify business profile compatibility with charging point
3. **Auto-Linking Failed**: Check hierarchy relationships and profile availability
4. **Validation Errors**: Ensure business profiles are properly configured

### Debug Commands
```bash
# Check business profile status
php artisan tinker
>>> BusinessProfile::where('is_active', true)->count()

# Test auto-linking
php artisan tinker
>>> $cp = ChargingPoint::find(1)
>>> BusinessProfileAutoLinkService::autoLinkBusinessProfile($cp)

# Check available profiles
php artisan tinker
>>> $cp = ChargingPoint::find(1)
>>> BusinessProfileAutoLinkService::getAvailableBusinessProfiles($cp)
```

### Monitoring
- **Auto-Linking Success Rate**: Monitor successful auto-linking
- **Profile Usage**: Track business profile usage patterns
- **Error Rates**: Monitor validation and compatibility errors
- **Performance Metrics**: Track service performance

## Conclusion

The BusinessProfileAutoLinkService provides a robust, automated solution for linking business profiles to charging points based on their hierarchical relationships. With proper configuration and monitoring, it ensures accurate business profile assignment while maintaining performance and reliability.

For more information, see the related documentation:
- [Wallet System Documentation](WALLET_SYSTEM.md)
- [Money Service Documentation](MONEY_SERVICE.md)
- [User Transaction View Documentation](USER_TRANSACTION_VIEW.md)
- [Admin Transaction View Documentation](ADMIN_TRANSACTION_VIEW.md)
