<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class DatabaseConnectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Test database connection
            \DB::connection()->getPdo();
            return $next($request);
        } catch (\Exception $e) {
            Log::error('Database connection failed', [
                'error' => $e->getMessage(),
                'url' => $request->url(),
                'method' => $request->method()
            ]);

            // Return a user-friendly error page
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Service temporairement indisponible',
                    'message' => 'La base de données n\'est pas accessible. Veuillez réessayer plus tard.'
                ], 503);
            }

            return response()->view('errors.database', [
                'error' => 'Service temporairement indisponible',
                'message' => 'La base de données n\'est pas accessible. Veuillez réessayer plus tard.',
                'retry_url' => $request->url()
            ], 503);
        }
    }
}
