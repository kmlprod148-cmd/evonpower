<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

Route::get('/test-lang-debug', function (Request $request) {
    return response()->json([
        'app_locale' => App::getLocale(),
        'session_locale' => $request->session()->get('locale'),
        'session_facade' => Session::get('locale'),
        'cookie' => $request->cookie('locale'),
        'user' => auth()->check() ? auth()->user()->locale : null,
        'config' => config('app.locale'),
        'session_id' => $request->session()->getId(),
        'session_driver' => config('session.driver'),
    ]);
});
