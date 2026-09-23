<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;
use App\Http\Requests\StoreStationRequest;
use App\Http\Requests\UpdateStationRequest;

class StationController extends Controller
{
    /**
     * Display a listing of the stations.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Station::with(['chargingPoints', 'group']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        $stations = $query->latest()->paginate(15);
        
        // Get groups for filter dropdown
        $groups = \App\Models\Group::select('id', 'name')->get();
        
        return view('stations.index', compact('stations', 'groups'));
    }

    /**
     * Show the form for creating a new station.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $groups = \App\Models\Group::select('id', 'name')->get();
        $availableChargingPoints = \App\Models\ChargingPoint::whereNull('station_id')->get();
        
        return view('stations.create', compact('groups', 'availableChargingPoints'));
    }

    /**
     * Store a newly created station in storage.
     *
     * @param  \App\Http\Requests\StoreStationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreStationRequest $request)
    {
        $validatedData = $request->validated();
        
        // Extract charging point IDs if provided
        $chargingPointIds = $validatedData['charging_point_ids'] ?? [];
        unset($validatedData['charging_point_ids']);
        
        // Create the station
        $station = Station::create($validatedData);
        
        // Assign charging points if provided
        if (!empty($chargingPointIds)) {
            \App\Models\ChargingPoint::whereIn('id', $chargingPointIds)
                ->update(['station_id' => $station->id]);
        }

        return redirect()->route('stations.show', $station)
            ->with('success', 'Station créée avec succès.');
    }

    /**
     * Store a station via AJAX request (for the modal form)
     *
     * @param  \App\Http\Requests\StoreStationRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAjax(StoreStationRequest $request)
    {
        $station = Station::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Station créée avec succès',
            'station' => $station
        ]);
    }


    /**
     * Display the specified station.
     *
     * @param  \App\Models\Station  $station
     * @return \Illuminate\View\View
     */
    public function show(Station $station)
    {
        $chargingPoints = $station->chargingPoints()->paginate(10);
        return view('stations.show', compact('station', 'chargingPoints'));
    }

    /**
     * Show the form for editing the specified station.
     *
     * @param  \App\Models\Station  $station
     * @return \Illuminate\View\View
     */
    public function edit(Station $station)
    {
        $groups = \App\Models\Group::select('id', 'name')->get();
        return view('stations.edit', compact('station', 'groups'));
    }

    /**
     * Update the specified station in storage.
     *
     * @param  \App\Http\Requests\UpdateStationRequest  $request
     * @param  \App\Models\Station  $station
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateStationRequest $request, Station $station)
    {
        $station->update($request->validated());

        return redirect()->route('stations.show', $station)
            ->with('success', 'Station mise à jour avec succès.');
    }

    /**
     * Remove the specified station from storage.
     *
     * @param  \App\Models\Station  $station
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Station $station)
    {
        // Check if there are charging points associated with this station
        if ($station->chargingPoints()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer cette station car elle contient des bornes de recharge.');
        }

        $station->delete();

        return redirect()->route('stations.index')
            ->with('success', 'Station supprimée avec succès.');
    }

    /**
     * Add charging points to a station
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Station  $station
     * @return \Illuminate\Http\JsonResponse
     */
    public function addChargingPoints(Request $request, Station $station)
    {
        $request->validate([
            'charging_point_ids' => 'required|array|max:2',
            'charging_point_ids.*' => 'exists:charging_points,id'
        ]);

        // Check if station already has charging points
        $currentCount = $station->chargingPoints()->count();
        $newCount = count($request->charging_point_ids);
        
        if ($currentCount + $newCount > 2) {
            return response()->json([
                'success' => false,
                'message' => 'Une station ne peut contenir que 2 points de charge maximum.'
            ], 422);
        }

        // Check if charging points are already assigned to other stations
        $alreadyAssigned = \App\Models\ChargingPoint::whereIn('id', $request->charging_point_ids)
            ->whereNotNull('station_id')
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'Un ou plusieurs points de charge sont déjà assignés à une autre station.'
            ], 422);
        }

        // Assign charging points to station
        \App\Models\ChargingPoint::whereIn('id', $request->charging_point_ids)
            ->update(['station_id' => $station->id]);

        return response()->json([
            'success' => true,
            'message' => 'Points de charge ajoutés avec succès.'
        ]);
    }

    /**
     * Remove charging point from station
     *
     * @param  \App\Models\Station  $station
     * @param  \App\Models\ChargingPoint  $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeChargingPoint(Station $station, \App\Models\ChargingPoint $chargingPoint)
    {
        if ($chargingPoint->station_id !== $station->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce point de charge n\'appartient pas à cette station.'
            ], 422);
        }

        $chargingPoint->update(['station_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Point de charge retiré avec succès.'
        ]);
    }

    /**
     * Get available charging points for assignment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableChargingPoints()
    {
        $chargingPoints = \App\Models\ChargingPoint::whereNull('station_id')
            ->select('id', 'name', 'power_output', 'status')
            ->get();

        return response()->json([
            'success' => true,
            'charging_points' => $chargingPoints
        ]);
    }

    /**
     * API endpoint to get all stations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStations()
    {
        $stations = Station::select('id', 'name', 'address', 'city')->get();

        return response()->json([
            'success' => true,
            'stations' => $stations
        ]);
    }
}