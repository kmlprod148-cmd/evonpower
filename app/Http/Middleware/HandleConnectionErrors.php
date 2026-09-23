<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleConnectionErrors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (QueryException $e) {
            Log::error('Database connection error in middleware: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'exception' => $e
            ]);

            // Vérifier si c'est une erreur de connexion
            if ($this->isConnectionError($e)) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de connexion à la base de données. Le serveur peut être temporairement indisponible.',
                        'error' => 'database_connection_error',
                        'details' => 'Veuillez vérifier votre connexion internet et réessayer.',
                        'debug_info' => config('app.debug') ? [
                            'error_message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ] : null
                    ], 503);
                }

                return response()->view('errors.503', [
                    'message' => 'Service temporairement indisponible',
                    'details' => 'Le serveur de base de données est temporairement indisponible. Veuillez réessayer dans quelques minutes.'
                ], 503);
            }

            // Pour les autres erreurs de base de données, laisser Laravel les gérer
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error in middleware: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'exception' => $e
            ]);

            throw $e;
        }
    }

    /**
     * Détermine si l'erreur est liée à une connexion
     */
    private function isConnectionError(QueryException $e): bool
    {
        $message = $e->getMessage();
        
        $connectionErrors = [
            'Connection refused',
            'server has gone away',
            'Lost connection',
            'Connection timed out',
            'No connection could be made',
            'Connection reset by peer',
            'Network is unreachable',
            'Temporary failure in name resolution',
            'Could not connect to server',
            'Connection is broken',
            'SSL connection has been closed unexpectedly',
            'Communication link failure',
        ];

        foreach ($connectionErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        return false;
    }
}
