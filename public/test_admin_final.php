<?php
/**
 * Test final de l'admin dans l'environnement web
 * 
 * Ce script teste l'authentification et les permissions
 * de l'admin dans l'environnement web.
 */

require_once '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Integrator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Middleware\UnifiedPermissionMiddleware;

echo "<h1>🔍 Test Final de l'Admin</h1>";

// 1. Vérifier l'utilisateur
echo "<h2>👤 Vérification de l'utilisateur...</h2>";
$user = User::find(1);
if (!$user) {
    echo "<p>❌ Utilisateur ID 1 non trouvé</p>";
    exit;
}

echo "<p>✅ Utilisateur trouvé: {$user->email}</p>";
echo "<p>Rôles: " . implode(', ', $user->getRoleNames()->toArray()) . "</p>";
echo "<p>Permissions: " . $user->getAllPermissions()->count() . "</p>";

// 2. Test des permissions partenaires
echo "<h2>📝 Test des permissions partenaires...</h2>";
$partnerPermissions = ['view_partners', 'create_partners', 'edit_partners', 'delete_partners', 'show_partners'];
foreach ($partnerPermissions as $perm) {
    $can = $user->can($perm);
    echo "<p>$perm: " . ($can ? '✅ OUI' : '❌ NON') . "</p>";
}

// 3. Test du statut admin
echo "<h2>🔐 Test du statut admin...</h2>";
$adminVariants = ['admin', 'Admin', 'super-admin', 'super_admin', 'Super Admin', 'Super-Admin'];
foreach ($adminVariants as $variant) {
    $has = $user->hasRole($variant);
    echo "<p>hasRole('$variant'): " . ($has ? '✅ OUI' : '❌ NON') . "</p>";
}

// 4. Test des middlewares
echo "<h2>🔧 Test des middlewares...</h2>";
$middleware = new UnifiedPermissionMiddleware();
$request = Request::create('/partners', 'POST');

try {
    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    }, 'create_partners');
    echo "<p>UnifiedPermissionMiddleware (create_partners): ✅ AUTORISÉ</p>";
} catch (Exception $e) {
    echo "<p>UnifiedPermissionMiddleware (create_partners): ❌ REFUSÉ</p>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
}

// 5. Vérification des données
echo "<h2>🏗️ Vérification des données...</h2>";
$integrator = Integrator::find(6);
if ($integrator) {
    echo "<p>✅ Intégrateur ID 6: {$integrator->name}</p>";
} else {
    echo "<p>❌ Intégrateur ID 6: NON TROUVÉ</p>";
}

$businessProfile = DB::table('business_profiles')->where('id', 7)->first();
if ($businessProfile) {
    echo "<p>✅ Business Profile ID 7: Commission {$businessProfile->operator_commission}</p>";
} else {
    echo "<p>❌ Business Profile ID 7: NON TROUVÉ</p>";
}

// 6. Test de simulation de connexion
echo "<h2>🔐 Test de simulation de connexion...</h2>";
Auth::login($user);
echo "<p>Utilisateur connecté: " . (Auth::check() ? '✅ OUI' : '❌ NON') . "</p>";
echo "<p>Utilisateur connecté: " . (Auth::user() ? Auth::user()->email : 'NON') . "</p>";

// 7. Test final des permissions après connexion
echo "<h2>🧪 Test final des permissions...</h2>";
foreach ($partnerPermissions as $perm) {
    $can = Auth::user()->can($perm);
    echo "<p>$perm: " . ($can ? '✅ OUI' : '❌ NON') . "</p>";
}

// 8. Test des routes
echo "<h2>🔗 Test des routes...</h2>";
try {
    $indexUrl = route('partners.index');
    echo "<p>✅ Route partners.index: $indexUrl</p>";
} catch (Exception $e) {
    echo "<p>❌ Route partners.index: " . $e->getMessage() . "</p>";
}

try {
    $createUrl = route('partners.create');
    echo "<p>✅ Route partners.create: $createUrl</p>";
} catch (Exception $e) {
    echo "<p>❌ Route partners.create: " . $e->getMessage() . "</p>";
}

echo "<h2>📋 Résumé</h2>";
$allTestsPassed = true;
foreach ($partnerPermissions as $perm) {
    if (!$user->can($perm)) {
        $allTestsPassed = false;
        break;
    }
}

if ($allTestsPassed && $integrator && $businessProfile) {
    echo "<p style='color: green; font-weight: bold;'>✅ TOUS LES TESTS RÉUSSIS</p>";
    echo "<p>L'admin devrait pouvoir accéder aux partenaires.</p>";
    echo "<p>Si le problème persiste, vérifiez:</p>";
    echo "<ul>";
    echo "<li>Que l'utilisateur est bien connecté dans l'application web</li>";
    echo "<li>Que la session est active</li>";
    echo "<li>Que les cookies de session sont présents</li>";
    echo "<li>Redémarrez le serveur web si nécessaire</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ CERTAINS TESTS ONT ÉCHOUÉ</p>";
    echo "<p>Vérifiez les erreurs ci-dessus.</p>";
}

echo "<p><a href='/partners'>Tester l'accès aux partenaires</a></p>";
echo "<p><a href='/force_admin_logout.php'>Forcer la déconnexion</a></p>";
?>
