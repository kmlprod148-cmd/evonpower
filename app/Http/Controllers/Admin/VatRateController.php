<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VatRate;
use Illuminate\Http\Request;

class VatRateController extends Controller
{
    public function index()
    {
        $vatRates = VatRate::orderBy('rate')->get();
        return view('admin.vat_rates.index', compact('vatRates'));
    }

    public function create()
    {
        return view('admin.vat_rates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        
        // Si ce taux est défini comme défaut, mettre tous les autres à false
        if ($request->has('is_default') && $request->is_default) {
            VatRate::where('is_default', true)->update(['is_default' => false]);
        }

        VatRate::create($data);

        return redirect()->route('admin.vat_rates.index')
            ->with('success', 'Taux de TVA ajouté avec succès.');
    }

    public function edit(VatRate $vatRate)
    {
        return view('admin.vat_rates.edit', compact('vatRate'));
    }

    public function update(Request $request, VatRate $vatRate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        
        // Si ce taux est défini comme défaut, mettre tous les autres à false
        if ($request->has('is_default') && $request->is_default) {
            VatRate::where('id', '!=', $vatRate->id)
                  ->where('is_default', true)
                  ->update(['is_default' => false]);
        }

        $vatRate->update($data);

        return redirect()->route('admin.vat_rates.index')
            ->with('success', 'Taux de TVA mis à jour avec succès.');
    }

    public function destroy(VatRate $vatRate)
    {
        // Vérifier si c'est le taux par défaut
        if ($vatRate->is_default) {
            return redirect()->route('admin.vat_rates.index')
                ->with('error', 'Impossible de supprimer le taux de TVA par défaut.');
        }

        $vatRate->delete();

        return redirect()->route('admin.vat_rates.index')
            ->with('success', 'Taux de TVA supprimé avec succès.');
    }
}