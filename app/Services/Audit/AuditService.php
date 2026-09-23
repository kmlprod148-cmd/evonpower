<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log an audit event
     */
    public function log(
        string $action,
        string $description,
        ?Model $model = null,
        array $metadata = [],
        ?User $user = null
    ): AuditLog {
        $user = $user ?? Auth::user();
        
        $auditLog = AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->id,
            'metadata' => array_merge($metadata, [
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'timestamp' => now()->toISOString()
            ]),
            'created_at' => now()
        ]);

        // Also log to Laravel's log system for immediate visibility
        Log::info("Audit: {$action}", [
            'user_id' => $user?->id,
            'description' => $description,
            'model' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'metadata' => $metadata
        ]);

        return $auditLog;
    }

    /**
     * Log user authentication events
     */
    public function logAuthentication(string $action, User $user, array $metadata = []): void
    {
        $this->log(
            "auth.{$action}",
            "User {$action}",
            $user,
            array_merge($metadata, [
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray()
            ])
        );
    }

    /**
     * Log role and permission changes
     */
    public function logRoleChange(
        string $action,
        User $targetUser,
        string $roleName,
        ?User $performedBy = null,
        array $metadata = []
    ): void {
        $this->log(
            "role.{$action}",
            "Role '{$roleName}' {$action} for user {$targetUser->email}",
            $targetUser,
            array_merge($metadata, [
                'role_name' => $roleName,
                'target_user_id' => $targetUser->id,
                'performed_by' => $performedBy?->id ?? Auth::id()
            ]),
            $performedBy
        );
    }

    /**
     * Log financial transactions
     */
    public function logFinancialTransaction(
        string $action,
        Model $transaction,
        array $metadata = []
    ): void {
        $this->log(
            "financial.{$action}",
            "Financial transaction {$action}",
            $transaction,
            array_merge($metadata, [
                'transaction_type' => $transaction->type ?? 'unknown',
                'amount' => $transaction->amount ?? 0,
                'currency' => $transaction->currency ?? 'EUR'
            ])
        );
    }

    /**
     * Log charging point operations
     */
    public function logChargingPointOperation(
        string $action,
        Model $chargingPoint,
        array $metadata = []
    ): void {
        $this->log(
            "charging_point.{$action}",
            "Charging point {$action}",
            $chargingPoint,
            array_merge($metadata, [
                'charging_point_name' => $chargingPoint->name ?? 'Unknown',
                'status' => $chargingPoint->status ?? 'unknown',
                'location' => [
                    'latitude' => $chargingPoint->latitude ?? null,
                    'longitude' => $chargingPoint->longitude ?? null
                ]
            ])
        );
    }

    /**
     * Log system configuration changes
     */
    public function logSystemChange(
        string $action,
        string $component,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = []
    ): void {
        $this->log(
            "system.{$action}",
            "System {$component} {$action}",
            null,
            array_merge($metadata, [
                'component' => $component,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'changes' => $this->calculateChanges($oldValues, $newValues)
            ])
        );
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(
        string $event,
        string $description,
        array $metadata = [],
        ?User $user = null
    ): void {
        $this->log(
            "security.{$event}",
            $description,
            $user,
            array_merge($metadata, [
                'severity' => $this->getSecuritySeverity($event),
                'event_type' => $event
            ]),
            $user
        );

        // Log security events with higher priority
        Log::warning("Security Event: {$event}", [
            'description' => $description,
            'user_id' => $user?->id,
            'metadata' => $metadata
        ]);
    }

    /**
     * Get audit logs with filtering
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditLog::with('user');

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if (isset($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('action', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $stats = [
            'total_events' => AuditLog::where('created_at', '>=', $startDate)->count(),
            'events_by_action' => AuditLog::where('created_at', '>=', $startDate)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'action')
                ->toArray(),
            'events_by_user' => AuditLog::where('created_at', '>=', $startDate)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->pluck('count', 'user_id')
                ->toArray(),
            'events_by_day' => AuditLog::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray()
        ];

        return $stats;
    }

    /**
     * Clean up old audit logs
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deleted = AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        Log::info("Audit logs cleanup completed", [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateString()
        ]);
        
        return $deleted;
    }

    /**
     * Export audit logs
     */
    public function exportAuditLogs(array $filters = [], string $format = 'csv'): string
    {
        $logs = $this->getAuditLogs($filters);
        
        if ($format === 'csv') {
            return $this->exportToCsv($logs->items());
        }
        
        if ($format === 'json') {
            return json_encode($logs->items(), JSON_PRETTY_PRINT);
        }
        
        throw new \InvalidArgumentException("Unsupported export format: {$format}");
    }

    /**
     * Calculate changes between old and new values
     */
    private function calculateChanges(array $oldValues, array $newValues): array
    {
        $changes = [];
        
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Get security event severity
     */
    private function getSecuritySeverity(string $event): string
    {
        $highSeverityEvents = [
            'login_failed_multiple',
            'unauthorized_access',
            'suspicious_activity',
            'data_breach',
            'privilege_escalation'
        ];
        
        $mediumSeverityEvents = [
            'password_changed',
            'role_changed',
            'permission_changed',
            'account_locked'
        ];
        
        if (in_array($event, $highSeverityEvents)) {
            return 'high';
        }
        
        if (in_array($event, $mediumSeverityEvents)) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv(array $logs): string
    {
        $csv = "ID,User,Action,Description,Model Type,Model ID,IP Address,User Agent,Timestamp\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->user?->email ?? 'N/A',
                $log->action,
                '"' . str_replace('"', '""', $log->description) . '"',
                $log->auditable_type ?? 'N/A',
                $log->auditable_id ?? 'N/A',
                $log->metadata['ip_address'] ?? 'N/A',
                '"' . str_replace('"', '""', $log->metadata['user_agent'] ?? 'N/A') . '"',
                $log->created_at->toISOString()
            );
        }
        
        return $csv;
    }
}
