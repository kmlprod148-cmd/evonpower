# Module Profil Contrat Intégrateur (Integrator Contract Profile)

## Overview

This module provides a comprehensive billing system for integrators with three distinct billing components:

1. **Maintenance Fee (Frais maintenance)** - Recurring periodic charges
2. **Per-Active-Terminal Fee (Frais par borne active)** - Costs based on active terminals
3. **Transaction-Based Commission (Commission par transaction)** - Percentage or fixed fees per transaction

## Architecture

### Database Migrations

The module includes 4 database migrations:

| Migration | Table | Description |
|-----------|-------|-------------|
| `2026_03_18_100000` | `integrator_contract_profiles` | Main contract profile configuration |
| `2026_03_18_100001` | `integrator_billing_line_items` | Individual billing line items |
| `2026_03_18_100002` | `integrator_billing_invoices` | Generated invoices |
| `2026_03_18_100003` | `integrator_billing_invoice_line_item` | Pivot table for invoice-line items |

### Models

- **[`IntegratorContractProfile`](app/Models/IntegratorContractProfile.php)** - Main contract model with billing configurations
- **[`IntegratorBillingLineItem`](app/Models/IntegratorBillingLineItem.php)** - Individual billing items (maintenance, terminal, commission)
- **[`IntegratorBillingInvoice`](app/Models/IntegratorBillingInvoice.php)** - Invoice aggregation model

### Service Layer

**[`IntegratorBillingService`](app/Services/IntegratorBillingService.php)** - Core business logic:

- `processMaintenanceFee()` - Calculates and creates maintenance fee line items
- `processTerminalFee()` - Calculates fees based on active terminal count
- `processTransactionCommission()` - Calculates commission for each transaction
- `processPendingCommissions()` - Batch processes all pending commissions
- `generateInvoice()` - Creates invoices from line items
- `generateConsolidatedInvoice()` - Creates consolidated monthly invoices
- `getBillingSummary()` - Returns comprehensive billing data

### Controllers

- **[`IntegratorContractProfileController`](app/Http/Controllers/Admin/IntegratorContractProfileController.php)** - CRUD operations for contracts
- **[`IntegratorBillingInvoiceController`](app/Http/Controllers/Admin/IntegratorBillingInvoiceController.php)** - Invoice management

## Billing Components

### 1. Maintenance Fee (Frais maintenance)

Recurring periodic charges for system maintenance.

**Configuration:**
- `maintenance_fee_enabled` - Enable/disable
- `maintenance_fee_amount` - Fee amount per period
- `maintenance_fee_period` - Period type: `monthly`, `quarterly`, `yearly`
- `maintenance_fee_start_date` - Start date for billing
- `maintenance_fee_next_due_date` - Auto-calculated next due date

**Calculation:**
```php
// In IntegratorBillingService::processMaintenanceFee()
$lineItem = IntegratorBillingLineItem::create([
    'type' => 'maintenance',
    'quantity' => 1,
    'unit_price' => $contract->maintenance_fee_amount,
    // Tax and totals calculated automatically
]);
```

### 2. Per-Active-Terminal Fee (Frais par borne active)

Charges based on the number of active charging terminals.

**Configuration:**
- `terminal_fee_enabled` - Enable/disable
- `terminal_fee_amount` - Fee per terminal
- `terminal_fee_period` - Period type: `monthly`, `quarterly`, `yearly`
- `terminal_fee_minimum` - Minimum terminals before billing
- `terminal_fee_free_count` - Free terminals (not billed)

**Calculation:**
```php
// In IntegratorContractProfile::calculateTerminalFee()
$billableTerminals = max(0, $activeTerminalCount - $this->terminal_fee_free_count);
$billableTerminals = max(0, $billableTerminals - $this->terminal_fee_minimum);
return round($billableTerminals * $this->terminal_fee_amount, 2);
```

### 3. Transaction Commission (Commission par transaction)

Percentage or fixed fees applied to each transaction.

**Configuration:**
- `transaction_commission_enabled` - Enable/disable
- `transaction_commission_type` - `percentage`, `fixed`, or `combined`
- `transaction_commission_percentage` - Percentage rate
- `transaction_commission_fixed_amount` - Fixed amount
- `transaction_commission_min_amount` - Minimum commission
- `transaction_commission_max_amount` - Maximum commission (optional)

**Calculation:**
```php
// In IntegratorContractProfile::calculateTransactionCommission()
switch ($this->transaction_commission_type) {
    case 'percentage':
        $commission = $transactionAmount * ($percentage / 100);
        break;
    case 'fixed':
        $commission = $fixedAmount;
        break;
    case 'combined':
        $commission = ($transactionAmount * $percentage / 100) + $fixedAmount;
        break;
}
// Apply min/max constraints
```

## Currency Handling

All monetary values use **decimal(12, 2)** for precision. The [`CurrencyService`](app/Services/CurrencyService.php) provides:

