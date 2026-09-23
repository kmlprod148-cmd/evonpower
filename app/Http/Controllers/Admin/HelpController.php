<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HelpService;
use App\Models\HelpCategory;
use App\Models\HelpArticle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Contrôleur Admin pour la gestion de l'aide et FAQ
 */
class HelpController extends Controller
{
    public function __construct(
        protected HelpService $helpService
    ) {}

    /**
     * Tableau de bord de l'aide
     */
    public function index(): View
    {
        $stats = $this->helpService->getStatistics();
        
        return view('admin.help.index', compact('stats'));
    }

    /**
     * Liste des catégories
     */
    public function categoriesIndex(): View
    {
        $categories = HelpCategory::withCount('articles')->ordered()->get();
        
        return view('admin.help.categories.index', compact('categories'));
    }

    /**
     * Créer une catégorie
     */
    public function categoryStore(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:help_categories,id',
        ]);

        $category = $this->helpService->createCategory($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'category' => $category,
        ]);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function categoryUpdate(Request $request, HelpCategory $category): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category = $this->helpService->updateCategory($category, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour',
            'category' => $category,
        ]);
    }

    /**
     * Supprimer une catégorie
     */
    public function categoryDestroy(HelpCategory $category): JsonResponse
    {
        $this->helpService->deleteCategory($category);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée',
        ]);
    }

    /**
     * Liste des articles
     */
    public function articlesIndex(Request $request): View
    {
        $articles = HelpArticle::with('category', 'author')
            ->when($request->status, fn($q, $status) => $q->where('is_published', $status === 'published'))
            ->when($request->category, fn($q, $cat) => $q->where('category_id', $cat))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $categories = HelpCategory::active()->ordered()->get();

        return view('admin.help.articles.index', compact('articles', 'categories'));
    }

    /**
     * Formulaire de création d'article
     */
    public function articleCreate(): View
    {
        $categories = HelpCategory::active()->ordered()->get();
        
        return view('admin.help.articles.create', compact('categories'));
    }

    /**
     * Enregistrer un article
     */
    public function articleStore(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:help_categories,id',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $article = $this->helpService->createArticle($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Article créé avec succès',
            'article' => $article,
        ]);
    }

    /**
     * Formulaire d'édition d'article
     */
    public function articleEdit(HelpArticle $article): View
    {
        $categories = HelpCategory::active()->ordered()->get();
        
        return view('admin.help.articles.edit', compact('article', 'categories'));
    }

    /**
     * Mettre à jour un article
     */
    public function articleUpdate(Request $request, HelpArticle $article): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:help_categories,id',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $article = $this->helpService->updateArticle($article, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour',
            'article' => $article,
        ]);
    }

    /**
     * Supprimer un article
     */
    public function articleDestroy(HelpArticle $article): JsonResponse
    {
        $this->helpService->deleteArticle($article);

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé',
        ]);
    }

    /**
     * Basculer le statut de publication
     */
    public function articleTogglePublish(HelpArticle $article): JsonResponse
    {
        $article = $this->helpService->togglePublish($article);

        return response()->json([
            'success' => true,
            'message' => $article->is_published ? 'Article publié' : 'Article dépublié',
            'is_published' => $article->is_published,
        ]);
    }

    /**
     * Statistiques de l'aide
     */
    public function statistics(): JsonResponse
    {
        return response()->json($this->helpService->getStatistics());
    }

    /**
     * Recherches populaires
     */
    public function popularSearches(): JsonResponse
    {
        $searches = $this->helpService->getPopularSearches();

        return response()->json([
            'searches' => $searches,
        ]);
    }
}
