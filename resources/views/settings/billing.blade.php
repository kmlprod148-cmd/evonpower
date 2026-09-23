@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <div class="text-gray-500 text-sm mb-1">Paramètres > Facturation</div>
        <h1 class="text-2xl font-medium">Paramètres de facturation</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Current Plan Section -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium mb-4">Formule actuelle</h2>
                    
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-medium text-blue-800">{{ $subscription->plan->name ?? 'Formule standard' }}</h3>
                                <p class="text-blue-600 mt-1">{{ $subscription->plan->description ?? 'Accès à toutes les fonctionnalités de base.' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-blue-800">{{ $subscription->plan->price ?? '299,99' }} EUR<span class="text-sm font-medium">/mois</span></p>
                                <p class="text-blue-600 text-sm">Prochaine facturation: {{ $subscription->next_billing_date ?? now()->addMonth()->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex">
                            <a href="{{ route('plans.index') }}" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Modifier ma formule
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h3 class="text-md font-medium mb-2">Fonctionnalités incluses</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $subscription->plan->feature1 ?? 'Gestion de 10 stations de recharge' }}</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $subscription->plan->feature2 ?? 'Rapports mensuels d\'utilisation' }}</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $subscription->plan->feature3 ?? 'Support client par email' }}</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $subscription->plan->feature4 ?? 'Accès au tableau de bord' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Payment Method Section -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Méthode de paiement</h2>
                        <button type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            + Ajouter une carte
                        </button>
                    </div>
                    
                    @if($paymentMethods ?? false)
                        <div class="space-y-4">
                            @foreach($paymentMethods as $method)
                                <div class="flex justify-between items-center p-4 border rounded-lg {{ $method->default ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                    <div class="flex items-center">
                                        @if($method->type === 'card')
                                            <svg class="h-8 w-8 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium">{{ $method->brand }} •••• {{ $method->last4 }}</p>
                                                <p class="text-sm text-gray-500">Expire le {{ $method->exp_month }}/{{ $method->exp_year }}</p>
                                            </div>
                                        @else
                                            <svg class="h-8 w-8 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium">Prélèvement automatique</p>
                                                <p class="text-sm text-gray-500">{{ $method->account_number }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        @if($method->default)
                                            <span class="mr-4 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Par défaut</span>
                                        @else
                                            <form action="{{ route('settings.billing.default', $method->id) }}" method="POST" class="mr-4">
                                                @csrf
                                                <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">Définir par défaut</button>
                                            </form>
                                        @endif
                                        <button type="button" class="text-gray-400 hover:text-gray-500">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 border border-dashed border-gray-300 rounded-lg text-center">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <p class="text-gray-500 mb-2">Aucune méthode de paiement enregistrée</p>
                            <button type="button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Ajouter une méthode de paiement
                            </button>
                        </div>
                    @endif
                </div>
                
                <!-- Billing History Section -->
                <div class="p-6">
                    <h2 class="text-lg font-medium mb-4">Historique de facturation</h2>
                    
                    @if($invoices ?? false)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facture</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($invoices as $invoice)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $invoice->date->format('d/m/Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $invoice->amount }} EUR</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($invoice->status === 'paid')
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Payée
                                                    </span>
                                                @elseif($invoice->status === 'pending')
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        En attente
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Échec
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ $invoice->pdf_url ?? '#' }}" class="text-blue-600 hover:text-blue-900">Télécharger</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center p-6 text-gray-500">
                            Aucune facture disponible pour le moment.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Billing Information Section -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden h-full">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Informations de facturation</h2>
                        <button type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Modifier
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Société</h3>
                            <p>{{ $billingInfo->company_name ?? auth()->user()->company_name ?? 'Non spécifié' }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Adresse</h3>
                            <p>{{ $billingInfo->address_line1 ?? 'Non spécifié' }}</p>
                            @if($billingInfo->address_line2 ?? false)
                                <p>{{ $billingInfo->address_line2 }}</p>
                            @endif
                            <p>{{ ($billingInfo->postal_code ?? '') . ' ' . ($billingInfo->city ?? '') }}</p>
                            <p>{{ $billingInfo->country ?? 'Maroc' }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Identifiant fiscal</h3>
                            <p>{{ $billingInfo->tax_id ?? 'Non spécifié' }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Contact de facturation</h3>
                            <p>{{ $billingInfo->contact_name ?? auth()->user()->name ?? 'Non spécifié' }}</p>
                            <p>{{ $billingInfo->contact_email ?? auth()->user()->email ?? 'Non spécifié' }}</p>
                            <p>{{ $billingInfo->contact_phone ?? auth()->user()->phone ?? 'Non spécifié' }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <h2 class="text-lg font-medium mb-4">Besoin d'aide?</h2>
                    
                    <div class="space-y-4">
                        <a href="#" class="block text-blue-600 hover:text-blue-800">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>FAQ sur la facturation</span>
                            </div>
                        </a>
                        
                        <a href="#" class="block text-blue-600 hover:text-blue-800">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>Contacter le service client</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection