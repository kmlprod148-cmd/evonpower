# Admin Transaction View

This document describes the admin-only transaction management interface for viewing and managing all wallet transactions in the system.

## Overview

The admin transaction view provides a comprehensive interface for administrators to:
- View all wallet transactions across the system
- Filter transactions by various criteria
- Export transaction data
- View detailed transaction information

## Features

### 1. Transaction Listing
- **All Transactions**: View all wallet transactions from all users, partners, and integrators
- **Color Coding**: Green for credits, red for debits
- **Real-time Statistics**: Total transactions, credits, debits, and net amount
- **Pagination**: Efficient handling of large transaction datasets

### 2. Advanced Filtering
- **By Role**: Filter transactions by user role (admin, integrator, partner, operator)
- **By User**: Filter transactions by specific user
- **By Date Range**: Filter transactions by date range
- **By Type**: Filter by credit or debit transactions
- **Search**: Full-text search across descriptions and owner names

### 3. Transaction Details
- **Comprehensive View**: Detailed transaction information
- **Owner Information**: Complete owner details with role information
- **Timeline**: Transaction history and timeline
- **Metadata**: Additional transaction metadata when available

### 4. Export Functionality
- **CSV Export**: Export filtered transactions to CSV format
- **Filtered Export**: Export only filtered results
- **Complete Data**: All transaction fields included in export

## Database Schema

### WalletTransaction Model
```php
class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',           // 'credit' or 'debit'
        'amount',
        'description',
        'metadata',
        'current_balance',
    ];
}
```

### Relationships
- `wallet()` - Belongs to Wallet
- `wallet.owner()` - Polymorphic relationship to owner (User, Partner, Integrator)

## API Endpoints

### Transaction Management
```http
GET /admin/transactions                    # List all transactions
GET /admin/transactions/{id}              # Show transaction details
GET /admin/transactions/export/csv        # Export transactions to CSV
GET /admin/transactions/api/users         # Get users for autocomplete
```

### Query Parameters
```http
GET /admin/transactions?role=operator     # Filter by role
GET /admin/transactions?user=123          # Filter by user ID
GET /admin/transactions?type=credit       # Filter by type
GET /admin/transactions?date_from=2024-01-01&date_to=2024-01-31  # Filter by date range
GET /admin/transactions?search=charging   # Search transactions
```

## Controller Implementation

### TransactionController
```php
class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $filters = [
            'role' => $request->get('role'),
            'user' => $request->get('user'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'type' => $request->get('type'),
            'search' => $request->get('search'),
        ];

        // Build query with joins for owner information
        $query = WalletTransaction::with(['wallet.owner'])
            ->join('wallets', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->leftJoin('users', function($join) {
                $join->on('wallets.owner_id', '=', 'users.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\User');
            })
            // ... more joins for partners and integrators

        // Apply filters and pagination
        $transactions = $query->orderBy('wallet_transactions.created_at', 'desc')
            ->paginate(50)
            ->appends($filters);

        return view('admin.transactions.index', compact('transactions', 'filters'));
    }
}
```

## View Implementation

### Main Transaction List
```blade
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($stats['total_transactions']) }}</h3>
                <p>Total Transactions</p>
            </div>
        </div>
    </div>
    <!-- More statistics cards -->
</div>

<!-- Filters -->
<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.transactions.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <select name="role" class="form-control">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- More filter fields -->
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Owner</th>
            <th>Role</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Description</th>
            <th>Balance</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
            <tr>
                <td><span class="badge badge-secondary">#{{ $transaction->id }}</span></td>
                <td>{{ $transaction->owner_name ?? 'N/A' }}</td>
                <td>{{ $transaction->owner_role ?? 'N/A' }}</td>
                <td>
                    <span class="{{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                        {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} €
                    </span>
                </td>
                <td>
                    <span class="badge {{ $transaction->type === 'credit' ? 'badge-success' : 'badge-danger' }}">
                        {{ ucfirst($transaction->type) }}
                    </span>
                </td>
                <td>{{ $transaction->description ?? 'N/A' }}</td>
                <td>{{ number_format($transaction->current_balance, 2) }} €</td>
                <td>{{ $transaction->created_at->format('M d, Y H:i:s') }}</td>
                <td>
                    <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No transactions found</td>
            </tr>
        @endforelse
    </tbody>
</table>
```

