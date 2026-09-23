<?php
// fix-migrations.php

require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Remplacez '2023_01_01_000000_problematic_migration' par le nom du fichier de migration problématique sans l'extension .php
$problematicMigration = '2023_01_01_000000_problematic_migration';

// Vérifier si la migration est déjà marquée comme exécutée
$migrationExists = DB::table('migrations')
    ->where('migration', $problematicMigration)
    ->exists();

if (!$migrationExists) {
    // Marquer la migration comme déjà exécutée
    DB::table('migrations')->insert([
        'migration' => $problematicMigration,
        'batch' => DB::table('migrations')->max('batch') + 1
    ]);
    
    echo "La migration problématique a été marquée comme exécutée.\n";
} else {
    echo "La migration est déjà marquée comme exécutée.\n";
}