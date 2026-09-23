<header class="evon-topbar">
    <div class="topbar-container">
        <!-- Logo and Brand -->
        <div class="brand-section">
            <a href="{{ route('dashboard') }}" class="brand-logo">
                <img src="{{ asset('images/evon-logo.svg') }}" alt="Evon" class="logo-img">
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="main-navigation">
            <ul class="nav-menu">
                @can('view-dashboard')
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                </li>
                @endcan
                
                @can('view-transactions')
                <li class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <a href="{{ route('transactions.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        <span>{{ __('Transactions') }}</span>
                    </a>
                </li>
                @endcan
                
                @can('view-terminals')
                <li class="nav-item {{ request()->routeIs('terminals.*') ? 'active' : '' }}">
                    <a href="{{ route('terminals.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-charging-station"></i>
                        <span>{{ __('Bornes') }}</span>
                    </a>
                </li>
                @endcan
                
                @can('view-subscriptions')
                <li class="nav-item {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                    <a href="{{ route('subscriptions.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <span>{{ __('Abonnements') }}</span>
                    </a>
                </li>
                @endcan
            </ul>
        </nav>

        <!-- Right-aligned Content (User, Notifications, etc.) -->
        <div class="topbar-right">
            <!-- Notifications -->
            <div class="notifications-dropdown">
                <button class="dropdown-toggle" id="notificationsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    @if($unreadNotifications > 0)
                        <span class="notification-badge">{{ $unreadNotifications }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationsDropdown">
                    <h6 class="dropdown-header">{{ __('Notifications') }}</h6>
                    <div class="notification-list">
                        @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
                            <a href="{{ route('notifications.show', $notification->id) }}" class="dropdown-item {{ $notification->read_at ? '' : 'unread' }}">
                                <span class="notification-icon"><i class="fas {{ $notification->data['icon'] ?? 'fa-info-circle' }}"></i></span>
                                <span class="notification-text">{{ $notification->data['message'] ?? 'Notification' }}</span>
                                <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                            </a>
                        @empty
                            <div class="dropdown-item no-notifications">
                                {{ __('Aucune notification') }}
                            </div>
                        @endforelse
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('notifications.index') }}" class="dropdown-item view-all">
                        {{ __('Voir toutes les notifications') }}
                    </a>
                </div>
            </div>

            <!-- User Profile -->
            @auth
            <div class="user-dropdown">
                <button class="dropdown-toggle" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar">
                        <img src="{{ auth()->user()->avatar_url ?? asset('images/default-avatar.png') }}" alt="{{ auth()->user()->name }}">
                    </div>
                    <span class="user-name d-none d-md-inline">{{ auth()->user()->name }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <h6 class="dropdown-header">{{ auth()->user()->email }}</h6>
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">
                        <i class="fas fa-user"></i> {{ __('Mon profil') }}
                    </a>
                    <a href="{{ route('settings.index') }}" class="dropdown-item">
                        <i class="fas fa-cog"></i> {{ __('Paramètres') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> {{ __('Déconnexion') }}
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </div>
</header>

<!-- Mobile Toggle Menu Button (for responsive design) -->
<div class="mobile-menu-toggle">
    <button id="sidebarToggleBtn" class="btn btn-toggle">
        <i class="fas fa-bars"></i>
    </button>
</div>

@push('scripts')
<script>
    // Topbar interaction logic
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-open');
            });
        }
        
        // Initialize dropdowns if using custom JS rather than Bootstrap
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                this.nextElementSibling.classList.toggle('show');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                });
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* You can include these in your main CSS file instead */
    .evon-topbar {
        display: flex;
        height: 60px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1030;
    }
    
    .topbar-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0 1.5rem;
    }
    
    .brand-section {
        display: flex;
        align-items: center;
    }
    
    .brand-logo {
        display: flex;
        align-items: center;
    }
    
    .logo-img {
        height: 36px;
        width: auto;
    }
    
    .main-navigation {
        display: flex;
        flex-grow: 1;
        padding-left: 2rem;
    }
    
    .nav-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .nav-item {
        margin-right: 0.5rem;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        color: #495057;
        padding: 0.75rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .nav-link:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: #007bff;
    }
    
    .nav-item.active .nav-link {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
        font-weight: 500;
    }
    
    .nav-icon {
        margin-right: 0.5rem;
        font-size: 1rem;
    }
    
    .topbar-right {
        display: flex;
        align-items: center;
    }
    
    .notifications-dropdown,
    .user-dropdown {
        position: relative;
        margin-left: 1rem;
    }
    
    .dropdown-toggle {
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 0.5rem;
        border-radius: 50%;
        color: #495057;
    }
    
    .dropdown-toggle:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: #dc3545;
        color: #fff;
        font-size: 0.65rem;
        height: 18px;
        width: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 0.5rem;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        min-width: 280px;
        background-color: #fff;
        border-radius: 4px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        display: none;
        z-index: 1000;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        border: 1px solid rgba(0, 0, 0, 0.15);
    }
    
    .dropdown-menu.show {
        display: block;
    }
    
    .dropdown-header {
        display: block;
        padding: 0.5rem 1.5rem;
        margin-bottom: 0;
        font-size: 0.875rem;
        color: #6c757d;
        white-space: nowrap;
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        padding: 0.5rem 1.5rem;
        clear: both;
        font-weight: 400;
        color: #212529;
        text-align: inherit;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
        text-decoration: none;
    }
    
    .dropdown-item:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: #16181b;
    }
    
    .dropdown-item i {
        margin-right: 0.5rem;
    }
    
    .dropdown-divider {
        height: 0;
        margin: 0.5rem 0;
        overflow: hidden;
        border-top: 1px solid #e9ecef;
    }
    
    .notification-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .dropdown-item.unread {
        background-color: rgba(0, 123, 255, 0.05);
        font-weight: 500;
    }
    
    .notification-icon {
        margin-right: 0.75rem;
        font-size: 1rem;
        color: #6c757d;
    }
    
    .notification-text {
        flex-grow: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .notification-time {
        font-size: 0.75rem;
        color: #6c757d;
        white-space: nowrap;
        margin-left: 0.5rem;
    }
    
    .view-all {
        text-align: center;
        font-weight: 500;
    }
    
    .mobile-menu-toggle {
        display: none;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
        .nav-menu {
            display: none;
        }
        
        .mobile-menu-toggle {
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }
        
        .btn-toggle {
            background: transparent;
            border: none;
            color: #495057;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
        }
        
        .btn-toggle:hover {
            color: #007bff;
        }
        
        .user-name {
            display: none !important;
        }
    }
</style>
@endpush