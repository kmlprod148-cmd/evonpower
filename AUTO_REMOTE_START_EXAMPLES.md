# 📘 Exemples Pratiques - Auto Remote Start

## 🎯 Cas d'Usage Courants

### 1. Démarrage Automatique Standard

**Scénario**: Un utilisateur réserve une borne pour 14h00, paie sa réservation. Le système démarre automatiquement à 14h00.

```php
// 1. Création de la réservation (via API ou interface)
$reservation = Reservation::create([
    'user_id' => $user->id,
    'charging_point_id' => $chargingPoint->id,
    'connector_id' => $connector->id,
    'start_time' => '2025-01-15 14:00:00',
    'end_time' => '2025-01-15 16:00:00',
    'payment_status' => 'PENDING',
    'status' => 'pending_confirmation',
    'max_kwh' => 50,
    'estimated_cost' => 15.00,
]);

// 2. Paiement validé
$reservation->update([
    'payment_status' => 'PAID',
    'status' => 'confirmed',
    'approved_at' => now(),
]);

// 3. Déclencher l'événement (automatique dans le controller)
event(new ReservationApproved($reservation, $user->id, 'payment'));

// 4. Le système prend le relais:
// - À 13:55 (grace period), le scheduler détecte la réservation
// - À 14:00, le job AutoStartTransactionJob est dispatchéà - La transaction démarre via SteVe
// - L'utilisateur reçoit une notification
```

---

### 2. Démarrage Immédiat (Mode Immediate)

**Scénario**: L'utilisateur veut charger immédiatement après le paiement.

```php
// Dans .env
AUTO_REMOTE_START_MODE=immediate

// Ou dans config/auto-remote-start.php
'mode' => [
    'start_mode' => 'immediate',
],

// Quand la réservation est approuvée, elle démarre dans les 5 secondes
```

---

### 3. Forcer le Démarrage Manuel

**Scénario**: Une réservation n'a pas démarré automatiquement, l'admin veut la forcer.

```bash
# Via CLI
php artisan ocpp:auto-start-transactions --force-reservation=123

# Via API
curl -X POST https://your-api.com/api/auto-remote-start/reservation/123 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"force": true}'
```

```php
// Via code PHP
$service = app(\App\Services\AutoRemoteStartService::class);
$result = $service->forceStartReservation($reservationId);

if ($result['success']) {
    echo "Transaction démarrée avec succès!";
} else {
    echo "Erreur: " . $result['message'];
}
```

---

### 4. Vérifier l'Éligibilité Avant Création

**Scénario**: Vérifier si une réservation pourra être démarrée automatiquement.

```php
// Dans le controller de création de réservation
public function store(Request $request)
{
    $validated = $request->validate([
        'charging_point_id' => 'required|exists:charging_points,id',
        'start_time' => 'required|date|after:now',
        // ...
    ]);
    
    // Créer la réservation temporairement
    $reservation = new Reservation($validated);
    
    // Vérifier l'éligibilité
    $autoStartService = app(\App\Services\AutoRemoteStartService::class);
    $eligibility = $autoStartService->checkReservationEligibility($reservation);
    
    if (!$eligibility['eligible']) {
        return response()->json([
            'message' => 'Cette réservation ne pourra pas être démarrée automatiquement',
            'reason' => $eligibility['reason'],
            'code' => $eligibility['code'],
        ], 422);
    }
    
    // Sauvegarder
    $reservation->save();
    
    return response()->json([
        'message' => 'Réservation créée. Démarrage automatique programmé.',
        'reservation' => $reservation,
        'auto_start_info' => $eligibility,
    ]);
}
```

---

### 5. Monitoring en Temps Réel

**Scénario**: Dashboard admin pour monitorer les démarrages automatiques.

```javascript
// Frontend: appel API toutes les 30 secondes
async function refreshDashboard() {
    const response = await fetch('/api/auto-remote-start/dashboard', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    
    const data = await response.json();
    
    // Afficher les stats
    document.getElementById('success-24h').textContent = data.dashboard.stats_24h.success;
    document.getElementById('failed-24h').textContent = data.dashboard.stats_24h.failed;
    document.getElementById('success-rate').textContent = data.dashboard.stats_24h.success_rate + '%';
    
    // Afficher les réservations éligibles
    document.getElementById('eligible-count').textContent = data.dashboard.eligible_reservations_count;
    
    // Afficher le health status
    const healthStatus = document.getElementById('health-status');
    healthStatus.className = data.dashboard.health.healthy ? 'badge-success' : 'badge-danger';
    healthStatus.textContent = data.dashboard.health.status;
}

// Rafraîchir toutes les 30 secondes
setInterval(refreshDashboard, 30000);
```

---

### 6. Traiter les Échecs

**Scénario**: Gérer les réservations qui n'ont pas pu démarrer automatiquement.

