<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class SeedSuperAdminAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:super-admin 
                            {--email= : Email address for the super admin}
                            {--password= : Password for the super admin}
                            {--name= : Name for the super admin}
                            {--all : Create all default super admin accounts}
                            {--fresh : Delete existing super admins before creating new ones}
                            {--force : Force execution even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed super admin accounts with full system permissions';

    /**
     * Default super admin accounts configuration
     */
    private array $defaultSuperAdmins = [
        [
            'email' => 'superadmin@evonpower.com',
            'password' => 'SuperAdmin2024!',
            'name' => 'Super Administrateur',
        ],
        [
            'email' => 'admin@evonpower.com',
            'password' => 'Admin2024!',
            'name' => 'Administrateur Principal',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check environment
        if (app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('⚠️  You are in PRODUCTION environment. Do you really want to continue?')) {
                $this->error('❌ Operation cancelled.');
                return 1;
            }
        }

        $this->displayHeader();

        // Handle fresh option
        if ($this->option('fresh')) {
            if ($this->confirm('⚠️  This will DELETE all existing super admin accounts. Continue?')) {
                $this->deleteExistingSuperAdmins();
            } else {
                $this->warn('❌ Fresh mode cancelled. Creating without deletion...');
            }
        }

        // Ensure roles and permissions exist
        $this->ensureRolesAndPermissions();

        // Determine which accounts to create
        $accountsToCreate = $this->getAccountsToCreate();

        if (empty($accountsToCreate)) {
            $this->warn('⚠️  No accounts to create. Use --email, --password, --name or --all option.');
            return 1;
        }

        // Create super admin accounts
        $createdAccounts = $this->createSuperAdminAccounts($accountsToCreate);

        // Display results
        $this->displayResults($createdAccounts);

        return 0;
    }

    /**
     * Display command header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         👑 SUPER ADMIN ACCOUNTS SEEDER                      ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Delete existing super admin accounts
     */
    private function deleteExistingSuperAdmins(): void
    {
        $this->warn('🗑️  Deleting existing super admin accounts...');

        try {
            DB::beginTransaction();

            // Get super admin role
            $superAdminRole = Role::where('name', 'super_admin')->first();

            if ($superAdminRole) {
                // Get users with super_admin role
                $superAdmins = User::role('super_admin')->get();

                foreach ($superAdmins as $admin) {
                    // Remove roles and permissions
                    $admin->syncRoles([]);
                    $admin->syncPermissions([]);
                    
                    // Delete user
                    $admin->delete();
                    
                    $this->info("   ✅ Deleted: {$admin->email}");
                }
            }

            DB::commit();
            $this->info('   ✅ Existing super admin accounts deleted');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('   ❌ Error during deletion: ' . $e->getMessage());
        }
    }

    /**
     * Ensure roles and permissions exist
     */
    private function ensureRolesAndPermissions(): void
    {
        $this->info('📋 Ensuring roles and permissions exist...');

        // Create super_admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        // Create admin role if it doesn't exist
        Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        // Create essential permissions if they don't exist
        $essentialPermissions = [
            'admin_access',
            'manage_all',
            'view_admin_dashboard',
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_settings',
            'manage_system',
        ];

        foreach ($essentialPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );
        }

        $this->info('   ✅ Roles and permissions ready');
    }

    /**
     * Get accounts to create based on options
     */
    private function getAccountsToCreate(): array
    {
        // If specific email/password/name provided, create single account
        if ($this->option('email')) {
            return [[
                'email' => $this->option('email'),
                'password' => $this->option('password') ?? 'SuperAdmin2024!',
                'name' => $this->option('name') ?? 'Super Admin',
            ]];
        }

        // If --all option, create all default accounts
        if ($this->option('all')) {
            return $this->defaultSuperAdmins;
        }

        // If no options, ask user what to do
        $choice = $this->choice(
            'What would you like to do?',
            [
                'Create default super admin accounts',
                'Create a custom super admin account',
                'Cancel',
            ],
            0
        );

        switch ($choice) {
            case 'Create default super admin accounts':
                return $this->defaultSuperAdmins;

            case 'Create a custom super admin account':
                return [[
                    'email' => $this->ask('Enter email address'),
                    'password' => $this->secret('Enter password') ?? 'SuperAdmin2024!',
                    'name' => $this->ask('Enter name', 'Super Admin'),
                ]];

            default:
                return [];
        }
    }

    /**
     * Create super admin accounts
     */
    private function createSuperAdminAccounts(array $accounts): array
    {
        $this->info('👑 Creating super admin accounts...');
        $this->newLine();

        $createdAccounts = [];

        foreach ($accounts as $accountData) {
            try {
                DB::beginTransaction();

                // Check if user already exists
                $existingUser = User::where('email', $accountData['email'])->first();

                if ($existingUser) {
                    $this->warn("   ⚠️  User with email {$accountData['email']} already exists.");
                    
                    if ($this->confirm('   Do you want to update this user to super admin?', true)) {
                        $user = $this->updateExistingUser($existingUser, $accountData);
                        $createdAccounts[] = $user;
                        $this->info("   ✅ Updated: {$user->email}");
                    }
                    
                    DB::commit();
                    continue;
                }

                // Create new user
                $user = User::create([
                    'name' => $accountData['name'],
                    'email' => $accountData['email'],
                    'password' => Hash::make($accountData['password']),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'balance' => 0.00,
                    'currency' => 'EUR',
                    'language' => 'fr',
                    'timezone' => 'Africa/Casablanca',
                ]);

                // Assign super_admin role
                $user->assignRole('super_admin');

                // Give all permissions to super admin
                $user->givePermissionTo(Permission::all());

                DB::commit();

                $createdAccounts[] = $user;
                $this->info("   ✅ Created: {$user->email}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("   ❌ Failed to create {$accountData['email']}: " . $e->getMessage());
            }
        }

        return $createdAccounts;
    }

    /**
     * Update existing user to super admin
     */
    private function updateExistingUser(User $user, array $data): User
    {
        // Update user data
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Sync super_admin role
        $user->syncRoles(['super_admin']);

        // Give all permissions
        $user->syncPermissions(Permission::all());

        return $user;
    }

    /**
     * Display results
     */
    private function displayResults(array $createdAccounts): void
    {
        $this->newLine(2);

        if (empty($createdAccounts)) {
            $this->warn('⚠️  No accounts were created.');
            return;
        }

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    ✅ SUCCESS!                               ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Display credentials table
        $this->info('📋 Super Admin Account Credentials:');
        $this->newLine();

        $tableData = [];
        foreach ($createdAccounts as $user) {
            $tableData[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->getRoleNames()->implode(', '),
                $user->is_active ? '✅ Active' : '❌ Inactive',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Roles', 'Status'],
            $tableData
        );

        $this->newLine();
        $this->info('🔐 Security Recommendations:');
        $this->info('   1. Change default passwords immediately after first login');
        $this->info('   2. Enable two-factor authentication');
        $this->info('   3. Use strong, unique passwords');
        $this->info('   4. Regularly audit admin account activities');
        $this->newLine();

        $this->info('📊 Permissions Summary:');
        $totalPermissions = Permission::count();
        $this->info("   Total system permissions: {$totalPermissions}");
        $this->info('   All super admins have been granted ALL permissions.');
        $this->newLine();
    }
}
