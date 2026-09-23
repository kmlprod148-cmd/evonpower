<?php

namespace App\Http\Controllers;

use App\Models\IntegratorProfile;
use Illuminate\Http\Request;

class IntegratorProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $integratorProfiles = IntegratorProfile::latest()->paginate(10);
        return view('integrator-profiles.index', compact('integratorProfiles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('integrator-profiles.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Add other validation rules as needed
        ]);

        $integratorProfile = IntegratorProfile::create($validated);

        return redirect()->route('integrator-profiles.index')
            ->with('success', 'Integrator Profile created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\IntegratorProfile  $integratorProfile
     * @return \Illuminate\Http\Response
     */
    public function show(IntegratorProfile $integratorProfile)
    {
        $this->authorize('view', $integratorProfile);
        return view('integrator-profiles.show', compact('integratorProfile'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\IntegratorProfile  $integratorProfile
     * @return \Illuminate\Http\Response
     */
    public function edit(IntegratorProfile $integratorProfile)
    {
        $this->authorize('update', $integratorProfile);
        return view('integrator-profiles.edit', compact('integratorProfile'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\IntegratorProfile  $integratorProfile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, IntegratorProfile $integratorProfile)
    {
        $this->authorize('update', $integratorProfile);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Add other validation rules as needed
        ]);

        $integratorProfile->update($validated);

        return redirect()->route('integrator-profiles.index')
            ->with('success', 'Integrator Profile updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\IntegratorProfile  $integratorProfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(IntegratorProfile $integratorProfile)
    {
        $integratorProfile->delete();

        return redirect()->route('integrator-profiles.index')
            ->with('success', 'Integrator Profile deleted successfully.');
    }
}