```php
// Récupérer les logs d'échecs nécessitant une action
$failedLogs = AutoRemoteStartLog::requiresManualAction()->get();

foreach ($failedLogs as $log) {
    echo "Réservation #{$log->reservation_id} - Erreur: {$log->error_message}\n";
    
    $reservation = $log->reservation;
    
    // Option 1: Réessayer manuellement
    $service = app(\App\Services\AutoRemoteStartService::class);
    $result = $service->forceStartReservation($reservation->id);
    
    // Option 2: Notifier l'utilisateur
    $reservation->user->notify(new ReservationFailedNotification($log));
    
    // Option 3: Rembourser (si prepaid)
    if ($reservation->isPrepaid() && $reservation->prepaid_amount > 0) {
        $reservation->user->wallet->credit($reservation->prepaid_amount);
        $reservation->update(['refund_amount' => $reservation->prepaid_amount]);
    }
    
    // Marquer comme traité
    $log->update(['requires_manual_action' => false]);
}
```

---

### 7. Statistiques et Rapports

**Scénario**: Générer un rapport mensuel des démarrages automatiques.

```php
use App\Models\AutoRemoteStartLog;
use Carbon\Carbon;

class AutoStartReportGenerator
{
    public function generateMonthlyReport($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        // Statistiques globales
        $stats = AutoRemoteStartLog::getGlobalStats([
            'date_from' => $startDate,
            'date_to' => $endDate,
        ]);
        
        // Par jour
        $dailyStats = AutoRemoteStartLog::whereBetween('processed_at', [$startDate, $endDate])
            ->selectRaw('DATE(processed_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Par point de charge
        $byChargingPoint = AutoRemoteStartLog::whereBetween('processed_at', [$startDate, $endDate])
            ->with('chargingPoint')
            ->selectRaw('charging_point_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(processing_duration_ms) as avg_duration')
            ->groupBy('charging_point_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        
        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'global_stats' => $stats,
            'daily_stats' => $dailyStats,
            'top_charging_points' => $byChargingPoint,
        ];
    }
}

// Utilisation
$generator = new AutoStartReportGenerator();
$report = $generator->generateMonthlyReport(1, 2025);

// Exporter en CSV
$csv = fopen('auto_start_report_2025_01.csv', 'w');
fputcsv($csv, ['Date', 'Total', 'Succès', 'Échecs', 'Taux Succès']);

foreach ($report['daily_stats'] as $day) {
    fputcsv($csv, [
        $day->date,
        $day->total,
        $day->success,
        $day->failed,
        round(($day->success / $day->total) * 100, 2) . '%',
    ]);
}

fclose($csv);
```

---

### 8. Webhook / Callback

**Scénario**: Notifier un système externe quand une transaction démarre automatiquement.

```php
// Dans AutoStartTransactionJob après succès

if ($result['success']) {
    // Déclencher un webhook
    Http::post(config('webhooks.auto_start_success'), [
        'event' => 'auto_start.success',
        'reservation_id' => $reservation->id,
        'user_id' => $reservation->user_id,
        'charging_point_id' => $reservation->charging_point_id,
        'transaction_id' => $result['transaction_data']['transaction']['id'] ?? null,
        'timestamp' => now()->toISOString(),
    ]);
    
    // Ou dispatcher un événement Laravel
    event(new AutoStartSuccessEvent($reservation, $result));
}
```

---

### 9. Test Unitaire

**Scénario**: Tester la logique de démarrage automatique.

```php
// tests/Feature/AutoRemoteStartTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Reservation;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Services\AutoRemoteStartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AutoRemoteStartTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_eligible_reservation_can_start()
    {
        // Arrange
        $user = User::factory()->create(['balance' => 100]);
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'start_time' => now()->addMinutes(3),
            'status' => 'confirmed',
            'payment_status' => 'PAID',
        ]);
        
        // Act
        $service = app(AutoRemoteStartService::class);
        $eligibility = $service->checkReservationEligibility($reservation);
        
        // Assert
        $this->assertTrue($eligibility['eligible']);
        $this->assertEquals('ELIGIBLE', $eligibility['code']);
    }
    
    public function test_insufficient_balance_prevents_start()
    {
        // Arrange
        $user = User::factory()->create(['balance' => 0]);
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'payment_mode' => 'postpaid',
        ]);
        
        // Act
        $service = app(AutoRemoteStartService::class);
        $eligibility = $service->checkReservationEligibility($reservation);
        
        // Assert
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals('INSUFFICIENT_BALANCE', $eligibility['code']);
    }
}
```

---

### 10. Intégration avec d'Autres Systèmes

**Scénario**: Synchroniser avec un CRM ou système de facturation.

