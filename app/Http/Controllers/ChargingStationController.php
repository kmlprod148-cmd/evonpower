<?php

namespace App\Http\Controllers;

use App\Models\ChargingStation;
use App\Models\Group;
use Illuminate\Http\Request;

class ChargingStationController extends Controller
{
    public function index()
    {
        $stations = ChargingStation::with(['group'])
            ->paginate(10);
            
        return view('stations.index', compact('stations'));
    }
    
    public function create()
    {
        $groups = Group::all();
        
        return view('stations.create', compact('groups'));
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', ChargingStation::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|unique:charging_stations',
            'group_id' => 'required|exists:station_groups,id',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:online,offline,maintenance',
        ]);
        
        ChargingStation::create($validated);
        
        return redirect()->route('stations.index')
            ->with('success', 'Charging station created successfully');
    }
    
    public function show(ChargingStation $station)
    {
        $this->authorize('view', $station);
        $station->load(['group', 'chargingPoints']);
        
        return view('stations.show', compact('station'));
    }
    
    public function edit(ChargingStation $station)
    {
        $this->authorize('update', $station);
        $groups = Group::all();
        
        return view('stations.edit', compact('station', 'groups'));
    }
    
    public function update(Request $request, ChargingStation $station)
    {
        $this->authorize('update', $station);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|unique:charging_stations,identifier,' . $station->id,
            'group_id' => 'required|exists:station_groups,id',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:online,offline,maintenance',
        ]);
        
        $station->update($validated);
        
        return redirect()->route('stations.index')
            ->with('success', 'Charging station updated successfully');
    }
    
    public function destroy(ChargingStation $station)
    {
        $this->authorize('delete', $station);
        $station->delete();
        
        return redirect()->route('stations.index')
            ->with('success', 'Charging station deleted successfully');
    }

    public function someMethod()
    {
        $station = ChargingStation::first(); // Or however you're getting the station
        return redirect()->route('stations.show', ['station' => $station->id]);
    }
}