<?php
require_once "../vendor/autoload.php";
$app = require_once "../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::find(1);
if ($user) {
    Auth::login($user);
    echo "Utilisateur connecté: " . Auth::user()->email . "\n";
    echo "Rôles: " . implode(", ", Auth::user()->getRoleNames()->toArray()) . "\n";
    echo "Peut voir les partenaires: " . (Auth::user()->can("view_partners") ? "OUI" : "NON") . "\n";
    echo "Peut créer des partenaires: " . (Auth::user()->can("create_partners") ? "OUI" : "NON") . "\n";
} else {
    echo "Utilisateur non trouvé\n";
}
?>