```php
// Listener personnalisé
namespace App\Listeners;

use App\Events\AutoStartSuccessEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncAutoStartWithCRM implements ShouldQueue
{
    public function handle(AutoStartSuccessEvent $event)
    {
        $reservation = $event->reservation;
        $result = $event->result;
        
        // Envoyer à votre CRM
        Http::post('https://crm.example.com/api/charging-sessions', [
            'customer_id' => $reservation->user->crm_id,
            'session_id' => $result['transaction_id'],
            'charging_point' => $reservation->chargingPoint->name,
            'start_time' => now()->toISOString(),
            'estimated_cost' => $reservation->estimated_cost,
            'auto_started' => true,
        ]);
        
        // Ou mettre à jour votre système de facturation
        $this->billingSystem->createPendingInvoice([
            'user_id' => $reservation->user_id,
            'reservation_id' => $reservation->id,
            'description' => 'Session de charge automatique',
            'amount' => $reservation->estimated_cost,
        ]);
    }
}
```

---

## 🔧 Commandes Artisan Utiles

```bash
# Afficher toutes les réservations éligibles
php artisan tinker
>>> app(\App\Services\AutoRemoteStartService::class)->getEligibleReservations();

# Nettoyer les anciens logs (>30 jours)
php artisan tinker
>>> \App\Models\AutoRemoteStartLog::cleanup(30);

# Voir les jobs en queue
php artisan queue:work --once --verbose

# Simuler l'exécution du scheduler
php artisan schedule:test

# Vider le cache des échecs
php artisan tinker
>>> Cache::flush();
```

---

## 📊 Requêtes SQL Utiles

```sql
-- Réservations éligibles actuelles
SELECT r.id, r.start_time, r.status, u.name as user, cp.name as charging_point
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN charging_points cp ON r.charging_point_id = cp.id
WHERE r.status IN ('confirmed', 'pending_confirmation')
  AND r.payment_status = 'PAID'
  AND r.start_time BETWEEN (NOW() - INTERVAL 5 MINUTE) AND (NOW() + INTERVAL 15 MINUTE)
  AND NOT EXISTS (
      SELECT 1 FROM charging_sessions cs 
      WHERE cs.reservation_id = r.id 
      AND cs.status IN ('active', 'in_progress')
  );

-- Taux de succès par jour (derniers 7 jours)
SELECT 
    DATE(processed_at) as date,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
    ROUND(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM auto_remote_start_logs
WHERE processed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(processed_at)
ORDER BY date DESC;

-- Logs d'échecs avec détails
SELECT 
    l.id,
    l.reservation_id,
    l.error_message,
    l.attempt_number,
    l.created_at,
    u.email as user_email,
    cp.name as charging_point
FROM auto_remote_start_logs l
JOIN reservations r ON l.reservation_id = r.id
JOIN users u ON r.user_id = u.id
JOIN charging_points cp ON r.charging_point_id = cp.id
WHERE l.status = 'failed'
  AND l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY l.created_at DESC;

-- Performance moyenne par point de charge
SELECT 
    cp.name,
    COUNT(*) as attempts,
    AVG(l.processing_duration_ms) as avg_duration_ms,
    SUM(CASE WHEN l.status = 'success' THEN 1 ELSE 0 END) as success_count,
    ROUND(SUM(CASE WHEN l.status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM auto_remote_start_logs l
JOIN charging_points cp ON l.charging_point_id = cp.id
WHERE l.processed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY cp.id, cp.name
HAVING COUNT(*) >= 5
ORDER BY success_rate DESC, avg_duration_ms ASC;
```

---

## 🎓 Bonnes Pratiques

### 1. Toujours Vérifier l'Éligibilité

```php
// ❌ Mauvais
AutoStartTransactionJob::dispatch($reservationId);

// ✅ Bon
$eligibility = $autoStartService->checkReservationEligibility($reservation);
if ($eligibility['eligible']) {
    AutoStartTransactionJob::dispatch($reservationId);
} else {
    Log::warning('Reservation not eligible', $eligibility);
}
```

### 2. Gérer les Timeouts

```php
// Dans config/auto-remote-start.php
'ocpp_timeout' => 30, // 30 secondes max

// Dans le job
public $timeout = 120; // 2 minutes max pour le job complet
```

### 3. Logger Toujours

```php
Log::info('Auto Remote Start: Processing reservation', [
    'reservation_id' => $reservation->id,
    'user_id' => $reservation->user_id,
    'context' => 'additional_info'
]);
```

### 4. Utiliser les Transactions DB

```php
DB::transaction(function () use ($reservation, $result) {
    $session = ChargingSession::create([...]);
    $reservation->update(['status' => 'active']);
    AutoRemoteStartLog::logSuccess($reservation, $result);
});
```

---

**🎉 Vous avez maintenant tous les exemples pour utiliser le système Auto Remote Start !**

