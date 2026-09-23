<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\ClientUser::create([
    'name' => 'Test User',
    'first_name' => 'Test',
    'email' => 'test@example.com',
    'password' => \Illuminate\Support\Facades\Hash::make('password123'),
    'is_active' => true,
]);

echo "Created user test@example.com / password123\n";
echo "Attempting login with Auth::guard('client')->attempt()...\n";
$success = \Illuminate\Support\Facades\Auth::guard('client')->attempt(['email' => 'test@example.com', 'password' => 'password123']);
echo $success ? "SUCCESS" : "FAILURE";
echo "\n";
