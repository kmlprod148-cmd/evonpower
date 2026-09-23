<?php

use App\Http\Controllers\Billing\BillingCycleController;
use App\Http\Controllers\Billing\BillingPlanController;
use App\Http\Controllers\Billing\BillingInvoiceController;
use App\Http\Controllers\Billing\BillingReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de Facturation
|--------------------------------------------------------------------------
|
| Routes pour la gestion du système de facturation périodique
|
*/

Route::prefix('billing')->name('billing.')->middleware(['auth'])->group(function () {
    
    // === CYCLES DE FACTURATION ===
    Route::prefix('cycles')->name('cycles.')->group(function () {
        Route::get('/', [BillingCycleController::class, 'index'])->name('index');
        Route::get('/create', [BillingCycleController::class, 'create'])->name('create');
        Route::post('/', [BillingCycleController::class, 'store'])->name('store');
        Route::get('/{billingCycle}', [BillingCycleController::class, 'show'])->name('show');
        Route::get('/{billingCycle}/edit', [BillingCycleController::class, 'edit'])->name('edit');
        Route::put('/{billingCycle}', [BillingCycleController::class, 'update'])->name('update');
        Route::delete('/{billingCycle}', [BillingCycleController::class, 'destroy'])->name('destroy');
        Route::patch('/{billingCycle}/toggle', [BillingCycleController::class, 'toggle'])->name('toggle');
    });

    // === PLANS DE FACTURATION ===
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [BillingPlanController::class, 'index'])->name('index');
        Route::get('/create', [BillingPlanController::class, 'create'])->name('create');
        Route::post('/', [BillingPlanController::class, 'store'])->name('store');
        Route::get('/{billingPlan}', [BillingPlanController::class, 'show'])->name('show');
        Route::get('/{billingPlan}/edit', [BillingPlanController::class, 'edit'])->name('edit');
        Route::put('/{billingPlan}', [BillingPlanController::class, 'update'])->name('update');
        Route::delete('/{billingPlan}', [BillingPlanController::class, 'destroy'])->name('destroy');
        Route::patch('/{billingPlan}/toggle', [BillingPlanController::class, 'toggle'])->name('toggle');
    });

    // === FACTURES ===
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [BillingInvoiceController::class, 'index'])->name('index');
        Route::get('/{billingInvoice}', [BillingInvoiceController::class, 'show'])->name('show');
        Route::patch('/{billingInvoice}/mark-paid', [BillingInvoiceController::class, 'markPaid'])->name('mark-paid');
        Route::patch('/{billingInvoice}/mark-overdue', [BillingInvoiceController::class, 'markOverdue'])->name('mark-overdue');
        Route::delete('/{billingInvoice}', [BillingInvoiceController::class, 'destroy'])->name('destroy');
        Route::get('/{billingInvoice}/download', [BillingInvoiceController::class, 'download'])->name('download');
    });

    // === RAPPORTS ===
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [BillingReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [BillingReportController::class, 'dashboard'])->name('dashboard');
        Route::post('/generate', [BillingReportController::class, 'generate'])->name('generate');
        Route::get('/download/{filename}', [BillingReportController::class, 'download'])->name('download');
        Route::delete('/{filename}', [BillingReportController::class, 'destroy'])->name('destroy');
        
        // Rapports spécialisés
        Route::post('/revenue', [BillingReportController::class, 'revenue'])->name('revenue');
        Route::post('/by-entity', [BillingReportController::class, 'byEntity'])->name('by-entity');
    });

    // === API ENDPOINTS ===
    Route::prefix('api')->name('api.')->group(function () {
        // Statistiques
        Route::get('/stats', function () {
            return app(\App\Services\BillingService::class)->getBillingStats();
        })->name('stats');

        // Génération de factures manuelle
        Route::post('/generate-invoices', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'date' => 'required|date',
                'dry_run' => 'boolean'
            ]);

            $exitCode = \Illuminate\Support\Facades\Artisan::call('billing:generate-invoices', [
                '--date' => $request->date,
                '--dry-run' => $request->boolean('dry_run', false)
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'output' => \Illuminate\Support\Facades\Artisan::output()
            ]);
        })->name('generate-invoices');

        // Traitement des factures en retard
        Route::post('/process-overdue', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'dry_run' => 'boolean'
            ]);

            $exitCode = \Illuminate\Support\Facades\Artisan::call('billing:process-overdue', [
                '--dry-run' => $request->boolean('dry_run', false)
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'output' => \Illuminate\Support\Facades\Artisan::output()
            ]);
        })->name('process-overdue');
    });
});
