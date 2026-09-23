<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use App\Models\OcppTag;
use App\Models\OcppTagHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClientOcppTagController extends Controller
{
    /**
     * Display the OCPP tags management page for a client.
     */
    public function index(ClientUser $client)
    {
        $client->load(['ocppTags', 'vehicles']);

        return view('admin.clients.tags.index', compact('client'));
    }

    /**
     * Show the form for creating a new tag for a client.
     */
    public function create(ClientUser $client)
    {
        return view('admin.clients.tags.create', compact('client'));
    }

    /**
     * Store a new OCPP tag for a client.
     */
    public function store(Request $request, ClientUser $client)
    {
        $validated = $request->validate([
            'ocpp_tag' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'unique:ocpp_tags,ocpp_tag',
            ],
            'expiry_date' => 'nullable|date|after:now',
            'is_default' => 'boolean',
            'note' => 'nullable|string|max:500',
        ], [
            'ocpp_tag.required' => 'Le code tag OCPP est obligatoire.',
            'ocpp_tag.regex' => 'Le code tag ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'ocpp_tag.unique' => 'Ce code tag existe déjà dans le système.',
            'expiry_date.after' => 'La date d\'expiration doit être dans le futur.',
        ]);

        try {
            // Create the tag
            $tag = OcppTag::create([
                'ocpp_tag' => $validated['ocpp_tag'],
                'user_id' => $client->id,
                'blocked' => false,
                'is_default' => $validated['is_default'] ?? false,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'note' => $validated['note'] ?? null,
                'total_sessions' => 0,
            ]);

            // If this is set as default, update other tags
            if ($tag->is_default) {
                $tag->setAsDefault();
            }

            // Record history
            OcppTagHistory::record(
                $tag,
                $client,
                'created',
                null,
                'active',
                'Tag créé via l\'administration'
            );

            return redirect()->route('admin.clients.tags.index', $client)
                ->with('success', 'Tag OCPP créé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error creating OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création du tag: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for associating an existing tag.
     */
    public function showAssociateForm(ClientUser $client)
    {
        // Get unassigned tags
        $availableTags = OcppTag::whereNull('user_id')
            ->orWhere('user_id', $client->id)
            ->get();

        return view('admin.clients.tags.associate', compact('client', 'availableTags'));
    }

    /**
     * Associate an existing tag with a client.
     */
    public function associate(Request $request, ClientUser $client)
    {
        $validated = $request->validate([
            'ocpp_tag' => [
                'required',
                'string',
                'max:50',
                Rule::exists('ocpp_tags', 'ocpp_tag'),
            ],
            'is_default' => 'boolean',
            'note' => 'nullable|string|max:500',
        ], [
            'ocpp_tag.required' => 'Le code tag OCPP est obligatoire.',
            'ocpp_tag.exists' => 'Ce tag n\'existe pas dans le système.',
        ]);

        try {
            $tag = OcppTag::where('ocpp_tag', $validated['ocpp_tag'])->first();

            // Check if already associated
            if ($tag->user_id && $tag->user_id != $client->id) {
                return back()->with('error', 'Ce tag est déjà associé à un autre client.');
            }

            $statusBefore = $tag->user_id ? 'associated' : 'unassigned';
            
            // Update the tag
            $tag->update([
                'user_id' => $client->id,
                'is_default' => $validated['is_default'] ?? false,
                'note' => $validated['note'] ?? $tag->note,
            ]);

            // If this is set as default, update other tags
            if ($tag->is_default) {
                $tag->setAsDefault();
            }

            // Record history
            OcppTagHistory::record(
                $tag,
                $client,
                'associated',
                $statusBefore,
                'active',
                'Tag associé via l\'administration'
            );

            return redirect()->route('admin.clients.tags.index', $client)
                ->with('success', 'Tag OCPP associé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error associating OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'association du tag: ' . $e->getMessage());
        }
    }

    /**
     * Dissociate a tag from a client.
     */
    public function dissociate(Request $request, ClientUser $client, OcppTag $tag)
    {
        // Verify the tag belongs to this client
        if ($tag->user_id != $client->id) {
            return back()->with('error', 'Ce tag n\'appartient pas à ce client.');
        }

        try {
            $statusBefore = $tag->blocked ? 'blocked' : 'active';
            
            // Check if tag has active sessions
            if ($tag->total_sessions > 0) {
                return back()->with('error', 'Impossible de dissocier un tag ayant des sessions enregistrées.');
            }

            // Dissociate the tag (set user_id to null but keep the tag record)
            $tag->update(['user_id' => null]);

            // Record history
            OcppTagHistory::record(
                $tag,
                $client,
                'dissociated',
                $statusBefore,
                'unassigned',
                'Tag dissocié via l\'administration'
            );

            return redirect()->route('admin.clients.tags.index', $client)
                ->with('success', 'Tag OCPP dissocié avec succès.');

        } catch (\Exception $e) {
            Log::error('Error dissociating OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la dissociation du tag: ' . $e->getMessage());
        }
    }

    /**
     * Block a tag.
     */
    public function block(Request $request, ClientUser $client, OcppTag $tag)
    {
        // Verify the tag belongs to this client
        if ($tag->user_id != $client->id) {
            return back()->with('error', 'Ce tag n\'appartient pas à ce client.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $statusBefore = $tag->blocked ? 'blocked' : 'active';
            
            $tag->block($validated['reason'] ?? 'Bloqué par l\'administration');

            // Record history
            OcppTagHistory::record(
                $tag,
                $client,
                'blocked',
                $statusBefore,
                'blocked',
                $validated['reason'] ?? 'Bloqué par l\'administration'
            );

            return redirect()->route('admin.clients.tags.index', $client)
                ->with('success', 'Tag OCPP bloqué avec succès.');

        } catch (\Exception $e) {
            Log::error('Error blocking OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du blocage du tag: ' . $e->getMessage());
        }
    }

    /**
     * Unblock a tag.
     */
    public function unblock(ClientUser $client, OcppTag $tag)
    {
        // Verify the tag belongs to this client
        if ($tag->user_id != $client->id) {
            return back()->with('error', 'Ce tag n\'appartient pas à ce client.');
        }

        try {
            $statusBefore = $tag->blocked ? 'blocked' : 'active';
            
            $tag->unblock();

            // Record history
            OcppTagHistory::record(
                $tag,
                $client,
                'unblocked',
                $statusBefore,
                'active',
                'Débloqué par l\'administration'
            );

            return redirect()->route('admin.clients.tags.index', $client)
                ->with('success', 'Tag OCPP débloqué avec succès.');

        } catch (\Exception $e) {
            Log::error('Error unblocking OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du déblocage du tag: ' . $e->getMessage());
        }
    }

    /**
     * Set a tag as default.
     */
    public function setDefault(ClientUser $client, OcppTag $tag)
    {
        // Verify the tag belongs to this client
        if ($tag->user_id != $client->id) {
            return back()->with('error', 'Ce tag n\'appartient pas à ce client.');
        }

        try {
            $tag->setAsDefault();

            return redirect()->route('admin.clients.tags.index', $client)
                ->with('success', 'Tag OCPP défini par défaut avec succès.');

        } catch (\Exception $e) {
            Log::error('Error setting default OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la définition du tag par défaut: ' . $e->getMessage());
        }
    }

    /**
     * Show tag history.
     */
    public function history(ClientUser $client, OcppTag $tag)
    {
        // Verify the tag belongs to this client
        if ($tag->user_id != $client->id) {
            return back()->with('error', 'Ce tag n\'appartient pas à ce client.');
        }

        $history = OcppTagHistory::where('ocpp_tag_id', $tag->id)
            ->orderBy('action_date', 'desc')
            ->get();

        return view('admin.clients.tags.history', compact('client', 'tag', 'history'));
    }

    /**
     * Delete a tag.
     */
    public function destroy(ClientUser $client, OcppTag $tag)
    {
        // Verify the tag belongs to this client
        if ($tag->user_id != $client->id) {
            return back()->with('error', 'Ce tag n\'appartient pas à ce client.');
        }

        try {
            // Check if tag has active sessions
            if ($tag->total_sessions > 0) {
                return back()->with('error', 'Impossible de supprimer un tag ayant des sessions enregistrées.');
            }

            $tag->delete();

            return redirect()->route('admin.clients.show', $client)
                ->with('success', 'Tag OCPP supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error deleting OCPP tag: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la suppression du tag: ' . $e->getMessage());
        }
    }
}
