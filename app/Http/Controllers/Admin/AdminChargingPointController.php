<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\PricingPlan;
use App\Models\User;
use App\Services\ChargingPointCrudService;
use App\Services\SteVeHttpClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin CRUD controller for Charging Points.
 *
 * Provides:
 *  - Full local CRUD (index / create / store / show / edit / update / destroy)
 *  - Steve registration sync on create / update / delete
 *  - Real-time status overlay via Steve API (JSON endpoint)
 *  - Steve-side listing (browse what Steve knows about)
 *  - Force-delete bypass for when Steve is unreachable
 *
 * Views live under resources/views/admin/charging-points/
 */
class AdminChargingPointController extends Controller
{
    public function __construct(
        private readonly ChargingPointCrudService $crudService,
        private readonly SteVeHttpClientService    $steve,
    ) {
        $this->middleware(['auth', 'role:admin|super_admin']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View
    {
        $query = ChargingPoint::with(['pricingPlan', 'partner', 'group', 'user', 'integrator'])
            ->orderByDesc('created_at');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }
        if ($request->filled('steve_synced')) {
            if ($request->steve_synced === '1') {
                $query->whereNotNull('steve_charge_box_pk');
            } elseif ($request->steve_synced === '0') {
                $query->whereNull('steve_charge_box_pk');
            }
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('charge_box_id', 'like', "%{$s}%")
                  ->orWhere('serial_number', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        $chargingPoints = $query->paginate(20)->withQueryString();

        // Sidebar filter options
        $partners = Partner::orderBy('name')->get(['id', 'name']);
        $groups   = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.charging-points.index', compact('chargingPoints', 'partners', 'groups'));
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    public function create(): View
    {
        $pricingPlans = PricingPlan::where('is_active', true)->orderBy('name')->get();
        $partners     = Partner::orderBy('name')->get();
        $groups       = Group::orderBy('name')->get();
        $users        = User::whereIn('role', ['operator', 'partner', 'integrator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.charging-points.create', compact('pricingPlans', 'partners', 'groups', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $result = $this->crudService->create($validated, Auth::user());

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create charging point: ' . ($result['error'] ?? $result['message']));
        }

        $cp      = $result['charging_point'];
        $message = $result['message'];

        if (!$result['steve_synced'] && $result['steve_error']) {
            $message .= ' (Steve warning: ' . $result['steve_error'] . ')';
        }

        return redirect()->route('admin.charging-points.show', $cp)
            ->with('success', $message);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(ChargingPoint $chargingPoint): View
    {
        $chargingPoint->load([
            'pricingPlan', 'partner', 'group', 'user', 'integrator',
            'connectors', 'transactions' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('admin.charging-points.show', compact('chargingPoint'));
    }

    // =========================================================================
    // EDIT / UPDATE
    // =========================================================================

    public function edit(ChargingPoint $chargingPoint): View
    {
        $pricingPlans = PricingPlan::where('is_active', true)->orderBy('name')->get();
        $partners     = Partner::orderBy('name')->get();
        $groups       = Group::orderBy('name')->get();
        $users        = User::whereIn('role', ['operator', 'partner', 'integrator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.charging-points.edit', compact('chargingPoint', 'pricingPlans', 'partners', 'groups', 'users'));
    }

    public function update(Request $request, ChargingPoint $chargingPoint): RedirectResponse
    {
        $validated = $request->validate($this->validationRules(isUpdate: true));

        $result = $this->crudService->update($chargingPoint, $validated);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update charging point: ' . ($result['error'] ?? $result['message']));
        }

        $message = $result['message'];
        if (!$result['steve_synced'] && !empty($result['steve_error'])) {
            $message .= ' (Steve warning: ' . $result['steve_error'] . ')';
        }

        return redirect()->route('admin.charging-points.show', $chargingPoint)
            ->with('success', $message);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function destroy(Request $request, ChargingPoint $chargingPoint): RedirectResponse
    {
        $force  = $request->boolean('force_delete', false);
        $result = $this->crudService->delete($chargingPoint, $force);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message'] ?? 'Failed to delete charging point.');
        }

        return redirect()->route('admin.charging-points.index')
            ->with('success', $result['message']);
    }

    // =========================================================================
    // REAL-TIME STATUS (JSON — used by AJAX on the show page)
    // GET /admin/charging-points/{chargingPoint}/realtime-status
    // =========================================================================

    public function realtimeStatus(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $connectorId = $request->filled('connector_id') ? (int) $request->connector_id : null;
        $idTag       = $request->input('id_tag') ?: null;

        $result = $this->crudService->getRealtimeStatusForModel($chargingPoint, $connectorId, $idTag);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 502);
    }

    // =========================================================================
    // STEVE SYNC — manual trigger
    // POST /admin/charging-points/{chargingPoint}/sync-steve
    // =========================================================================

    /**
     * Register (or re-register) a charging point on Steve.
     * Useful when auto-sync failed at creation time.
     */
    public function syncToSteve(ChargingPoint $chargingPoint): JsonResponse
    {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (empty($chargeBoxId)) {
            return response()->json([
                'success' => false,
                'message' => 'No chargeBoxId set on this charging point.',
            ], 400);
        }

        // Adopt an existing SteVe row first. This covers previous partial
        // attempts where SteVe inserted the chargeBoxId but the local row never
        // received the chargeBoxPk.
        $payload = $this->buildStevePayload($chargingPoint);
        $existing = $this->steve->findChargePointByChargeBoxId($chargeBoxId);
        $steveResult = $existing !== null
            ? [
                'success' => true,
                'data' => $existing,
                'message' => 'Existing SteVe charge point linked locally.',
            ]
            : $this->steve->createChargePoint($payload);

        if (!($steveResult['success'] ?? false)) {
            // If creation failed because another process inserted it after our
            // pre-flight check, adopt it before falling back to update-by-PK.
            $raced = $this->steve->findChargePointByChargeBoxId($chargeBoxId);
            if ($raced !== null) {
                $steveResult = [
                    'success' => true,
                    'data' => $raced,
                    'message' => 'Existing SteVe charge point linked locally.',
                ];
            } elseif ($chargingPoint->steve_charge_box_pk) {
                $steveResult = $this->steve->updateChargePoint(
                    $chargingPoint->steve_charge_box_pk,
                    $payload
                );
            }
        }

        if ($steveResult['success'] ?? false) {
            $pk = $steveResult['data']['chargeBoxPk'] ?? $steveResult['data']['chargePointPk'] ?? $steveResult['data']['id'] ?? null;
            $chargingPoint->updateQuietly([
                'steve_charge_box_pk'  => $pk ?? $chargingPoint->steve_charge_box_pk,
                'steve_provisioned_at' => now(),
                'charge_box_id' => $steveResult['data']['chargeBoxId'] ?? $chargingPoint->charge_box_id,
                'steve_charging_point_id' => $steveResult['data']['chargeBoxId'] ?? $chargingPoint->steve_charging_point_id,
            ]);
        }

        return response()->json($steveResult, ($steveResult['success'] ?? false) ? 200 : 502);
    }

    // =========================================================================
    // STEVE LIST — browse what Steve knows about
    // GET /admin/charging-points/steve-list
    // =========================================================================

    public function steveList(Request $request): JsonResponse
    {
        $filters = array_filter([
            'chargeBoxId' => $request->input('charge_box_id'),
            'description' => $request->input('description'),
        ]);

        $result = $this->crudService->listOnSteve($filters);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 502);
    }

    // =========================================================================
    // TOGGLE STATUS (local only — not a Steve call)
    // POST /admin/charging-points/{chargingPoint}/toggle-status
    // =========================================================================

    public function toggleStatus(ChargingPoint $chargingPoint): RedirectResponse
    {
        $newStatus = $chargingPoint->status === 'online' ? 'offline' : 'online';
        $chargingPoint->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Status set to {$newStatus}.");
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function validationRules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'name'            => "{$required}|string|max:255",
            'charge_box_id'   => 'nullable|string|max:100',
            'serial_number'   => 'nullable|string|max:100',
            'manufacturer'    => 'nullable|string|max:100',
            'model'           => 'nullable|string|max:100',
            'group_id'        => "{$required}|exists:groups,id",
            'partner_id'      => 'nullable|exists:partners,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'postal_code'     => 'nullable|string|max:20',
            'country'         => 'nullable|string|max:100',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'notes'           => 'nullable|string',
            'installation_notes' => 'nullable|string',
            'access_type'     => 'nullable|string|in:public,private,restricted',
            'public_access'   => 'boolean',
            'is_active'       => 'boolean',
            'communication_protocol' => 'nullable|string|max:50',
            'firmware_version' => 'nullable|string|max:50',
        ];
    }

    private function buildStevePayload(ChargingPoint $cp): array
    {
        $payload = [
            'chargeBoxId' => $cp->charge_box_id ?? $cp->steve_charging_point_id,
            'description' => $cp->name ?? 'Charging Point #' . $cp->id,
            'note'        => $cp->notes ?? $cp->installation_notes ?? null,
        ];

        if ($cp->latitude && $cp->longitude) {
            $payload['locationLatitude']  = (float) $cp->latitude;
            $payload['locationLongitude'] = (float) $cp->longitude;
        }

        if ($cp->address || $cp->city) {
            $payload['address'] = array_filter([
                'street'  => $cp->address,
                'zipCode' => $cp->postal_code,
                'city'    => $cp->city,
                'country' => $cp->country ?? 'UNDEFINED',
            ]);
        }

        return $payload;
    }
}
