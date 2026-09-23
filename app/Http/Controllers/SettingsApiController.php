<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsApiController extends Controller
{
    public function index()
    {
        $apiKeys = ApiKey::where('user_id', auth()->id())->get();
        return view('settings.api', compact('apiKeys'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $apiKey = ApiKey::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'key' => Str::random(64),
        ]);
        
        return back()->with('success', 'API Key created successfully.');
    }
    
    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();
        return back()->with('success', 'API Key deleted successfully.');
    }
}