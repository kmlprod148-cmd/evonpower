<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class FixUserPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-passwords {--dry-run : Afficher seulement les utilisateurs qui seraient modifiés}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifie et corrige les mots de passe non hachés dans la base de données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recherche des utilisateurs avec des mots de passe non hachés...');

        // Récupérer tous les utilisateurs
        $users = User::all();
        $fixedCount = 0;
        $dryRun = $this->option('dry-run');

        foreach ($users as $user) {
            $password = $user->password;
            
            // Vérifier si le mot de passe est haché avec Bcrypt
            if (!$this->isBcryptHash($password)) {
                $this->warn("Utilisateur {$user->email} a un mot de passe non haché: {$password}");
                
                if (!$dryRun) {
                    // Si ce n'est pas un dry-run, on peut soit:
                    // 1. Supprimer l'utilisateur
                    // 2. Définir un mot de passe par défaut
                    // 3. Demander à l'utilisateur de réinitialiser son mot de passe
                    
                    $choice = $this->choice(
                        "Que faire pour l'utilisateur {$user->email}?",
                        ['supprimer', 'mot_de_passe_defaut', 'reinitialiser', 'ignorer'],
                        'mot_de_passe_defaut'
                    );

                    switch ($choice) {
                        case 'supprimer':
                            $user->delete();
                            $this->info("Utilisateur {$user->email} supprimé.");
                            break;
                            
                        case 'mot_de_passe_defaut':
                            $user->password = Hash::make('password123');
                            $user->save();
                            $this->info("Mot de passe de {$user->email} défini à 'password123'.");
                            break;
                            
                        case 'reinitialiser':
                            // Créer un token de réinitialisation
                            $token = \Illuminate\Support\Str::random(60);
                            DB::table('password_reset_tokens')->updateOrInsert(
                                ['email' => $user->email],
                                [
                                    'email' => $user->email,
                                    'token' => Hash::make($token),
                                    'created_at' => now()
                                ]
                            );
                            $this->info("Token de réinitialisation créé pour {$user->email}: {$token}");
                            break;
                            
                        case 'ignorer':
                            $this->info("Utilisateur {$user->email} ignoré.");
                            break;
                    }
                }
                
                $fixedCount++;
            }
        }

        if ($fixedCount === 0) {
            $this->info('Aucun utilisateur avec un mot de passe non haché trouvé.');
        } else {
            if ($dryRun) {
                $this->info("{$fixedCount} utilisateur(s) avec des mots de passe non hachés trouvé(s).");
            } else {
                $this->info("{$fixedCount} utilisateur(s) traité(s).");
            }
        }

        return 0;
    }

    /**
     * Vérifie si une chaîne est un hash Bcrypt valide
     */
    private function isBcryptHash($hash)
    {
        // Un hash Bcrypt commence par $2y$ et fait 60 caractères
        return preg_match('/^\$2y\$[0-9]{2}\$[./A-Za-z0-9]{53}$/', $hash);
    }
} 