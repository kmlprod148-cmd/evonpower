# Auto-Assignment System Documentation

This document describes the automatic relationship assignment system that ensures entities are created with the correct hierarchical relationships based on the authenticated user's role and organizational tree.

## Overview

The auto-assignment system automatically sets related fields (integrator_id, group_id, partner_id, etc.) when creating entities, ensuring proper hierarchical relationships without requiring manual field assignment.

## Core Components

### 1. AutoAssignmentService

The main service that handles relationship auto-assignment for different model types.

**Key Methods:**
- `assignUserRelationships()` - Auto-assign for User models
- `assignChargingPointRelationships()` - Auto-assign for ChargingPoint models
- `assignGroupRelationships()` - Auto-assign for Group models
- `assignPartnerRelationships()` - Auto-assign for Partner models
- `getAvailableOptions()` - Get available options for the authenticated user
- `validateAssignment()` - Validate if user can assign to specific relationships
- `getDefaultValues()` - Get default values for creating entities

### 2. AutoAssignRelationships Middleware

Middleware that automatically processes requests and assigns relationships based on the route and authenticated user.

### 3. AutoAssignmentRule

Custom validation rule that ensures users can only assign to relationships within their hierarchical scope.

## Auto-Assignment Logic

### Admin Users
- Can assign to any integrator
- Can assign to any partner
- Can assign to any group
- Full access to all relationships

### Integrator Users
- Can only assign to their own integrator
- Can assign to their integrator's partners
- Can assign to their integrator's groups
- Can assign to their integrator's charging points

### Partner Users
- Can only assign to their own partner
- Can assign to their partner's groups
- Can assign to their partner's charging points
- Auto-assigns to their integrator

### Operator Users
- Can assign to their integrator's hierarchy
- Can assign to their integrator's partners' groups
- Can assign to their integrator's charging points

## Usage Examples

### In Controllers

```php
use App\Services\AutoAssignmentService;
use App\Traits\PolicyEnforcement;

class UserController extends Controller
{
    use PolicyEnforcement;

    public function store(Request $request)
    {
        $this->authorizeCreate(User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'integrator_id' => [
                'nullable',
                'integer',
                'exists:integrators,id',
                new AutoAssignmentRule('user', 'integrator_id'),
            ],
        ]);

        // Auto-assign relationships
        $autoAssignedData = AutoAssignmentService::assignUserRelationships(new User(), $validated);
        $userData = array_merge($validated, $autoAssignedData);

        $user = User::create($userData);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'auto_assigned_fields' => $autoAssignedData,
        ], 201);
    }
}
```

### In ChargingPoint Controller

```php
public function store(Request $request)
{
    $this->authorizeCreate(ChargingPoint::class);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'serial_number' => 'required|string|unique:charging_points',
        'group_id' => [
            'nullable',
            'integer',
            'exists:groups,id',
            new AutoAssignmentRule('charging_point', 'group_id'),
        ],
        'status' => 'required|in:online,offline,maintenance',
    ]);

    // Auto-assign relationships
    $autoAssignedData = AutoAssignmentService::assignChargingPointRelationships(new ChargingPoint(), $validated);
    $chargingPointData = array_merge($validated, $autoAssignedData);

    $chargingPoint = ChargingPoint::create($chargingPointData);

    return response()->json([
        'message' => 'Charging point created successfully',
        'charging_point' => $chargingPoint->load(['group', 'partner', 'integrator']),
        'auto_assigned_fields' => $autoAssignedData,
    ], 201);
}
```

### Using Middleware

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\AutoAssignRelationships::class,
];
```

The middleware will automatically process requests and assign relationships based on the route and authenticated user.

### Getting Available Options

```php
// Get available integrators for the authenticated user
$integrators = AutoAssignmentService::getAvailableOptions('integrators');

// Get available groups for the authenticated user
$groups = AutoAssignmentService::getAvailableOptions('groups');

// Get available partners for the authenticated user
$partners = AutoAssignmentService::getAvailableOptions('partners');
```

### Getting Default Values

```php
// Get default values for creating a user
$defaults = AutoAssignmentService::getDefaultValues('user');

// Get default values for creating a charging point
$defaults = AutoAssignmentService::getDefaultValues('charging_point');
```

### Validating Assignments

```php
// Check if user can assign to specific integrator
$canAssign = AutoAssignmentService::validateAssignment('user', [
    'integrator_id' => $integratorId
]);

