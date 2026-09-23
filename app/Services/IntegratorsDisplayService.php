<?php

namespace App\Services;

class IntegratorsDisplayService
{
    public static function shouldShowIntegratorsLink()
    {
        return true; // Toujours afficher
    }
    
    public static function getIntegratorsRoute()
    {
        return "integrators.index";
    }
    
    public static function getIntegratorsText()
    {
        return "Intégrateurs";
    }
}
?>