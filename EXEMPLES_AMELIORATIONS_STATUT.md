# 🛠️ Exemples de Code pour Améliorations du Système de Statut

## 1️⃣ Méthode Unifiée dans ChargingPoint Model

Ajoutez cette méthode au modèle `ChargingPoint` pour centraliser la logique de mise à jour :

```php
/**
 * Refresh status from Steve API based on connectors
 * 
 * @return array ['success' => bool, 'old_status' => string, 'new_status' => string]
 */
public function refreshStatusFromConnectors(): array
{
    if (!$this->steve_charging_point_id) {
        return [
            'success' => false,
            'error' => 'No Steve ID configured',
            'old_status' => $this->status,
            'new_status' => $this->status
        ];
    }

    $steveService = app(\App\Services\SteveService::class);
    $oldStatus = $this->status;

    // Try connector status first
    $res = $steveService->getConnectorStatus($this->steve_charging_point_id);
    
    if ($res['ok'] && is_array($res['body'])) {
        $newStatus = $this->deriveStatusFromConnectorBody($res['body']);
    } else {
        // Fallback to charging point details
        $detail = $steveService->getChargingPoint($this->steve_charging_point_id);
        
        if ($detail['ok'] && is_array($detail['body'])) {
            $newStatus = $this->deriveStatusFromChargingPointBody($detail['body']);
        } else {
            return [
                'success' => false,
                'error' => 'Failed to fetch status from Steve',
                'old_status' => $oldStatus,
                'new_status' => $oldStatus
            ];
        }
    }

    // Update only if changed
    if ($oldStatus !== $newStatus) {
        $this->updateStatus($newStatus, 'Auto-refresh from connectors');
        
        // Dispatch event for real-time updates
        event(new \App\Events\ChargingPointStatusChanged($this, $oldStatus, $newStatus));
    } else {
        // Update timestamp only
        $this->status_updated_at = now();
        $this->saveQuietly();
    }

    return [
        'success' => true,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'changed' => $oldStatus !== $newStatus
    ];
}

/**
 * Derive status from connector body
 */
protected function deriveStatusFromConnectorBody(array $body): string
{
    // Check connectors array
    if (isset($body['connectors']) && is_array($body['connectors'])) {
        $statuses = [];
        foreach ($body['connectors'] as $connector) {
            $status = strtolower($connector['status'] ?? '');
            $statuses[] = $status;
            
            // Priority status check
            if (in_array($status, ['charging', 'preparing', 'finishing'])) {
                return 'online'; // Actively in use
            }
        }
        
        // Check if any available
        if (in_array('available', $statuses)) {
            return 'online';
        }
        
        // All unavailable or faulted
        if (in_array('faulted', $statuses)) {
            return 'error';
        }
    }
    
    // Check global status
    if (isset($body['status'])) {
        $s = strtolower($body['status']);
        if (in_array($s, ['online', 'connected', 'active', 'available'])) {
            return 'online';
        }
        if (in_array($s, ['faulted', 'error'])) {
            return 'error';
        }
        if ($s === 'maintenance') {
            return 'maintenance';
        }
    }
    
    return 'offline';
}

/**
 * Derive status from charging point body
 */
protected function deriveStatusFromChargingPointBody(array $body): string
{
    // Check status field
    $s = strtolower($body['status'] ?? ($body['availability'] ?? ''));
    
    $mapping = [
        'online' => 'online',
        'connected' => 'online',
        'active' => 'online',
        'available' => 'online',
        'offline' => 'offline',
        'disconnected' => 'offline',
        'unavailable' => 'offline',
        'maintenance' => 'maintenance',
        'faulted' => 'error',
        'error' => 'error',
    ];
    
    if (isset($mapping[$s])) {
        return $mapping[$s];
    }
    
    // Check lastSeen
    if (!empty($body['lastSeen']) || !empty($body['last_seen'])) {
        try {
            $lastSeen = \Carbon\Carbon::parse($body['lastSeen'] ?? $body['last_seen']);
            return $lastSeen->gt(now()->subMinutes(5)) ? 'online' : 'offline';
        } catch (\Exception $e) {
            \Log::debug("Failed to parse lastSeen: {$e->getMessage()}");
        }
    }
    
    // Check connected flag
    if (isset($body['connected'])) {
        return $body['connected'] === true ? 'online' : 'offline';
    }
    
    return 'offline';
}
```

---

