<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;

class GroupViewController extends Controller
{
    public function index()
    {
        $groups = Group::paginate(15);
        \Log::info('Rendering groups.index view');
        return view('groups.index', compact('groups'));
    }

    public function show($id)
    {
        $group = Group::findOrFail($id);
        return view('groups.show', compact('group'));
    }
    
    // Add this missing method
    public function create()
    {
        $partners = $this->getAvailablePartnersForUser(auth()->user());
        return view('groups.create', compact('partners'));
    }

    /**
     * Show the form for editing the specified group.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $group = Group::findOrFail($id);
        $partners = $this->getAvailablePartnersForUser(auth()->user());
        return view('groups.edit', compact('group', 'partners'));
    }

    /**
     * Get available partners for the given user based on their role.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    private function getAvailablePartnersForUser(User $user)
    {
        if ($user->hasRole('admin')) {
            return Partner::where('is_active', true)->orderBy('name')->get();
        }

        if ($user->hasRole('integrator') && $user->integrator_id) {
            return Partner::where('integrator_id', $user->integrator_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        if ($user->partner_id) {
            return Partner::where('id', $user->partner_id)->get();
        }

        return collect();
    }
}