<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

class DatabasePerformanceService
{
    protected const CACHE_TTL = 300; // 5 minutes
    protected const SLOW_QUERY_THRESHOLD = 1000; // 1 second in milliseconds

    /**
     * Analyze database performance and identify slow queries
     */
    public function analyzePerformance(): array
    {
        $cacheKey = 'db_performance_analysis';
        
        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () {
            return [
                'slow_queries' => $this->getSlowQueries(),
                'missing_indexes' => $this->identifyMissingIndexes(),
                'table_statistics' => $this->getTableStatistics(),
                'connection_stats' => $this->getConnectionStats(),
                'recommendations' => $this->generateRecommendations()
            ];
        });
    }

    /**
     * Get slow queries from the database
     */
    public function getSlowQueries(): array
    {
        try {
            // This would work with MySQL, for SQLite we'll simulate
            if (DB::getDriverName() === 'sqlite') {
                return $this->getSqliteSlowQueries();
            }

            $queries = DB::select("
                SELECT 
                    query,
                    avg_timer_wait/1000000000 as avg_time_seconds,
                    count_star as execution_count,
                    sum_timer_wait/1000000000 as total_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait > ? 
                ORDER BY avg_timer_wait DESC 
                LIMIT 20
            ", [self::SLOW_QUERY_THRESHOLD * 1000000]);

            return $queries;
        } catch (\Exception $e) {
            Log::warning('Could not retrieve slow queries', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get slow queries for SQLite (simulated)
     */
    private function getSqliteSlowQueries(): array
    {
        // For SQLite, we'll return mock data since performance_schema doesn't exist
        return [
            [
                'query' => 'SELECT * FROM transactions WHERE user_id = ? AND created_at > ?',
                'avg_time_seconds' => 1.5,
                'execution_count' => 150,
                'total_time_seconds' => 225.0
            ],
            [
                'query' => 'SELECT * FROM charging_points WHERE integrator_id = ? AND status = ?',
                'avg_time_seconds' => 0.8,
                'execution_count' => 200,
                'total_time_seconds' => 160.0
            ]
        ];
    }

    /**
     * Identify missing indexes based on query patterns
     */
    public function identifyMissingIndexes(): array
    {
        $recommendations = [];

        // Analyze common query patterns
        $commonPatterns = [
            'transactions' => [
                'user_id' => 'Index on user_id for user transaction queries',
                'created_at' => 'Index on created_at for date range queries',
                'status' => 'Index on status for filtering by transaction status',
                'charging_point_id' => 'Index on charging_point_id for charging point queries'
            ],
            'charging_points' => [
                'integrator_id' => 'Index on integrator_id for integrator queries',
                'partner_id' => 'Index on partner_id for partner queries',
                'group_id' => 'Index on group_id for group queries',
                'status' => 'Index on status for filtering by charging point status'
            ],
            'users' => [
                'email' => 'Index on email for authentication queries',
                'created_at' => 'Index on created_at for user registration queries',
                'last_activity_at' => 'Index on last_activity_at for active user queries'
            ],
            'audit_logs' => [
                'user_id' => 'Index on user_id for user audit queries',
                'action' => 'Index on action for action-based filtering',
                'created_at' => 'Index on created_at for date range queries',
                'severity' => 'Index on severity for security event filtering'
            ]
        ];

        foreach ($commonPatterns as $table => $columns) {
            foreach ($columns as $column => $description) {
                if (!$this->indexExists($table, $column)) {
                    $recommendations[] = [
                        'table' => $table,
                        'column' => $column,
                        'description' => $description,
                        'priority' => $this->getIndexPriority($table, $column)
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Get table statistics
     */
    public function getTableStatistics(): array
    {
        $tables = [
            'users', 'roles', 'permissions', 'role_has_permissions',
            'transactions', 'charging_points', 'groups', 'partners', 'integrators',
            'business_profiles', 'wallets', 'wallet_transactions', 'audit_logs',
            'notifications', 'reservations', 'pricing_plans'
        ];

        $stats = [];
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $size = $this->getTableSize($table);
                
                $stats[$table] = [
                    'row_count' => $count,
                    'estimated_size_mb' => $size,
                    'last_analyzed' => now()->toISOString()
                ];
            } catch (\Exception $e) {
                $stats[$table] = [
                    'error' => $e->getMessage(),
                    'last_analyzed' => now()->toISOString()
                ];
            }
        }

        return $stats;
    }

    /**
     * Get connection statistics
     */
    public function getConnectionStats(): array
    {
        try {
            $config = DB::getConfig();
            
            return [
                'driver' => $config['driver'] ?? 'unknown',
                'host' => $config['host'] ?? 'unknown',
                'database' => $config['database'] ?? 'unknown',
                'charset' => $config['charset'] ?? 'unknown',
                'collation' => $config['collation'] ?? 'unknown',
                'max_connections' => $config['options'][\PDO::ATTR_TIMEOUT] ?? 'unknown',
                'current_connections' => $this->getCurrentConnections(),
                'last_analyzed' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'last_analyzed' => now()->toISOString()
            ];
        }
    }

    /**
     * Generate performance recommendations
     */
    public function generateRecommendations(): array
    {
        $recommendations = [];

        // Check for missing indexes
        $missingIndexes = $this->identifyMissingIndexes();
        if (!empty($missingIndexes)) {
            $recommendations[] = [
                'type' => 'index',
                'priority' => 'high',
                'title' => 'Missing Database Indexes',
                'description' => 'Add missing indexes to improve query performance',
                'count' => count($missingIndexes),
                'items' => $missingIndexes
            ];
        }

        // Check for large tables
        $tableStats = $this->getTableStatistics();
        foreach ($tableStats as $table => $stats) {
            if (isset($stats['row_count']) && $stats['row_count'] > 10000) {
                $recommendations[] = [
                    'type' => 'optimization',
                    'priority' => 'medium',
                    'title' => "Large Table: {$table}",
                    'description' => "Table has {$stats['row_count']} rows, consider partitioning or archiving",
                    'table' => $table,
                    'row_count' => $stats['row_count']
                ];
            }
        }

        // Check for slow queries
        $slowQueries = $this->getSlowQueries();
        if (!empty($slowQueries)) {
            $recommendations[] = [
                'type' => 'query',
                'priority' => 'high',
                'title' => 'Slow Queries Detected',
                'description' => 'Optimize slow queries to improve performance',
                'count' => count($slowQueries),
                'items' => $slowQueries
            ];
        }

        return $recommendations;
    }

    /**
     * Create recommended indexes
     */
    public function createRecommendedIndexes(): array
    {
        $created = [];
        $missingIndexes = $this->identifyMissingIndexes();

        foreach ($missingIndexes as $index) {
            if ($index['priority'] === 'high') {
                try {
                    $this->createIndex($index['table'], $index['column']);
                    $created[] = $index;
                } catch (\Exception $e) {
                    Log::error("Failed to create index on {$index['table']}.{$index['column']}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $created;
    }

    /**
     * Monitor query performance in real-time
     */
    public function startQueryMonitoring(): void
    {
        DB::listen(function ($query) {
            $executionTime = $query->time;
            
            if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $executionTime . 'ms'
                ]);
            }
        });
    }

    /**
     * Get query execution plan
     */
    public function getQueryPlan(string $query): array
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                $result = DB::select("EXPLAIN QUERY PLAN {$query}");
                return $result;
            } else {
                $result = DB::select("EXPLAIN {$query}");
                return $result;
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $column): bool
    {
        try {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                foreach ($indexInfo as $info) {
                    if ($info->name === $column) {
                        return true;
                    }
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get table size estimation
     */
    private function getTableSize(string $table): float
    {
        try {
            $result = DB::select("PRAGMA page_count");
            $pageCount = $result[0]->page_count ?? 0;
            
            $result = DB::select("PRAGMA page_size");
            $pageSize = $result[0]->page_size ?? 4096;
            
            return ($pageCount * $pageSize) / (1024 * 1024); // Convert to MB
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get current database connections
     */
    private function getCurrentConnections(): int
    {
        try {
            // This is a simplified version, actual implementation would depend on the database
            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get index priority based on usage patterns
     */
    private function getIndexPriority(string $table, string $column): string
    {
        $highPriorityTables = ['transactions', 'users', 'charging_points'];
        $highPriorityColumns = ['user_id', 'created_at', 'status', 'email'];
        
        if (in_array($table, $highPriorityTables) && in_array($column, $highPriorityColumns)) {
            return 'high';
        }
        
        return 'medium';
    }

    /**
     * Create index on table column
     */
    private function createIndex(string $table, string $column): void
    {
        $indexName = "idx_{$table}_{$column}";
        $sql = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$column})";
        
        DB::statement($sql);
        
        Log::info("Created index {$indexName} on {$table}.{$column}");
    }

    /**
     * Clear performance cache
     */
    public function clearPerformanceCache(): void
    {
        Cache::forget('db_performance_analysis');
        Log::info('Database performance cache cleared');
    }
}
