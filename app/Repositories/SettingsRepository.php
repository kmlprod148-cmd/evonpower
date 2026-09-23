<?php

namespace App\Repositories;

class SettingsRepository
{
    public function getDefaultVatRate()
    {
        return config('app.default_vat_rate', 20);
    }
    
    public function getAvailableVatRates()
    {
        return [
            0 => '0%',
            5 => '5%',
            10 => '10%',
            20 => '20%',
        ];
    }
}