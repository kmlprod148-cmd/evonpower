<?php
echo "<h1>Test de configuration Apache</h1>";

// Test 1: Vérifier que mod_rewrite est activé
echo "<h2>1. Test mod_rewrite</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "✓ mod_rewrite est activé<br>";
    } else {
        echo "✗ mod_rewrite n'est pas activé<br>";
    }
} else {
    echo "⚠ Impossible de vérifier mod_rewrite (fonction non disponible)<br>";
}

// Test 2: Vérifier l'accès aux fichiers statiques
echo "<h2>2. Test accès aux fichiers statiques</h2>";
$qrcodesDir = __DIR__ . '/storage/qrcodes/';
if (is_dir($qrcodesDir)) {
    $files = scandir($qrcodesDir);
    $qrFiles = array_filter($files, function($file) {
        return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'png';
    });
    
    if (!empty($qrFiles)) {
        $firstFile = reset($qrFiles);
        echo "✓ Fichiers QR codes trouvés: " . count($qrFiles) . "<br>";
        echo "Premier fichier: " . $firstFile . "<br>";
        echo "Taille: " . filesize($qrcodesDir . $firstFile) . " bytes<br>";
        
        // Test d'affichage de l'image
        echo "<h3>Affichage de l'image QR code:</h3>";
        echo "<img src='/storage/qrcodes/" . $firstFile . "' alt='QR Code Test' style='border: 1px solid #ccc;'><br>";
        echo "<p>URL de l'image: <a href='/storage/qrcodes/" . $firstFile . "' target='_blank'>/storage/qrcodes/" . $firstFile . "</a></p>";
    } else {
        echo "✗ Aucun fichier QR code trouvé dans le dossier<br>";
        echo "Dossier vérifié: " . $qrcodesDir . "<br>";
    }
} else {
    echo "✗ Le dossier qrcodes n'existe pas<br>";
    echo "Dossier vérifié: " . $qrcodesDir . "<br>";
}

// Test 3: Vérifier les permissions
echo "<h2>3. Test des permissions</h2>";
$storageDir = __DIR__ . '/storage';
if (is_readable($storageDir)) {
    echo "✓ Le dossier storage est lisible<br>";
} else {
    echo "✗ Le dossier storage n'est pas lisible<br>";
}

if (is_writable($storageDir)) {
    echo "✓ Le dossier storage est accessible en écriture<br>";
} else {
    echo "✗ Le dossier storage n'est pas accessible en écriture<br>";
}

// Test 4: Informations sur le serveur
echo "<h2>4. Informations serveur</h2>";
echo "Serveur: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

// Test 5: Vérifier .htaccess
echo "<h2>5. Test .htaccess</h2>";
$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✓ Le fichier .htaccess existe<br>";
    $content = file_get_contents($htaccessFile);
    if (strpos($content, 'RewriteEngine On') !== false) {
        echo "✓ RewriteEngine est configuré<br>";
    } else {
        echo "✗ RewriteEngine n'est pas configuré<br>";
    }
} else {
    echo "✗ Le fichier .htaccess n'existe pas<br>";
}

echo "<h2>Test terminé</h2>";
echo "<p>Si l'image QR code s'affiche ci-dessus, la configuration Apache fonctionne correctement.</p>";
?> 