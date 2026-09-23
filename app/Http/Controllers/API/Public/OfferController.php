<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicOfferService; // Import the new service
use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseApiController;

class OfferController extends BaseApiController
{
    protected $publicOfferService;

    /**
     * Constructor to inject the PublicOfferService.
     */
    public function __construct(PublicOfferService $publicOfferService)
    {
        $this->publicOfferService = $publicOfferService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(string $id)
    {
        $offerDetails = $this->publicOfferService->getOfferDetails($id);

        if (!$offerDetails) {
            return response()->json(['message' => 'Offer not found'], 404);
        }

        return response()->json($offerDetails);
    }
}