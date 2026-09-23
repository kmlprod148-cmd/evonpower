<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Track which locales have been loaded to avoid duplicate loading
     * 
     * @var array
     */
    private static $loadedLocales = [];
    
    /**
     * Track if translations have been loaded to avoid duplicate loading
     * 
     * @var bool
     */
    private static $translationsLoaded = false;
    
    /**
     * Cache for language variables to avoid recalculating on every view
     * 
     * @var array|null
     */
    private static $cachedLanguageVariables = null;
    
    /**
     * Cache key prefix for translations
     */
    private const CACHE_KEY_PREFIX = 'translations_extracted_';
    
    /**
     * Cache duration in seconds (24 hours)
     */
    private const CACHE_DURATION = 86400;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // NE PAS définir la locale ici - c'est le rôle du LocaleMiddleware
        // La locale sera définie par le middleware après l'initialisation de la session
        
        // IMPORTANT: S'assurer que les traductions standard sont chargées en premier
        // en forçant le chargement pour toutes les langues supportées
        $this->ensureStandardTranslationsLoaded();
        
        // Utiliser un View Composer pour partager les variables de langue
        // Cela garantit que la locale est correctement définie par le middleware avant le partage
        View::composer('*', function ($view) {
            $this->shareLanguageVariables($view);
        });
        
        // Charger les traductions depuis extracted_translations (une seule fois)
        // Ces traductions complètent les traductions standard sans les écraser
        $this->loadExtractedTranslations();
    }
    
    /**
     * S'assurer que les traductions standard sont chargées en premier
     * Cela garantit que les traductions standard ne sont pas écrasées par les traductions extraites
     */
    protected function ensureStandardTranslationsLoaded(): void
    {
        $languages = ['fr', 'en', 'ar', 'es'];
        $translator = $this->app['translator'];
        
        foreach ($languages as $lang) {
            try {
                // Forcer le chargement des traductions standard pour cette langue
                // Cela garantit qu'elles sont dans la mémoire du translator avant les traductions extraites
                $translator->load('*', 'messages', $lang);
            } catch (\Throwable $e) {
                // Attraper Throwable pour inclure les TypeError (ex: array_replace_recursive error)
                Log::error("TranslationServiceProvider: Critical error loading standard translations for {$lang}. This usually means the translation file returns 1 instead of an array. Error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Load translations from the extracted_translations folder
     * Only loads translations once per request to avoid duplicate loading
     * Uses cache to improve performance
     * IMPORTANT: This only loads SUPPLEMENTARY translations, not standard ones
     * Standard translations are loaded automatically by Laravel's FileLoader
     */
    protected function loadExtractedTranslations(): void
    {
        // Use static property to track if translations have been loaded in this request
        // This is more reliable than app->bound() which can be reset
        if (self::$translationsLoaded) {
            return;
        }
        
        // Mark as loading to prevent concurrent loads
        self::$translationsLoaded = true;
        
        $extractedPath = base_path('extracted_translations');
        
        if (!\Illuminate\Support\Facades\File::exists($extractedPath)) {
            // Directory doesn't exist - this is fine, extracted_translations is optional
            return;
        }

        $languages = ['fr', 'en', 'ar', 'es'];
        $translator = $this->app['translator'];
        
        foreach ($languages as $lang) {
            // Skip if this locale has already been loaded
            if (isset(self::$loadedLocales[$lang])) {
                continue;
            }
            
            // IMPORTANT: Ne pas appeler load() ici car cela peut causer des erreurs
            // si un fichier de traduction retourne un entier au lieu d'un tableau.
            // Laravel chargera automatiquement les traductions standard quand nécessaire.
            // On évite d'appeler load() pour prévenir l'erreur array_replace_recursive.
            
            // Try to load from cache first
            $cacheKey = self::CACHE_KEY_PREFIX . $lang;
            $cachedTranslations = Cache::get($cacheKey);
            
            if ($cachedTranslations !== null && is_array($cachedTranslations)) {
                // Load from cache
                $this->addTranslationsToTranslator($translator, $lang, $cachedTranslations);
                self::$loadedLocales[$lang] = true;
                continue;
            }
            
            $extractedFile = $extractedPath . '/' . $lang . '/extracted.php';
            
            if (!\Illuminate\Support\Facades\File::exists($extractedFile)) {
                // File not found - optional language, continue silently
                continue;
            }
            
            try {
                // Utiliser output buffering pour capturer toute sortie inattendue
                ob_start();
                $translations = include $extractedFile;
                $output = ob_get_clean();
                
                // Si le fichier a produit une sortie, c'est suspect
                if (!empty($output)) {
                    Log::warning("TranslationServiceProvider: Translation file produced output", [
                        'file' => $extractedFile,
                        'output' => $output
                    ]);
                }
                
                // Vérifier que le fichier retourne un tableau valide
                // Certains fichiers peuvent retourner 1 (succès) au lieu d'un tableau
                if (!is_array($translations)) {
                    Log::warning("TranslationServiceProvider: Invalid translation format in {$extractedFile}", [
                        'type' => gettype($translations),
                        'value' => is_scalar($translations) ? $translations : 'non-scalar',
                        'file_exists' => file_exists($extractedFile),
                        'file_size' => file_exists($extractedFile) ? filesize($extractedFile) : 0
                    ]);
                    continue;
                }
                
                // Vérifier que le tableau n'est pas vide et contient des valeurs valides
                if (empty($translations)) {
                    if (config('app.debug')) {
                        Log::debug("TranslationServiceProvider: Empty translations array in {$extractedFile}");
                    }
                    continue;
                }
                
                // Process and add translations
                $processedTranslations = $this->processTranslations($lang, $translations, $translator);
                
                // Cache the processed translations
                Cache::put($cacheKey, $processedTranslations, self::CACHE_DURATION);
                
                // Add to translator
                $this->addTranslationsToTranslator($translator, $lang, $processedTranslations);
                
                // Mark this locale as loaded
                self::$loadedLocales[$lang] = true;
            } catch (\Exception $e) {
                Log::error("TranslationServiceProvider: Error loading translations from {$extractedFile}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Process translations: check against standard translations and prepare for caching
     */
    protected function processTranslations(string $lang, array $translations, $translator): array
    {
        $messagesTranslations = [];
        $extractedTranslations = [];
        
        // Load standard translations once
        $standardTranslations = [];
        try {
            $messagesPath = base_path("resources/lang/{$lang}/messages.php");
            if (\Illuminate\Support\Facades\File::exists($messagesPath)) {
                $standardTranslations = include $messagesPath;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        foreach ($translations as $key => $value) {
            // Skip invalid keys
            if (empty($key) || !is_string($key)) {
                continue;
            }
            
            // Only add non-empty translations
            if (!empty($value) || $value === '0') {
                // Normalize the key: remove any existing 'messages.' or 'extracted.' prefix
                // to avoid double prefixes
                $normalizedKey = $key;
                if (strpos($normalizedKey, 'messages.') === 0) {
                    $normalizedKey = substr($normalizedKey, 8); // Remove 'messages.' prefix
                }
                if (strpos($normalizedKey, 'extracted.') === 0) {
                    $normalizedKey = substr($normalizedKey, 10); // Remove 'extracted.' prefix
                }
                
                // Skip keys that are empty after normalization
                if (empty($normalizedKey)) {
                    continue;
                }
                
                // Only add if key doesn't exist in standard translations
                // This way extracted_translations supplements but doesn't replace
                if (!isset($standardTranslations[$normalizedKey])) {
                    // Store without 'messages.' prefix - we'll add it later in addTranslationsToTranslator
                    $messagesTranslations[$normalizedKey] = $value;
                }
                
                // Always add to extracted namespace for backwards compatibility
                $extractedTranslations[$normalizedKey] = $value;
            }
        }
        
        return [
            'messages' => $messagesTranslations,
            'extracted' => $extractedTranslations
        ];
    }
    
    /**
     * Add processed translations to the translator
     * IMPORTANT: Standard translations from resources/lang are loaded automatically by Laravel's FileLoader
     * This method only adds supplementary translations from extracted_translations
     * Standard translations take precedence and won't be overwritten
     */
    protected function addTranslationsToTranslator($translator, string $lang, array $processedTranslations): void
    {
        // IMPORTANT: Ne pas appeler load() ici car cela peut causer des erreurs
        // si un fichier de traduction retourne un entier au lieu d'un tableau.
        // Laravel chargera automatiquement les traductions standard quand nécessaire.
        // On évite d'appeler load() pour prévenir l'erreur array_replace_recursive.
        
        if (!empty($processedTranslations['messages'])) {
            // Format keys with 'messages.' prefix for addLines()
            // addLines() expects format 'group.item' and will parse it
            // When using namespace '*', Laravel will parse 'messages.key' as group='messages', item='key'
            $formattedMessages = [];
            foreach ($processedTranslations['messages'] as $key => $value) {
                // Skip empty or invalid keys
                if (empty($key) || !is_string($key)) {
                    continue;
                }
                
                // Normalize the key: remove any existing 'messages.' prefix to avoid double prefixes
                $normalizedKey = $key;
                if (strpos($normalizedKey, 'messages.') === 0) {
                    $normalizedKey = substr($normalizedKey, 8); // Remove 'messages.' prefix
                }
                
                // Skip keys that are empty after normalization
                if (empty($normalizedKey)) {
                    continue;
                }
                
                // Ensure the key has the 'messages.' prefix
                $formattedKey = 'messages.' . $normalizedKey;
                
                // CRITICAL: Validate that the key has at least one dot (group.item format)
                // addLines() uses explode('.', $key, 2) which requires at least one dot
                // The formatted key should always have at least one dot (messages.key)
                if (strpos($formattedKey, '.') === false) {
                    // This should never happen, but skip to be safe
                    continue;
                }
                
                $formattedMessages[$formattedKey] = $value;
            }
            
            // Only add if there are valid formatted messages
            if (!empty($formattedMessages)) {
                // Use '*' namespace to let Laravel parse 'messages.key' format automatically
                // This will store translations in $loaded['*']['messages'][$lang][$key]
                // Note: addLines() will merge with existing translations, but standard translations
                // loaded by FileLoader take precedence due to load order
                $translator->addLines($formattedMessages, $lang, '*');
            }
        }
        
        if (!empty($processedTranslations['extracted'])) {
            // Format keys with 'extracted.' prefix for addLines()
            $formattedExtracted = [];
            foreach ($processedTranslations['extracted'] as $key => $value) {
                // Skip empty or invalid keys
                if (empty($key) || !is_string($key)) {
                    continue;
                }
                
                // Normalize the key: remove any existing 'extracted.' prefix to avoid double prefixes
                $normalizedKey = $key;
                if (strpos($normalizedKey, 'extracted.') === 0) {
                    $normalizedKey = substr($normalizedKey, 10); // Remove 'extracted.' prefix
                }
                
                // Skip keys that are empty after normalization
                if (empty($normalizedKey)) {
                    continue;
                }
                
                // Ensure the key has the 'extracted.' prefix
                $formattedKey = 'extracted.' . $normalizedKey;
                
                // CRITICAL: Validate that the key has at least one dot (group.item format)
                // addLines() uses explode('.', $key, 2) which requires at least one dot
                // The formatted key should always have at least one dot (extracted.key)
                if (strpos($formattedKey, '.') === false) {
                    // This should never happen, but skip to be safe
                    continue;
                }
                
                $formattedExtracted[$formattedKey] = $value;
            }
            
            // Only add if there are valid formatted extracted translations
            if (!empty($formattedExtracted)) {
                // Use '*' namespace to let Laravel parse 'extracted.key' format automatically
                $translator->addLines($formattedExtracted, $lang, '*');
            }
        }
    }
    
    /**
     * Ensure translations for a specific locale are loaded
     * This method is called when locale changes dynamically
     */
    protected function ensureLocaleTranslationsLoaded(string $locale): void
    {
        // Skip if already loaded
        if (isset(self::$loadedLocales[$locale])) {
            return;
        }
        
        // IMPORTANT: Ne pas appeler load() ici car cela peut causer des erreurs
        // si un fichier de traduction retourne un entier au lieu d'un tableau.
        // Laravel chargera automatiquement les traductions standard quand nécessaire.
        // On évite d'appeler load() pour prévenir l'erreur array_replace_recursive.
        
        // Try cache first
        $cacheKey = self::CACHE_KEY_PREFIX . $locale;
        $cachedTranslations = Cache::get($cacheKey);
        
        if ($cachedTranslations !== null && is_array($cachedTranslations)) {
            $this->addTranslationsToTranslator($this->app['translator'], $locale, $cachedTranslations);
            self::$loadedLocales[$locale] = true;
            return;
        }
        
        $extractedFile = base_path("extracted_translations/{$locale}/extracted.php");
        
        if (!\Illuminate\Support\Facades\File::exists($extractedFile)) {
            return;
        }
        
        try {
            // Utiliser output buffering pour capturer toute sortie inattendue
            ob_start();
            $translations = include $extractedFile;
            $output = ob_get_clean();
            
            // Si le fichier a produit une sortie, c'est suspect
            if (!empty($output)) {
                Log::warning("TranslationServiceProvider: Translation file produced output in ensureLocaleTranslationsLoaded", [
                    'file' => $extractedFile,
                    'output' => $output
                ]);
            }
            
            // Vérifier que le fichier retourne un tableau valide
            // Certains fichiers peuvent retourner 1 (succès) au lieu d'un tableau
            if (!is_array($translations)) {
                Log::warning("TranslationServiceProvider: Invalid translation format in ensureLocaleTranslationsLoaded for {$extractedFile}", [
                    'type' => gettype($translations),
                    'value' => is_scalar($translations) ? $translations : 'non-scalar',
                    'file_exists' => file_exists($extractedFile),
                    'file_size' => file_exists($extractedFile) ? filesize($extractedFile) : 0
                ]);
                return;
            }
            
            // Vérifier que le tableau n'est pas vide
            if (empty($translations)) {
                if (config('app.debug')) {
                    Log::debug("TranslationServiceProvider: Empty translations array in ensureLocaleTranslationsLoaded for {$extractedFile}");
                }
                return;
            }
            
            if (is_array($translations)) {
                $processedTranslations = $this->processTranslations($locale, $translations, $this->app['translator']);
                
                // Cache the processed translations
                Cache::put($cacheKey, $processedTranslations, self::CACHE_DURATION);
                
                // Add to translator
                $this->addTranslationsToTranslator($this->app['translator'], $locale, $processedTranslations);
                
                // Mark as loaded
                self::$loadedLocales[$locale] = true;
            }
        } catch (\Exception $e) {
            Log::error("TranslationServiceProvider: Error ensuring locale translations for {$locale}: " . $e->getMessage());
        }
    }
    
    /**
     * Partager les variables de langue avec toutes les vues
     * Utilise un View Composer pour garantir que la locale est correctement définie
     * 
     * @param \Illuminate\Contracts\View\View $view
     */
    private function shareLanguageVariables($view)
    {
        // La locale devrait être définie par le LocaleMiddleware à ce stade
        $currentLocale = App::getLocale();
        
        // Utiliser le cache si disponible et si la locale n'a pas changé
        if (self::$cachedLanguageVariables !== null && 
            isset(self::$cachedLanguageVariables['currentLocale']) &&
            self::$cachedLanguageVariables['currentLocale'] === $currentLocale) {
            $view->with(self::$cachedLanguageVariables);
            return;
        }
        
        $availableLocales = config('app.available_locales', ['fr', 'en', 'ar', 'es']);
        $localeNames = config('app.locale_names', [
            'fr' => 'Français',
            'en' => 'English',
            'ar' => 'العربية',
            'es' => 'Español'
        ]);
        
        // Déterminer la direction basée sur la locale actuelle
        $rtlLocales = config('app.rtl_locales', ['ar']);
        $direction = in_array($currentLocale, $rtlLocales) ? 'rtl' : 'ltr';
        
        // S'assurer que les traductions pour cette locale sont chargées
        $this->ensureLocaleTranslationsLoaded($currentLocale);
        
        $languageVariables = [
            'currentLocale' => $currentLocale,
            'availableLocales' => $availableLocales,
            'localeNames' => $localeNames,
            'direction' => $direction,
            'isRTL' => $direction === 'rtl',
            'isLTR' => $direction === 'ltr'
        ];
        
        // Mettre en cache pour cette requête
        self::$cachedLanguageVariables = $languageVariables;
        
        $view->with($languageVariables);
    }
    
    /**
     * Clear translation cache for all locales or a specific locale
     * 
     * @param string|null $locale If null, clears cache for all locales
     * @return void
     */
    public static function clearTranslationCache(?string $locale = null): void
    {
        if ($locale !== null) {
            Cache::forget(self::CACHE_KEY_PREFIX . $locale);
            unset(self::$loadedLocales[$locale]);
        } else {
            $languages = ['fr', 'en', 'ar', 'es'];
            foreach ($languages as $lang) {
                Cache::forget(self::CACHE_KEY_PREFIX . $lang);
            }
            self::$loadedLocales = [];
        }
    }
}