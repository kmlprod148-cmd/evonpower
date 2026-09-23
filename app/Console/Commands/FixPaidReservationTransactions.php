<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Services\TransactionStatusSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPaidReservationTransactions extends Command
{
    protected $signature = 'reservations:fix-paid-transactions
                            {--dry-run : Afficher les corrections sans les appliquer}
                            {--full : Corriger aussi les réservations et synchroniser les soldes}
                            {--diagnose : Afficher les réservations et transactions pour diagnostic}';

    protected $description = 'Corrige le statut des transactions et réservations payées par solde (PAID, prepaid_credit)';

    public function handle(TransactionStatusSyncService $syncService): int
    {
        $dryRun = $this->option('dry-run');
        $full = $this->option('full');
        $diagnose = $this->option('diagnose');

        if ($diagnose) {
            return $this->runDiagnose();
        }

        if ($dryRun) {
            $this->warn('Mode simulation - aucune modification ne sera effectuée.');
            $count = $syncService->countPendingTransactionsForPaidReservations();
            $this->info("Transactions à corriger : {$count}");
            return 0;
        }

        if ($full) {
            $normCount = $syncService->normalizePaidReservations();
            if ($normCount > 0) {
                $this->info("✓ {$normCount} réservation(s) normalisée(s) (payment_status → PAID).");
            }
        }

        $txCount = $syncService->syncAllPendingTransactionsForPaidReservations();
        $this->info("✓ {$txCount} transaction(s) mise(s) à completed.");

        if ($full) {
            $reservationIds = $syncService->getPaidReservationIds();
            $resCount = 0;
            foreach ($reservationIds as $id) {
                $reservation = Reservation::find($id);
                if ($reservation && $reservation->ensureTransactionConfirmedIfPaid()) {
                    $resCount++;
                }
            }
            $this->info("✓ {$resCount} réservation(s) confirmée(s) et soldes synchronisés.");
        }

        return 0;
    }

    protected function runDiagnose(): int
    {
        $this->info('=== Diagnostic réservations et transactions ===');
        $this->newLine();

        $totalReservations = DB::table('reservations')->count();
        $this->info("Total réservations : {$totalReservations}");

        $withPaymentStatus = DB::table('reservations')
            ->select('payment_status', 'payment_method', 'payment_mode', DB::raw('count(*) as cnt'))
            ->groupBy('payment_status', 'payment_method', 'payment_mode')
            ->get();

        $this->table(
            ['payment_status', 'payment_method', 'payment_mode', 'count'],
            $withPaymentStatus->map(fn ($r) => [
                $r->payment_status ?? 'NULL',
                $r->payment_method ?? 'NULL',
                $r->payment_mode ?? 'NULL',
                $r->cnt,
            ])->toArray()
        );

        $paidIds = app(TransactionStatusSyncService::class)->getPaidReservationIds();
        $this->info("Réservations considérées 'payées' : {$paidIds->count()}");

        $pendingTx = Transaction::query()
            ->whereNotNull('reservation_id')
            ->whereRaw('LOWER(COALESCE(status, \'\')) IN (?, ?)', ['pending', 'confirmed'])
            ->count();
        $this->info("Transactions en pending/confirmed : {$pendingTx}");

        $paidIds = app(TransactionStatusSyncService::class)->getPaidReservationIds();
        if ($paidIds->isNotEmpty()) {
            $this->newLine();
            $this->info('Réservations payées (détail) :');
            $paidRes = DB::table('reservations')
                ->whereIn('id', $paidIds)
                ->select('id', 'status', 'payment_status', 'payment_method', 'payment_mode', 'prepaid_amount')
                ->get();
            $this->table(
                ['id', 'status', 'payment_status', 'payment_method', 'payment_mode', 'prepaid_amount'],
                $paidRes->map(fn ($r) => [
                    $r->id,
                    $r->status ?? 'NULL',
                    $r->payment_status ?? 'NULL',
                    $r->payment_method ?? 'NULL',
                    $r->payment_mode ?? 'NULL',
                    $r->prepaid_amount ?? '0',
                ])->toArray()
            );

            $txForPaid = DB::table('transactions')
                ->whereIn('reservation_id', $paidIds)
                ->select('id', 'reservation_id', 'status')
                ->get();
            $this->info('Transactions liées à ces réservations :');
            $this->table(
                ['transaction_id', 'reservation_id', 'status'],
                $txForPaid->map(fn ($t) => [$t->id, $t->reservation_id, $t->status ?? 'NULL'])->toArray()
            );
        }

        $prepaidWithAmount = DB::table('reservations')
            ->where('payment_mode', 'prepaid')
            ->whereRaw('COALESCE(prepaid_amount, 0) > 0')
            ->whereRaw('UPPER(COALESCE(payment_status, \'\')) NOT IN (?, ?)', ['PAID', 'PAYE'])
            ->select('id', 'status', 'payment_status', 'payment_method', 'prepaid_amount', 'estimated_cost')
            ->limit(15)
            ->get();

        $prepaidCreditNoAmount = DB::table('reservations')
            ->where('payment_mode', 'prepaid')
            ->whereRaw('LOWER(COALESCE(payment_method, \'\')) = ?', ['credit'])
            ->whereRaw('COALESCE(estimated_cost, 0) > 0')
            ->whereRaw('UPPER(COALESCE(payment_status, \'\')) NOT IN (?, ?)', ['PAID', 'PAYE'])
            ->select('id', 'status', 'payment_status', 'payment_method', 'prepaid_amount', 'estimated_cost')
            ->limit(15)
            ->get();

        if ($prepaidWithAmount->isNotEmpty()) {
            $this->newLine();
            $this->warn('Réservations prepaid_amount>0 mais payment_status != PAID (à corriger) :');
            $this->table(
                ['id', 'status', 'payment_status', 'payment_method', 'prepaid_amount', 'estimated_cost'],
                $prepaidWithAmount->map(fn ($r) => [
                    $r->id,
                    $r->status ?? 'NULL',
                    $r->payment_status ?? 'NULL',
                    $r->payment_method ?? 'NULL',
                    $r->prepaid_amount ?? '0',
                    $r->estimated_cost ?? '0',
                ])->toArray()
            );
        }

        if ($prepaidCreditNoAmount->isNotEmpty()) {
            $this->newLine();
            $this->warn('Réservations prepaid+credit+estimated_cost>0 mais payment_status != PAID (à corriger) :');
            $this->table(
                ['id', 'status', 'payment_status', 'payment_method', 'prepaid_amount', 'estimated_cost'],
                $prepaidCreditNoAmount->map(fn ($r) => [
                    $r->id,
                    $r->status ?? 'NULL',
                    $r->payment_status ?? 'NULL',
                    $r->payment_method ?? 'NULL',
                    $r->prepaid_amount ?? '0',
                    $r->estimated_cost ?? '0',
                ])->toArray()
            );
        }

        return 0;
    }
}
