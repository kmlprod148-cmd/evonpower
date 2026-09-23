<?php

namespace App\Http\Controllers;

use App\Services\OcppTagRemoteOperationsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Contrôleur pour la gestion complète des Tags OCPP
 * 
 * Permet de visualiser, créer, modifier et supprimer les tags OCPP
 * utilisés pour l'authentification et l'autorisation des sessions de charge
 */
class OcppTagsManagementController extends Controller
{
    protected OcppTagRemoteOperationsService $ocppService;

    public function __construct(OcppTagRemoteOperationsService $ocppService)
    {
        $this->middleware('auth');
        $this->ocppService = $ocppService;
    }

    /**
     * Afficher la page de gestion des tags OCPP
     */
    public function index(Request $request)
    {
        // Récupérer les filtres depuis la requête
        $filters = $request->only([
            'idTag', 'expired', 'inTransaction', 'blocked', 'note'
        ]);

        // Récupérer tous les tags
        $tagsResult = $this->ocppService->listOcppTags($filters);
        $tags = $tagsResult['success'] ? $tagsResult['data'] : [];

        // Statistiques
        $stats = [
            'total' => count($tags),
            'active' => collect($tags)->where('blocked', false)->count(),
            'blocked' => collect($tags)->where('blocked', true)->count(),
            'in_transaction' => collect($tags)->where('inTransaction', true)->count(),
            'expired' => collect($tags)->filter(function($tag) {
                if (empty($tag['expiryDate'])) return false;
                return Carbon::parse($tag['expiryDate'])->isPast();
            })->count(),
        ];

        // Tags par défaut configuré
        $defaultTag = config('steve.default_id_tag', 'Open10Tag');
        $defaultTagInfo = $this->ocppService->getOcppTagInfo($defaultTag);

        return view('ocpp-tags.index', [
            'tags' => $tags,
            'stats' => $stats,
            'filters' => $filters,
            'defaultTag' => $defaultTag,
            'defaultTagInfo' => $defaultTagInfo,
            'config' => $this->ocppService->getConfig()
        ]);
    }

    /**
     * Afficher le formulaire de création d'un tag
     */
    public function create()
    {
        // Récupérer les tags existants pour le parent
        $tagsResult = $this->ocppService->listOcppTags();
        $existingTags = $tagsResult['success'] ? $tagsResult['data'] : [];

        return view('ocpp-tags.create', [
            'existingTags' => $existingTags
        ]);
    }

    /**
     * Enregistrer un nouveau tag OCPP
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_tag' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
            'expiry_date' => 'nullable|date|after:now',
            'max_active_transaction_count' => 'required|integer|min:-1',
            'note' => 'nullable|string|max:255',
            'parent_id_tag' => 'nullable|string|max:50'
        ], [
            'id_tag.required' => 'L\'ID Tag est requis',
            'id_tag.regex' => 'L\'ID Tag ne peut contenir que des lettres, chiffres, tirets et underscores',
            'expiry_date.after' => 'La date d\'expiration doit être dans le futur',
            'max_active_transaction_count.min' => 'La valeur minimum est -1 (illimité)'
        ]);

        try {
            // Vérifier les permissions
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return redirect()->back()
                    ->with('error', 'Seuls les administrateurs peuvent créer des tags OCPP');
            }

            $tagData = [
                'idTag' => $request->input('id_tag'),
                'expiryDate' => $request->input('expiry_date') 
                    ? Carbon::parse($request->input('expiry_date'))->toISOString() 
                    : null,
                'maxActiveTransactionCount' => (int) $request->input('max_active_transaction_count', -1),
                'note' => $request->input('note'),
                'parentIdTag' => $request->input('parent_id_tag')
            ];

            Log::info('OcppTagsManagementController: Creating OCPP tag', [
                'user_id' => Auth::id(),
                'tag_data' => $tagData
            ]);

            $result = $this->ocppService->createOcppTag($tagData);

            if ($result['success']) {
                return redirect()->route('ocpp-tags.index')
                    ->with('success', '✅ Tag OCPP "' . $tagData['idTag'] . '" créé avec succès');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Échec de la création: ' . ($result['error'] ?? $result['message']));

        } catch (\Exception $e) {
            Log::error('OcppTagsManagementController: Exception creating tag', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'un tag OCPP
     */
    public function show(int $ocppTagPk)
    {
        $tagResult = $this->ocppService->getOcppTagByPk($ocppTagPk);

        if (!$tagResult['success']) {
            return redirect()->route('ocpp-tags.index')
                ->with('error', 'Tag OCPP non trouvé');
        }

        $tag = $tagResult['data'];

        // Validation du tag
        $validation = null;
        if (!empty($tag['idTag'])) {
            $validation = $this->ocppService->validateOcppTag($tag['idTag']);
        }

        return view('ocpp-tags.show', [
            'tag' => $tag,
            'validation' => $validation
        ]);
    }

    /**
     * Afficher le formulaire d'édition d'un tag
     */
    public function edit(int $ocppTagPk)
    {
        $tagResult = $this->ocppService->getOcppTagByPk($ocppTagPk);

        if (!$tagResult['success']) {
            return redirect()->route('ocpp-tags.index')
                ->with('error', 'Tag OCPP non trouvé');
        }

        // Récupérer les tags existants pour le parent
        $tagsResult = $this->ocppService->listOcppTags();
        $existingTags = $tagsResult['success'] ? $tagsResult['data'] : [];

        return view('ocpp-tags.edit', [
            'tag' => $tagResult['data'],
            'existingTags' => $existingTags
        ]);
    }

