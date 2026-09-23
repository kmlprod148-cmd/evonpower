<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\PDF;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $reports = Report::latest()->paginate(10);
        return view('reports.index', compact('reports'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('reports.create');
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $report = new Report();
        $report->title = $validated['title'];
        $report->description = $validated['description'] ?? null;
        $report->period_start = $validated['period_start'];
        $report->period_end = $validated['period_end'];
        $report->status = $validated['status'];
        $report->user_id = Auth::id();
        $report->save();

        return redirect()->route('reports.index')
            ->with('success', 'Rapport créé avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function show(Report $report)
    {
        return view('reports.show', compact('report'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function edit(Report $report)
    {
        return view('reports.edit', compact('report'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $report->title = $validated['title'];
        $report->description = $validated['description'] ?? null;
        $report->period_start = $validated['period_start'];
        $report->period_end = $validated['period_end'];
        $report->status = $validated['status'];
        $report->save();

        return redirect()->route('reports.index')
            ->with('success', 'Rapport mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Rapport supprimé avec succès.');
    }

    /**
     * Generate a PDF of the report.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Report $report)
    {
    // Generate HTML content
    $html = view('reports.pdf', compact('report'))->render();
    
    // For now, return a simple HTML response
    return response($html)->header('Content-Type', 'text/html')
        ->header('Content-Disposition', 'attachment; filename="rapport-' . $report->id . '.html"');
    }

    /**
     * Export the report data to CSV.
     *
     * @param  \App\Models\Report  $report
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCsv(Report $report)
    {
        $filename = 'rapport-' . $report->id . '-' . date('Ymd') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        // Create the CSV content
        $callback = function() use ($report) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper encoding in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, ['ID', 'Titre', 'Description', 'Période début', 'Période fin', 'Statut', 'Créé le', 'Mis à jour le']);
            
            // Add report data
            fputcsv($file, [
                $report->id,
                $report->title,
                $report->description,
                $report->period_start,
                $report->period_end,
                $report->status,
                $report->created_at,
                $report->updated_at
            ]);
            
            // You can add more data rows here as needed
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}