## 2️⃣ Event pour WebSocket Temps Réel

### Créer l'Event

```bash
php artisan make:event ChargingPointStatusChanged
```

**Fichier:** `app/Events/ChargingPointStatusChanged.php`

```php
<?php

namespace App\Events;

use App\Models\ChargingPoint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChargingPointStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chargingPoint;
    public $oldStatus;
    public $newStatus;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(ChargingPoint $chargingPoint, string $oldStatus, string $newStatus)
    {
        $this->chargingPoint = $chargingPoint;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('charging-points'),
            new Channel("charging-point.{$this->chargingPoint->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->chargingPoint->id,
            'name' => $this->chargingPoint->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_color' => $this->chargingPoint->status_color,
            'timestamp' => $this->timestamp,
        ];
    }
}
```

### Frontend (JavaScript/Vue.js)

```javascript
// resources/js/charging-points-status.js

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Listen to all charging points status changes
window.Echo.channel('charging-points')
    .listen('.status.changed', (e) => {
        console.log('Status changed:', e);
        
        // Update UI
        updateChargingPointStatus(e.id, e.new_status, e.status_color);
        
        // Show notification
        showNotification(`${e.name} est maintenant ${e.new_status}`, e.status_color);
    });

// Listen to specific charging point
function subscribeToChargingPoint(chargingPointId) {
    window.Echo.channel(`charging-point.${chargingPointId}`)
        .listen('.status.changed', (e) => {
            console.log(`Charging point ${chargingPointId} status:`, e);
            updateChargingPointStatus(e.id, e.new_status, e.status_color);
        });
}

function updateChargingPointStatus(id, status, color) {
    const element = document.querySelector(`[data-charging-point-id="${id}"]`);
    if (element) {
        const badge = element.querySelector('.status-badge');
        if (badge) {
            badge.className = `status-badge badge-${color}`;
            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }
    }
}

function showNotification(message, type) {
    // Use your notification system (Toastr, SweetAlert, etc.)
    if (typeof toastr !== 'undefined') {
        toastr.info(message);
    }
}
```

---

## 3️⃣ Table d'Historique des Statuts

### Migration

```bash
php artisan make:migration create_charging_point_status_history_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charging_point_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charging_point_id')
                ->constrained('charging_points')
                ->onDelete('cascade');
            $table->string('old_status', 50);
            $table->string('new_status', 50);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable(); // Store API response, user info, etc.
            $table->timestamp('changed_at');
            $table->foreignId('changed_by')->nullable(); // User who triggered change
            
            // Indexes pour performance
            $table->index(['charging_point_id', 'changed_at']);
            $table->index('new_status');
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charging_point_status_history');
    }
};
```

### Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargingPointStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'charging_point_status_history';

    protected $fillable = [
        'charging_point_id',
        'old_status',
        'new_status',
        'reason',
        'metadata',
        'changed_at',
        'changed_by',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function chargingPoint()
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get status duration (time between this change and next)
     */
    public function getDuration(): ?\Carbon\CarbonInterval
    {
        $next = self::where('charging_point_id', $this->charging_point_id)
            ->where('changed_at', '>', $this->changed_at)
            ->orderBy('changed_at')
            ->first();

        if ($next) {
            return $this->changed_at->diff($next->changed_at);
        }

        // If no next, duration until now
        return $this->changed_at->diff(now());
    }

    /**
     * Scope for recent changes
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('changed_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for specific charging point
     */
    public function scopeForChargingPoint($query, $chargingPointId)
    {
        return $query->where('charging_point_id', $chargingPointId);
    }
}
```

### Observer pour Enregistrer Automatiquement

```php
<?php

namespace App\Observers;

use App\Models\ChargingPoint;
use App\Models\ChargingPointStatusHistory;

class ChargingPointStatusObserver
{
    /**
     * Handle the ChargingPoint "updated" event.
     */
    public function updated(ChargingPoint $chargingPoint): void
    {
        // Check if status changed
        if ($chargingPoint->isDirty('status')) {
            $oldStatus = $chargingPoint->getOriginal('status');
            $newStatus = $chargingPoint->status;

            ChargingPointStatusHistory::create([
                'charging_point_id' => $chargingPoint->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $chargingPoint->status_change_reason ?? 'Auto-update',
                'metadata' => [
                    'steve_id' => $chargingPoint->steve_charging_point_id,
                    'updated_at' => $chargingPoint->status_updated_at,
                ],
                'changed_at' => now(),
                'changed_by' => auth()->id(),
            ]);

            // Clear temporary reason
            unset($chargingPoint->status_change_reason);
        }
    }
}
```

**Enregistrer l'Observer dans `AppServiceProvider`:**

```php
use App\Models\ChargingPoint;
use App\Observers\ChargingPointStatusObserver;

