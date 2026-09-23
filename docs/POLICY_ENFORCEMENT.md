# Policy Enforcement Documentation

This document describes the comprehensive policy enforcement system implemented for the EVON application.

## Overview

The policy enforcement system ensures that users can only access and modify resources within their hierarchical scope:

- **Admin**: Can view/edit all resources
- **Integrator**: Can only view/edit their operators and related resources
- **Partner**: Can only view/edit their groups and charging points
- **Operator**: Can only manage their own charging points

## Policy Classes

### 1. OperatorPolicy

Controls access to User models with 'operator' role.

**Key Methods:**
- `viewAny()` - Check if user can view any operators
- `view()` - Check if user can view specific operator
- `create()` - Check if user can create operators
- `update()` - Check if user can update operator
- `delete()` - Check if user can delete operator
- `manageChargingPoints()` - Check if user can manage operator's charging points

**Access Rules:**
- Admin: Full access to all operators
- Integrator: Can manage their own operators
- Operator: Can manage themselves

### 2. IntegratorPolicy

Controls access to Integrator models.

**Key Methods:**
- `viewAny()` - Check if user can view any integrators
- `view()` - Check if user can view specific integrator
- `create()` - Check if user can create integrators
- `update()` - Check if user can update integrator
- `delete()` - Check if user can delete integrator
- `managePartners()` - Check if user can manage integrator's partners
- `manageOperators()` - Check if user can manage integrator's operators
- `viewStatistics()` - Check if user can view integrator statistics

**Access Rules:**
- Admin: Full access to all integrators
- Integrator: Can manage themselves

### 3. ChargingPointPolicy

Controls access to ChargingPoint models.

**Key Methods:**
- `viewAny()` - Check if user can view any charging points
- `view()` - Check if user can view specific charging point
- `create()` - Check if user can create charging points
- `update()` - Check if user can update charging point
- `delete()` - Check if user can delete charging point
- `manageSessions()` - Check if user can manage charging sessions
- `manageReservations()` - Check if user can manage reservations
- `control()` - Check if user can control charging point
- `configure()` - Check if user can configure charging point

**Access Rules:**
- Admin: Full access to all charging points
- Integrator: Can manage their charging points
- Partner: Can manage their charging points
- Operator: Can manage their integrator's charging points

## Error Handling

### PolicyException

Custom exception class that provides detailed error information:

```php
use App\Exceptions\PolicyException;

// Create a policy exception
$exception = PolicyException::denied('ChargingPointPolicy', 'view', $model, $user);

// Get exception details
$policy = $exception->getPolicy();
$action = $exception->getAction();
$model = $exception->getModel();
```

### 403 Error Page

Custom Blade template (`resources/views/errors/403.blade.php`) provides:

- User-friendly error messages
- Role-based guidance
- Debug information (in development mode)
- Navigation options

## Middleware

### EnforcePolicies

Automatically converts `AuthorizationException` to `PolicyException` with contextual information:

```php
// Register in app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\EnforcePolicies::class,
];
```

## Helper Traits

### PolicyEnforcement

Provides convenient methods for controllers:

```php
use App\Traits\PolicyEnforcement;

class ChargingPointController extends Controller
{
    use PolicyEnforcement;

    public function show(ChargingPoint $chargingPoint)
    {
        // Authorize viewing the charging point
        $this->authorizeView($chargingPoint);
        
        // Get filtered models based on user's access
        $chargingPoints = $this->getAccessibleModels(ChargingPoint::class);
        
        // Check if user can perform action
        $canUpdate = $this->canPerformAction('update', $chargingPoint);
    }
}
```

## Gates

Additional gates defined in `AuthServiceProvider`:

- `manage-integrator-operators` - Check if user can manage integrator's operators
- `manage-partner-groups` - Check if user can manage partner's groups
- `manage-group-charging-points` - Check if user can manage group's charging points

## Usage Examples

### In Controllers

