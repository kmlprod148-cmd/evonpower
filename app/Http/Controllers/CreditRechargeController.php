<?php

namespace App\Http\Controllers;

use App\Models\CreditRecharge;
use App\Models\CreditPack;
use App\Models\User;
use App\Services\CreditRechargeService;
use App\Services\PaymentIntegrationService;
use App\Services\CMICreditRechargeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
/**
 * Contrôleur pour la gestion des recharges de crédit client
 * 
 * Gère les recharges via trois méthodes :
 * - Offline : Recharge manuelle par un administrateur
 * - CMI : Paiement en ligne via CMI (Maroc)
 * - Stripe : Paiement en ligne via Stripe (International)
 */
class CreditRechargeController extends Controller
{
    protected CreditRechargeService $creditRechargeService;

    public function __construct(CreditRechargeService $creditRechargeService)
    {
        // This controller is used by both system users and client users.
        // Use the combined web/client guard so client sessions are not rejected
        // by the default web-only auth middleware.
        $this->middleware('auth:web,client');
        $this->creditRechargeService = $creditRechargeService;
    }

    /**
     * Affiche la page de recharge de crédit
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtenir l'historique des recharges avec filtres
        $filters = $request->only(['status', 'payment_method', 'start_date', 'end_date']);
        $recharges = $this->creditRechargeService->getUserRechargeHistory($user, $filters);
        
        // Optimiser avec eager loading si nécessaire
        if ($recharges->count() > 0) {
            $recharges->load(['creditPack']);
        }
        
        // Solde via ClientBalanceService (source unique)
        $balanceService = app(\App\Services\ClientBalanceService::class);
        $balance = $balanceService->getBalance($user);
        $formattedBalance = $balanceService->getFormatted($user);
        
        // Obtenir les méthodes de paiement disponibles
        // IMPORTANT: CMI et Stripe sont disponibles pour TOUS les utilisateurs authentifiés,
        // y compris les clients avec le rôle 'user', s'ils sont configurés et activés par l'admin
        $paymentMethods = $this->getAvailablePaymentMethods();
        
        // Filtrer uniquement les méthodes activées
        // Aucune restriction basée sur les rôles - tous les utilisateurs authentifiés voient les mêmes méthodes
        $paymentMethods = array_filter($paymentMethods, function($method) {
            return $method['enabled'] ?? false;
        });
        
        // Obtenir les packs de crédit actifs
        // Les packs "client_only" ne sont visibles que pour les clients qui ont fait des réservations
        $creditPacksQuery = CreditPack::active()->ordered();
        
        // Vérifier si la colonne is_client_only existe dans la table (database-agnostic)
        $hasClientOnlyColumn = $this->hasColumn('credit_packs', 'is_client_only');
        
        if ($hasClientOnlyColumn) {
            // Si l'utilisateur a fait des réservations, inclure les packs client_only
            // Sinon, exclure les packs client_only
            if ($user->hasMadeReservations()) {
                // Inclure tous les packs (publics + client_only)
                $creditPacks = $creditPacksQuery->get();
            } else {
                // Exclure les packs client_only
                $creditPacks = $creditPacksQuery->public()->get();
            }
        } else {
            // Si la colonne n'existe pas encore, retourner tous les packs actifs
            // (comportement par défaut avant la migration)
            $creditPacks = $creditPacksQuery->get();
        }
        
        return view('credit-recharge.index', compact(
            'recharges',
            'balance',
            'formattedBalance',
            'paymentMethods',
            'creditPacks',
            'filters'
        ));
    }

    /**
     * Initie une nouvelle recharge de crédit
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Obtenir les méthodes de paiement disponibles et activées
        // IMPORTANT: CMI et Stripe sont inclus ici pour TOUS les utilisateurs authentifiés,
        // y compris les clients, s'ils sont configurés et activés
        $availableMethods = $this->getAvailablePaymentMethods();
        $allowedPaymentMethods = array_keys(array_filter($availableMethods, function($method) {
            return $method['enabled'] ?? false;
        }));
        
        // Toujours inclure 'offline' comme méthode de secours
        if (!in_array('offline', $allowedPaymentMethods)) {
            $allowedPaymentMethods[] = 'offline';
        }
        
        $validator = Validator::make($request->all(), [
            'credit_pack_id' => 'nullable|exists:credit_packs,id',
            'amount' => 'required_without:credit_pack_id|nullable|numeric|min:0.01|max:10000',
            'payment_method' => ['required', 'in:' . implode(',', $allowedPaymentMethods)],
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:500',
        ], [
            'credit_pack_id.exists' => 'Le pack de crédit sélectionné n\'existe pas ou n\'est plus disponible.',
            'amount.required_without' => 'Veuillez sélectionner un pack ou saisir un montant personnalisé.',
            'amount.numeric' => 'Le montant doit être un nombre valide.',
            'amount.min' => 'Le montant minimum est de 0,01 EUR.',
            'amount.max' => 'Le montant maximum est de 10 000 EUR.',
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in' => 'La méthode de paiement sélectionnée n\'est pas valide.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Veuillez corriger les erreurs dans le formulaire.'
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Veuillez corriger les erreurs dans le formulaire.')
                ->withInput();
        }

        try {
            $user = Auth::user();
            
            // Si un pack est sélectionné, utiliser la méthode avec pack
            if ($request->credit_pack_id) {
                $result = $this->creditRechargeService->initiateRechargeWithPack(
                    $user,
                    $request->credit_pack_id,
                    $request->payment_method,
                    [
                        'description' => $request->description,
                        'metadata' => [
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent()
                        ]
                    ]
                );
            } else {
                // Sinon, recharge personnalisée
                $result = $this->creditRechargeService->initiateRecharge(
                    $user,
                    $request->amount,
                    $request->payment_method,
                    $request->currency ?? 'EUR',
                    [
                        'is_custom' => true,
                        'description' => $request->description,
                        'metadata' => [
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent()
                        ]
                    ]
                );
            }

            if (!$result['success']) {
                $errorMessage = $result['message'] ?? 'Une erreur est survenue lors de la création de la recharge.';
                
                // Messages d'erreur plus conviviaux pour les clients
                if ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
                    $errorMessage = match(true) {
                        str_contains(strtolower($errorMessage), 'wallet') => 'Erreur lors de l\'accès à votre wallet. Veuillez réessayer ou contacter le support.',
                        str_contains(strtolower($errorMessage), 'pack') => 'Le pack sélectionné n\'est plus disponible. Veuillez en choisir un autre.',
                        str_contains(strtolower($errorMessage), 'montant') || str_contains(strtolower($errorMessage), 'amount') => 'Le montant saisi n\'est pas valide. Veuillez vérifier et réessayer.',
                        default => $errorMessage
                    };
                }
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => $result['errors'] ?? []
                    ], 400);
                }

                return redirect()->back()
                    ->with('error', $errorMessage)
                    ->withInput();
            }

            // Si c'est un paiement en ligne (CMI ou Stripe), rediriger vers la page de paiement
            if (in_array($request->payment_method, ['cmi', 'stripe']) && isset($result['payment_url'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $result['payment_url'],
                        'payment_data' => $result['payment_data'] ?? null
                    ]);
                }

                // Pour CMI, rediriger vers la route qui génère le formulaire avec hash
                if ($request->payment_method === 'cmi' && isset($result['recharge_id'])) {
                    return redirect()->route('credit-recharge.cmi.send', $result['recharge_id']);
                }

                // Pour Stripe, redirection simple
                return redirect($result['payment_url']);
            }

            // Pour les recharges offline, afficher un message de confirmation
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'recharge' => $result
                ]);
            }

            return redirect()->route('credit-recharge.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la recharge', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Une erreur est survenue lors de la création de la recharge.';
            
            // Messages d'erreur plus conviviaux pour les clients
            if ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
                $errorMessage = 'Une erreur est survenue. Veuillez réessayer ou contacter le support si le problème persiste.';
            } elseif (config('app.debug')) {
                $errorMessage = 'Erreur lors de la création de la recharge: ' . $e->getMessage();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'une recharge
     */
    public function show(CreditRecharge $creditRecharge)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut voir cette recharge
        if ($creditRecharge->user_id !== $user->id && !$user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Accès non autorisé');
        }

