<?php

/**
 * Configuration des chemins locaux pour remplacer les CDN
 * Tous les assets sont maintenant hébergés localement
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Font Awesome
    |--------------------------------------------------------------------------
    */
    'fontawesome' => [
        'css' => '/vendor/fontawesome/css/all.min.css',
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Leaflet (Cartes)
    |--------------------------------------------------------------------------
    */
    'leaflet' => [
        'css' => '/css/leaflet/leaflet.css',
        'js' => '/js/leaflet/leaflet.js',
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrap
    |--------------------------------------------------------------------------
    */
    'bootstrap' => [
        'css' => '/vendor/bootstrap/css/bootstrap.min.css',
        'js' => '/vendor/bootstrap/js/bootstrap.bundle.min.js',
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | jQuery
    |--------------------------------------------------------------------------
    */
    'jquery' => [
        'js' => '/vendor/jquery/jquery.min.js',
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | DataTables
    |--------------------------------------------------------------------------
    */
    'datatables' => [
        'css' => '/vendor/datatables/css/dataTables.bootstrap5.min.css',
        'buttons_css' => '/vendor/datatables/css/buttons.bootstrap5.min.css',
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Remplacement automatique des CDN
    |--------------------------------------------------------------------------
    | Mapping des URLs CDN vers les chemins locaux
    */
    'cdn_replacements' => [
        // Font Awesome
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' => '/vendor/fontawesome/css/all.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' => '/vendor/fontawesome/css/all.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' => '/vendor/fontawesome/css/all.min.css',
        
        // Leaflet
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' => '/css/leaflet/leaflet.css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js' => '/js/leaflet/leaflet.js',
        
        // Bootstrap
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' => '/vendor/bootstrap/css/bootstrap.min.css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js' => '/vendor/bootstrap/js/bootstrap.bundle.min.js',
        
        // jQuery
        'https://code.jquery.com/jquery-3.7.0.min.js' => '/vendor/jquery/jquery.min.js',
        
        // DataTables
        'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css' => '/vendor/datatables/css/dataTables.bootstrap5.min.css',
        'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css' => '/vendor/datatables/css/buttons.bootstrap5.min.css',
    ],

];