- Multi-currency support (EUR, MAD, USD, etc.)
- Currency formatting with locale-specific separators
- Exchange rate handling

## Routes

### Admin Routes

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/admin/integrator-contracts` | `admin.integrator-contracts.index` | List contracts |
| GET | `/admin/integrator-contracts/create` | `admin.integrator-contracts.create` | Create form |
| POST | `/admin/integrator-contracts` | `admin.integrator-contracts.store` | Store contract |
| GET | `/admin/integrator-contracts/{contract}` | `admin.integrator-contracts.show` | View contract |
| GET | `/admin/integrator-contracts/{contract}/edit` | `admin.integrator-contracts.edit` | Edit form |
| PUT | `/admin/integrator-contracts/{contract}` | `admin.integrator-contracts.update` | Update contract |
| DELETE | `/admin/integrator-contracts/{contract}` | `admin.integrator-contracts.destroy` | Delete contract |
| POST | `/admin/integrator-contracts/{contract}/process-billing` | `admin.integrator-contracts.process-billing` | Process billing |

### Invoice Routes

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/admin/integrator-invoices` | `admin.integrator-invoices.index` | List invoices |
| GET | `/admin/integrator-invoices/{invoice}` | `admin.integrator-invoices.show` | View invoice |
| POST | `/admin/integrator-invoices/{invoice}/mark-paid` | `admin.integrator-invoices.mark-paid` | Mark as paid |
| POST | `/admin/integrator-invoices/{invoice}/cancel` | `admin.integrator-invoices.cancel` | Cancel invoice |

## Permissions

The following permissions are required:

```php
// config/permissions.php
'financial_management' => [
    'view_integrator_contracts',
    'create_integrator_contracts',
    'edit_integrator_contracts',
    'delete_integrator_contracts',
    'view_integrator_invoices',
    'create_integrator_invoices',
    'edit_integrator_invoices',
    'delete_integrator_invoices',
]
```

## Views

Admin views are located in:
- `resources/views/admin/integrator-contracts/` - Contract management
- `resources/views/admin/integrator-invoices/` - Invoice management

## Usage Example

### Creating a Contract

```php
$contract = IntegratorContractProfile::create([
    'integrator_id' => $integrator->id,
    'name' => 'Premium Contract',
    'status' => 'active',
    'currency' => 'EUR',
    
    // Maintenance fee
    'maintenance_fee_enabled' => true,
    'maintenance_fee_amount' => 100.00,
    'maintenance_fee_period' => 'monthly',
    
    // Terminal fee
    'terminal_fee_enabled' => true,
    'terminal_fee_amount' => 10.00,
    'terminal_fee_free_count' => 5,
    
    // Transaction commission
    'transaction_commission_enabled' => true,
    'transaction_commission_type' => 'combined',
    'transaction_commission_percentage' => 2.5,
    'transaction_commission_fixed_amount' => 0.50,
    'transaction_commission_min_amount' => 1.00,
]);
```

### Processing Billing

```php
$billingService = app(IntegratorBillingService::class);

// Process all billing for active contracts
$results = $billingService->processAllBilling();

// Process specific billing type
$billingService->processMaintenanceFee($contract);
$billingService->processTerminalFee($contract);

// Process transaction commission
$billingService->processTransactionCommission($transaction);

// Generate invoice
$invoice = $billingService->generateConsolidatedInvoice($contract);
```

### Viewing Billing Summary

```php
$summary = $billingService->getBillingSummary($integrator, 2026, 3);

// Returns:
// [
//     'contract' => [...],
//     'period' => ['start' => '2026-03-01', 'end' => '2026-03-31'],
//     'active_terminals' => 15,
//     'maintenance_fee' => ['enabled' => true, 'amount' => 100, ...],
//     'terminal_fee' => ['enabled' => true, 'amount' => 10, ...],
//     'transaction_commission' => [...],
//     'invoices' => [...],
//     'totals' => ['maintenance' => 100, 'terminal' => 100, 'commission' => 50, 'total' => 250]
// ]
```

## Integration with Transaction System

The module integrates with the existing transaction system. When a transaction is created, the `IntegratorBillingService::processTransactionCommission()` method can be called to automatically calculate and record commission fees.

## Best Practices

1. **Currency Precision** - Always use `decimal(12, 2)` for monetary values
2. **Tax Handling** - Default tax rate is 20% but can be configured
3. **Invoice Numbering** - Auto-generates unique invoice numbers: `MAINT|TERM|COMM|INV + YYYYMM + 4-digit sequence`
4. **Contract Numbering** - Auto-generates: `CNT + YYYYMM + 4-digit sequence`
5. **Soft Deletes** - Contracts and invoices use soft deletes for data preservation
6. **Status Tracking** - Comprehensive status workflow: draft → pending → paid/overdue/cancelled

## Security Considerations

- All routes require admin authentication
- Permissions are enforced via middleware
- Input validation on all forms
- CSRF protection enabled
- SQL injection prevention via Eloquent ORM
