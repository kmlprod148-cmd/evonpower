@extends('layouts.app')

@section('content')
<div class="withdrawal-requests-container">
    <div class="flex mb-6 border-b border-gray-200">
        <a href="{{ route('transactions.index') }}" class="text-gray-700 px-4 py-2">Transactions</a>
        <a href="{{ route('withdrawal-requests.index') }}" class="border-b-2 border-green-500 text-green-500 px-4 py-2 font-medium mr-4">Demande de retrait</a>
        <a href="{{ route('refunds.index') }}" class="text-gray-700 px-4 py-2">Remboursement</a>
    </div>
    
    <div class="mb-6">
        <h1 class="text-2xl font-medium mb-6">Demande de retrait</h1>
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
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" width="30" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TVA</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant TTC</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($withdrawalRequests ?? [] as $request)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $request->id ?? 'M16242FZYU61' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $request->type ?? 'Commission Opérateur (Partenaire)' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $request->details ?? 'Monthly subscription, Deleted Plan' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $request->tva ?? '132,00 EUR' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $request->amount ?? '432,00 EUR' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $status = $request->status ?? (rand(1,10) > 5 ? 'terminé' : (rand(1,5) > 3 ? 'eligible' : 'en_cours'));
                                $statusClass = [
                                    'terminé' => 'bg-green-100 text-green-800',
                                    'eligible' => 'bg-blue-100 text-blue-800',
                                    'en_cours' => 'bg-yellow-100 text-yellow-800'
                                ][$status] ?? 'bg-gray-100 text-gray-800';
                                $statusText = [
                                    'terminé' => 'Terminé',
                                    'eligible' => 'Éligible',
                                    'en_cours' => 'En cours'
                                ][$status] ?? 'Inconnu';
                            @endphp
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('withdrawal-requests.show', $request ?? 1) }}" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    @for($i = 0; $i < 11; $i++)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">M16242FZYU61</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Commission Opérateur (Partenaire)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Monthly subscription, Deleted Plan</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">132,00 EUR</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">432,00 EUR</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusOptions = [
                                    'Terminé' => 'bg-green-100 text-green-800',
                                    'Éligible' => 'bg-blue-100 text-blue-800',
                                    'En cours' => 'bg-yellow-100 text-yellow-800'
                                ];
                                if ($i % 4 == 0 || $i % 5 == 0) {
                                    $status = 'Terminé';
                                } elseif ($i % 3 == 0) {
                                    $status = 'En cours';
                                } else {
                                    $status = 'Éligible';
                                }
                            @endphp
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusOptions[$status] }}">
                                {{ $status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endfor
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-between items-center mt-6">
            <div class="text-sm text-gray-700">
                Page 1 | 8 of 14
            </div>
            <div class="flex space-x-2">
                <button class="h-8 w-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm">1</button>
                <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">2</button>
                <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">...</button>
                <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">3</button>
                <button class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-sm">&gt;</button>
            </div>
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