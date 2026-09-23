<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $financialTransactions = FinancialTransaction::with(['payerAccount.accountable', 'payeeAccount.accountable'])->paginate(10);
        return view('financial_transactions.index', compact('financialTransactions'));
    }

    /**
     * Display the specified resource.
     */
    public function show(FinancialTransaction $financialTransaction)
    {
        $financialTransaction->load(['payerAccount.accountable', 'payeeAccount.accountable']);
        return view('financial_transactions.show', compact('financialTransaction'));
    }

    // The 'create', 'store', 'edit', 'update', 'destroy' methods are not needed for this task
    // as financial transactions are created by the RevenueDistributionService.
    // They are kept as stubs for resource controller completeness.

    public function create()
    {
        // Not applicable for direct creation via UI
    }

    public function store(Request $request)
    {
        // Not applicable for direct creation via UI
    }

    public function edit(string $id)
    {
        // Not applicable for direct editing via UI
    }

    public function update(Request $request, string $id)
    {
        // Not applicable for direct updating via UI
    }

    public function destroy(string $id)
    {
        // Not applicable for direct deletion via UI
    }
}
