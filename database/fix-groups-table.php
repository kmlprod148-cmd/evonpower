<?php

// Connexion directe à la base de données avec vos paramètres
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
    
    // Récupérer les colonnes existantes
    $stmt = $pdo->query("SHOW COLUMNS FROM groups");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un tableau simple des noms de colonnes
    $columns = [];
    foreach ($existingColumns as $col) {
        $columns[] = $col['Field'];
    }
    
    echo "Colonnes existantes dans la table groups: " . implode(', ', $columns) . "\n\n";
    
    // Définition des colonnes à ajouter avec leurs types
    $columnsToAdd = [
        'integrator_id' => "BIGINT UNSIGNED NULL",
        'partner_id' => "BIGINT UNSIGNED NULL",
        'parent_group_id' => "BIGINT UNSIGNED NULL",
        'name' => "VARCHAR(255) NOT NULL",
        'slug' => "VARCHAR(255) NULL",
        'description' => "TEXT NULL",
        'status' => "ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'",
        'type' => "ENUM('station', 'area', 'organization', 'other') DEFAULT 'station'",
        'is_featured' => "BOOLEAN DEFAULT FALSE",
        'location' => "VARCHAR(255) NULL",
        'latitude' => "DECIMAL(10,8) NULL",
        'longitude' => "DECIMAL(11,8) NULL",
        'address' => "VARCHAR(255) NULL",
        'city' => "VARCHAR(255) NULL",
        'postal_code' => "VARCHAR(20) NULL",
        'country' => "VARCHAR(50) NULL",
        'timezone' => "VARCHAR(50) DEFAULT 'UTC'",
        'public_access' => "BOOLEAN DEFAULT TRUE",
        'max_charging_points' => "INT NULL",
        'contact_name' => "VARCHAR(255) NULL",
        'contact_email' => "VARCHAR(255) NULL",
        'contact_phone' => "VARCHAR(50) NULL",
        'manager_name' => "VARCHAR(255) NULL",
        'manager_email' => "VARCHAR(255) NULL",
        'manager_phone' => "VARCHAR(50) NULL",
        'commission_rate' => "DECIMAL(5,2) NULL",
        'image' => "VARCHAR(255) NULL",
        'color_code' => "VARCHAR(20) NULL",
        'opening_hours' => "JSON NULL",
        'services' => "JSON NULL",
        'restrictions' => "JSON NULL",
        'access_control' => "JSON NULL",
        'metadata' => "JSON NULL",
        'analytics_config' => "JSON NULL",
        'pricing_plan_id' => "BIGINT UNSIGNED NULL",
        'deleted_at' => "TIMESTAMP NULL"
    ];
    
    // Ajouter seulement les colonnes qui n'existent pas encore
    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $columns)) {
            try {
                // Déterminer après quelle colonne ajouter
                $position = '';
                $lastCol = end($columns);
                $position = "AFTER " . $lastCol;
                
                $sql = "ALTER TABLE groups ADD COLUMN {$column} {$definition} {$position}";
                $pdo->exec($sql);
                echo "✅ Colonne '{$column}' ajoutée avec succès.\n";
                
                // Mettre à jour notre liste de colonnes pour positionner la suivante
                $columns[] = $column;
            } catch (PDOException $e) {
                echo "❌ Erreur lors de l'ajout de la colonne '{$column}': " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠️ La colonne '{$column}' existe déjà.\n";
        }
    }
    
    // Ajout d'index
    try {
        $pdo->exec("ALTER TABLE groups ADD INDEX groups_status_index (status)");
        echo "✅ Index sur 'status' ajouté.\n";
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de l'ajout de l'index status: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE groups ADD INDEX groups_type_index (type)");
        echo "✅ Index sur 'type' ajouté.\n";
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de l'ajout de l'index type: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE groups ADD UNIQUE INDEX groups_slug_unique (slug)");
        echo "✅ Index unique sur 'slug' ajouté.\n";
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de l'ajout de l'index unique slug: " . $e->getMessage() . "\n";
    }
    
    echo "\nMise à jour de la table groups terminée!\n";
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
}