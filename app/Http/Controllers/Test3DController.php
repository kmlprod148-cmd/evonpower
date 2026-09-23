<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class Test3DController extends Controller
{
    /**
     * Affiche la page de test du modèle 3D
     */
    public function index()
    {
        return view('test-3d');
    }
    
    /**
     * Vérifie la disponibilité du modèle 3D
     */
    public function checkModel()
    {
        $modelPath = public_path('models/hitem3d.glb');
        
        if (file_exists($modelPath)) {
            $fileSize = filesize($modelPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Modèle 3D disponible',
                'data' => [
                    'filename' => 'hitem3d.glb',
                    'size' => $fileSizeMB . ' MB',
                    'path' => 'public/models/hitem3d.glb',
                    'exists' => true,
                    'last_modified' => date('Y-m-d H:i:s', filemtime($modelPath))
                ]
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Modèle 3D non trouvé',
            'data' => [
                'filename' => 'hitem3d.glb',
                'exists' => false,
                'searched_path' => 'public/models/hitem3d.glb'
            ]
        ], 404);
    }
    
    /**
     * Teste le composant JavaScript 3D
     */
    public function testComponent()
    {
        $jsPath = resource_path('js/3d-model-viewer.js');
        $cssPath = public_path('css/3d-model.css');
        
        $results = [
            'javascript' => [
                'exists' => file_exists($jsPath),
                'path' => 'resources/js/3d-model-viewer.js',
                'size' => file_exists($jsPath) ? filesize($jsPath) : 0
            ],
            'css' => [
                'exists' => file_exists($cssPath),
                'path' => 'public/css/3d-model.css',
                'size' => file_exists($cssPath) ? filesize($cssPath) : 0
            ]
        ];
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vérification des composants 3D',
            'data' => $results
        ]);
    }
    
    /**
     * Teste la compatibilité WebGL
     */
    public function testWebGL()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Test de compatibilité WebGL',
            'data' => [
                'note' => 'La compatibilité WebGL doit être testée côté client',
                'recommendations' => [
                    'Utiliser un navigateur moderne (Chrome, Firefox, Safari, Edge)',
                    'Vérifier que WebGL est activé',
                    'Tester sur différents appareils'
                ]
            ]
        ]);
    }
    
    /**
     * Affiche les informations de débogage complètes
     */
    public function debug()
    {
        $debugInfo = [
            'timestamp' => now()->toISOString(),
            'environment' => config('app.env'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'webgl_support' => 'À tester côté client',
            'files' => [
                'model_3d' => [
                    'path' => 'public/models/hitem3d.glb',
                    'exists' => file_exists(public_path('models/hitem3d.glb')),
                    'size' => file_exists(public_path('models/hitem3d.glb')) ? 
                        round(filesize(public_path('models/hitem3d.glb')) / 1024 / 1024, 2) . ' MB' : 'N/A'
                ],
                'javascript' => [
                    'path' => 'resources/js/3d-model-viewer.js',
                    'exists' => file_exists(resource_path('js/3d-model-viewer.js')),
                    'size' => file_exists(resource_path('js/3d-model-viewer.js')) ? 
                        filesize(resource_path('js/3d-model-viewer.js')) . ' bytes' : 'N/A'
                ],
                'css' => [
                    'path' => 'public/css/3d-model.css',
                    'exists' => file_exists(public_path('css/3d-model.css')),
                    'size' => file_exists(public_path('css/3d-model.css')) ? 
                        filesize(public_path('css/3d-model.css')) . ' bytes' : 'N/A'
                ]
            ],
            'routes' => [
                'test_3d' => route('test.3d'),
                'check_model' => route('test.3d.check-model'),
                'test_component' => route('test.3d.component'),
                'test_webgl' => route('test.3d.webgl'),
                'debug' => route('test.3d.debug')
            ]
        ];
        
        return response()->json($debugInfo);
    }
}
