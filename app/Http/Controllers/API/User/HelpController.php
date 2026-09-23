<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\HelpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour l'aide et FAQ (Utilisateur)
 */
class HelpController extends Controller
{
    public function __construct(
        protected HelpService $helpService
    ) {}

    /**
     * Obtenir toutes les catégories
     */
    public function categories(): JsonResponse
    {
        $categories = $this->helpService->getCategoriesWithArticles();

        return response()->json([
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'articles_count' => $category->articles->count(),
                    'articles' => $category->articles->map(function ($article) {
                        return [
                            'id' => $article->id,
                            'title' => $article->title,
                            'slug' => $article->slug,
                            'excerpt' => $article->excerpt,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Obtenir les articles en vedette
     */
    public function featured(): JsonResponse
    {
        $articles = $this->helpService->getFeaturedArticles();

        return response()->json([
            'articles' => $articles->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'category' => [
                        'name' => $article->category->name,
                        'slug' => $article->category->slug,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Rechercher des articles
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $articles = $this->helpService->search($request->q);

        return response()->json([
            'query' => $request->q,
            'results' => $articles->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'category' => [
                        'name' => $article->category->name,
                        'slug' => $article->category->slug,
                    ],
                ];
            }),
            'count' => $articles->count(),
        ]);
    }

    /**
     * Obtenir un article par slug
     */
    public function show(string $slug): JsonResponse
    {
        $article = $this->helpService->getArticleBySlug($slug);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé',
            ], 404);
        }

        $related = $this->helpService->getRelatedArticles($article);

        return response()->json([
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'content' => $article->content,
                'content_html' => $article->content_html,
                'excerpt' => $article->excerpt,
                'meta_title' => $article->meta_title,
                'meta_description' => $article->meta_description,
                'view_count' => $article->view_count,
                'tags' => $article->tags,
                'category' => [
                    'id' => $article->category->id,
                    'name' => $article->category->name,
                    'slug' => $article->category->slug,
                ],
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ],
            'related_articles' => $related->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'slug' => $a->slug,
                    'excerpt' => $a->excerpt,
                ];
            }),
        ]);
    }

    /**
     * Obtenir les articles populaires
     */
    public function popular(): JsonResponse
    {
        $articles = $this->helpService->getPopularArticles();

        return response()->json([
            'articles' => $articles->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'view_count' => $article->view_count,
                ];
            }),
        ]);
    }
}
