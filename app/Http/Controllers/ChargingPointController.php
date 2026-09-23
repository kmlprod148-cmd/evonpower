<?php

namespace App\Http\Controllers;

use App\DTO\Connector\ConnectorErrorDTO;
use App\Http\Controllers\BaseResourceController;
use App\Http\Requests\ChargingPoint\ChargingPointStoreRequest;
use App\Http\Requests\ChargingPoint\ChargingPointUpdateRequest;
use App\Http\Resources\ChargingPoint\ChargingPointResource; // Assuming you have this resource
use App\Jobs\OcppCommandJob;
use App\Models\ChargePointCommand;
use App\Models\ChargingPoint;
use App\Modules\ChargingPoints\Services\ChargingPointService;
use App\Services\ConnectorService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Class ChargingPointController
 *
 * Unified controller for managing ChargingPoint resources (Web and API).
 *
 * @package App\Http\Controllers
 */
class ChargingPointController extends BaseResourceController
{
    protected ChargingPointService $chargingPointService;
    protected ConnectorService $connectorService;

    /**
     * ChargingPointController constructor.
     *
     * @param ChargingPointService $chargingPointService
     * @param ConnectorService $connectorService
     */
    public function __construct(
        ChargingPointService $chargingPointService,
        ConnectorService $connectorService
    ) {
        $this->chargingPointService = $chargingPointService;
        $this->connectorService = $connectorService;
        // Apply authentication middleware
        $this->middleware('auth');
    }

    /**
     * Display a listing of the charging points.
     * Handles both web (HTML) and API (JSON) requests.
     *
     * @param Request $request
     * @return JsonResponse|View
     */
    public function index(Request $request)
    {
        try {
            // Delegate data fetching and filtering to the service layer
            $data = $this->chargingPointService->getFilteredForUser(
                $request->user(), // Pass the authenticated user
                $request->all()
            );

            // Handle API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'data' => ChargingPointResource::collection($data['chargingPoints']),
                    'stats' => $data['stats']
                ]);
            }

            // Handle web requests - pass the data with correct variable names
            return view('charging-points.index', [
                'chargingPoints' => $data['chargingPoints'],
                'stats' => $data['stats']
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading charging points index: ' . $e->getMessage());

            // Handle exceptions based on request format
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to load charging points.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()->back()
                    ->with('error', 'Unable to load charging points. Please try again.');
            }
        }
    }

    /**
     * Show the form for creating a new charging point.
     * This method is primarily for web requests.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function create(Request $request)
    {
        // Although create is primarily for web, we should still consider API requests
        // and return an appropriate response (e.g., 405 Method Not Allowed or a JSON error)
        // if an API client tries to access this route.
        // For now, we'll assume it's a web-only route based on typical usage.
        if ($request->expectsJson()) {
             return response()->json(['message' => 'Method Not Allowed'], 405);
        }

        try {
            // Delegate form data fetching to the service if needed, or prepare data here
            $data = $this->chargingPointService->getCreateFormData($request->user()); // Assuming getCreateFormData exists in service

            return view('charging-points.create', $data);

        } catch (\Exception $e) {
            Log::error('Error loading create form: ' . $e->getMessage());

            return redirect()->route('charging-points.index')
                ->with('error', 'Unable to load create form. Please try again.');
        }
    }

    /**
     * Store a newly created charging point.
     * Handles both web (HTML) and API (JSON) requests.
     *
     * @param ChargingPointStoreRequest $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function store(ChargingPointStoreRequest $request)
    {
        // Check authorization using policy
        $this->authorize('create', ChargingPoint::class);
        
        try {
            $validatedData = $request->validated();
            $user = auth()->user();

            if ($user->hasRole('integrator')) {
                // If integrator_id is not provided, automatically set it to the user's integrator_id
                if (!isset($validatedData['integrator_id'])) {
                    $validatedData['integrator_id'] = $user->integrator_id;
                } elseif ($validatedData['integrator_id'] != $user->integrator_id) {
                    // If integrator_id is provided but doesn't match, deny access
                    return response()->json([
                        'message' => 'Vous ne pouvez créer des bornes que pour votre intégrateur'
                    ], 400);
                }
            }

            if ($user->hasRole('partner')) {
                // If partner_id is not provided, automatically set it to the user's partner_id
                if (!isset($validatedData['partner_id'])) {
                    $validatedData['partner_id'] = $user->partner_id;
                } elseif ($validatedData['partner_id'] != $user->partner_id) {
                    // If partner_id is provided but doesn't match, deny access
                    return response()->json([
                        'message' => 'Vous ne pouvez créer des bornes que pour votre partenaire'
                    ], 400);
                }
            }

            if ($user->hasRole('operator')) {
                // Automatically assign operator fields
                $validatedData['operator_id'] = $user->id;
                $validatedData['group_id'] = $user->group_id;
                $validatedData['integrator_id'] = $user->integrator_id;
                
                // Ensure operator can only create for their own hierarchy
                if (isset($validatedData['operator_id']) && $validatedData['operator_id'] != $user->id) {
                    return response()->json([
                        'message' => 'Vous ne pouvez créer des bornes que pour votre propre compte'
                    ], 400);
                }
            }

            // Security: Use Gate authorization to check permissions (respects ChargingPointPolicy::create)
            // This allows Admin, Integrator, Partner, and Operator roles
            if (!$user->can('create', ChargingPoint::class)) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas autorisé à créer des bornes de recharge.'
                ], 403);
            }

            // Validate group ownership for integrators if group_id is provided
            if ($user->hasRole('integrator') && !empty($validatedData['group_id'])) {
                $group = \App\Models\Group::find($validatedData['group_id']);
                if ($group && $group->integrator_id !== $user->integrator_id) {
                    return response()->json([
                        'message' => 'Vous ne pouvez créer des bornes que pour vos propres groupes.'
                    ], 403);
                }
            }

            // Validate pricing plan belongs to the selected partner if plan_id is provided
            if (!empty($validatedData['pricing_plan_id']) && !empty($validatedData['partner_id'])) {
                $plan = \App\Models\PricingPlan::find($validatedData['pricing_plan_id']);
                $partner = \App\Models\Partner::find($validatedData['partner_id']);
                
                if ($plan && $partner) {
                    // Check if plan is linked to this partner
                    if (!$plan->partners()->where('partner_id', $partner->id)->exists()) {
                        return response()->json([
                            'message' => 'Le plan tarifaire sélectionné n\'appartient pas à ce partenaire.'
                        ], 403);
                    }
                }
            }

            // For partners, ensure they can only use their own plans
            if ($user->hasRole('partner') && !empty($validatedData['pricing_plan_id'])) {
                $plan = \App\Models\PricingPlan::find($validatedData['pricing_plan_id']);
                if ($plan && !$plan->partners()->where('partner_id', $user->partner_id)->exists()) {
                    return response()->json([
                        'message' => 'Vous ne pouvez utiliser que vos propres plans tarifaires.'
                    ], 403);
                }
            }

            // Create charging point. SteVe auto-provisioning is config-driven
            // (see config/steve.php → STEVE_AUTO_PROVISION_ON_CREATE). Default is
            // strict atomic mode; flip the env flag to persist locally and sync later.
            $autoProvision = (bool) config('steve.auto_provision_on_create', true);
            $chargingPoint = $this->chargingPointService->createChargingPoint($validatedData, $autoProvision);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Charging point created successfully.',
                    'data' => new ChargingPointResource($chargingPoint)
                ], 201);
            } else {
                return redirect()
                    ->route('charging-points.show', ['chargingPoint' => $chargingPoint->id])
                    ->with('success', 'Point de recharge créé avec succès.');
            }
        } catch (\Exception $e) {
            Log::error('Error creating charging point: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error creating charging point.'], 500);
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Erreur lors de la création du point de recharge.')
                    ->withInput();
            }
        }
    }

    /**
     * Display the specified charging point.
     * Handles both web (HTML) and API (JSON) requests.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse|View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            // Delegate data fetching to the service layer
            $chargingPoint = $this->chargingPointService->findChargingPointWithRelations($id, [
                'connectors', 'partner', 'group', 'integrator', 'pricingPlan'
            ]);

            if (!$chargingPoint) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Charging point not found.'], 404);
                } else {
                    return redirect()->route('charging-points.index')
                        ->with('error', 'Charging point not found.');
                }
            }

            // Authorization check
            $this->authorize('view', $chargingPoint);

            // Get QR code URL for integration tab
            $qrCodeUrl = null;
            try {
                $qrCodeService = app(QRCodeService::class);
                $qrCodeData = $qrCodeService->get($chargingPoint->id);
                $qrCodeUrl = $qrCodeData['qr_code_url'];
            } catch (\Exception $e) {
                Log::warning('Could not get QR code URL for charging point ' . $chargingPoint->id . ': ' . $e->getMessage());
            }

            // Use the base controller's method for content negotiation
            return $this->handleShowResponse(
                $request,
                $chargingPoint,
                'charging-points.show', // Blade view for HTML
                ChargingPointResource::class, // API Resource for JSON
                ['qrCodeUrl' => $qrCodeUrl] // Additional data for the view
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to view this charging point.'], 403);
            } else {
                return redirect()->route('charging-points.index')
                    ->with('error', 'You are not authorized to view this charging point.');
            }
        } catch (\Exception $e) {
            Log::error('Error showing charging point: ' . $e->getMessage());

            // Handle exceptions based on request format
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to load charging point details.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()->route('charging-points.index')
                    ->with('error', 'Unable to load charging point details.');
            }
        }
    }

    /**
     * Show the form for editing the specified charging point.
     * This method is primarily for web requests.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function edit(Request $request, int $id)
    {
        // Although edit is primarily for web, we should still consider API requests
        // and return an appropriate response (e.g., 405 Method Not Allowed or a JSON error)
        // if an API client tries to access this route.
        // For now, we'll assume it's a web-only route based on typical usage.
        if ($request->expectsJson()) {
             return response()->json(['message' => 'Method Not Allowed'], 405);
        }

        try {
            // Delegate data fetching to the service layer
            $chargingPoint = $this->chargingPointService->findChargingPointWithRelations($id, [
                // Include relations needed for the edit form
                'group', 'partner', 'integrator', 'pricingPlan', 'connectors'
            ]);

            if (!$chargingPoint) {
                return redirect()->route('charging-points.index')
                    ->with('error', 'Charging point not found.');
            }

            // Authorization check
            $this->authorize('update', $chargingPoint);

            // Delegate form data fetching to the service if needed, or prepare data here
            $data = $this->chargingPointService->getEditFormData($chargingPoint, $request->user()); // Assuming getEditFormData exists in service

            return view('charging-points.edit', $data);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('charging-points.index')
                ->with('error', 'You are not authorized to edit this charging point.');
        } catch (\Exception $e) {
            Log::error('Error loading edit form: ' . $e->getMessage());

            return redirect()->route('charging-points.index')
                ->with('error', 'Unable to load edit form.');
        }
    }

    /**
     * Update the specified charging point.
     * Handles both web (HTML) and API (JSON) requests.
     *
     * @param ChargingPointUpdateRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function update(ChargingPointUpdateRequest $request, int $id)
    {
        try {
            // Find the charging point first
            $chargingPoint = $this->chargingPointService->findChargingPointWithRelations($id);

            if (!$chargingPoint) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Charging point not found.'], 404);
                } else {
                    return redirect()->route('charging-points.index')
                        ->with('error', 'Charging point not found.');
                }
            }

            // Authorization check
            $this->authorize('update', $chargingPoint);

            $validatedData = $request->validated();

            // Delegate update logic to the service layer
            $updatedChargingPoint = $this->chargingPointService->updateChargingPoint($chargingPoint, $validatedData);

            // Handle response based on request format
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Charging point updated successfully.',
                    'data' => new ChargingPointResource($updatedChargingPoint)
                ]);
            } else {
                return redirect()
                    ->route('charging-points.show', ['chargingPoint' => $updatedChargingPoint->id])
                    ->with('success', 'Charging point updated successfully.');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to update this charging point.'], 403);
            } else {
                return redirect()->route('charging-points.index')
                    ->with('error', 'You are not authorized to update this charging point.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating charging point: ' . $e->getMessage());

            // Handle exceptions based on request format
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error updating charging point.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Error updating charging point.')
                    ->withInput();
            }
        }
    }

    /**
     * Show the charging point's QR code.
     * This method is primarily for web requests.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function qrCode(Request $request, int $id)
    {
        // Although qrCode is primarily for web, consider API requests
        if ($request->expectsJson()) {
             return response()->json(['message' => 'Method Not Allowed'], 405);
        }

        try {
            // Delegate data fetching to the service layer
            $chargingPoint = $this->chargingPointService->findChargingPointWithRelations($id);

            if (!$chargingPoint) {
                return redirect()->route('charging-points.index')
                    ->with('error', 'Charging point not found.');
            }

            // Authorization check
            $this->authorize('view', $chargingPoint);

            // Delegate QR code generation/retrieval to the service layer
            // Assuming a method like getQrCodeUrl exists in the service
            $qrCodeService = app(QRCodeService::class);
            $qrCodeUrl = $qrCodeService->getQRCodeUrl($id); // Use the service to get the URL

            return view('charging-points.qr-code', compact('chargingPoint', 'qrCodeUrl'));

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('charging-points.index')
                ->with('error', 'You are not authorized to view this charging point\'s QR code.');
        } catch (\Exception $e) {
            Log::error('Error loading QR code for charging point ' . $id . ': ' . $e->getMessage());

            return redirect()->route('charging-points.show', $id)
                ->with('error', 'Unable to load QR code: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the status of a charging point.
     * Handles both web (HTML redirect) and API (JSON) requests.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function toggleStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:online,offline,maintenance,error'
        ]);

        try {
            // Find the charging point first
            $chargingPoint = $this->chargingPointService->findChargingPointWithRelations($id);

            if (!$chargingPoint) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Charging point not found.'], 404);
                } else {
                    return redirect()->route('charging-points.index')
                        ->with('error', 'Charging point not found.');
                }
            }

            // Authorization check
            $this->authorize('update', $chargingPoint); // Assuming 'update' policy covers status changes

            // Delegate status update logic to the service layer
            $updatedChargingPoint = $this->chargingPointService->updateChargingPointStatus($chargingPoint, $request->status); // Assuming updateChargingPointStatus exists in service

            // Handle response based on request format
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Status updated successfully.',
                    'data' => new ChargingPointResource($updatedChargingPoint)
                ]);
            } else {
                return redirect()
                    ->back()
                    ->with('success', 'Status updated successfully.');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to update the status of this charging point.'], 403);
            } else {
                return redirect()->back()
                    ->with('error', 'You are not authorized to update the status of this charging point.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating status for charging point ' . $id . ': ' . $e->getMessage());

            // Handle exceptions based on request format
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error updating status.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Error updating status.');
            }
        }
    }

    /**
     * Generate QR code for a specific charging point.
     * Handles both web (HTML redirect) and API (JSON) requests.
     *
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function generateQrCode(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            // Authorization check
            $this->authorize('update', $chargingPoint); // Assuming 'update' policy covers QR code generation

            $qrCodeService = app(QRCodeService::class);
            $result = $qrCodeService->generateAndSaveQRCode($chargingPoint->id);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'QR code generated successfully.',
                    'data' => $result
                ]);
            } else {
                return redirect()
                    ->back()
                    ->with('success', 'QR code generated successfully.');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to generate QR code for this charging point.'], 403);
            } else {
                return redirect()->back()
                    ->with('error', 'You are not authorized to generate QR code for this charging point.');
            }
        } catch (\App\Exceptions\ChargingPoint\ChargingPointNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Charging point not found.'], 404);
            } else {
                return redirect()->back()
                    ->with('error', 'Charging point not found.');
            }
        } catch (\App\Exceptions\QRCodeGenerationFailedException $e) {
            Log::error('Error generating QR code for charging point ' . $chargingPoint->id . ': ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to generate QR code.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to generate QR code: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Unexpected error generating QR code for charging point ' . $chargingPoint->id . ': ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'An unexpected error occurred.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()->back()
                    ->with('error', 'An unexpected error occurred: ' . $e->getMessage());
            }
        }
    }

    /**
     * Remove the specified charging point.
     * Handles both web (HTML) and API (JSON) requests.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        try {
            // Find the charging point first
            $chargingPoint = $this->chargingPointService->findChargingPointWithRelations($id);

            if (!$chargingPoint) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Charging point not found.'], 404);
                } else {
                    return redirect()->route('charging-points.index')
                        ->with('error', 'Charging point not found.');
                }
            }

            // Authorization check
            $this->authorize('delete', $chargingPoint);

            // Delegate deletion logic to the service layer
            $this->chargingPointService->deleteChargingPoint($chargingPoint);

            // Handle response based on request format
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Charging point deleted successfully.']);
            } else {
                return redirect()
                    ->route('charging-points.index')
                    ->with('success', 'Charging point deleted successfully.');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to delete this charging point.'], 403);
            } else {
                return redirect()->route('charging-points.index')
                    ->with('error', 'You are not authorized to delete this charging point.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting charging point: ' . $e->getMessage());

            // Handle exceptions based on request format
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error deleting charging point.', 'error' => $e->getMessage()], 500);
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Error deleting charging point: ' . $e->getMessage());
            }
        }
    }

    /**
     * Connect a charging point to SteVe server with a specific ID
     */
    public function connectToSteVe(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'charge_box_id' => 'required|string|max:255',
            'steve_server_url' => 'nullable|url',
        ]);

        try {
            // Default SteVe server URL if not provided
            $steveServerUrl = $request->steve_server_url ?? 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';
            
            // Generate the full WebSocket URL for the charging point
            $websocketUrl = $steveServerUrl . $request->charge_box_id;
            
            // Update the charging point with the connection information
            $chargingPoint->update([
                'charge_box_id' => $request->charge_box_id,
                'steve_server_url' => $steveServerUrl,
                'websocket_url' => $websocketUrl,
                'status' => 'connecting', // Set status to connecting
                'last_connection_attempt' => now(),
            ]);

            // Log the connection attempt
            Log::info("Charging point {$chargingPoint->id} connected to SteVe with charge_box_id: {$request->charge_box_id}");

            return response()->json([
                'success' => true,
                'message' => 'Borne connectée avec succès au serveur SteVe',
                'data' => [
                    'charge_box_id' => $request->charge_box_id,
                    'websocket_url' => $websocketUrl,
                    'charging_point' => $chargingPoint->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error connecting charging point to SteVe: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au serveur SteVe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connect a charging point by its ID with proper error handling
     */
    public function connectById(Request $request, int $chargingPointId)
    {
        try {
            // Validate the request
            $request->validate([
                'charging_point_id' => 'required|integer|min:1',
            ]);

            // Use the service to connect the charging point
            $chargingPoint = $this->chargingPointService->connectById($chargingPointId);

            return response()->json([
                'success' => true,
                'message' => 'Borne connectée avec succès',
                'data' => [
                    'charging_point' => $chargingPoint,
                    'status' => $chargingPoint->status,
                    'charge_box_id' => $chargingPoint->charge_box_id,
                    'websocket_url' => $chargingPoint->websocket_url
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error connecting charging point by ID: " . $e->getMessage(), [
                'charging_point_id' => $chargingPointId,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Test connection to SteVe server
     */
    public function testSteVeConnection(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'charge_box_id' => 'required|string|max:255',
        ]);

        try {
            // Test the WebSocket connection
            $steveServerUrl = 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';
            $websocketUrl = $steveServerUrl . $request->charge_box_id;
            
            // For now, we'll just validate the URL format and return success
            // In a real implementation, you would test the actual WebSocket connection
            
            return response()->json([
                'success' => true,
                'message' => 'Connexion testée avec succès',
                'data' => [
                    'charge_box_id' => $request->charge_box_id,
                    'websocket_url' => $websocketUrl,
                    'server_status' => 'available'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche le formulaire pour créer un point de charge directement sur Steve
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function createFromSteve(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Method Not Allowed'], 405);
        }

        return view('charging-points.create-steve');
    }

    /**
     * Crée un point de charge directement sur Steve via l'API REST
     *
     * @param Request $request
     * @param SteveService $steve
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function storeFromSteve(Request $request, SteveService $steve)
    {
        $request->validate([
            'chargeBoxId' => 'required|string|max:255',
            'endpointAddress' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Vérifier que c'est une adresse IP valide (pas une URL)
                    if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                        // Si ce n'est pas une IP, essayer d'extraire l'IP d'une URL
                        $parsed = parse_url($value);
                        if ($parsed && isset($parsed['host'])) {
                            $ip = $parsed['host'];
                            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                                $fail('L\'adresse du point de terminaison doit être une adresse IP valide (ex: 192.168.1.100).');
                            }
                        } else {
                            $fail('L\'adresse du point de terminaison doit être une adresse IP valide (ex: 192.168.1.100).');
                        }
                    }
                },
            ],
            'notes' => 'nullable|string|max:500',
        ]);

        // Extraire l'IP de l'endpointAddress si c'est une URL
        $endpointAddress = $request->endpointAddress;
        $parsed = parse_url($endpointAddress);
        if ($parsed && isset($parsed['host'])) {
            $endpointAddress = $parsed['host'];
        }

        $payload = [
            'chargeBoxId' => $request->chargeBoxId,
            'endpointAddress' => $endpointAddress,
            'notes' => $request->notes ?? 'Created from Laravel',
        ];

        // Créer sur l'API Steve
        $steveResponse = $steve->createChargePoint($payload);

        // Vérifier si la création a échoué
        // Une réponse null ou un tableau avec success=false indique un échec
        // Mais si on a un tableau avec success=true, c'est un succès même si d'autres champs sont vides
        $isFailure = false;
        
        if ($steveResponse === null) {
            $isFailure = true;
        } elseif (is_array($steveResponse)) {
            // Si success est explicitement false, c'est un échec
            if (isset($steveResponse['success']) && $steveResponse['success'] === false) {
                $isFailure = true;
            }
            // Si success est true ou n'est pas défini mais qu'on a un statut 200+, c'est un succès
            elseif (isset($steveResponse['success']) && $steveResponse['success'] === true) {
                $isFailure = false;
            } elseif (isset($steveResponse['status']) && in_array($steveResponse['status'], [200, 201, 204])) {
                $isFailure = false;
            } else {
                // Par défaut, si on a un tableau mais pas d'indicateur de succès, considérer comme échec
                $isFailure = true;
            }
        } else {
            // Si ce n'est ni null ni un tableau, c'est probablement un échec
            $isFailure = true;
        }

        if ($isFailure) {
            // Construire un message d'erreur détaillé
            $errorMessage = 'Échec de la création du point de charge sur l\'API Steve.';
            
            // Si on a des détails de l'erreur depuis l'API
            if (is_array($steveResponse) && isset($steveResponse['error'])) {
                $errorMessage .= ' Erreur de l\'API : ' . $steveResponse['error'];
                if (isset($steveResponse['status'])) {
                    $errorMessage .= ' (Code HTTP: ' . $steveResponse['status'] . ')';
                }
            } else {
                $errorMessage .= ' Vérifiez les logs pour plus de détails et assurez-vous que :';
                $errorMessage .= '<br>- L\'URL de base est correctement configurée (STEVE_API_URL)';
                $errorMessage .= '<br>- Les identifiants sont corrects (STEVE_API_USER / STEVE_API_PASS)';
                $errorMessage .= '<br>- L\'endpoint est accessible : ' . config('services.steve.url', 'non configuré');
            }
            
            Log::error('ChargingPointController: Failed to create charge point on Steve', [
                'payload' => $payload,
                'steveResponse' => $steveResponse,
                'config_url' => config('services.steve.url'),
                'config_user' => config('services.steve.user') ? 'configured' : 'not configured',
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'details' => is_array($steveResponse) ? $steveResponse : null
                ], 500);
            }
            return back()->with('error', $errorMessage)
                ->withInput();
        }

        // Sauvegarder dans la base de données locale
        try {
            // Récupérer le group_id de l'utilisateur ou utiliser celui fourni dans la requête
            $user = $request->user();
            $groupId = $request->input('group_id') ?? ($user ? $user->group_id : null);

            // Si aucun group_id n'est disponible, on ne peut pas créer le point de charge localement
            // mais on peut quand même retourner le succès de la création sur Steve
            if (!$groupId && !app()->environment('testing')) {
                Log::warning('Cannot save charging point to local DB: group_id is required', [
                    'steve_response' => $steveResponse
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Point de charge créé avec succès sur Steve, mais non sauvegardé localement (group_id requis).',
                        'steve_response' => $steveResponse,
                        'warning' => 'group_id is required to save locally'
                    ], 201);
                }

                return redirect()->route('charging-points.index')
                    ->with('warning', '✅ Point de charge créé sur Steve, mais non sauvegardé localement (group_id requis).');
            }

            $chargingPoint = ChargingPoint::create([
                'steve_charging_point_id' => $steveResponse['id'] ?? $request->chargeBoxId,
                'name' => $steveResponse['chargeBoxId'] ?? $request->chargeBoxId,
                'charge_box_id' => $steveResponse['chargeBoxId'] ?? $request->chargeBoxId,
                'ip_address' => $steveResponse['endpointAddress'] ?? $request->endpointAddress,
                'status' => $steveResponse['status'] ?? 'Unknown',
                'notes' => $request->notes,
                'group_id' => $groupId,
            ]);

            // Optionnel : Synchroniser automatiquement après création
            if (config('services.steve.auto_sync_after_create', false)) {
                try {
                    Artisan::call('steve:sync-charge-points');
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-sync charge points after creation: ' . $e->getMessage());
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Point de charge créé avec succès.',
                    'data' => [
                        'charging_point' => $chargingPoint,
                        'steve_response' => $steveResponse
                    ]
                ], 201);
            }

            return redirect()->route('charging-points.index')
                ->with('success', '✅ Point de charge créé avec succès sur Steve.');
        } catch (\Exception $e) {
            Log::error('Error saving charging point to local DB after Steve creation: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Point de charge créé sur Steve mais erreur lors de la sauvegarde locale.',
                    'steve_response' => $steveResponse
                ], 500);
            }

            return back()->with('warning', 'Point de charge créé sur Steve mais erreur lors de la sauvegarde locale.')
                ->withInput();
        }
    }

    // =========================================================================
    // MÉTHODES DE GESTION DES CONNECTEURS
    // =========================================================================

    /**
     * Récupère tous les connecteurs d'un point de charge avec leurs statuts.
     *
     * @param Request $request
     * @param int $chargingPointId
     * @return JsonResponse
     */
    public function getConnectors(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Vérifier les autorisations
            $this->authorize('view', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge non configuré pour SteVe (charge_box_id manquant)',
                ], 400);
            }

            $collection = $this->connectorService->getConnectorStatuses($chargeBoxId);

            return response()->json($collection->toApiResponse());

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error fetching connectors', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des connecteurs',
            ], 500);
        }
    }

    /**
     * Récupère le statut d'un connecteur spécifique.
     *
     * @param Request $request
     * @param int $chargingPointId
     * @param int $connectorId
     * @return JsonResponse
     */
    public function getConnectorStatus(Request $request, int $chargingPointId, int $connectorId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Vérifier les autorisations
            $this->authorize('view', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge non configuré pour SteVe',
                ], 400);
            }

            $status = $this->connectorService->getConnectorStatus($chargeBoxId, $connectorId);

            if ($status instanceof ConnectorErrorDTO) {
                return response()->json($status->toApiResponse(), 400);
            }

            return response()->json([
                'success' => true,
                'data' => $status->toArray(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error fetching connector status', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du statut du connecteur',
            ], 500);
        }
    }

    /**
     * Récupère le connecteur par défaut pour démarrer une recharge.
     *
     * @param Request $request
     * @param int $chargingPointId
     * @return JsonResponse
     */
    public function getDefaultConnector(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Vérifier les autorisations
            $this->authorize('view', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge non configuré pour SteVe',
                ], 400);
            }

            $connector = $this->connectorService->getDefaultConnector($chargeBoxId);

            if ($connector instanceof ConnectorErrorDTO) {
                return response()->json($connector->toApiResponse(), 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'connector' => $connector->toArray(),
                    'canStartSession' => $connector->canStartSession(),
                    'statusLabel' => $connector->getStatusLabel(),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error fetching default connector', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du connecteur par défaut',
            ], 500);
        }
    }

    /**
     * Vérifie si un connecteur peut démarrer une session de recharge.
     *
     * @param Request $request
     * @param int $chargingPointId
     * @param int $connectorId
     * @return JsonResponse
     */
    public function checkConnectorAvailability(Request $request, int $chargingPointId, int $connectorId = 1): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Vérifier les autorisations
            $this->authorize('view', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge non configuré pour SteVe',
                ], 400);
            }

            $result = $this->connectorService->canStartSession($chargeBoxId, $connectorId);

            return response()->json([
                'success' => true,
                'data' => [
                    'canStart' => $result['canStart'],
                    'reason' => $result['reason'],
                    'status' => isset($result['status']) ? $result['status']->toArray() : null,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error checking connector availability', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification de disponibilité',
            ], 500);
        }
    }

    /**
     * Synchronise les connecteurs d'un point de charge depuis SteVe.
     *
     * @param Request $request
     * @param int $chargingPointId
     * @return JsonResponse
     */
    public function syncConnectors(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Vérifier les autorisations (update car c'est une modification)
            $this->authorize('update', $chargingPoint);

            $result = $this->connectorService->syncConnectorsFromSteve($chargingPoint);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] 
                    ? "Synchronisation terminée : {$result['synced']}/{$result['total']} connecteurs mis à jour"
                    : $result['error'],
                'data' => $result,
            ], $result['success'] ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error syncing connectors', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la synchronisation des connecteurs',
            ], 500);
        }
    }

    /**
     * Déverrouille un connecteur.
     *
     * @param Request $request
     * @param int $chargingPointId
     * @param int $connectorId
     * @return JsonResponse
     */
    /**
     * POST /admin/charge-points/{id}/remote-start
     */
    public function remoteStart(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'connectorId' => 'required|integer',
            'idTag' => 'required|string',
        ]);

        $job = new OcppCommandJob(
            'remote-start',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'Remote start command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * POST /admin/charge-points/{id}/remote-stop
     */
    public function remoteStop(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'transactionId' => 'required|integer',
        ]);

        $job = new OcppCommandJob(
            'remote-stop',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'Remote stop command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * POST /admin/charge-points/{id}/reset
     */
    public function reset(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'type' => 'required|string|in:Soft,Hard',
        ]);

        if ($validated['type'] === 'Hard' && Gate::denies('admin')) {
            return response()->json(['message' => 'Unauthorized: Hard Reset is restricted to administrators.'], 403);
        }

        $job = new OcppCommandJob(
            'reset',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'Reset command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * POST /admin/charge-points/{id}/availability
     */
    public function availability(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'connectorId' => 'required|integer',
            'type' => 'required|string|in:Operative,Inoperative',
        ]);

        $job = new OcppCommandJob(
            'availability',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'Availability change command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * POST /admin/charge-points/{id}/unlock
     */
    public function unlock(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'connectorId' => 'required|integer',
        ]);

        $job = new OcppCommandJob(
            'unlock',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'Unlock connector command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * POST /admin/charge-points/{id}/reserve
     */
    public function reserve(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'connectorId' => 'required|integer',
            'expiryDate' => 'required|date',
            'idTag' => 'required|string',
            'reservationId' => 'required|integer',
        ]);

        $job = new OcppCommandJob(
            'reserve',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'ReserveNow command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * POST /admin/charge-points/{id}/cancel-reserve
     */
    public function cancelReserve(Request $request, int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);

        $validated = $request->validate([
            'reservationId' => 'required|integer',
        ]);

        $job = new OcppCommandJob(
            'cancel-reserve',
            $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id,
            $validated,
            auth()->id()
        );

        dispatch($job);

        return response()->json([
            'message' => 'CancelReservation command dispatched.',
            'command_id' => $job->commandRecord->id
        ], 202);
    }

    /**
     * GET /admin/charge-points/{id}/status
     */
    public function getStatus(int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('view', $chargingPoint);

        $service = app(\App\Services\SteveApiService::class);
        
        try {
            $status = $service->getConnectorStatus($chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id);
            $recentCommands = ChargePointCommand::where('charge_box_id', $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id)
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'status' => $status,
                'recent_commands' => $recentCommands,
                'local_status' => $chargingPoint->status,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'local_status' => $chargingPoint->status,
            ], 503);
        }
    }
}