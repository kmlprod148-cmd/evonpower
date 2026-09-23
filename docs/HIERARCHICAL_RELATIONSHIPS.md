# Hierarchical Relationships Documentation

This document describes the enforced hierarchical relationships in the EVON system.

## Hierarchy Structure

The system enforces the following hierarchical relationships:

```
Admin
  └── Integrator
      ├── Partner
      │   └── Group
      │       └── ChargingPoint
      └── Operator (User with 'operator' role)
```

## Model Relationships

### 1. Admin → Integrator
- **Constraint**: An Integrator must belong to one Admin
- **Implementation**: 
  - `Integrator::admin()` - belongsTo relationship to User with 'admin' role
  - Validation in `Integrator::boot()` method
  - `created_by` field must reference an admin user

### 2. Integrator → Operator
- **Constraint**: An Operator must belong to one Integrator
- **Implementation**:
  - `User::operatorIntegrator()` - belongsTo relationship to Integrator
  - `Integrator::operators()` - hasMany relationship to Users with 'operator' role
  - Validation in `User::boot()` method for operators

### 3. Partner → Group
- **Constraint**: A Group must belong to one Partner
- **Implementation**:
  - `Group::partner()` - belongsTo relationship to Partner
  - `Partner::groups()` - hasMany relationship to Groups
  - Validation in `Group::boot()` method

### 4. Group → ChargingPoint
- **Constraint**: A ChargingPoint must belong to one Group
- **Implementation**:
  - `ChargingPoint::group()` - belongsTo relationship to Group
  - `Group::chargingPoints()` - hasMany relationship to ChargingPoints
  - Validation in `ChargingPoint::boot()` method

## Validation Rules

### Model-Level Validation

Each model includes validation in its `boot()` method:

```php
// Example from Integrator model
protected static function boot()
{
    parent::boot();
    
    static::saving(function ($integrator) {
        if ($integrator->created_by) {
            $admin = User::where('id', $integrator->created_by)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })->first();
            
            if (!$admin) {
                throw new \Exception('Integrator must be created by an admin user.');
            }
        }
    });
}
```

### Service-Level Validation

The `HierarchicalValidationService` provides comprehensive validation:

```php
use App\Services\HierarchicalValidationService;

// Validate a single model
$errors = HierarchicalValidationService::validateHierarchy($model);

// Validate complete hierarchy chain
$errors = HierarchicalValidationService::validateCompleteHierarchy($model);

// Check if user can manage a resource
$canManage = HierarchicalValidationService::canUserManageResource($user, $resource);
```

## Access Control

### Role-Based Access

- **Admin**: Can manage all resources in the hierarchy
- **Integrator**: Can manage their own resources and their partners' resources
- **Partner**: Can manage their own resources and their groups' resources
- **Operator**: Can manage resources in their integrator's hierarchy

### Middleware Protection

The `EnforceHierarchy` middleware automatically checks permissions:

```php
// Register in app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\EnforceHierarchy::class,
];
```

## Database Constraints

### Foreign Key Relationships

```sql
-- Integrator belongs to Admin (User)
ALTER TABLE integrators ADD CONSTRAINT fk_integrators_admin 
FOREIGN KEY (created_by) REFERENCES users(id);

-- User (Operator) belongs to Integrator
ALTER TABLE users ADD CONSTRAINT fk_users_integrator 
FOREIGN KEY (integrator_id) REFERENCES integrators(id);

-- Partner belongs to Integrator
ALTER TABLE partners ADD CONSTRAINT fk_partners_integrator 
FOREIGN KEY (integrator_id) REFERENCES integrators(id);

-- Group belongs to Partner
ALTER TABLE groups ADD CONSTRAINT fk_groups_partner 
FOREIGN KEY (partner_id) REFERENCES partners(id);

-- ChargingPoint belongs to Group
ALTER TABLE charging_points ADD CONSTRAINT fk_charging_points_group 
FOREIGN KEY (group_id) REFERENCES groups(id);
```

## Usage Examples

### Creating a Complete Hierarchy

```php
// 1. Create Admin
$admin = User::create([
    'name' => 'System Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password')
]);
$admin->assignRole('admin');

// 2. Create Integrator
$integrator = Integrator::create([
    'name' => 'Test Integrator',
    'email' => 'integrator@example.com',
    'created_by' => $admin->id,
    'created_by_role' => 'admin'
]);

// 3. Create Partner
$partner = Partner::create([
    'name' => 'Test Partner',
    'email' => 'partner@example.com',
    'integrator_id' => $integrator->id,
    'created_by' => $admin->id,
    'created_by_role' => 'admin'
]);

// 4. Create Group
$group = Group::create([
    'name' => 'Test Group',
    'description' => 'Test Description',
    'partner_id' => $partner->id,
    'user_id' => $admin->id
]);

// 5. Create ChargingPoint
$chargingPoint = ChargingPoint::create([
    'name' => 'Test Charging Point',
    'serial_number' => 'TEST123',
    'group_id' => $group->id,
    'partner_id' => $partner->id,
    'integrator_id' => $integrator->id,
    'created_by' => $admin->id,
    'created_by_role' => 'admin'
]);
```

### Validating Hierarchy

```php
// Validate a model
$errors = HierarchicalValidationService::validateHierarchy($chargingPoint);
if (!empty($errors)) {
    throw new \Exception('Hierarchy validation failed: ' . implode(', ', $errors));
}

// Get hierarchy path
$path = HierarchicalValidationService::getHierarchyPath($chargingPoint);
// Returns: ['Admin: System Admin', 'Integrator: Test Integrator', 'Partner: Test Partner', 'Group: Test Group', 'ChargingPoint']
```

## Testing

Run the hierarchical relationships tests:

```bash
php artisan test tests/Feature/HierarchicalRelationshipsTest.php
```

The test suite covers:
- Valid hierarchy creation
- Invalid hierarchy prevention
- Relationship validation
- Access control verification

## Error Handling

When hierarchical constraints are violated, the system throws exceptions with descriptive messages:

- `"Integrator must be created by an admin user."`
- `"Operator must belong to a valid integrator."`
- `"Group must belong to a valid partner."`
- `"ChargingPoint must belong to a valid group."`

## Best Practices

1. **Always validate hierarchy** before saving models
2. **Use the validation service** for complex hierarchy checks
3. **Implement proper error handling** for constraint violations
4. **Test hierarchy relationships** thoroughly
5. **Use middleware** to enforce access control
6. **Maintain referential integrity** in the database

## Troubleshooting

### Common Issues

1. **"Integrator must be created by an admin user"**
   - Ensure the `created_by` field references a user with 'admin' role

2. **"Operator must belong to a valid integrator"**
   - Ensure the `integrator_id` field references a valid integrator

3. **"Group must belong to a valid partner"**
   - Ensure the `partner_id` field references a valid partner

4. **"ChargingPoint must belong to a valid group"**
   - Ensure the `group_id` field references a valid group

### Debugging

Use the hierarchy path to debug relationship issues:

```php
$path = HierarchicalValidationService::getHierarchyPath($model);
dd($path); // Shows the complete hierarchy path
```
