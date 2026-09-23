@extends('layouts.app')

@section('content')
<div class="refunds-container">
    <div class="flex mb-6 border-b border-gray-200">
        <a href="{{ route('transactions.index') }}" class="text-gray-700 px-4 py-2">Transactions</a>
        <a href="{{ route('withdrawal-requests.index') }}" class="text-gray-700 px-4 py-2">Demande de retrait</a>
        <a href="{{ route('refunds.index') }}" class="border-b-2 border-green-500 text-green-500 px-4 py-2 font-medium mr-4">Remboursement</a>
    </div>
    
    <div class="mb-6">
        <h1 class="text-2xl font-medium mb-6">Remboursement</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="relative w-80">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            
            <button class="flex items-center bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium">
                <span class="mr-2">Filtrer</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </button>
        </div>
        
        <!-- Sample content for now -->
        <div class="text-center py-10 text-gray-500">
            <p class="text-xl font-medium">Aucun remboursement disponible</p>
            <p class="mt-2">Les remboursements apparaîtront ici.</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background-color: #f9fafb;
    }
</style>
@endpush