### Transaction Details
```blade
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Transaction Details #{{ $transaction->id }}</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <!-- Transaction Information -->
                <div class="form-group">
                    <label class="fw-bold">Amount</label>
                    <p class="form-control-plaintext">
                        <span class="h4 {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} €
                        </span>
                    </p>
                </div>
                <!-- More transaction details -->
            </div>
            <div class="col-md-4">
                <!-- Owner Information -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold">{{ $transaction->wallet->owner->name ?? 'N/A' }}</h6>
                        <p class="text-muted">{{ $transaction->wallet->owner->email ?? 'N/A' }}</p>
                        <span class="badge badge-info">{{ $transaction->wallet->owner->roles->first()->name ?? 'No Role' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

## Authorization

### WalletTransactionPolicy
```php
class WalletTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, WalletTransaction $walletTransaction): bool
    {
        // Admin can view all transactions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view their own transactions
        if ($walletTransaction->wallet && $walletTransaction->wallet->owner_type === User::class) {
            return $walletTransaction->wallet->owner_id === $user->id;
        }

        // Hierarchical access for integrators and partners
        // ... more authorization logic
    }
}
```

## Styling and UI

### Color Coding
- **Credits**: Green color (`text-success`, `badge-success`)
- **Debits**: Red color (`text-danger`, `badge-danger`)
- **Statistics**: Color-coded statistics cards

### Responsive Design
- **Mobile-friendly**: Responsive table design
- **Filter Collapse**: Collapsible filter section
- **Pagination**: Efficient pagination for large datasets

### JavaScript Features
- **Select2**: Enhanced user selection
- **Auto-submit**: Automatic form submission on filter change
- **Search Debounce**: Debounced search input
- **Export**: Direct CSV export functionality

## Testing

### Test Coverage
```php
class AdminTransactionViewTest extends TestCase
{
    public function test_admin_can_view_transactions_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/transactions');
        $response->assertStatus(200);
    }

    public function test_transactions_can_be_filtered_by_role()
    {
        // Test role filtering
    }

    public function test_transactions_show_correct_colors()
    {
        // Test color coding
    }

    public function test_admin_can_export_transactions()
    {
        // Test CSV export
    }
}
```

## Performance Considerations

### Database Optimization
- **Indexes**: Proper indexing on frequently queried fields
- **Joins**: Efficient joins for owner information
- **Pagination**: Limit results to prevent memory issues

### Caching
- **Statistics**: Cache transaction statistics
- **User Lists**: Cache user lists for filters
- **Role Lists**: Cache role lists

### Query Optimization
- **Selective Fields**: Only select necessary fields
- **Eager Loading**: Load relationships efficiently
- **Filter Optimization**: Optimize filter queries

## Security

### Access Control
- **Admin Only**: Only admin users can access transaction views
- **Policy Protection**: Comprehensive authorization policies
- **Data Privacy**: Respect user privacy in transaction display

### Data Protection
- **Sensitive Data**: Mask sensitive information if needed
- **Audit Trail**: Log admin access to transaction data
- **Export Security**: Secure CSV export functionality

## Usage Examples

### Basic Transaction Viewing
```php
// Access the admin transactions page
GET /admin/transactions

// View specific transaction
GET /admin/transactions/123

// Export transactions
GET /admin/transactions/export/csv
```

### Filtering Examples
```php
// Filter by role
GET /admin/transactions?role=operator

// Filter by date range
GET /admin/transactions?date_from=2024-01-01&date_to=2024-01-31

// Search transactions
GET /admin/transactions?search=charging

// Combined filters
GET /admin/transactions?role=operator&type=credit&date_from=2024-01-01
```

## Future Enhancements

### Planned Features
1. **Real-time Updates**: WebSocket integration for real-time transaction updates
2. **Advanced Analytics**: Transaction analytics and reporting
3. **Bulk Operations**: Bulk transaction management
4. **Transaction Categories**: Categorize transactions by type
5. **Export Formats**: Additional export formats (Excel, PDF)

### Integration Opportunities
1. **Notification System**: Real-time notifications for new transactions
2. **Audit Logging**: Comprehensive audit logging
3. **API Integration**: REST API for transaction management
4. **Mobile App**: Mobile interface for transaction management

## Troubleshooting

### Common Issues
1. **Slow Loading**: Check database indexes and query optimization
2. **Filter Issues**: Verify filter parameters and query logic
3. **Export Problems**: Check file permissions and memory limits
4. **Authorization Errors**: Verify user roles and permissions

### Debug Commands
```bash
# Check transaction statistics
php artisan tinker
>>> App\Models\WalletTransaction::count()

# Test authorization
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->can('viewAny', App\Models\WalletTransaction::class)

# Check database performance
php artisan db:show --table=wallet_transactions
```

## Conclusion

The admin transaction view provides a comprehensive, secure, and efficient interface for managing all wallet transactions in the system. With advanced filtering, detailed views, and export capabilities, administrators have full visibility and control over the financial aspects of the platform.

For more information, see the related documentation:
- [Wallet System Documentation](WALLET_SYSTEM.md)
- [Prepaid/Postpaid System Documentation](PREPAID_POSTPAID_SYSTEM.md)
- [Transaction System Documentation](TRANSACTION_SYSTEM.md)
