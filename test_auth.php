<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\ClientUser::latest()->first();
echo "Email: " . $user->email . "\n";
echo "Password hash: " . $user->password . "\n";
echo "Hash matches 'password'? " . (\Illuminate\Support\Facades\Hash::check('password', $user->password) ? 'YES' : 'NO') . "\n";
echo "Hash matches 'password123'? " . (\Illuminate\Support\Facades\Hash::check('password123', $user->password) ? 'YES' : 'NO') . "\n";
echo "Active: " . $user->is_active . "\n";
echo "Auth test for client guard: " . (\Illuminate\Support\Facades\Auth::guard('client')->attempt(['email' => $user->email, 'password' => 'password123']) ? 'SUCCESS' : 'FAILURE') . "\n";
