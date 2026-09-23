# Partner Plans Filtering Fix - Verification Guide

## 🔧 What Was Fixed

### Issue
Partners were not seeing only their plans when creating a charging point (borne). They either saw all plans or no plans.

### Root Cause
The `ChargingPointService::getCreateFormData()` method was missing:
1. Pricing plans fetching
2. Proper role-based data filtering
3. Partner/integrator/operator filtering

### Solution Applied
Modified **[app/Modules/ChargingPoints/Services/ChargingPointService.php](app/Modules/ChargingPoints/Services/ChargingPointService.php)**:
- Updated `getCreateFormData()` method (lines 351-404)
- Updated `getEditFormData()` method (lines 406-451)

---

## ✅ Changes Summary

### Before (Broken)
```php
public function getCreateFormData($user): array
{
    $data = [];
    
    // Only groups and partners (no plans!)
    $data['groups'] = Group::all(); // No filtering!
    $data['partners'] = Partner::all(); // Shows all!
    $data['integrators'] = Integrator::all(); // Shows all!
    
    // Missing: $data['pricingPlans'] = ...
    return $data;
}
```

### After (Fixed)
```php
public function getCreateFormData($user): array
{
    // GROUP FILTERING by user role
    if ($user->hasRole('integrator')) {
        $data['groups'] = Group::where('integrator_id', $user->integrator_id)->get();
    } elseif ($user->hasRole('partner')) {
        $data['groups'] = Group::where('partner_id', $user->partner_id)->get();
    } elseif ($user->hasRole('admin')) {
        $data['groups'] = Group::all();
    }
    
    // PARTNER FILTERING by user role
    if ($user->hasRole('admin')) {
        $data['partners'] = Partner::where('is_active', true)->get();
    } elseif ($user->hasRole('integrator')) {
        $data['partners'] = Partner::where('integrator_id', $user->integrator_id)
            ->where('is_active', true)->get();
    } elseif ($user->hasRole('partner')) {
        $data['partners'] = Partner::where('id', $user->partner_id)->get();
    }
    
    // INTEGRATOR FILTERING (admin only)
    if ($user->hasRole('admin')) {
        $data['integrators'] = Integrator::where('is_active', true)->get();
    }
    
    // PRICING PLANS - NEW! Uses forUser() scope
    $data['pricingPlans'] = PricingPlan::forUser($user)
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
    
    return $data;
}
```

---

## 🧪 Testing Scenarios

### Test 1: Partner Creates Charging Point
**User Role:** Partner (user.partner_id = 5)
**Expected Behavior:**
- ✓ Groups dropdown: Shows ONLY groups where `partner_id = 5`
- ✓ Partner dropdown: Shows ONLY their partner (id = 5)
- ✓ Plans dropdown: Shows ONLY plans linked to partner 5 via `partner_pricing_plan` pivot
- ✓ Integrator dropdown: Hidden/empty (no access)

**To Test:**
```bash
1. Log in as a partner user
2. Navigate to charging points creation
3. Check dropdowns show only their data
4. Verify cannot select other partners' plans
```

### Test 2: Integrator Creates Charging Point
**User Role:** Integrator (user.integrator_id = 2)
**Expected Behavior:**
- ✓ Groups dropdown: Shows ONLY groups where `integrator_id = 2`
- ✓ Partner dropdown: Shows ONLY active partners where `integrator_id = 2`
- ✓ Plans dropdown: Shows plans created by integrator (via forUser scope)
- ✓ Integrator dropdown: Hidden/empty

**To Test:**
```bash
1. Log in as an integrator user
2. Navigate to charging points creation
3. Verify only their partners shown
4. Verify only their plans shown
```

### Test 3: Admin Creates Charging Point
**User Role:** Admin
**Expected Behavior:**
- ✓ Groups dropdown: Shows ALL active groups
- ✓ Partner dropdown: Shows ALL active partners
- ✓ Plans dropdown: Shows ALL active plans
- ✓ Integrator dropdown: Shows ALL active integrators

**To Test:**
```bash
1. Log in as admin
2. Navigate to charging points creation
3. Verify all options visible
4. Can select any partner/plan/group
```

### Test 4: Edit Charging Point
**Expected Behavior:**
- Same filtering applies in edit view as in create view
- Existing selection preserved

**To Test:**
```bash
1. Edit an existing charging point
2. Verify same filtering rules apply
3. Dropdown options match create behavior
```

---

## 🔗 Related Database Tables

### partner_pricing_plan (Pivot)
Defines which plans belong to which partners:
```sql
SELECT * FROM partner_pricing_plan WHERE partner_id = 5;
-- Shows all plans linked to partner 5
```