        return view('credit-recharge.show', compact('creditRecharge'));
    }

    /**
     * Envoyer les données vers CMI (génère le formulaire avec hash et redirige automatiquement)
     */
    public function sendCmiPayment(CreditRecharge $recharge)
    {
        try {
            // Vérifier que la recharge peut être payée
            if ($recharge->status !== 'pending' && $recharge->status !== 'processing') {
                return redirect()->route('credit-recharge.index')
                    ->with('error', 'Cette recharge ne peut pas être payée');
            }

            // Utiliser le service CMI
            $cmiService = app(CMICreditRechargeService::class);
            $paymentData = $cmiService->preparePaymentData($recharge);
            $paymentUrl = $cmiService->getPaymentUrl();

            Log::info('CMI Payment initiated for credit recharge', [
                'recharge_id' => $recharge->id,
                'amount' => $recharge->amount
            ]);

            // Afficher la vue avec auto-submit du formulaire
            // Ajouter des headers pour empêcher la mise en cache et éviter les redirections persistantes
            return response()
                ->view('credit-recharge.cmi.send-data', [
                    'recharge' => $recharge,
                    'paymentData' => $paymentData,
                    'paymentUrl' => $paymentUrl
                ])
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('CMI Payment initiation failed for credit recharge', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Message d'erreur amélioré avec indication pour configurer les clés
            $errorMessage = $e->getMessage();
            
            // Si les clés ne sont pas configurées, améliorer le message
            if (strpos($errorMessage, 'clés CMI') !== false || 
                strpos($errorMessage, 'configur') !== false || 
                strpos($errorMessage, 'Store Key') !== false ||
                strpos($errorMessage, 'Client ID') !== false) {
                
                // Extraire les informations importantes du message
                $isKeyMissing = strpos($errorMessage, 'Clés manquantes') !== false || 
                               strpos($errorMessage, 'Store Key') !== false ||
                               strpos($errorMessage, 'Client ID') !== false;
                
                if ($isKeyMissing) {
                    $errorMessage = "⚠️ Configuration CMI requise\n\n" . 
                                   "Les clés API CMI ne sont pas encore configurées. " .
                                   "Pour activer les paiements CMI, veuillez configurer les clés dans l'interface admin.\n\n" .
                                   "📍 Accès : Paramètres > API CMI\n" .
                                   "🔑 Vous devez configurer :\n" .
                                   "   - Clé API (Store Key)\n" .
                                   "   - ID Marchand (Client ID)\n" .
                                   "   - URL de Callback\n\n" .
                                   "ℹ️ Ces informations sont fournies par CMI lors de l'activation de votre compte marchand.";
                } else {
                    $errorMessage .= "\n\n📍 Vous pouvez configurer les clés CMI dans l'interface admin : Paramètres > API CMI";
                }
            }

            return redirect()->route('credit-recharge.index')
                ->with('error', $errorMessage);
        }
    }

    /**
     * Generic CMI server-to-server callback (no recharge in URL path).
     * The recharge is identified via the 'oid' POST field set by CMICreditRechargeService.
     * This route must be publicly accessible (no auth).
     */
    public function handleCmiCallbackGeneric(Request $request)
    {
        $rechargeId = $request->input('oid');

        if (!$rechargeId) {
            Log::warning('CMI generic callback: oid manquant', ['post_data' => $request->all()]);
            return response('FAILURE', 400)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $recharge = CreditRecharge::find($rechargeId);

        if (!$recharge) {
            Log::error('CMI generic callback: recharge introuvable', ['oid' => $rechargeId]);
            return response('FAILURE', 404)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return $this->handleCmiCallback($request, $recharge);
    }

    /**
     * Callback serveur-à-serveur de CMI (équivalent à callback.php)
     * Cette route doit être accessible publiquement (sans auth)
     */
    public function handleCmiCallback(Request $request, CreditRecharge $recharge)
    {
        try {
            $postData = $request->all();
            
            Log::info('CMI Callback received for credit recharge', [
                'recharge_id' => $recharge->id,
                'post_data' => $postData
            ]);

            // Utiliser le service CMI
            $cmiService = app(CMICreditRechargeService::class);
            $response = $cmiService->handleCallback($postData);

            // Si le paiement est approuvé, mettre à jour via CreditRechargeService
            if ($response === 'ACTION=POSTAUTH' && isset($postData['ProcReturnCode']) && $postData['ProcReturnCode'] === '00') {
                DB::beginTransaction();
                try {
                    $this->creditRechargeService->handleCmiSuccessCallback($postData);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error updating credit recharge after CMI callback', [
                        'recharge_id' => $recharge->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Retourner la réponse text/plain comme attendu par CMI
            return response($response, 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');

        } catch (\Exception $e) {
            Log::error('CMI Callback processing failed for credit recharge', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('FAILURE', 500)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    /**
     * Page de retour succès/échec (équivalent à Ok-Fail.php)
     */
    public function handleCmiReturn(Request $request, CreditRecharge $recharge)
    {
        try {
            $postData = $request->all();

            Log::info('CMI Return page accessed for credit recharge', [
                'recharge_id' => $recharge->id,
                'post_data' => $postData
            ]);

            // Utiliser le service CMI
            $cmiService = app(CMICreditRechargeService::class);
            $result = $cmiService->handleReturn($postData);

            // Si le hash est valide et le paiement approuvé, mettre à jour la recharge
            if ($result['hash_valid'] && $result['payment_approved']) {
                DB::beginTransaction();
                try {
                    $this->creditRechargeService->handleCmiSuccessCallback($postData);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error updating credit recharge after CMI return', [
                        'recharge_id' => $recharge->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif (!$result['payment_approved']) {
                // Paiement échoué
                $errorMessage = $result['error_message'] ?? 'Paiement échoué';
                $recharge->markAsFailed($errorMessage);
            }

            // Afficher la page de résultat
            return view('credit-recharge.cmi.ok-fail', [
                'recharge' => $recharge,
                'result' => $result,
                'postData' => $postData
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Return processing failed for credit recharge', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('credit-recharge.cmi.ok-fail', [
                'recharge' => $recharge,
                'result' => [
                    'success' => false,
                    'hash_valid' => false,
                    'payment_approved' => false,
                    'error_message' => 'Erreur lors du traitement du paiement: ' . $e->getMessage()
                ],
                'postData' => $request->all()
            ]);
        }
    }

    /**
     * Callback de succès CMI (ancienne méthode - conservée pour compatibilité)
     */
    public function cmiSuccess(Request $request)
    {
        return $this->handleCmiReturn($request, CreditRecharge::findOrFail($request->input('oid')));
    }

    /**
     * Callback d'échec CMI (ancienne méthode - conservée pour compatibilité)
     */
    public function cmiFailure(Request $request)
    {
        try {
            $rechargeId = $request->input('oid');
            
            if ($rechargeId) {
                $recharge = CreditRecharge::find($rechargeId);
                if ($recharge) {
                    $recharge->markAsFailed('Paiement CMI échoué');
                }
            }

            return redirect()->route('credit-recharge.index')
                ->with('error', 'Le paiement a échoué. Veuillez réessayer.');

        } catch (\Exception $e) {
            Log::error('Erreur callback CMI failure', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('credit-recharge.index')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * Callback de succès Stripe
     */
    public function stripeSuccess(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            
            if ($sessionId) {
                // Le webhook Stripe devrait avoir déjà traité la recharge
                // On vérifie juste que tout s'est bien passé
                $recharge = CreditRecharge::where('external_id', $sessionId)
                    ->orWhere('payment_data->session_id', $sessionId)
                    ->first();
                
                if ($recharge && $recharge->isCompleted()) {
                    return redirect()->route('credit-recharge.index')
                        ->with('success', 'Recharge complétée avec succès !');
                }
            }

            return redirect()->route('credit-recharge.index')
                ->with('info', 'Votre paiement est en cours de traitement.');

        } catch (\Exception $e) {
            Log::error('Erreur callback Stripe success', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('credit-recharge.index')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * Callback d'annulation Stripe
     */
    public function stripeCancel(Request $request)
    {
        return redirect()->route('credit-recharge.index')
            ->with('info', 'Paiement annulé. Vous pouvez réessayer à tout moment.');
    }

    /**
     * Webhook Stripe pour les recharges
     */
    public function stripeWebhook(Request $request)
    {
        try {
            $stripeConfig = config('payments.stripe', []);
            $endpointSecret = $stripeConfig['webhook_secret'] ?? null;

            $payload = $request->getContent();
            
            if ($endpointSecret) {
                $sigHeader = $request->header('Stripe-Signature');
                
                if (!$sigHeader) {
                    Log::warning('Webhook Stripe: Signature manquante dans les headers');
                    return response()->json([
                        'success' => false,
                        'message' => 'Signature manquante'
                    ], 400);
                }
                
                try {
                    $event = \Stripe\Webhook::constructEvent(
                        $payload,
                        $sigHeader,
                        $endpointSecret
                    );
                } catch (\Stripe\Exception\SignatureVerificationException $e) {
                    Log::error('Webhook Stripe: Signature invalide', [
                        'error' => $e->getMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Signature invalide: ' . $e->getMessage()
                    ], 400);
                }
            } else {
                // En mode développement, on peut traiter sans vérification de signature
                Log::warning('Webhook Stripe: Mode développement - signature non vérifiée');
                $event = json_decode($payload, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON invalide: ' . json_last_error_msg());
                }
            }

            $result = $this->creditRechargeService->handleStripeWebhook($event);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erreur webhook Stripe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur traitement webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annule une recharge
     */
    public function cancel(CreditRecharge $creditRecharge, Request $request)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut annuler cette recharge
        if ($creditRecharge->user_id !== $user->id && !$user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Accès non autorisé');
        }

        if ($creditRecharge->isCompleted()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'annuler une recharge complétée'
                ], 400);
            }

            return redirect()->back()
                ->with('error', 'Impossible d\'annuler une recharge complétée');
        }

        $result = $this->creditRechargeService->cancelRecharge(
            $creditRecharge,
            $request->input('reason', 'Annulée par l\'utilisateur')
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Obtient les méthodes de paiement disponibles
     */
    protected function getAvailablePaymentMethods(): array
    {
        $paymentIntegrationService = app(PaymentIntegrationService::class);
        return $paymentIntegrationService->getAvailablePaymentMethods();
    }

    /**
     * Check if a column exists in a given table in a database-agnostic way.
     */
    protected function hasColumn(string $table, string $columnName): bool
    {
        // Whitelist of tables this helper is allowed to inspect.
        // Interpolating $table directly into a PRAGMA statement is a SQL-injection
        // risk when SQLite is the active driver, since PRAGMA does not support
        // parameter binding. We therefore reject any unknown table name.
        static $allowedTables = [
            'credit_packs', 'credit_recharges', 'wallet_transactions',
            'orders', 'reservations', 'charging_sessions', 'transactions',
            'wallets', 'users',
        ];

        if (!in_array($table, $allowedTables, true)) {
            Log::warning("hasColumn: table '{$table}' not in allowed list — rejecting schema check");
            return false;
        }

        try {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                // PRAGMA does not support parameter binding; the whitelist above ensures safety.
                $columns = DB::select("PRAGMA table_info(`{$table}`)");
                foreach ($columns as $column) {
                    if (isset($column->name) && $column->name === $columnName) {
                        return true;
                    }
                }
                return false;
            } else {
                // For MySQL, PostgreSQL, etc., use Laravel's Schema::hasColumn
                return Schema::hasColumn($table, $columnName);
            }
        } catch (\Exception $e) {
            // If schema check fails, assume column doesn't exist
            Log::error("hasColumn check failed for table {$table}, column {$columnName}: " . $e->getMessage());
            return false;
        }
    }
}


