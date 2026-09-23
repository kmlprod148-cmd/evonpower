# User Transaction View

This document describes the user transaction management interface for operators, integrators, and partners to view their own wallet transactions in a read-only format.

## Overview

The user transaction view provides a personalized interface for non-admin users to:
- View only their own wallet transactions
- Filter and search their transaction history
- Export their transaction data
- View detailed transaction information
- Monitor their wallet balance

## Features

### 1. Personal Transaction Listing
- **Own Transactions Only**: Users can only view transactions from their own wallet
- **Color Coding**: Green for credits, red for debits
- **Real-time Balance**: Current wallet balance displayed prominently
- **Statistics**: Personal transaction statistics (total, credits, debits, net amount)

### 2. Advanced Filtering
- **By Type**: Filter by credit or debit transactions
- **By Date Range**: Filter transactions by date range
- **Search**: Full-text search across transaction descriptions

### 3. Transaction Details
- **Comprehensive View**: Detailed transaction information
- **Wallet Information**: Current wallet status and balance
- **Metadata**: Additional transaction metadata when available
- **Timeline**: Transaction history and timeline

### 4. Export Functionality
- **CSV Export**: Export filtered transactions to CSV format
- **Personal Data**: Only user's own transactions included in export

## Database Schema

### User Transaction Access
```php
// Users can only access their own wallet transactions
$user = Auth::user();
$wallet = $user->getOrCreateWallet();
$transactions = $wallet->transactions();
```

### Security Model
- **Authentication Required**: All routes require authentication
- **Ownership Validation**: Users can only view their own transactions
- **Read-Only Access**: No edit or delete capabilities
- **Role-Based Access**: Works for all user roles (operator, integrator, partner)

## API Endpoints

### User Transaction Management
```http
GET /transactions                    # List user's transactions
GET /transactions/{id}              # Show transaction details
GET /transactions/export/csv        # Export transactions to CSV
GET /transactions/api/balance       # Get wallet balance
GET /transactions/api/recent        # Get recent transactions
```

### Query Parameters
```http
GET /transactions?type=credit       # Filter by type
GET /transactions?date_from=2024-01-01&date_to=2024-01-31  # Filter by date range
GET /transactions?search=charging  # Search transactions
```

## Controller Implementation

### TransactionController
```php
class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        
        // Get filter parameters
        $filters = [
            'type' => $request->get('type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        // Build query for user's transactions only
        $query = $wallet->transactions()->latest();

        // Apply filters
        if ($filters['type']) {
            $query->where('type', $filters['type']);
        }

        // Get paginated results
        $transactions = $query->paginate(20)->appends($filters);

        return view('transactions.index', compact('transactions', 'filters', 'wallet'));
    }

    public function show(WalletTransaction $transaction)
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        
        // Ensure user can only view their own transactions
        if ($transaction->wallet_id !== $wallet->id) {
            abort(403, 'You can only view your own transactions.');
        }

        return view('transactions.show', compact('transaction', 'wallet'));
    }
}
```

## View Implementation

### Main Transaction List
```blade
<!-- Wallet Balance Card -->
<div class="card bg-primary">
    <div class="card-body text-center">
        <h4 class="text-white mb-2">
            <i class="fas fa-wallet"></i>
            Current Balance
        </h4>
        <h2 class="text-white mb-0">
            {{ $wallet->getFormattedBalance() }}
        </h2>
    </div>
</div>

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
        <form method="GET" action="{{ route('transactions.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="credit">Credit</option>
                        <option value="debit">Debit</option>
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
                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No transactions found</td>
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
                <!-- Wallet Information -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold">My Wallet</h6>
                        <span class="h4 text-primary">{{ $wallet->getFormattedBalance() }}</span>
                        <div class="mb-2">
                            <small class="text-muted">
                                Currency: {{ $wallet->currency }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

## Security & Authorization

### Access Control
```php
// Authentication required
Route::middleware(['auth'])->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index']);
});

