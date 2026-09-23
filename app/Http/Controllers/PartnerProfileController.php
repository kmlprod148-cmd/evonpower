<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Added Validator

class PartnerProfileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:partners',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'integrator_id' => 'nullable|exists:integrators,id', // Made nullable
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
        }

        try {
            DB::beginTransaction();

            $partner = new Partner();
            $partner->name = $request->input('name');
            $partner->email = $request->input('email');
            $partner->phone = $request->input('phone');
            $partner->address = $request->input('address');
            $partner->business_profile_id = $request->input('business_profile_id');

            // Use the provided integrator or use the first by default
            $partner->integrator_id = $request->input('integrator_id');

            // Si aucun intégrateur n'est fourni, utiliser le premier disponible
            if (empty($partner->integrator_id)) {
                $firstIntegrator = Integrator::first();
                if ($firstIntegrator) {
                    $partner->integrator_id = $firstIntegrator->id;
                } else {
                    // Si aucun intégrateur n'existe, lever une exception
                    throw new \Exception("Aucun intégrateur disponible dans le système. Veuillez créer un intégrateur avant d'ajouter un partenaire.");
                }
            }

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
                $partner->logo = $logoPath;
            }

            $partner->save();

            DB::commit();

            return redirect()->route('partners.index')->with('success', 'Partner created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating partner: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating partner: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $businessProfiles = BusinessProfile::all();
        $integrators = \App\Models\Integrator::orderBy('name')->get(); // Fetch integrators
        return view('partner-profile.create', compact('businessProfiles', 'integrators')); // Pass integrators to the view
    }

    // Add other methods like edit, update, destroy, etc. if needed for a full resource controller
}