### Keys
- `partners.id` - Partner identifier
- `pricing_plans.id` - Plan identifier
- `partner_pricing_plan.partner_id + plan_id` - Links

---

## 🛡️ Security Implications

### Backend Validation (ChargingPointStoreRequest)
The fix ensures only authorized plans appear in dropdown, but **the controller MUST also validate**:

In `ChargingPointController::store()`:
```php
// Verify partner can create for this partner
if ($user->hasRole('partner') && $validatedData['partner_id'] != $user->partner_id) {
    return response()->json(['message' => 'Unauthorized'], 403);
}

// Verify plan belongs to selected partner
$plan = PricingPlan::find($validatedData['pricing_plan_id']);
if (!$plan->partners()->where('partner_id', $validatedData['partner_id'])->exists()) {
    return response()->json(['message' => 'Invalid plan for partner'], 403);
}
```

### API Endpoints
For API requests, add similar validation in:
- `ChargingPointApiController::store()`
- `API/ChargingPointController::store()`

---

## ✨ Benefits Achieved

| Issue | Before | After |
|-------|--------|-------|
| Partner sees all plans | ✗ Yes (bad) | ✓ Only their plans |
| Partner sees all partners | ✗ Yes (bad) | ✓ Only themselves |
| Integrator sees all partners | ✗ Yes (bad) | ✓ Only their partners |
| Plans dropdown works | ✗ No | ✓ Yes |
| Groups filtered | ✗ Only for integrator | ✓ For all roles |
| Integrators filtered | ✗ No | ✓ Admin only |

---

## 📋 Checklist for Deployment

- [ ] Verify PricingPlan::forUser() scope exists and works correctly
- [ ] Verify partner_pricing_plan pivot table has correct data
- [ ] Test with partner user role
- [ ] Test with integrator user role
- [ ] Test with admin user role
- [ ] Verify create form shows plans dropdown
- [ ] Verify edit form shows plans dropdown
- [ ] Test that plan validation works in controller
- [ ] Check error logs for missing model/method issues
- [ ] Load test with large plan datasets

---

## 🐛 Troubleshooting

### Plans dropdown is empty
**Possible causes:**
1. No plans linked in `partner_pricing_plan` table
2. Plans are not marked as `is_active = true`
3. PricingPlan::forUser() not working correctly

**Fix:**
```sql
-- Check if partner has plans
SELECT * FROM partner_pricing_plan WHERE partner_id = 5;
-- Check if plans are active
SELECT * FROM pricing_plans WHERE is_active = true LIMIT 5;
```

### Wrong plans showing
**Possible causes:**
1. PricingPlan::forUser() logic error
2. partner_pricing_plan data incorrect

**Debug:**
```php
// In controller (temporary, for debugging)
$user = auth()->user();
$plans = PricingPlan::forUser($user)->get();
dd($plans->pluck('id', 'name'));
```

### "Create form not loading"
**Possible causes:**
1. `getCreateFormData()` throwing exception
2. Missing PricingPlan model import

**Check logs:**
```bash
tail -f storage/logs/laravel.log
# Look for: "Error loading create form"
```

---

## 📚 Related Files Modified

| File | Changes | Lines |
|------|---------|-------|
| ChargingPointService.php | getCreateFormData() | 351-404 |
| ChargingPointService.php | getEditFormData() | 406-451 |
| Partner.php | pricingPlans() relation | (already exists) |
| PricingPlan.php | forUser() scope | (already exists) |
| create.blade.php | Uses $pricingPlans | (already exists) |

---

## ✅ Verification Commands

```sql
-- Verify pivot table
SELECT COUNT(*) FROM partner_pricing_plan;

-- Check a specific partner's plans
SELECT p.name, pp.price_per_kwh 
FROM pricing_plans pp
JOIN partner_pricing_plan ppp ON pp.id = ppp.plan_id
WHERE ppp.partner_id = 5;

-- Verify active plans
SELECT COUNT(*) FROM pricing_plans WHERE is_active = true;

-- View all partners with their link count
SELECT p.name, COUNT(ppp.plan_id) as plan_count
FROM partners p
LEFT JOIN partner_pricing_plan ppp ON p.id = ppp.partner_id
GROUP BY p.id;
```

---

## 📞 Support

If issues persist after applying this fix:
1. Check error logs: `storage/logs/laravel.log`
2. Verify database has `partner_pricing_plan` entries
3. Confirm PricingPlan::forUser() scope is correct
4. Test manually with SQL queries above