public function boot(): void
{
    ChargingPoint::observe(ChargingPointStatusObserver::class);
}
```

### API pour Historique

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Models\ChargingPointStatusHistory;
use Illuminate\Http\Request;

class ChargingPointStatusHistoryController extends Controller
{
    /**
     * Get status history for a charging point
     * 
     * GET /api/charging-points/{id}/status/history
     */
    public function index(Request $request, $id)
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        
        $hours = $request->input('hours', 24);
        $limit = $request->input('limit', 100);

        $history = ChargingPointStatusHistory::forChargingPoint($id)
            ->recent($hours)
            ->orderBy('changed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'old_status' => $record->old_status,
                    'new_status' => $record->new_status,
                    'reason' => $record->reason,
                    'changed_at' => $record->changed_at->toIso8601String(),
                    'duration' => $record->getDuration()->forHumans(),
                    'changed_by' => $record->changedBy ? [
                        'id' => $record->changedBy->id,
                        'name' => $record->changedBy->name,
                    ] : null,
                ];
            });

        return response()->json([
            'charging_point' => [
                'id' => $chargingPoint->id,
                'name' => $chargingPoint->name,
                'current_status' => $chargingPoint->status,
            ],
            'history' => $history,
            'period' => [
                'hours' => $hours,
                'from' => now()->subHours($hours)->toIso8601String(),
                'to' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get status statistics for a charging point
     * 
     * GET /api/charging-points/{id}/status/stats
     */
    public function stats(Request $request, $id)
    {
        $hours = $request->input('hours', 24);
        
        $history = ChargingPointStatusHistory::forChargingPoint($id)
            ->recent($hours)
            ->orderBy('changed_at')
            ->get();

        $stats = [];
        foreach ($history as $record) {
            $duration = $record->getDuration();
            $status = $record->new_status;
            
            if (!isset($stats[$status])) {
                $stats[$status] = [
                    'count' => 0,
                    'total_seconds' => 0,
                ];
            }
            
            $stats[$status]['count']++;
            $stats[$status]['total_seconds'] += $duration->totalSeconds;
        }

        // Calculate percentages
        $totalSeconds = array_sum(array_column($stats, 'total_seconds'));
        foreach ($stats as $status => &$data) {
            $data['percentage'] = $totalSeconds > 0 
                ? round(($data['total_seconds'] / $totalSeconds) * 100, 2) 
                : 0;
            $data['formatted_duration'] = \Carbon\CarbonInterval::seconds($data['total_seconds'])->cascade()->forHumans();
        }

        return response()->json([
            'charging_point_id' => $id,
            'period' => [
                'hours' => $hours,
                'from' => now()->subHours($hours)->toIso8601String(),
                'to' => now()->toIso8601String(),
            ],
            'stats' => $stats,
            'total_changes' => $history->count(),
        ]);
    }
}
```

---

