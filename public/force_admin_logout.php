<?php
/**
 * Script de déconnexion forcée pour l'admin
 * 
 * Ce script force la déconnexion de l'utilisateur admin
 * et nettoie toutes les sessions pour forcer une reconnexion.
 */

require_once '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

echo "<h1>🔧 Déconnexion Forcée Admin</h1>";

// 1. Nettoyer tous les caches
echo "<h2>🧹 Nettoyage des caches...</h2>";
try {
    Artisan::call('cache:clear');
    Artisan::call('permission:cache-reset');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Cache::flush();
    echo "<p>✅ Tous les caches vidés</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur lors du nettoyage: " . $e->getMessage() . "</p>";
}

// 2. Vérifier l'utilisateur admin
echo "<h2>👤 Vérification de l'utilisateur admin...</h2>";
$user = User::find(1);
if ($user) {
    echo "<p>✅ Utilisateur trouvé: {$user->email}</p>";
    echo "<p>Rôles: " . implode(', ', $user->getRoleNames()->toArray()) . "</p>";
} else {
    echo "<p>❌ Utilisateur ID 1 non trouvé</p>";
    exit;
}

// 3. Forcer la déconnexion
echo "<h2>🚪 Déconnexion forcée...</h2>";
Auth::logout();
echo "<p>✅ Utilisateur déconnecté</p>";

// 4. Détruire la session
echo "<h2>💾 Destruction de la session...</h2>";
session_start();
session_destroy();
echo "<p>✅ Session détruite</p>";

// 5. Nettoyer les cookies
echo "<h2>🍪 Nettoyage des cookies...</h2>";
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
    echo "<p>✅ Cookie de session supprimé</p>";
}

// 6. Rediriger vers la page de connexion
echo "<h2>🔄 Redirection...</h2>";
echo "<p>Redirection vers la page de connexion dans 3 secondes...</p>";
echo "<script>setTimeout(function(){ window.location.href = '/login'; }, 3000);</script>";
echo "<p><a href='/login'>Cliquez ici pour aller à la page de connexion</a></p>";

echo "<h2>📋 Instructions</h2>";
echo "<ol>";
echo "<li>Reconnectez-vous avec l'utilisateur admin (test@example.com)</li>";
echo "<li>Essayez d'accéder aux partenaires</li>";
echo "<li>Si le problème persiste, vérifiez les logs Laravel</li>";
echo "</ol>";
?>
