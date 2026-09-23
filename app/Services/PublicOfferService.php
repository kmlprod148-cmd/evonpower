<?php

namespace App\Services;

class PublicOfferService
{
    /**
     * Retrieve public offer details by ID.
     *
     * @param string $offerId
     * @return array|null
     */
    public function getOfferDetails(string $offerId): ?array
    {
        // Placeholder for business logic to retrieve offer details
        return [
            'id' => $offerId,
            'name' => 'Sample Charging Offer',
            'price_per_kwh' => 0.25,
            'currency' => 'EUR',
            'available' => true,
        ];
    }
}