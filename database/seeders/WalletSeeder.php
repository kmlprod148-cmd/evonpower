<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use App\Services\WalletService;
use Spatie\Permission\Models\Role;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create roles if they don't exist
        $this->createRoles();

        // Create admin user and wallet
        $this->createAdminWallet();

        // Create integrator wallets
        $this->createIntegratorWallets();

        // Create partner wallets
        $this->createPartnerWallets();

        // Create user wallets
        $this->createUserWallets();

        // Create some test wallets with specific scenarios
        $this->createTestWallets();
    }

    /**
     * Create roles if they don't exist
     */
    protected function createRoles()
    {
        $roles = ['admin', 'integrator', 'partner', 'operator'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    /**
     * Create admin user and wallet
     */
    protected function createAdminWallet()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        $adminWallet = WalletService::createWallet($admin, [
            'name' => 'System Admin Wallet',
            'description' => 'Main system wallet for admin operations',
            'currency' => 'EUR',
            'balance' => 100000,
            'is_active' => true,
        ]);

        $this->command->info('Created admin wallet with balance: ' . $adminWallet->getFormattedBalance());
    }

    /**
     * Create integrator wallets
     */
    protected function createIntegratorWallets()
    {
        $integrators = [
            [
                'name' => 'EV Solutions Integrator',
                'email' => 'integrator1@example.com',
                'balance' => 50000,
            ],
            [
                'name' => 'Green Energy Integrator',
                'email' => 'integrator2@example.com',
                'balance' => 75000,
            ],
            [
                'name' => 'Smart Grid Integrator',
                'email' => 'integrator3@example.com',
                'balance' => 30000,
            ],
        ];

        foreach ($integrators as $integratorData) {
            $integrator = Integrator::firstOrCreate(
                ['email' => $integratorData['email']],
                [
                    'name' => $integratorData['name'],
                    'created_by' => 1, // Admin user
                    'created_by_role' => 'admin',
                ]
            );

            $wallet = WalletService::createWallet($integrator, [
                'name' => $integratorData['name'] . ' Wallet',
                'description' => 'Business wallet for ' . $integratorData['name'],
                'currency' => 'EUR',
                'balance' => $integratorData['balance'],
                'is_active' => true,
                'auto_recharge' => true,
                'auto_recharge_threshold' => 5000,
                'auto_recharge_amount' => 10000,
            ]);

            $this->command->info('Created integrator wallet: ' . $integratorData['name'] . ' with balance: ' . $wallet->getFormattedBalance());
        }
    }

    /**
     * Create partner wallets
     */
    protected function createPartnerWallets()
    {
        $partners = [
            [
                'name' => 'City Charging Partners',
                'email' => 'partner1@example.com',
                'integrator_id' => 1,
                'balance' => 25000,
            ],
            [
                'name' => 'Highway Charging Co',
                'email' => 'partner2@example.com',
                'integrator_id' => 1,
                'balance' => 40000,
            ],
            [
                'name' => 'Urban EV Solutions',
                'email' => 'partner3@example.com',
                'integrator_id' => 2,
                'balance' => 35000,
            ],
        ];

        foreach ($partners as $partnerData) {
            $partner = Partner::firstOrCreate(
                ['email' => $partnerData['email']],
                [
                    'name' => $partnerData['name'],
                    'integrator_id' => $partnerData['integrator_id'],
                    'created_by' => 1, // Admin user
                    'created_by_role' => 'admin',
                ]
            );

            $wallet = WalletService::createWallet($partner, [
                'name' => $partnerData['name'] . ' Wallet',
                'description' => 'Business wallet for ' . $partnerData['name'],
                'currency' => 'EUR',
                'balance' => $partnerData['balance'],
                'is_active' => true,
                'min_balance' => 1000,
            ]);

            $this->command->info('Created partner wallet: ' . $partnerData['name'] . ' with balance: ' . $wallet->getFormattedBalance());
        }
    }

    /**
     * Create user wallets
     */
    protected function createUserWallets()
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'operator',
                'integrator_id' => 1,
                'balance' => 1000,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'role' => 'operator',
                'integrator_id' => 1,
                'balance' => 1500,
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@example.com',
                'role' => 'operator',
                'integrator_id' => 2,
                'balance' => 2000,
            ],
            [
                'name' => 'Alice Brown',
                'email' => 'alice@example.com',
                'role' => 'partner',
                'partner_id' => 1,
                'balance' => 5000,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'integrator_id' => $userData['integrator_id'] ?? null,
                    'partner_id' => $userData['partner_id'] ?? null,
                    'created_by' => 1, // Admin user
                ]
            );

            $user->assignRole($userData['role']);

            $wallet = WalletService::createWallet($user, [
                'name' => $userData['name'] . "'s Wallet",
                'description' => 'Personal wallet for ' . $userData['name'],
                'currency' => 'EUR',
                'balance' => $userData['balance'],
                'is_active' => true,
            ]);

            $this->command->info('Created user wallet: ' . $userData['name'] . ' with balance: ' . $wallet->getFormattedBalance());
        }
    }

    /**
     * Create test wallets with specific scenarios
     */
    protected function createTestWallets()
    {
        // Create a wallet that needs auto-recharge
        $needingRechargeUser = User::factory()->create([
            'name' => 'Test User Needing Recharge',
            'email' => 'needing-recharge@example.com',
        ]);

        $needingRechargeWallet = WalletService::createWallet($needingRechargeUser, [
            'name' => 'Low Balance Wallet',
            'description' => 'Test wallet with low balance',
            'currency' => 'EUR',
            'balance' => 5, // Low balance
            'is_active' => true,
            'auto_recharge' => true,
            'auto_recharge_threshold' => 10,
            'auto_recharge_amount' => 50,
        ]);

        $this->command->info('Created wallet needing recharge: ' . $needingRechargeWallet->getFormattedBalance());

        // Create a wallet with minimum balance constraint
        $minBalanceUser = User::factory()->create([
            'name' => 'Test User Min Balance',
            'email' => 'min-balance@example.com',
        ]);

        $minBalanceWallet = WalletService::createWallet($minBalanceUser, [
            'name' => 'Min Balance Wallet',
            'description' => 'Test wallet with minimum balance constraint',
            'currency' => 'EUR',
            'balance' => 100,
            'is_active' => true,
            'min_balance' => 50,
        ]);

        $this->command->info('Created wallet with min balance: ' . $minBalanceWallet->getFormattedBalance());

        // Create a wallet with maximum balance constraint
        $maxBalanceUser = User::factory()->create([
            'name' => 'Test User Max Balance',
            'email' => 'max-balance@example.com',
        ]);

        $maxBalanceWallet = WalletService::createWallet($maxBalanceUser, [
            'name' => 'Max Balance Wallet',
            'description' => 'Test wallet with maximum balance constraint',
            'currency' => 'EUR',
            'balance' => 1000,
            'is_active' => true,
            'max_balance' => 2000,
        ]);

        $this->command->info('Created wallet with max balance: ' . $maxBalanceWallet->getFormattedBalance());

        // Create some inactive wallets
        $inactiveUser = User::factory()->create([
            'name' => 'Test User Inactive',
            'email' => 'inactive@example.com',
        ]);

        $inactiveWallet = WalletService::createWallet($inactiveUser, [
            'name' => 'Inactive Wallet',
            'description' => 'Test inactive wallet',
            'currency' => 'EUR',
            'balance' => 500,
            'is_active' => false,
        ]);

        $this->command->info('Created inactive wallet: ' . $inactiveWallet->getFormattedBalance());

        // Create wallets with different currencies
        $currencies = ['USD', 'GBP', 'CHF', 'CAD', 'AUD', 'JPY'];
        
        foreach ($currencies as $currency) {
            $user = User::factory()->create([
                'name' => 'Test User ' . $currency,
                'email' => 'test-' . strtolower($currency) . '@example.com',
            ]);

            $wallet = WalletService::createWallet($user, [
                'name' => $currency . ' Wallet',
                'description' => 'Test wallet in ' . $currency,
                'currency' => $currency,
                'balance' => 1000,
                'is_active' => true,
            ]);

            $this->command->info('Created ' . $currency . ' wallet: ' . $wallet->getFormattedBalance());
        }
    }
}