// Ownership validation in controller
public function show(WalletTransaction $transaction)
{
    $user = Auth::user();
    $wallet = $user->getOrCreateWallet();
    
    if ($transaction->wallet_id !== $wallet->id) {
        abort(403, 'You can only view your own transactions.');
    }
}
```

### Data Privacy
- **Personal Data Only**: Users can only access their own transactions
- **No Cross-User Access**: Strict isolation between users
- **Read-Only Interface**: No modification capabilities

## Styling and UI

### Color Coding
- **Credits**: Green color (`text-success`, `badge-success`)
- **Debits**: Red color (`text-danger`, `badge-danger`)
- **Balance**: Primary color for wallet balance

### Responsive Design
- **Mobile-friendly**: Responsive table design
- **Filter Collapse**: Collapsible filter section
- **Pagination**: Efficient pagination for large datasets

### JavaScript Features
- **Auto-submit**: Automatic form submission on filter change
- **Search Debounce**: Debounced search input
- **Export**: Direct CSV export functionality

## Testing

### Test Coverage
```php
class UserTransactionViewTest extends TestCase
{
    public function test_authenticated_user_can_view_their_transactions()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $response = $this->actingAs($user)->get('/transactions');
        $response->assertStatus(200);
    }

    public function test_user_can_only_view_their_own_transactions()
    {
        // Test ownership isolation
    }

    public function test_transactions_show_correct_colors()
    {
        // Test color coding
    }

    public function test_user_can_export_their_transactions()
    {
        // Test CSV export
    }
}
```

## Performance Considerations

### Database Optimization
- **User-Scoped Queries**: Only query user's own transactions
- **Efficient Filtering**: Optimized filter queries
- **Pagination**: Limit results to prevent memory issues

### Caching
- **Wallet Balance**: Cache wallet balance for performance
- **Statistics**: Cache transaction statistics

### Query Optimization
- **Selective Fields**: Only select necessary fields
- **Index Usage**: Proper indexing on frequently queried fields
- **Filter Optimization**: Optimize filter queries

## Security

### Access Control
- **Authentication Required**: All routes require authentication
- **Ownership Validation**: Users can only view their own transactions
- **No Cross-User Access**: Strict isolation between users

### Data Protection
- **Personal Data Only**: Users can only access their own data
- **Read-Only Access**: No modification capabilities
- **Secure Export**: Secure CSV export functionality

## Usage Examples

### Basic Transaction Viewing
```php
// Access the user transactions page
GET /transactions

// View specific transaction
GET /transactions/123

// Export transactions
GET /transactions/export/csv
```

### Filtering Examples
```php
// Filter by type
GET /transactions?type=credit

// Filter by date range
GET /transactions?date_from=2024-01-01&date_to=2024-01-31

// Search transactions
GET /transactions?search=charging

// Combined filters
GET /transactions?type=credit&date_from=2024-01-01
```

### API Usage
```php
// Get wallet balance
GET /transactions/api/balance

// Get recent transactions
GET /transactions/api/recent
```

## Role-Based Access

### Operator Access
- **Full Access**: Can view all their own transactions
- **Filtering**: Can filter by type, date, and search
- **Export**: Can export their transaction data

### Integrator Access
- **Full Access**: Can view all their own transactions
- **Same Features**: Same functionality as operators
- **Business Context**: Transactions related to their business

### Partner Access
- **Full Access**: Can view all their own transactions
- **Same Features**: Same functionality as other roles
- **Business Context**: Transactions related to their business

## Future Enhancements

### Planned Features
1. **Real-time Updates**: WebSocket integration for real-time balance updates
2. **Transaction Categories**: Categorize transactions by type
3. **Export Formats**: Additional export formats (Excel, PDF)
4. **Mobile App**: Mobile interface for transaction management
5. **Notifications**: Real-time notifications for new transactions

### Integration Opportunities
1. **Dashboard Integration**: Integrate with user dashboards
2. **Notification System**: Real-time notifications for transactions
3. **API Integration**: REST API for transaction management
4. **Mobile App**: Mobile interface for transaction viewing

## Troubleshooting

### Common Issues
1. **Access Denied**: Check user authentication and ownership
2. **Empty Results**: Verify user has transactions
3. **Filter Issues**: Check filter parameters and query logic
4. **Export Problems**: Check file permissions and memory limits

### Debug Commands
```bash
# Check user's wallet
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->getOrCreateWallet();

# Test transaction access
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->wallet->transactions()->count()

# Check database performance
php artisan db:show --table=wallet_transactions
```

## Conclusion

The user transaction view provides a secure, personalized, and efficient interface for users to manage and monitor their own wallet transactions. With advanced filtering, detailed views, and export capabilities, users have full visibility into their financial activity while maintaining strict data privacy and security.

For more information, see the related documentation:
- [Wallet System Documentation](WALLET_SYSTEM.md)
- [Admin Transaction View Documentation](ADMIN_TRANSACTION_VIEW.md)
- [Prepaid/Postpaid System Documentation](PREPAID_POSTPAID_SYSTEM.md)
