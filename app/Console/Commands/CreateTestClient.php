<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateTestClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:create-test 
                            {--email=client@test.com : Email du client de test}
                            {--name=Client Test : Nom du client de test}
                            {--password=password : Mot de passe du client}
                            {--balance=450 : Solde initial en EUR}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un compte client de test avec un solde de crédit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $password = $this->option('password');
        $balance = (float) $this->option('balance');

        $this->info('Création d\'un compte client de test...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // Vérifier si l'utilisateur existe déjà
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->warn("Un utilisateur avec l'email {$email} existe déjà.");
                
                if (!$this->confirm('Voulez-vous ajouter du crédit à cet utilisateur existant ?', false)) {
                    $this->info('Opération annulée.');
                    return 0;
                }

                $user = $existingUser;
                $this->info("Utilisation de l'utilisateur existant: {$user->name} (ID: {$user->id})");
            } else {
                // Créer le rôle 'user' s'il n'existe pas
                $userRole = Role::firstOrCreate(['name' => 'user']);

                // Créer l'utilisateur
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);

                // Assigner le rôle 'user'
                $user->assignRole('user');

                $this->info("✅ Utilisateur créé avec succès:");
                $this->line("   - ID: {$user->id}");
                $this->line("   - Nom: {$user->name}");
                $this->line("   - Email: {$user->email}");
                $this->line("   - Rôle: user");
            }

            // Obtenir ou créer le wallet
            $wallet = $user->getOrCreateWallet();
            $currentBalance = (float) $wallet->balance;

            $this->newLine();
            $this->info("Solde actuel du wallet: " . number_format($currentBalance, 2) . " EUR");

            // Si l'utilisateur existe déjà et a déjà un solde, proposer d'ajouter ou de définir
            if ($existingUser && $currentBalance > 0) {
                $action = $this->choice(
                    'Que souhaitez-vous faire ?',
                    ['Ajouter au solde existant', 'Définir le solde à ' . number_format($balance, 2) . ' EUR', 'Annuler'],
                    0
                );

                if ($action === 'Annuler') {
                    $this->info('Opération annulée.');
                    DB::rollBack();
                    return 0;
                }

                if ($action === 'Définir le solde à ' . number_format($balance, 2) . ' EUR') {
                    // Calculer la différence
                    $difference = $balance - $currentBalance;
                    
                    if ($difference > 0) {
                        // Ajouter la différence directement au wallet
                        $wallet->credit(
                            $difference,
                            'Solde défini par commande de test',
                            [
                                'type' => 'manual',
                                'source' => 'test_command',
                                'created_by' => $this->getAdminUserId()
                            ]
                        );
                        $this->info("✅ Solde défini à " . number_format($balance, 2) . " EUR");
                    } elseif ($difference < 0) {
                        // Retirer la différence (débit)
                        $wallet->debit(
                            abs($difference),
                            'Ajustement de solde par commande de test',
                            ['type' => 'manual_adjustment', 'test_command' => true]
                        );
                        $this->info("✅ Solde défini à " . number_format($balance, 2) . " EUR");
                    } else {
                        $this->info("Le solde est déjà à " . number_format($balance, 2) . " EUR");
                    }
                } else {
                    // Ajouter au solde existant directement au wallet
                    $wallet->credit(
                        $balance,
                        'Crédit ajouté par commande de test',
                        [
                            'type' => 'manual',
                            'source' => 'test_command',
                            'created_by' => $this->getAdminUserId()
                        ]
                    );
                    $this->info("✅ " . number_format($balance, 2) . " EUR ajoutés au solde");
                }
            } else {
                // Nouvel utilisateur ou solde à 0, ajouter le crédit directement au wallet
                $wallet->credit(
                    $balance,
                    'Solde initial pour compte de test',
                    [
                        'type' => 'manual',
                        'source' => 'test_command',
                        'created_by' => $this->getAdminUserId()
                    ]
                );
                $this->info("✅ Solde initial de " . number_format($balance, 2) . " EUR ajouté");
            }

            DB::commit();

            // Afficher le solde final
            $finalBalance = (float) $wallet->fresh()->balance;
            $this->newLine();
            $this->info("═══════════════════════════════════════");
            $this->info("✅ Compte client créé avec succès !");
            $this->info("═══════════════════════════════════════");
            $this->line("Email: {$user->email}");
            $this->line("Mot de passe: {$password}");
            $this->line("Solde: " . number_format($finalBalance, 2) . " EUR");
            $this->info("═══════════════════════════════════════");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur lors de la création du client: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Obtenir l'ID d'un utilisateur admin pour les opérations de crédit
     */
    protected function getAdminUserId(): int
    {
        // Essayer de trouver un admin
        $admin = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->first();

        if ($admin) {
            return $admin->id;
        }

        // Si aucun admin trouvé, créer un admin temporaire ou utiliser l'ID 1
        $this->warn('Aucun administrateur trouvé. Utilisation de l\'ID 1 par défaut.');
        return 1;
    }
}