```php
class ChargingPointController extends Controller
{
    use PolicyEnforcement;

    public function index()
    {
        // Get only charging points accessible to the user
        $chargingPoints = $this->getAccessibleModels(ChargingPoint::class);
        
        return view('charging-points.index', compact('chargingPoints'));
    }

    public function show(ChargingPoint $chargingPoint)
    {
        // Authorize access with custom error handling
        $this->authorizeView($chargingPoint);
        
        return view('charging-points.show', compact('chargingPoint'));
    }

    public function update(Request $request, ChargingPoint $chargingPoint)
    {
        // Authorize update access
        $this->authorizeUpdate($chargingPoint);
        
        $chargingPoint->update($request->validated());
        
        return redirect()->back()->with('success', 'Updated successfully.');
    }
}
```

### In Blade Templates

```blade
@can('view', $chargingPoint)
    <div class="charging-point">
        <h3>{{ $chargingPoint->name }}</h3>
        <p>{{ $chargingPoint->description }}</p>
        
        @can('update', $chargingPoint)
            <a href="{{ route('charging-points.edit', $chargingPoint) }}">Edit</a>
        @endcan
        
        @can('control', $chargingPoint)
            <button onclick="startCharging({{ $chargingPoint->id }})">Start Charging</button>
        @endcan
    </div>
@endcan
```

### In API Routes

```php
Route::middleware(['auth:sanctum', 'enforce-policies'])->group(function () {
    Route::get('/charging-points', [ChargingPointController::class, 'index']);
    Route::get('/charging-points/{chargingPoint}', [ChargingPointController::class, 'show']);
    Route::put('/charging-points/{chargingPoint}', [ChargingPointController::class, 'update']);
    Route::delete('/charging-points/{chargingPoint}', [ChargingPointController::class, 'destroy']);
});
```

## Testing

### Policy Tests

```php
class PolicyEnforcementTest extends TestCase
{
    public function test_admin_can_view_all_operators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($admin);
        $this->assertTrue($admin->can('view', $operator));
    }

    public function test_integrator_can_only_view_their_operators()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $theirOperator = User::factory()->create(['integrator_id' => $integrator->integrator_id]);
        $theirOperator->assignRole('operator');
        
        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');
        
        $this->actingAs($integrator);
        $this->assertTrue($integrator->can('view', $theirOperator));
        $this->assertFalse($integrator->can('view', $otherOperator));
    }
}
```

## Configuration

### Registering Policies

Policies are automatically registered in `AuthServiceProvider`:

```php
protected $policies = [
    User::class => OperatorPolicy::class,
    Integrator::class => IntegratorPolicy::class,
    ChargingPoint::class => ChargingPointPolicy::class,
];
```

### Registering Middleware

Add middleware to `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\EnforcePolicies::class,
];
```

### Error Routes

Register error routes in `routes/errors.php`:

```php
Route::get('/errors/403', function () {
    return view('errors.403');
})->name('errors.403');
```

## Best Practices

1. **Always use policies** for authorization instead of manual checks
2. **Use the PolicyEnforcement trait** in controllers for consistent behavior
3. **Test policies thoroughly** with different user roles
4. **Provide clear error messages** to users
5. **Use gates for complex authorization logic**
6. **Filter models** based on user's hierarchical access
7. **Handle both web and API requests** appropriately

## Troubleshooting

### Common Issues

1. **"Policy not found" error**
   - Ensure policy is registered in `AuthServiceProvider`
   - Check that policy class exists and is properly namespaced

2. **"Access denied" for valid users**
   - Verify user has correct role assigned
   - Check hierarchical relationships are properly set
   - Ensure policy logic matches business requirements

3. **403 page not showing**
   - Ensure error routes are registered
   - Check that `EnforcePolicies` middleware is applied
   - Verify Blade template exists

### Debug Mode

Enable debug information in the 403 error page by setting `APP_DEBUG=true` in your `.env` file. This will show:

- Current URL and method
- User ID and roles
- Policy error details
- Request information

## Security Considerations

1. **Never bypass policies** for convenience
2. **Always validate hierarchical relationships** before granting access
3. **Use HTTPS** for all policy-related requests
4. **Log authorization failures** for security monitoring
5. **Regularly audit** policy implementations
6. **Test edge cases** thoroughly
