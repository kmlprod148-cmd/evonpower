<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = \App\Models\Report::paginate(10); // Fetch reports with pagination

        return view('reports.index', compact('reports'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Assuming you might need partners for the dropdown in the create view
        $partners = \App\Models\Partner::all();
        return view('reports.create', compact('partners'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:usage,financial,performance,custom',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'partner_id' => 'nullable|exists:partners,id',
            'status' => 'required|string|in:draft,scheduled,published',
            'recipients' => 'nullable|string',
            'include_charts' => 'boolean',
            'include_summary' => 'boolean',
            'auto_send' => 'boolean',
        ]);

        $report = new \App\Models\Report();
        $report->title = $validatedData['title'];
        $report->type = $validatedData['type'];
        $report->description = $validatedData['description'] ?? null;
        $report->start_date = $validatedData['start_date'] ?? null;
        $report->end_date = $validatedData['end_date'] ?? null;
        $report->partner_id = $validatedData['partner_id'] ?? null;
        $report->status = $validatedData['status'];
        $report->recipients = $validatedData['recipients'] ?? null;
        $report->include_charts = $validatedData['include_charts'] ?? false;
        $report->include_summary = $validatedData['include_summary'] ?? false;
        $report->auto_send = $validatedData['auto_send'] ?? false;
        $report->user_id = auth()->id(); // Assuming the authenticated user is creating the report

        $report->save();

        return redirect()->route('reports.index')->with('success', 'Rapport créé avec succès!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
