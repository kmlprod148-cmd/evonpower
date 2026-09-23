<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoSidebarController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Vérifier les permissions
        if (!$user->hasRole("admin")) {
            abort(403, "Accès non autorisé - Rôle admin requis");
        }
        
        return view("admin.demo-sidebar");
    }
}