// Check if user can assign to specific group
$canAssign = AutoAssignmentService::validateAssignment('charging_point', [
    'group_id' => $groupId
]);
```

## API Endpoints

### Get Available Options

```http
GET /api/users/available-integrators
GET /api/charging-points/available-groups
GET /api/partners/available-partners
```

### Get Default Values

```http
GET /api/users/default-values
GET /api/charging-points/default-values
```

### Validate Assignment

```http
POST /api/users/validate-integrator-assignment
Content-Type: application/json

{
    "integrator_id": 1
}
```

## Frontend Integration

### JavaScript Example

```javascript
// Get available options
async function getAvailableIntegrators() {
    const response = await fetch('/api/users/available-integrators');
    const data = await response.json();
    return data.integrators;
}

// Get default values
async function getDefaultValues() {
    const response = await fetch('/api/users/default-values');
    const data = await response.json();
    return data.default_values;
}

// Validate assignment
async function validateIntegratorAssignment(integratorId) {
    const response = await fetch('/api/users/validate-integrator-assignment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ integrator_id: integratorId })
    });
    const data = await response.json();
    return data.can_assign;
}
```

### Blade Template Example

```blade
<form action="{{ route('users.store') }}" method="POST">
    @csrf
    
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control" required>
    </div>
    
    @if(auth()->user()->hasRole('admin'))
        <div class="form-group">
            <label for="integrator_id">Integrator</label>
            <select name="integrator_id" id="integrator_id" class="form-control">
                <option value="">Select Integrator</option>
                @foreach($availableIntegrators as $integrator)
                    <option value="{{ $integrator->id }}">{{ $integrator->name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <input type="hidden" name="integrator_id" value="{{ $defaultValues['integrator_id'] ?? '' }}">
    @endif
    
    <button type="submit" class="btn btn-primary">Create User</button>
</form>
```

## Testing

### Unit Tests

```php
class AutoAssignmentTest extends TestCase
{
    public function test_integrator_can_only_assign_to_their_own_integrator()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $this->actingAs($integrator);
        
        $data = [];
        $autoAssigned = AutoAssignmentService::assignUserRelationships(new User(), $data);
        
        $this->assertEquals($integrator->integrator_id, $autoAssigned['integrator_id']);
        $this->assertEquals($integrator->id, $autoAssigned['created_by']);
        $this->assertEquals('integrator', $autoAssigned['created_by_role']);
    }
}
```

### Feature Tests

```php
public function test_middleware_auto_assigns_relationships()
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $this->actingAs($admin);
    
    $response = $this->postJson('/api/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'operator',
    ]);
    
    $response->assertStatus(201);
    
    $user = User::where('email', 'test@example.com')->first();
    $this->assertNotNull($user);
    $this->assertEquals($admin->id, $user->created_by);
    $this->assertEquals('admin', $user->created_by_role);
}
```

## Configuration

### Register Middleware

Add to `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\AutoAssignRelationships::class,
];
```

### Register Validation Rule

The `AutoAssignmentRule` is automatically available for use in validation.

## Best Practices

1. **Always use auto-assignment** for hierarchical relationships
2. **Validate assignments** before creating entities
3. **Provide user feedback** about auto-assigned fields
4. **Test thoroughly** with different user roles
5. **Use middleware** for automatic processing
6. **Handle edge cases** gracefully
7. **Document custom logic** clearly

## Troubleshooting

### Common Issues

1. **"Auto-assignment not working"**
   - Ensure middleware is registered
   - Check that user is authenticated
   - Verify route matches expected patterns

2. **"Permission denied for assignment"**
   - Check user's role and hierarchical position
   - Verify relationship exists and is accessible
   - Ensure proper validation rules are in place

3. **"Default values not loading"**
   - Check that user is authenticated
   - Verify user has proper role assigned
   - Ensure relationships exist in database

### Debug Mode

Enable debug information by setting `APP_DEBUG=true` in your `.env` file. This will show:

- Auto-assigned fields in session
- Validation errors
- Relationship information
- User role and permissions

## Security Considerations

1. **Never bypass auto-assignment** for convenience
2. **Always validate permissions** before assignment
3. **Use HTTPS** for all auto-assignment requests
4. **Log assignment activities** for audit purposes
5. **Regularly review** assignment logic
6. **Test edge cases** thoroughly
