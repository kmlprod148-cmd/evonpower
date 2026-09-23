<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LanguageController;

/*
|--------------------------------------------------------------------------
| Localization Routes
|--------------------------------------------------------------------------
|
| Here are the routes for language switching and localization features.
|
*/

Route::middleware(['web'])->group(function () {
    // Language switching routes are now defined in routes/web.php to avoid conflicts
    // Kept here only for reference - these routes are commented out
    
    /*
    Route::get('/language/{locale}', [LanguageController::class, 'change'])
        ->name('language.change')
        ->where('locale', '[a-z]{2}');
    
    Route::get('/language/switch/{locale}', [LanguageController::class, 'switchLanguage'])
        ->name('language.switch')
        ->where('locale', '[a-z]{2}');
    */
    
    // API routes for language information - kept for backward compatibility
    Route::get('/api/language/current', [LanguageController::class, 'getCurrent'])
        ->name('language.api.current');
    
    Route::get('/api/language/available', [LanguageController::class, 'getCurrent'])
        ->name('language.api.available');
    
    Route::get('/api/language/translations/{locale}', [LanguageController::class, 'getTranslations'])
        ->name('language.api.translations')
        ->where('locale', '[a-z]{2}');
    
    Route::get('/api/language/test', [LanguageController::class, 'testLanguage'])
        ->name('language.api.test');
    
    // Language test page
    Route::get('/language-test', function () {
        return view('language-test');
    })->name('language.test.page');
    
    // Localization test page
    Route::get('/localization-test', function () {
        return view('localization-test');
    })->name('localization.test.page');
    
    // Map test page
    Route::get('/map-test', function () {
        return view('map-test');
    })->name('map.test.page');
    
    // Compact layout demo page
    Route::get('/compact-demo', function () {
        return view('compact-demo');
    })->name('compact.demo.page');
    
    // Logo demo page
    Route::get('/logo-demo', function () {
        return view('logo-demo');
    })->name('logo.demo.page');
    
    // Fullsize layout demo page
    Route::get('/fullsize-demo', function () {
        return view('fullsize-demo');
    })->name('fullsize.demo.page');
    
    // Layout test page
    Route::get('/layout-test', function () {
        return view('layout-test');
    })->name('layout.test.page');
    
    // Centered content test page
    Route::get('/centered-content-test', function () {
        return view('centered-content-test');
    })->name('centered.content.test.page');
    
    // Sidebar spacing test page
    Route::get('/sidebar-spacing-test', function () {
        return view('sidebar-spacing-test');
    })->name('sidebar.spacing.test.page');
    
    // White space fix test page
    Route::get('/white-space-fix-test', function () {
        return view('white-space-fix-test');
    })->name('white.space.fix.test.page');
    
    // Sidebar modern test page
    Route::get('/sidebar-modern-test', function () {
        return view('sidebar-modern-test');
    })->name('sidebar.modern.test.page');
    
    // Layout enhanced test page
    Route::get('/layout-enhanced-test', function () {
        return view('layout-enhanced-test');
    })->name('layout.enhanced.test.page');
    
    // Layout final test page
    Route::get('/layout-final-test', function () {
        return view('layout-final-test');
    })->name('layout.final.test.page');
    
    // No white space test page
    Route::get('/no-white-space-test', function () {
        return view('no-white-space-test');
    })->name('no.white.space.test.page');
    
    // Layout fixed test page
    Route::get('/layout-fixed-test', function () {
        return view('layout-fixed-test');
    })->name('layout.fixed.test.page');
    
    // Charging points step1 test page
    Route::get('/charging-points-step1-test', function () {
        return view('charging-points-step1-test');
    })->name('charging.points.step1.test.page');
    
    // Main content fixed test page
    Route::get('/main-content-fixed-test', function () {
        return view('main-content-fixed-test');
    })->name('main.content.fixed.test.page');
    
    // Green sidebar test page
    Route::get('/green-sidebar-test', function () {
        return view('green-sidebar-test');
    })->name('green.sidebar.test.page');
    
    // Green sidebar respect layout test page
    Route::get('/green-sidebar-respect-layout', function () {
        return view('green-sidebar-respect-layout');
    })->name('green.sidebar.respect.layout.test.page');
});
