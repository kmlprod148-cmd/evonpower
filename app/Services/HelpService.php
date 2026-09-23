<?php

namespace App\Services;

use App\Models\HelpCategory;
use App\Models\HelpArticle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service de gestion de l'aide et FAQ
 */
class HelpService
{
    /**
     * Obtenir toutes les catégories actives avec leurs articles
     */
    public function getCategoriesWithArticles(): \Illuminate\Database\Eloquent\Collection
    {
        return HelpCategory::active()
            ->ordered()
            ->with(['articles' => function ($query) {
                $query->published()->ordered();
            }])
            ->whereNull('parent_id')
            ->get();
    }

    /**
     * Obtenir les articles en vedette
     */
    public function getFeaturedArticles(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return HelpArticle::published()
            ->featured()
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Rechercher des articles
     */
    public function search(string $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $articles = HelpArticle::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhereJsonContains('tags', $query);
            })
            ->ordered()
            ->limit($limit)
            ->get();

        // Enregistrer la recherche pour analytics
        $this->logSearch($query, $articles->count());

        return $articles;
    }

    /**
     * Obtenir un article par slug
     */
    public function getArticleBySlug(string $slug): ?HelpArticle
    {
        $article = HelpArticle::where('slug', $slug)->first();
        
        if ($article) {
            $article->incrementViewCount();
        }
        
        return $article;
    }

    /**
     * Obtenir les articles liés
     */
    public function getRelatedArticles(HelpArticle $article, int $limit = 3): \Illuminate\Database\Eloquent\Collection
    {
        return HelpArticle::published()
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les articles populaires
     */
    public function getPopularArticles(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return HelpArticle::published()
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Créer une catégorie
     */
    public function createCategory(array $data): HelpCategory
    {
        $data['slug'] = Str::slug($data['name']);
        
        return HelpCategory::create($data);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory(HelpCategory $category, array $data): HelpCategory
    {
        if (isset($data['name']) && $category->name !== $data['name']) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);
        
        return $category->fresh();
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory(HelpCategory $category): bool
    {
        // Déplacer les articles vers une catégorie par défaut ou supprimer
        $category->articles()->delete();
        
        return $category->delete();
    }

    /**
     * Créer un article
     */
    public function createArticle(array $data): HelpArticle
    {
        $data['slug'] = Str::slug($data['title']);
        $data['author_id'] = $data['author_id'] ?? auth()->id();
        
        return HelpArticle::create($data);
    }

    /**
     * Mettre à jour un article
     */
    public function updateArticle(HelpArticle $article, array $data): HelpArticle
    {
        if (isset($data['title']) && $article->title !== $data['title']) {
            $data['slug'] = Str::slug($data['title']);
        }

        $article->update($data);
        
        return $article->fresh();
    }

    /**
     * Supprimer un article
     */
    public function deleteArticle(HelpArticle $article): bool
    {
        return $article->delete();
    }

    /**
     * Publier un article
     */
    public function publishArticle(HelpArticle $article): HelpArticle
    {
        $article->update(['is_published' => true]);
        
        return $article->fresh();
    }

    /**
     * Dépublier un article
     */
    public function unpublishArticle(HelpArticle $article): HelpArticle
    {
        $article->update(['is_published' => false]);
        
        return $article->fresh();
    }

    /**
     * Basculer le statut de publication
     */
    public function togglePublish(HelpArticle $article): HelpArticle
    {
        $article->update(['is_published' => !$article->is_published]);
        
        return $article->fresh();
    }

    /**
     * Obtenir les statistiques d'aide
     */
    public function getStatistics(): array
    {
        return [
            'categories' => [
                'total' => HelpCategory::count(),
                'active' => HelpCategory::active()->count(),
            ],
            'articles' => [
                'total' => HelpArticle::count(),
                'published' => HelpArticle::published()->count(),
                'draft' => HelpArticle::where('is_published', false)->count(),
                'featured' => HelpArticle::featured()->count(),
            ],
            'views' => [
                'total' => HelpArticle::sum('view_count'),
                'average' => HelpArticle::published()->avg('view_count') ?? 0,
            ],
            'searches' => [
                'total' => DB::table('help_searches')->count(),
                'today' => DB::table('help_searches')->whereDate('created_at', today())->count(),
            ],
        ];
    }

    /**
     * Obtenir les recherches populaires
     */
    public function getPopularSearches(int $limit = 20): \Illuminate\Support\Collection
    {
        return DB::table('help_searches')
            ->select('query', DB::raw('COUNT(*) as count'), DB::raw('AVG(results_count) as avg_results'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Enregistrer une recherche pour analytics
     */
    protected function logSearch(string $query, int $resultsCount): void
    {
        if (strlen($query) >= 2) {
            DB::table('help_searches')->insert([
                'query' => $query,
                'results_count' => $resultsCount,
                'user_id' => auth()->id() ?? null,
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Obtenir l'arborescence des catégories
     */
    public function getCategoryTree(): \Illuminate\Database\Eloquent\Collection
    {
        return HelpCategory::active()
            ->ordered()
            ->with('children.articles')
            ->whereNull('parent_id')
            ->get();
    }

    /**
     * Obtenir tous les articles pour un utilisateur
     */
    public function getAllPublishedArticles(): \Illuminate\Database\Eloquent\Collection
    {
        return HelpArticle::published()
            ->with('category')
            ->ordered()
            ->get();
    }
}
