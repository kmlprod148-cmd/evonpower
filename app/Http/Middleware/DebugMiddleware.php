<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            Log::info('DebugMiddleware: Request started', [
                'url' => $request->url(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'user_id' => auth()->id(),
            ]);

            $response = $next($request);

            Log::info('DebugMiddleware: Request completed', [
                'status' => $response->getStatusCode(),
                'session_id' => $request->session()->getId(),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('DebugMiddleware: Exception caught', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->url(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
            ]);

            throw $e;
        }
    }
}
