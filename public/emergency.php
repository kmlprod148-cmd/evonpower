<?php
// emergency.php - SUPPRIMER CE FICHIER APRÈS UTILISATION!

// Charger l'application Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->bootstrap();

// Obtenir l'utilisateur ID 1
$user = DB::table('users')->where('id', 1)->first();
if ($user) {
    echo "Utilisateur trouvé: " . $user->name . "<br>";

    // Donner le rôle admin directement via SQL
    try {
        // S'assurer que le rôle admin existe
        $roleId = DB::table('roles')->where('name', 'admin')->value('id');
        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "Rôle admin créé avec ID: " . $roleId . "<br>";
        } else {
            echo "Rôle admin trouvé avec ID: " . $roleId . "<br>";
        }

        // Assigner le rôle à l'utilisateur
        $exists = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_id', 1)
            ->where('model_type', 'App\\Models\\User')
            ->exists();

        if (!$exists) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
            ]);
            echo "Rôle admin assigné à l'utilisateur ID 1<br>";
        } else {
            echo "L'utilisateur a déjà le rôle admin<br>";
        }

        echo '<a href="/dashboard">Aller au tableau de bord</a>';
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage();
    }
} else {
    echo "Utilisateur ID 1 non trouvé";
}