## 4️⃣ Health Check Endpoint

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Services\SteveService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Check Steve API health
     * 
     * GET /api/health/steve
     */
    public function steveHealth(SteveService $steveService)
    {
        $startTime = microtime(true);
        $checks = [];

        // 1. Check database connectivity
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // 2. Check if we have test data
        $testPoint = ChargingPoint::whereNotNull('steve_charging_point_id')
            ->first();

        if (!$testPoint) {
            $checks['steve_api'] = [
                'status' => 'unknown',
                'message' => 'No charging points with Steve ID found for testing'
            ];
        } else {
            // 3. Test Steve API connection
            try {
                $apiStartTime = microtime(true);
                $result = $steveService->testConnection($testPoint);
                $apiDuration = round((microtime(true) - $apiStartTime) * 1000, 2);

                $checks['steve_api'] = [
                    'status' => $result['success'] ? 'healthy' : 'unhealthy',
                    'message' => $result['message'] ?? 'Unknown',
                    'response_time_ms' => $apiDuration,
                    'test_charging_point_id' => $testPoint->id,
                ];
            } catch (\Exception $e) {
                $checks['steve_api'] = [
                    'status' => 'unhealthy',
                    'message' => 'Steve API test failed: ' . $e->getMessage(),
                    'test_charging_point_id' => $testPoint->id,
                ];
            }
        }

        // 4. Check cache
        try {
            $cacheTestKey = 'health_check_' . now()->timestamp;
            Cache::put($cacheTestKey, 'test', 10);
            $cacheValue = Cache::get($cacheTestKey);
            Cache::forget($cacheTestKey);

            $checks['cache'] = [
                'status' => $cacheValue === 'test' ? 'healthy' : 'unhealthy',
                'message' => $cacheValue === 'test' ? 'Cache working' : 'Cache not working'
            ];
        } catch (\Exception $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache test failed: ' . $e->getMessage()
            ];
        }

        // 5. Overall status
        $overallStatus = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            } elseif ($check['status'] === 'unknown' && $overallStatus !== 'unhealthy') {
                $overallStatus = 'degraded';
            }
        }

        $totalDuration = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'total_check_duration_ms' => $totalDuration,
            'checks' => $checks,
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Get charging points statistics
     * 
     * GET /api/health/charging-points
     */
    public function chargingPointsHealth()
    {
        $total = ChargingPoint::count();
        $withSteve = ChargingPoint::whereNotNull('steve_charging_point_id')->count();
        
        $statusCounts = ChargingPoint::whereNotNull('steve_charging_point_id')
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $staleStatuses = ChargingPoint::whereNotNull('steve_charging_point_id')
            ->where('status_updated_at', '<', now()->subMinutes(10))
            ->count();

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'charging_points' => [
                'total' => $total,
                'with_steve_id' => $withSteve,
                'without_steve_id' => $total - $withSteve,
            ],
            'status_distribution' => $statusCounts,
            'stale_statuses' => [
                'count' => $staleStatuses,
                'threshold_minutes' => 10,
                'message' => $staleStatuses > 0 
                    ? "Warning: $staleStatuses charging points have stale status (>10 min)"
                    : 'All statuses are fresh'
            ]
        ]);
    }
}
```

**Routes:**

```php
// routes/api.php

Route::prefix('health')->group(function () {
    Route::get('/steve', [HealthController::class, 'steveHealth']);
    Route::get('/charging-points', [HealthController::class, 'chargingPointsHealth']);
});
```

---

## 5️⃣ Scheduler Configuration Complète

**Fichier:** `app/Console/Kernel.php`

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Monitor charging points status every 5 minutes
        $schedule->command('steve:monitor --batch=20')
            ->everyFiveMinutes()
            ->withoutOverlapping(10) // Max 10 minutes runtime
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/steve-monitor.log'))
            ->emailOutputOnFailure('admin@example.com');

        // Daily summary of status changes
        $schedule->command('charging-points:daily-summary')
            ->dailyAt('08:00')
            ->timezone('Europe/Paris');

        // Clean old status history (keep last 90 days)
        $schedule->command('charging-points:clean-history --days=90')
            ->weeklyOn(1, '03:00'); // Every Monday at 3 AM

        // Health check every hour (log only)
        $schedule->call(function () {
            $health = app(\App\Http\Controllers\Api\HealthController::class)
                ->steveHealth(app(\App\Services\SteveService::class));
            
            $data = json_decode($health->getContent(), true);
            
            if ($data['status'] !== 'healthy') {
                \Log::warning('Health check failed', $data);
            }
        })->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```

---

## 6️⃣ Dashboard Vue.js Component

