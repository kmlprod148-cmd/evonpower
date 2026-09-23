<?php

namespace App\Http\Controllers;

use App\Models\CreditRequest;
use App\Models\ChargingPoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditRequestController extends Controller
{
    /**
     * Display a listing of credit requests
     * For clients: their own requests
     * For owners: requests from their clients
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Build query based on user role
        $query = CreditRequest::with(['client', 'owner', 'chargingPoint', 'reservation'])
            ->orderBy('created_at', 'desc');

        // If user is admin, they can see all requests for their clients
        if ($user->hasRole('admin')) {
            // Get all charging points owned by admin or their network
            $chargingPointIds = ChargingPoint::where('user_id', $user->id)
                ->orWhereHas('group', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->pluck('id');
            
            $query->where(function($q) use ($user, $chargingPointIds) {
                $q->where('owner_id', $user->id)
                  ->orWhere('client_id', $user->id)
                  ->orWhereIn('charging_point_id', $chargingPointIds);
            });
        } 
        // If user is integrator, operator, or partner
        elseif ($user->hasAnyRole(['integrator', 'operator', 'partner'])) {
            // Get charging points owned by this user
            $chargingPointIds = ChargingPoint::where('user_id', $user->id)
                ->pluck('id');
            
            $query->where(function($q) use ($user, $chargingPointIds) {
                $q->where('owner_id', $user->id)
                  ->orWhere('client_id', $user->id)
                  ->orWhereIn('charging_point_id', $chargingPointIds);
            });
        }
        // Regular client: only their own requests
        else {
            $query->where('client_id', $user->id);
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('owner', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $creditRequests = $query->paginate(15);

        // Get statistics
        $stats = [
            'total' => CreditRequest::where('owner_id', $user->id)->count(),
            'pending' => CreditRequest::where('owner_id', $user->id)->pending()->count(),
            'approved' => CreditRequest::where('owner_id', $user->id)->approved()->count(),
            'rejected' => CreditRequest::where('owner_id', $user->id)->rejected()->count(),
        ];

        return view('credit-requests.index', compact('creditRequests', 'stats'));
    }

    /**
     * Show the form for creating a new credit request (for clients)
     */
    public function create()
    {
        $user = auth()->user();
        
        // Get available charging point owners (from user's reservations)
        $owners = User::whereHas('chargingPoints.reservations', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->orWhereHas('groups.chargingPoints.reservations', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->distinct()
        ->get();

        // Get user's recent reservations
        $recentReservations = $user->reservations()
            ->with('chargingPoint.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('credit-requests.create', compact('owners', 'recentReservations'));
    }

    /**
     * Store a newly created credit request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'charging_point_id' => 'nullable|exists:charging_points,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'amount' => 'required|numeric|min:10|max:10000',
            'reason' => 'required|string|max:1000',
            'request_type' => 'in:reservation_based,manual',
        ]);

        $user = auth()->user();

        // Verify user can request from this owner
        // (must have made a reservation on owner's charging points)
        $hasReservation = $user->reservations()
            ->whereHas('chargingPoint', function($q) use ($validated) {
                $q->where('user_id', $validated['owner_id']);
            })
            ->exists();

        if (!$hasReservation && $user->id !== $validated['owner_id']) {
            return back()->with('error', __('Vous devez avoir fait au moins une réservation chez ce propriétaire.'));
        }

        try {
            $creditRequest = CreditRequest::create([
                'client_id' => $user->id,
                'owner_id' => $validated['owner_id'],
                'charging_point_id' => $validated['charging_point_id'] ?? null,
                'reservation_id' => $validated['reservation_id'] ?? null,
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
                'request_type' => $validated['request_type'] ?? 'manual',
                'status' => 'pending',
                'payment_method' => 'credit_request',
            ]);

            // Notify owner
            $owner = User::find($validated['owner_id']);
            $owner->notify(new \App\Notifications\NewCreditRequest($creditRequest));

            return redirect()->route('credit-requests.index')
                ->with('success', __('Demande de crédit envoyée avec succès !'));
                
        } catch (\Exception $e) {
            Log::error('Error creating credit request: ' . $e->getMessage());
            return back()->with('error', __('Erreur lors de la création de la demande.'));
        }
    }

    /**
     * Display the specified credit request
     */
    public function show(CreditRequest $creditRequest)
    {
        $user = auth()->user();

        // Check authorization
        if ($creditRequest->client_id !== $user->id && 
            $creditRequest->owner_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $creditRequest->load(['client', 'owner', 'chargingPoint', 'reservation']);

        return view('credit-requests.show', compact('creditRequest'));
    }

    /**
     * Approve a credit request (for owners)
     */
    public function approve(Request $request, CreditRequest $creditRequest)
    {
        $user = auth()->user();

        // Verify user is the owner or admin
        if ($creditRequest->owner_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $validated = $request->validate([
            'owner_response' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            if ($creditRequest->approve($validated['owner_response'] ?? null)) {
                DB::commit();
                return back()->with('success', __('Demande de crédit approuvée et crédit ajouté au compte client !'));
            } else {
                DB::rollBack();
                return back()->with('error', __('Impossible d\'approuver cette demande.'));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving credit request: ' . $e->getMessage());
            return back()->with('error', __('Erreur lors de l\'approbation de la demande.'));
        }
    }

    /**
     * Reject a credit request (for owners)
     */
    public function reject(Request $request, CreditRequest $creditRequest)
    {
        $user = auth()->user();

        // Verify user is the owner or admin
        if ($creditRequest->owner_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $validated = $request->validate([
            'owner_response' => 'required|string|max:1000',
        ]);

        try {
            if ($creditRequest->reject($validated['owner_response'])) {
                return back()->with('success', __('Demande de crédit rejetée.'));
            } else {
                return back()->with('error', __('Impossible de rejeter cette demande.'));
            }

        } catch (\Exception $e) {
            Log::error('Error rejecting credit request: ' . $e->getMessage());
            return back()->with('error', __('Erreur lors du rejet de la demande.'));
        }
    }

    /**
     * Cancel a credit request (for clients)
     */
    public function cancel(CreditRequest $creditRequest)
    {
        $user = auth()->user();

        // Verify user is the client
        if ($creditRequest->client_id !== $user->id) {
            abort(403, 'Accès non autorisé');
        }

        try {
            if ($creditRequest->cancel()) {
                return back()->with('success', __('Demande de crédit annulée.'));
            } else {
                return back()->with('error', __('Impossible d\'annuler cette demande.'));
            }

        } catch (\Exception $e) {
            Log::error('Error cancelling credit request: ' . $e->getMessage());
            return back()->with('error', __('Erreur lors de l\'annulation de la demande.'));
        }
    }

    /**
     * Get available owners for a client (AJAX)
     */
    public function getAvailableOwners()
    {
        $user = auth()->user();
        
        $owners = User::whereHas('chargingPoints.reservations', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->select('id', 'name', 'email')
        ->get();

        return response()->json($owners);
    }

    /**
     * Get charging points by owner (AJAX)
     */
    public function getOwnerChargingPoints($ownerId)
    {
        $user = auth()->user();
        
        $chargingPoints = ChargingPoint::where('user_id', $ownerId)
            ->whereHas('reservations', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->select('id', 'name', 'location')
            ->get();

        return response()->json($chargingPoints);
    }
}
