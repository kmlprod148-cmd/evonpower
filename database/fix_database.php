<?php
// fix_database.php

// Configuration de la base de données
$host = '127.0.0.1';
$dbname = 'evon2';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n";
    
    // 1. Sauvegarder les données existantes
    $pdo->exec("CREATE TABLE IF NOT EXISTS groups_backup AS SELECT * FROM groups");
    echo "Sauvegarde des données existantes créée.\n";
    
    // 2. Supprimer la table existante
    $pdo->exec("DROP TABLE IF EXISTS groups");
    echo "Table groups supprimée.\n";
    
    // 3. Créer une nouvelle table avec la structure correcte
    $sql = file_get_contents('fix_groups_table.sql');
    $pdo->exec($sql);
    echo "Table groups recréée avec la structure correcte.\n";
    
    // 4. Restaurer les données (si nécessaire et si la structure est compatible)
    // $pdo->exec("INSERT INTO groups (id, external_id, created_at, updated_at) SELECT id, external_id, created_at, updated_at FROM groups_backup");
    // echo "Données restaurées.\n";
    
    echo "Opération terminée avec succès!\n";
    
} catch(PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}