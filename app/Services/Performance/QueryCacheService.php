<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class QueryCacheService
{
    protected const DEFAULT_TTL = 600; // 10 minutes
    protected const CACHE_PREFIX = 'query_cache';
    protected const MAX_CACHE_SIZE = 1000; // Maximum number of cached queries

    /**
     * Cache a query result with intelligent TTL
     */
    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($callback, $key) {
            $startTime = microtime(true);
            $result = $callback();
            $executionTime = (microtime(true) - $startTime) * 1000;

            // Log slow queries
            if ($executionTime > 1000) {
                Log::warning('Slow cached query detected', [
                    'key' => $key,
                    'execution_time_ms' => $executionTime
                ]);
            }

            return $result;
        });
    }

    /**
     * Cache Eloquent query with automatic key generation
     */
    public function rememberEloquent(Builder $query, int $ttl = null): mixed
    {
        $key = $this->generateEloquentKey($query);
        return $this->remember($key, fn() => $query->get(), $ttl);
    }

    /**
     * Cache paginated results
     */
    public function rememberPaginated(Builder $query, int $perPage = 15, int $ttl = null): mixed
    {
        $key = $this->generatePaginatedKey($query, $perPage);
        return $this->remember($key, fn() => $query->paginate($perPage), $ttl);
    }

    /**
     * Cache count queries
     */
    public function rememberCount(Builder $query, int $ttl = null): int
    {
        $key = $this->generateCountKey($query);
        return $this->remember($key, fn() => $query->count(), $ttl);
    }

    /**
     * Cache exists queries
     */
    public function rememberExists(Builder $query, int $ttl = null): bool
    {
        $key = $this->generateExistsKey($query);
        return $this->remember($key, fn() => $query->exists(), $ttl);
    }

    /**
     * Cache first result
     */
    public function rememberFirst(Builder $query, int $ttl = null): ?Model
    {
        $key = $this->generateFirstKey($query);
        return $this->remember($key, fn() => $query->first(), $ttl);
    }

    /**
     * Cache find result
     */
    public function rememberFind(string $model, $id, int $ttl = null): ?Model
    {
        $key = $this->generateFindKey($model, $id);
        return $this->remember($key, fn() => $model::find($id), $ttl);
    }

    /**
     * Cache with tags for easy invalidation
     */
    public function rememberWithTags(string $key, callable $callback, array $tags = [], int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            return Cache::tags($tags)->remember($cacheKey, now()->addSeconds($ttl), $callback);
        }

        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Invalidate cache by pattern
     */
    public function invalidateByPattern(string $pattern): int
    {
        $cleared = 0;
        
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = \Illuminate\Support\Facades\Redis::keys(self::CACHE_PREFIX . ':' . $pattern);
                if (!empty($keys)) {
                    \Illuminate\Support\Facades\Redis::del($keys);
                    $cleared = count($keys);
                }
            } else {
                // For other cache drivers, we need to flush everything
                Cache::flush();
                $cleared = 1;
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }

        return $cleared;
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags): void
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
                Cache::tags($tags)->flush();
            } else {
                Log::warning('Cache store does not support tags, flushing all cache');
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache by tags', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate model-related cache
     */
    public function invalidateModel(Model $model): void
    {
        $tags = [
            'model:' . get_class($model),
            'model:' . get_class($model) . ':' . $model->getKey()
        ];

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate table-related cache
     */
    public function invalidateTable(string $table): void
    {
        $this->invalidateByPattern("*{$table}*");
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        try {
            $stats = [
                'total_keys' => 0,
                'memory_usage' => 0,
                'hit_rate' => 0,
                'keys_by_type' => []
            ];

            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $info = \Illuminate\Support\Facades\Redis::info();
                $stats['memory_usage'] = $info['used_memory_human'] ?? 'N/A';

                // Count keys by pattern
                $patterns = [
                    'eloquent' => self::CACHE_PREFIX . ':*eloquent*',
                    'count' => self::CACHE_PREFIX . ':*count*',
                    'exists' => self::CACHE_PREFIX . ':*exists*',
                    'first' => self::CACHE_PREFIX . ':*first*',
                    'find' => self::CACHE_PREFIX . ':*find*'
                ];

                foreach ($patterns as $type => $pattern) {
                    $keys = \Illuminate\Support\Facades\Redis::keys($pattern);
                    $stats['keys_by_type'][$type] = count($keys);
                    $stats['total_keys'] += count($keys);
                }
            }

            return $stats;
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm up cache for common queries
     */
    public function warmUpCache(): array
    {
        $warmed = [];

        try {
            // Warm up user queries
            $warmed['users'] = $this->warmUpUserQueries();
            
            // Warm up transaction queries
            $warmed['transactions'] = $this->warmUpTransactionQueries();
            
            // Warm up charging point queries
            $warmed['charging_points'] = $this->warmUpChargingPointQueries();

            Log::info('Query cache warmed up successfully', $warmed);
        } catch (\Exception $e) {
            Log::error('Failed to warm up query cache', [
                'error' => $e->getMessage()
            ]);
        }

        return $warmed;
    }

    /**
     * Clear all query cache
     */
    public function clearAll(): void
    {
        $this->invalidateByPattern('*');
        Log::info('All query cache cleared');
    }

    /**
     * Generate cache key for Eloquent query
     */
    private function generateEloquentKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'eloquent:' . md5($sql . serialize($bindings));
    }

    /**
     * Generate cache key for paginated query
     */
    private function generatePaginatedKey(Builder $query, int $perPage): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'paginated:' . $perPage . ':' . md5($sql . serialize($bindings));
    }

    /**
     * Generate cache key for count query
     */
    private function generateCountKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'count:' . md5($sql . serialize($bindings));
    }

    /**
     * Generate cache key for exists query
     */
    private function generateExistsKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'exists:' . md5($sql . serialize($bindings));
    }

    /**
     * Generate cache key for first query
     */
    private function generateFirstKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'first:' . md5($sql . serialize($bindings));
    }

    /**
     * Generate cache key for find query
     */
    private function generateFindKey(string $model, $id): string
    {
        return 'find:' . $model . ':' . $id;
    }

    /**
     * Get full cache key with prefix
     */
    private function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . ':' . $key;
    }

    /**
     * Warm up user-related queries
     */
    private function warmUpUserQueries(): int
    {
        $count = 0;
        
        try {
            // Warm up active users
            \App\Models\User::where('last_activity_at', '>=', now()->subDays(7))->get();
            $count++;
            
            // Warm up user roles
            \App\Models\User::with('roles')->get();
            $count++;
            
            // Warm up user permissions
            \App\Models\User::with('permissions')->get();
            $count++;
        } catch (\Exception $e) {
            Log::error('Failed to warm up user queries', ['error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Warm up transaction-related queries
     */
    private function warmUpTransactionQueries(): int
    {
        $count = 0;
        
        try {
            // Warm up recent transactions
            \App\Models\Transaction::where('created_at', '>=', now()->subDays(30))->get();
            $count++;
            
            // Warm up transaction statistics
            \App\Models\Transaction::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
            $count++;
        } catch (\Exception $e) {
            Log::error('Failed to warm up transaction queries', ['error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Warm up charging point queries
     */
    private function warmUpChargingPointQueries(): int
    {
        $count = 0;
        
        try {
            // Warm up active charging points
            \App\Models\ChargingPoint::where('status', 'available')->get();
            $count++;
            
            // Warm up charging point statistics
            \App\Models\ChargingPoint::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
            $count++;
        } catch (\Exception $e) {
            Log::error('Failed to warm up charging point queries', ['error' => $e->getMessage()]);
        }

        return $count;
    }
}
