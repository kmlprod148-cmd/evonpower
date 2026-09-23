<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentKeysService;
use App\Services\UnifiedPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Contrôleur pour tester l'intégration CMI
 * 
 * Cartes de test CMI:
 * - Visa (non-authentifiable): 4000000000000010, Exp: 12/XX, CVS: 000
 * - MasterCard (authentifiable): 5191630100004896, Exp: 12/XX, CVS: 000, Code: 123
 * - MasterCard (non participante): 5453010000066100, Exp: 12/XX, CVS: 000
 */
class CmiTestController extends Controller
{
    protected PaymentKeysService $paymentKeysService;

    public function __construct(PaymentKeysService $paymentKeysService)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole('admin')) {
                abort(403, 'Accès non autorisé. Seuls les administrateurs peuvent accéder à cette page.');
            }
            return $next($request);
        });
        
        $this->paymentKeysService = $paymentKeysService;
    }

    /**
     * Affiche la page de test CMI
     */
    public function index()
    {
        $cmiConfig = $this->getCmiConfiguration();
        $testCards = $this->getTestCards();
        
        return view('admin.cmi-test', [
            'cmiConfig' => $cmiConfig,
            'testCards' => $testCards,
        ]);
    }

    /**
     * Initie un paiement de test CMI
     */
    public function initiateTestPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000',
            'currency' => 'required|in:MAD,EUR,USD',
        ]);

        try {
            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            
            if (empty($cmiKeys['storekey'])) {
                return redirect()->back()->with('error', 
                    'La clé de hachage CMI n\'est pas configurée. Veuillez la configurer via le back office CMI: ' .
                    'Administration -> Changer les clés du magasin'
                );
            }

            if (empty($cmiKeys['clientid'])) {
                return redirect()->back()->with('error', 
                    'L\'identifiant marchand CMI n\'est pas configuré.'
                );
            }

            // Générer les données de paiement de test
            $amount = number_format($request->amount, 2, '.', '');
            $orderId = 'TEST-' . time() . '-' . Str::random(6);
            $rnd = (string) time();
            
            $baseUrl = $cmiKeys['callback_url'] ?? config('app.url');
            $okUrl = $baseUrl . '/admin/cmi-test/success';
            $failUrl = $baseUrl . '/admin/cmi-test/failure';
            $callbackUrl = $baseUrl . '/admin/cmi-test/callback';

            $paymentData = [
                'clientid' => $cmiKeys['clientid'],
                'storetype' => '3D_PAY_HOSTING',
                'amount' => $amount,
                'oid' => $orderId,
                'okUrl' => $okUrl,
                'failUrl' => $failUrl,
                'callbackUrl' => $callbackUrl,
                'rnd' => $rnd,
                'currency' => $request->currency,
                'hashAlgorithm' => 'ver3',
                'refreshtime' => '0',
                'lang' => 'fr',
                'email' => Auth::user()->email,
                'BillToName' => Auth::user()->name ?? 'Test User',
            ];

            // Générer le hash CMI
            $hashString = $paymentData['clientid'] . 
                         $paymentData['oid'] . 
                         $paymentData['amount'] . 
                         $paymentData['okUrl'] . 
                         $paymentData['failUrl'] . 
                         $paymentData['rnd'] . 
                         $cmiKeys['storekey'];
            
            $paymentData['HASH'] = base64_encode(pack('H*', sha1($hashString)));

            // URL de paiement CMI
            $paymentUrl = $cmiKeys['api_url'] ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate';

            Log::info('CMI Test payment initiated', [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $request->currency,
                'user' => Auth::user()->email,
            ]);

            return view('admin.cmi-test-form', [
                'paymentData' => $paymentData,
                'paymentUrl' => $paymentUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Test payment error', [
                'error' => $e->getMessage(),
                'user' => Auth::user()->email,
            ]);

            return redirect()->back()->with('error', 
                'Erreur lors de l\'initiation du paiement: ' . $e->getMessage()
            );
        }
    }

    /**
     * Gère le succès du paiement de test
     */
    public function handleSuccess(Request $request)
    {
        Log::info('CMI Test payment success', $request->all());

        return view('admin.cmi-test-result', [
            'status' => 'success',
            'title' => 'Paiement de test réussi !',
            'message' => 'Le paiement CMI de test a été traité avec succès.',
            'data' => $request->all(),
        ]);
    }

    /**
     * Gère l'échec du paiement de test
     */
    public function handleFailure(Request $request)
    {
        Log::warning('CMI Test payment failure', $request->all());

        return view('admin.cmi-test-result', [
            'status' => 'failure',
            'title' => 'Paiement de test échoué',
            'message' => 'Le paiement CMI de test a échoué. Vérifiez les détails ci-dessous.',
            'data' => $request->all(),
        ]);
    }

    /**
     * Gère le callback CMI de test
     */
    public function handleCallback(Request $request)
    {
        Log::info('CMI Test callback received', $request->all());

        // Vérifier le code de retour
        $procReturnCode = $request->get('ProcReturnCode');
        
        if ($procReturnCode === '00') {
            return response('ACTION=POSTAUTH', 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return response('FAILURE', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Vérifie la configuration CMI
     */
    public function checkConfiguration()
    {
        $config = $this->getCmiConfiguration();
        $issues = [];

        if (empty($config['client_id'])) {
            $issues[] = 'L\'identifiant marchand (Client ID) n\'est pas configuré.';
        }

        if (empty($config['store_key'])) {
            $issues[] = 'La clé de hachage (Store Key) n\'est pas configurée. ' .
                       'Veuillez la configurer via le back office CMI.';
        }

        if (empty($config['api_url'])) {
            $issues[] = 'L\'URL de l\'API CMI n\'est pas configurée.';
        }

        return response()->json([
            'success' => empty($issues),
            'config' => [
                'client_id' => $config['client_id'] ? 'Configuré' : 'Non configuré',
                'store_key' => $config['store_key'] ? 'Configuré (masqué)' : 'Non configuré',
                'api_url' => $config['api_url'] ?? 'Non configuré',
                'environment' => $config['environment'] ?? 'test',
                'callback_url' => $config['callback_url'] ?? 'Non configuré',
            ],
            'issues' => $issues,
        ]);
    }

    /**
     * Obtient la configuration CMI actuelle
     */
    private function getCmiConfiguration(): array
    {
        try {
            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            
            return [
                'client_id' => $cmiKeys['clientid'] ?? '',
                'store_key' => !empty($cmiKeys['storekey']) ? '••••••••' : '',
                'api_url' => $cmiKeys['api_url'] ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'callback_url' => $cmiKeys['callback_url'] ?? config('app.url'),
                'environment' => $cmiKeys['environment'] ?? 'test',
                'is_configured' => !empty($cmiKeys['storekey']) && !empty($cmiKeys['clientid']),
            ];
        } catch (\Exception $e) {
            Log::error('Error loading CMI configuration', ['error' => $e->getMessage()]);
            return [
                'client_id' => '',
                'store_key' => '',
                'api_url' => '',
                'callback_url' => '',
                'environment' => 'test',
                'is_configured' => false,
            ];
        }
    }

    /**
     * Retourne les cartes de test CMI
     */
    private function getTestCards(): array
    {
        return [
            [
                'brand' => 'Visa',
                'number' => '4000000000000010',
                'expiry_month' => '12',
                'expiry_year' => date('Y') + 2,
                'cvs' => '000',
                'auth_code' => 'N/A',
                'comment' => 'Carte non-authentifiable',
                'color' => 'blue',
            ],
            [
                'brand' => 'MasterCard',
                'number' => '5191630100004896',
                'expiry_month' => '12',
                'expiry_year' => date('Y') + 2,
                'cvs' => '000',
                'auth_code' => '123',
                'comment' => 'Carte authentifiable (3D Secure)',
                'color' => 'orange',
            ],
            [
                'brand' => 'MasterCard',
                'number' => '5453010000066100',
                'expiry_month' => '12',
                'expiry_year' => date('Y') + 2,
                'cvs' => '000',
                'auth_code' => 'N/A',
                'comment' => 'Carte non participante',
                'color' => 'gray',
            ],
        ];
    }
}