    /**
     * Mettre à jour un tag OCPP
     */
    public function update(Request $request, int $ocppTagPk)
    {
        $request->validate([
            'id_tag' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
            'expiry_date' => 'nullable|date',
            'max_active_transaction_count' => 'nullable|integer|min:-1',
            'note' => 'nullable|string|max:255',
            'parent_id_tag' => 'nullable|string|max:50'
        ]);

        try {
            // Vérifier les permissions
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return redirect()->back()
                    ->with('error', 'Seuls les administrateurs peuvent modifier des tags OCPP');
            }

            $tagData = array_filter([
                'idTag' => $request->input('id_tag'),
                'expiryDate' => $request->input('expiry_date') 
                    ? Carbon::parse($request->input('expiry_date'))->toISOString() 
                    : null,
                'maxActiveTransactionCount' => $request->has('max_active_transaction_count') 
                    ? (int) $request->input('max_active_transaction_count') 
                    : null,
                'note' => $request->input('note'),
                'parentIdTag' => $request->input('parent_id_tag')
            ], fn($v) => $v !== null);

            Log::info('OcppTagsManagementController: Updating OCPP tag', [
                'user_id' => Auth::id(),
                'ocpp_tag_pk' => $ocppTagPk,
                'tag_data' => $tagData
            ]);

            $result = $this->ocppService->updateOcppTag($ocppTagPk, $tagData);

            if ($result['success']) {
                return redirect()->route('ocpp-tags.index')
                    ->with('success', '✅ Tag OCPP mis à jour avec succès');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Échec de la mise à jour: ' . ($result['error'] ?? $result['message']));

        } catch (\Exception $e) {
            Log::error('OcppTagsManagementController: Exception updating tag', [
                'ocpp_tag_pk' => $ocppTagPk,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un tag OCPP
     */
    public function destroy(int $ocppTagPk)
    {
        try {
            // Vérifier les permissions
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return redirect()->back()
                    ->with('error', 'Seuls les administrateurs peuvent supprimer des tags OCPP');
            }

            Log::info('OcppTagsManagementController: Deleting OCPP tag', [
                'user_id' => Auth::id(),
                'ocpp_tag_pk' => $ocppTagPk
            ]);

            $result = $this->ocppService->deleteOcppTag($ocppTagPk);

            if ($result['success']) {
                return redirect()->route('ocpp-tags.index')
                    ->with('success', '✅ Tag OCPP supprimé avec succès');
            }

            return redirect()->back()
                ->with('error', '❌ Échec de la suppression: ' . ($result['error'] ?? $result['message']));

        } catch (\Exception $e) {
            Log::error('OcppTagsManagementController: Exception deleting tag', [
                'ocpp_tag_pk' => $ocppTagPk,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Valider un tag OCPP (AJAX)
     */
    public function validateTag(Request $request): JsonResponse
    {
        $request->validate([
            'id_tag' => 'required|string|max:50'
        ]);

        try {
            $validation = $this->ocppService->validateOcppTag($request->input('id_tag'));

            return response()->json($validation);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechercher des tags (AJAX pour autocomplete)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        try {
            $result = $this->ocppService->listOcppTags(['idTag' => $query]);

            if ($result['success']) {
                $tags = collect($result['data'])->map(function($tag) {
                    return [
                        'id' => $tag['ocppTagPk'],
                        'text' => $tag['idTag'],
                        'blocked' => $tag['blocked'] ?? false,
                        'in_transaction' => $tag['inTransaction'] ?? false,
                    ];
                });

                return response()->json(['results' => $tags]);
            }

            return response()->json(['results' => []]);

        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Exporter les tags en CSV
     */
    public function export(Request $request)
    {
        try {
            $result = $this->ocppService->listOcppTags();

            if (!$result['success']) {
                return redirect()->back()->with('error', 'Impossible de récupérer les tags');
            }

            $tags = $result['data'];

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="ocpp_tags_' . date('Y-m-d_His') . '.csv"',
            ];

            $callback = function() use ($tags) {
                $file = fopen('php://output', 'w');

                // En-têtes CSV
                fputcsv($file, [
                    'ID Tag', 'Note', 'Parent ID Tag', 'Date Expiration',
                    'Max Transactions Actives', 'Transactions Actives',
                    'Bloqué', 'En Transaction', 'OCPP Tag PK'
                ]);

                foreach ($tags as $tag) {
                    fputcsv($file, [
                        $tag['idTag'] ?? '',
                        $tag['note'] ?? '',
                        $tag['parentIdTag'] ?? '',
                        $tag['expiryDate'] ?? '',
                        $tag['maxActiveTransactionCount'] ?? '',
                        $tag['activeTransactionCount'] ?? 0,
                        ($tag['blocked'] ?? false) ? 'Oui' : 'Non',
                        ($tag['inTransaction'] ?? false) ? 'Oui' : 'Non',
                        $tag['ocppTagPk'] ?? ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur export: ' . $e->getMessage());
        }
    }

    /**
     * Rafraîchir les statistiques (AJAX)
     */
    public function refreshStats(): JsonResponse
    {
        try {
            $tagsResult = $this->ocppService->listOcppTags();
            $tags = $tagsResult['success'] ? $tagsResult['data'] : [];

            $stats = [
                'total' => count($tags),
                'active' => collect($tags)->where('blocked', false)->count(),
                'blocked' => collect($tags)->where('blocked', true)->count(),
                'in_transaction' => collect($tags)->where('inTransaction', true)->count(),
                'expired' => collect($tags)->filter(function($tag) {
                    if (empty($tag['expiryDate'])) return false;
                    return Carbon::parse($tag['expiryDate'])->isPast();
                })->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'updated_at' => now()->format('H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}

