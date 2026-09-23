<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data)
    {
        // Basic registration logic
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // You might want to generate an API token here
        // $token = $user->createToken('authToken')->plainTextToken;

        return $user;
    }

    public function login(array $credentials)
    {
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // You might want to generate an API token here
            // $token = $user->createToken('authToken')->plainTextToken;
            return $user;
        }

        return false;
    }

    public function logout($user)
    {
        // Revoke the user's current token
        $user->currentAccessToken()->delete();

        return true;
    }
}