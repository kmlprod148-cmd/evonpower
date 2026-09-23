<?php
// direct-fix-migrations.php

// Connexion directe à MySQL
$host = '127.0.0.1';
$port = '3306';
$database = 'evon2';
$username = 'root';
$password = '';

try {
    // Connexion PDO directe
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database}",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connexion à la base de données réussie!\n";
    
    // 1. Vérifier si la table migrations existe
    $tables = $pdo->query("SHOW TABLES LIKE 'migrations'")->fetchAll();
    if (count($tables) === 0) {
        echo "La table 'migrations' n'existe pas. Rien à faire.\n";
        exit;
    }
    
    // 2. Lister tous les fichiers de migration dans le dossier database/migrations
    $migrationFiles = glob('database/migrations/*.php');
    echo "Fichiers de migration trouvés: " . count($migrationFiles) . "\n";
    
    // 3. Rechercher les migrations problématiques (celles contenant external_id et groups)
    $problematicMigrations = [];
    foreach ($migrationFiles as $file) {
        $content = file_get_contents($file);
        $filename = basename($file, '.php');
        
        if (strpos($content, 'external_id') !== false && strpos($content, 'groups') !== false) {
            $problematicMigrations[] = $filename;
            echo "Migration problématique trouvée: {$filename}\n";
        }
    }
    
    // 4. Marquer ces migrations comme déjà exécutées
    if (!empty($problematicMigrations)) {
        $maxBatch = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations")->fetch(PDO::FETCH_ASSOC)['max_batch'];
        $newBatch = $maxBatch + 1;
        
        foreach ($problematicMigrations as $migration) {
            // Vérifier si la migration est déjà enregistrée
            $exists = $pdo->query("SELECT * FROM migrations WHERE migration = '{$migration}'")->fetchAll();
            
            if (empty($exists)) {
                $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migration, $newBatch]);
                echo "Migration {$migration} marquée comme exécutée (batch {$newBatch}).\n";
            } else {
                echo "Migration {$migration} déjà marquée comme exécutée.\n";
            }
        }
    } else {
        echo "Aucune migration problématique trouvée.\n";
    }
    
    // 5. Vérifier la structure de la table groups
    $columns = $pdo->query("SHOW COLUMNS FROM groups")->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "\nColonnes existantes dans la table groups: " . implode(', ', $columns) . "\n\n";
    
    // 6. Tenter de corriger la structure selon les besoins
    if (!in_array('name', $columns)) {
        try {
            $pdo->exec("ALTER TABLE groups ADD COLUMN name VARCHAR(255) NOT NULL AFTER external_id");
            echo "Colonne 'name' ajoutée avec succès.\n";
        } catch (PDOException $e) {
            echo "Erreur lors de l'ajout de la colonne 'name': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nOpération terminée!\n";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}