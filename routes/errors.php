<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Error Routes
|--------------------------------------------------------------------------
|
| Here are the routes for handling various error pages.
|
*/

Route::get('/errors/403', function () {
    return view('errors.403');
})->name('errors.403');

Route::get('/errors/404', function () {
    return view('errors.404');
})->name('errors.404');

Route::get('/errors/500', function () {
    return view('errors.500');
})->name('errors.500');
