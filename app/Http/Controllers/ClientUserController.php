<?php

namespace App\Http\Controllers;

use App\Models\ClientUser;
use Illuminate\Http\Request;

class ClientUserController extends Controller
{
    // Exemple : afficher la liste des clients
    public function index()
    {
        $clients = ClientUser::all();
        return response()->json($clients);
    }

    public function create()
    {
        // Afficher un formulaire de création (exemple API)
        return response()->json(['message' => 'Formulaire de création client']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:client_users,email',
            'password' => 'required|string|min:6',
        ]);
        $validated['password'] = bcrypt($validated['password']);
        $client = ClientUser::create($validated);
        return response()->json($client, 201);
    }

    public function show($id)
    {
        $client = ClientUser::findOrFail($id);
        return response()->json($client);
    }

    public function edit($id)
    {
        $client = ClientUser::findOrFail($id);
        // Afficher un formulaire d'édition (exemple API)
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $client = ClientUser::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:client_users,email,' . $id,
            'password' => 'nullable|string|min:6',
        ]);
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }
        $client->update($validated);
        return response()->json($client);
    }

    public function destroy($id)
    {
        $client = ClientUser::findOrFail($id);
        $client->delete();
        return response()->json(['message' => 'Client supprimé']);
    }
} 