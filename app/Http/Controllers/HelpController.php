<?php

namespace App\Http\Controllers;

use App\Models\HelpCategory;
use App\Models\HelpArticle;
use App\Services\HelpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Contrôleur pour les pages d'aide et FAQ (Web)
 */
class HelpController extends Controller
{
    public function __construct(
        protected HelpService $helpService
    ) {}

    /**
     * Page d'accueil de l'aide - liste des catégories
     */
    public function index()
    {
        $categories = $this->helpService->getCategoriesWithArticles();
        $featuredArticles = $this->helpService->getFeaturedArticles(6);

        return view('help.index', compact('categories', 'featuredArticles'));
    }

    /**
     * Afficher une catégorie d'aide
     */
    public function category($slug)
    {
        $category = HelpCategory::where('slug', $slug)
            ->with(['articles' => function ($query) {
                $query->published()->ordered();
            }])
            ->firstOrFail();

        return view('help.category', compact('category'));
    }

    /**
     * Afficher un article d'aide
     */
    public function article($slug)
    {
        $article = $this->helpService->getArticleBySlug($slug);
        
        if (!$article) {
            abort(404);
        }

        $relatedArticles = $this->helpService->getRelatedArticles($article, 4);

        return view('help.article', compact('article', 'relatedArticles'));
    }

    /**
     * Page FAQ
     */
    public function faq()
    {
        $categories = $this->helpService->getCategoriesWithArticles();
        
        return view('help.faq', compact('categories'));
    }

    /**
     * Page Getting Started / Guide de démarrage
     */
    public function gettingStarted()
    {
        $articles = HelpArticle::published()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'getting-started');
            })
            ->ordered()
            ->get();

        return view('help.getting-started', compact('articles'));
    }

    /**
     * Page Troubleshooting
     */
    public function troubleshooting()
    {
        $articles = HelpArticle::published()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'troubleshooting');
            })
            ->ordered()
            ->get();

        return view('help.troubleshooting', compact('articles'));
    }

    /**
     * Rechercher dans l'aide
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $articles = $this->helpService->search($request->q);

        return view('help.search', [
            'query' => $request->q,
            'results' => $articles,
            'count' => $articles->count(),
        ]);
    }
}