```vue
<!-- resources/js/components/ChargingPointsStatusDashboard.vue -->

<template>
  <div class="charging-points-dashboard">
    <div class="stats-header">
      <div class="stat-card" v-for="(count, status) in statusCounts" :key="status">
        <div :class="['stat-icon', `bg-${statusColor(status)}`]">
          <i :class="statusIcon(status)"></i>
        </div>
        <div class="stat-info">
          <h3>{{ count }}</h3>
          <p>{{ statusLabel(status) }}</p>
        </div>
      </div>
    </div>

    <div class="charging-points-list">
      <div 
        v-for="point in chargingPoints" 
        :key="point.id"
        :data-charging-point-id="point.id"
        class="charging-point-card"
      >
        <div class="card-header">
          <h4>{{ point.name }}</h4>
          <span :class="['status-badge', `badge-${point.status_color}`]">
            {{ point.status }}
          </span>
        </div>
        
        <div class="card-body">
          <p class="location">
            <i class="fas fa-map-marker-alt"></i>
            {{ point.address }}, {{ point.city }}
          </p>
          
          <div class="connectors">
            <div 
              v-for="connector in point.connectors" 
              :key="connector.id"
              class="connector-info"
            >
              <span class="connector-id">#{{ connector.connector_id }}</span>
              <span class="connector-type">{{ connector.type }}</span>
              <span :class="['connector-status', connector.status]">
                {{ connector.status }}
              </span>
            </div>
          </div>

          <p class="last-update">
            <i class="fas fa-clock"></i>
            Mis à jour {{ formatRelativeTime(point.status_updated_at) }}
          </p>
        </div>

        <div class="card-actions">
          <button @click="refreshStatus(point.id)" class="btn btn-sm btn-primary">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': point.refreshing }"></i>
            Actualiser
          </button>
          <button @click="showHistory(point.id)" class="btn btn-sm btn-secondary">
            <i class="fas fa-history"></i>
            Historique
          </button>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click.self="showHistoryModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Historique des Statuts</h3>
          <button @click="showHistoryModal = false" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="timeline">
            <div 
              v-for="record in history" 
              :key="record.id"
              class="timeline-item"
            >
              <div class="timeline-marker"></div>
              <div class="timeline-content">
                <div class="status-change">
                  <span :class="['badge', `badge-${statusColorMap[record.old_status]}`]">
                    {{ record.old_status }}
                  </span>
                  <i class="fas fa-arrow-right"></i>
                  <span :class="['badge', `badge-${statusColorMap[record.new_status]}`]">
                    {{ record.new_status }}
                  </span>
                </div>
                <p class="timestamp">{{ formatDateTime(record.changed_at) }}</p>
                <p class="duration">Durée: {{ record.duration }}</p>
                <p v-if="record.reason" class="reason">{{ record.reason }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { format, formatDistanceToNow, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';

export default {
  name: 'ChargingPointsStatusDashboard',

  data() {
    return {
      chargingPoints: [],
      statusCounts: {},
      showHistoryModal: false,
      history: [],
      currentChargingPointId: null,
      statusColorMap: {
        'online': 'green',
        'offline': 'red',
        'maintenance': 'yellow',
        'error': 'red',
        'charging': 'blue',
        'reserved': 'orange',
      },
    };
  },

  mounted() {
    this.fetchChargingPoints();
    this.setupRealtimeUpdates();
  },

  methods: {
    async fetchChargingPoints() {
      try {
        const response = await axios.get('/api/charging-points?with=connectors');
        this.chargingPoints = response.data.data.map(point => ({
          ...point,
          refreshing: false
        }));
        this.calculateStatusCounts();
      } catch (error) {
        console.error('Error fetching charging points:', error);
      }
    },

    calculateStatusCounts() {
      this.statusCounts = {};
      this.chargingPoints.forEach(point => {
        if (!this.statusCounts[point.status]) {
          this.statusCounts[point.status] = 0;
        }
        this.statusCounts[point.status]++;
      });
    },

    async refreshStatus(chargingPointId) {
      const point = this.chargingPoints.find(p => p.id === chargingPointId);
      if (!point) return;

      point.refreshing = true;

      try {
        const response = await axios.get(`/api/charging-points/${chargingPointId}/status?force=1`);
        
        if (response.data.ok) {
          point.status = response.data.status;
          point.status_updated_at = response.data.updated_at;
          point.status_color = this.statusColorMap[point.status];
          this.calculateStatusCounts();
        }
      } catch (error) {
        console.error('Error refreshing status:', error);
      } finally {
        point.refreshing = false;
      }
    },

    async showHistory(chargingPointId) {
      this.currentChargingPointId = chargingPointId;
      this.showHistoryModal = true;

      try {
        const response = await axios.get(`/api/charging-points/${chargingPointId}/status/history?hours=72`);
        this.history = response.data.history;
      } catch (error) {
        console.error('Error fetching history:', error);
      }
    },

    setupRealtimeUpdates() {
      if (window.Echo) {
        window.Echo.channel('charging-points')
          .listen('.status.changed', (e) => {
            console.log('Real-time status update:', e);
            
            const point = this.chargingPoints.find(p => p.id === e.id);
            if (point) {
              point.status = e.new_status;
              point.status_color = e.status_color;
              point.status_updated_at = e.timestamp;
              this.calculateStatusCounts();

              // Show toast notification
              this.$toast.info(`${e.name} est maintenant ${e.new_status}`);
            }
          });
      }
    },

    statusLabel(status) {
      const labels = {
        'online': 'En ligne',
        'offline': 'Hors ligne',
        'maintenance': 'Maintenance',
        'error': 'Erreur',
        'charging': 'En charge',
        'reserved': 'Réservé',
      };
      return labels[status] || status;
    },

    statusIcon(status) {
      const icons = {
        'online': 'fas fa-check-circle',
        'offline': 'fas fa-times-circle',
        'maintenance': 'fas fa-wrench',
        'error': 'fas fa-exclamation-triangle',
        'charging': 'fas fa-bolt',
        'reserved': 'fas fa-clock',
      };
      return icons[status] || 'fas fa-question-circle';
    },

    statusColor(status) {
      return this.statusColorMap[status] || 'gray';
    },

    formatRelativeTime(timestamp) {
      if (!timestamp) return 'Jamais';
      return formatDistanceToNow(parseISO(timestamp), { 
        addSuffix: true,
        locale: fr 
      });
    },

    formatDateTime(timestamp) {
      if (!timestamp) return '';
      return format(parseISO(timestamp), 'PPpp', { locale: fr });
    },
  },
};
</script>

<style scoped>
.charging-points-dashboard {
  padding: 20px;
}

.stats-header {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin-right: 15px;
  font-size: 24px;
  color: white;
}

.bg-green { background-color: #10b981; }
.bg-red { background-color: #ef4444; }
.bg-yellow { background-color: #f59e0b; }
.bg-blue { background-color: #3b82f6; }
.bg-orange { background-color: #f97316; }

.stat-info h3 {
  font-size: 32px;
  margin: 0;
  color: #1f2937;
}

.stat-info p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.charging-points-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.charging-point-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: transform 0.2s;
}

.charging-point-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h4 {
  margin: 0;
  font-size: 18px;
  color: #1f2937;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-green { background-color: #d1fae5; color: #065f46; }
.badge-red { background-color: #fee2e2; color: #991b1b; }
.badge-yellow { background-color: #fef3c7; color: #92400e; }
.badge-blue { background-color: #dbeafe; color: #1e40af; }
.badge-orange { background-color: #fed7aa; color: #9a3412; }

.card-body {
  padding: 15px;
}

.location {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 15px;
}

.connectors {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 15px;
}

.connector-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  background-color: #f9fafb;
  border-radius: 6px;
  font-size: 13px;
}

.connector-id {
  font-weight: 600;
  color: #374151;
}

.connector-type {
  color: #6b7280;
}

.connector-status {
  margin-left: auto;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.last-update {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #9ca3af;
  font-size: 12px;
  margin: 0;
}

.card-actions {
  display: flex;
  gap: 10px;
  padding: 15px;
  border-top: 1px solid #e5e7eb;
}

.btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background-color: #4b5563;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 10px;
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
}

.modal-body {
  padding: 20px;
}

.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline-item {
  position: relative;
  padding-bottom: 30px;
}

.timeline-marker {
  position: absolute;
  left: -33px;
  top: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: #3b82f6;
  border: 2px solid white;
  box-shadow: 0 0 0 2px #3b82f6;
}

.timeline-item:before {
  content: '';
  position: absolute;
  left: -27px;
  top: 12px;
  bottom: -12px;
  width: 2px;
  background-color: #e5e7eb;
}

.timeline-item:last-child:before {
  display: none;
}

.timeline-content {
  background-color: #f9fafb;
  padding: 15px;
  border-radius: 8px;
}

.status-change {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.timestamp {
  font-size: 14px;
  color: #6b7280;
  margin: 5px 0;
}

.duration {
  font-size: 13px;
  color: #9ca3af;
  margin: 5px 0;
}

.reason {
  font-size: 12px;
  color: #4b5563;
  font-style: italic;
  margin: 5px 0;
}

.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
```

---

## 📝 Notes d'Utilisation

1. **Event Broadcasting:** Nécessite Laravel Echo + Pusher (ou autre driver compatible)
2. **Frontend:** Les composants Vue.js nécessitent Vue 3 et date-fns
3. **Scheduler:** Nécessite un cron job système : `* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`
4. **Performance:** Ajoutez des index de base de données pour les requêtes fréquentes

---

**Prêt à implémenter ! 🚀**

