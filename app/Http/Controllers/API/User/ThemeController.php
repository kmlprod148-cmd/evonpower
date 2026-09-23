<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur API pour la gestion du thème
 */
class ThemeController extends Controller
{
    public function __construct(
        protected ThemeService $themeService
    ) {}

    /**
     * Obtenir le thème actuel
     */
    public function show(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json($this->themeService->prepareThemeData($user));
    }

    /**
     * Mettre à jour le thème
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $user = Auth::user();
        
        $this->themeService->setTheme($user, $request->theme);

        return response()->json([
            'success' => true,
            'message' => 'Thème mis à jour avec succès',
            'theme' => $this->themeService->getEffectiveTheme($user),
            'theme_preference' => $request->theme,
        ]);
    }

    /**
     * Basculer le thème
     */
    public function toggle(): JsonResponse
    {
        $user = Auth::user();
        
        $newTheme = $this->themeService->toggleTheme($user);

        return response()->json([
            'success' => true,
            'message' => 'Thème basculé avec succès',
            'theme' => $newTheme,
        ]);
    }

    /**
     * Obtenir les options de thème disponibles
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'options' => $this->themeService->getThemeOptions(),
        ]);
    }
}
