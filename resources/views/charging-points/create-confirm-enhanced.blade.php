@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-900/10">
    @include('charging-points.partials._creation-header', [
        'title' => 'Nouveau point de charge',
        'currentStep' => 4,
        'totalSteps' => 4
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 4])

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Main Content - Left Side (75%) -->
            <div class="lg:col-span-9">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Confirmation</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Vérifiez les informations avant de créer le point de charge</p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Display -->
                    @if ($errors->any())
                        <div class="mx-6 mt-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
                                        Erreurs détectées :
                                    </h3>
                                    <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li class="flex items-center gap-2">
                                                <i class="fas fa-dot-circle text-xs"></i>
                                                {{ $error }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Summary Content -->
                    <div class="p-6 space-y-8">
                        <!-- General Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-info-circle text-blue-500"></i>
                                Informations générales
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-tag text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Nom</span>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $allData['name'] ?? 'Non défini' }}</p>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-barcode text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Numéro de série</span>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $allData['serial_number'] ?? 'Non défini' }}</p>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-industry text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Fabricant</span>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $allData['manufacturer'] ?? 'Non défini' }}</p>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-cube text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Modèle</span>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $allData['model'] ?? 'Non défini' }}</p>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-power-off text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Statut initial</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @php
                                            $status = $allData['status'] ?? 'offline';
                                            $statusColors = [
                                                'online' => 'text-green-600 bg-green-100 dark:bg-green-900/20',
                                                'offline' => 'text-gray-600 bg-gray-100 dark:bg-gray-800',
                                                'maintenance' => 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/20'
                                            ];
                                            $statusLabels = [
                                                'online' => 'En ligne',
                                                'offline' => 'Hors ligne',
                                                'maintenance' => 'Maintenance'
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? $statusColors['offline'] }}">
                                            {{ $statusLabels[$status] ?? 'Hors ligne' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-map-marker-alt text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Localisation</span>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $allData['location'] ?? 'Localisation non définie' }}
                                        @if(isset($allData['latitude']) && isset($allData['longitude']))
                                            <br><span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ number_format($allData['latitude'], 4) }}, {{ number_format($allData['longitude'], 4) }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Operator and Business Profile -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-users text-blue-500"></i>
                                Opérateur et Business Profile
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-user-tie text-blue-600"></i>
                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Opérateur</span>
                                    </div>
                                    <p class="font-semibold text-blue-900 dark:text-blue-100">
                                        {{ $operator->name ?? 'Non défini' }}
                                    </p>
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-briefcase text-green-600"></i>
                                        <span class="text-sm font-medium text-green-800 dark:text-green-200">Business Profile</span>
                                    </div>
                                    <p class="font-semibold text-green-900 dark:text-green-100">
                                        {{ $businessProfile->name ?? 'Non défini' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Specifications -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-cogs text-purple-500"></i>
                                Spécifications techniques
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-bolt text-yellow-600"></i>
                                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Puissance</span>
                                    </div>
                                    <p class="font-bold text-xl text-yellow-900 dark:text-yellow-100">
                                        {{ $allData['power_output'] ?? '0' }} kW
                                    </p>
                                    @php
                                        $power = floatval($allData['power_output'] ?? 0);
                                        $powerCategory = '';
                                        if ($power <= 3.7) $powerCategory = 'Recharge lente';
                                        elseif ($power <= 22) $powerCategory = 'Recharge normale';
                                        elseif ($power <= 50) $powerCategory = 'Recharge semi-rapide';
                                        else $powerCategory = 'Recharge rapide';
                                    @endphp
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">{{ $powerCategory }}</p>
                                </div>

                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-plug text-blue-600"></i>
                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Connecteur</span>
                                    </div>
                                    <p class="font-semibold text-blue-900 dark:text-blue-100">
                                        {{ $allData['connector_type'] ?? 'Non défini' }}
                                    </p>
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-wifi text-green-600"></i>
                                        <span class="text-sm font-medium text-green-800 dark:text-green-200">Connexion</span>
                                    </div>
                                    <p class="font-semibold text-green-900 dark:text-green-100">
                                        {{ $allData['connection_type'] ?? 'Non défini' }}
                                    </p>
                                </div>

                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-comments text-purple-600"></i>
                                        <span class="text-sm font-medium text-purple-800 dark:text-purple-200">Protocole</span>
                                    </div>
                                    <p class="font-semibold text-purple-900 dark:text-purple-100">
                                        {{ $allData['communication_protocol'] ?? 'Non défini' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Connectors -->
                        @if(isset($allData['connectors']) && is_array($allData['connectors']))
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-plug text-orange-500"></i>
                                    Connecteurs
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($allData['connectors'] as $index => $connector)
                                        <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-800">
                                            <div class="flex items-center gap-2 mb-2">
                                                <i class="fas fa-plug text-orange-600"></i>
                                                <span class="text-sm font-medium text-orange-800 dark:text-orange-200">Connecteur {{ $index + 1 }}</span>
                                            </div>
                                            <div class="space-y-1 text-sm">
                                                <p class="font-semibold text-orange-900 dark:text-orange-100">
                                                    Type: {{ $connector['type'] ?? 'Non défini' }}
                                                </p>
                                                <p class="text-orange-700 dark:text-orange-300">
                                                    Puissance: {{ $connector['power'] ?? '0' }} kW
                                                </p>
                                                <p class="text-orange-700 dark:text-orange-300">
                                                    Statut: {{ ucfirst($connector['status'] ?? 'Non défini') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Pricing Plan -->
                        @if(isset($allData['pricing_plan_id']) && $allData['pricing_plan_id'])
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-euro-sign text-green-500"></i>
                                    Plan tarifaire
                                </h3>
                                
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-tags text-green-600"></i>
                                        <span class="text-sm font-medium text-green-800 dark:text-green-200">Plan sélectionné</span>
                                    </div>
                                    <p class="font-semibold text-green-900 dark:text-green-100">
                                        {{ $pricingPlan->name ?? 'Plan ID: ' . $allData['pricing_plan_id'] }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Access Configuration -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-shield-alt text-blue-500"></i>
                                Configuration d'accès
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-key text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Type d'accès</span>
                                    </div>
                                    @php
                                        $accessType = $allData['access_type'] ?? 'public';
                                        $accessLabels = [
                                            'public' => 'Public',
                                            'private' => 'Privé',
                                            'restricted' => 'Accès restreint'
                                        ];
                                        $accessColors = [
                                            'public' => 'text-green-600 bg-green-100 dark:bg-green-900/20',
                                            'private' => 'text-red-600 bg-red-100 dark:bg-red-900/20',
                                            'restricted' => 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/20'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $accessColors[$accessType] ?? $accessColors['public'] }}">
                                        {{ $accessLabels[$accessType] ?? 'Public' }}
                                    </span>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-user-check text-emerald-500"></i>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Authentification</span>
                                    </div>
                                    @php
                                        $authRequired = ($allData['authentication_required'] ?? '0') == '1';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $authRequired ? 'text-orange-600 bg-orange-100 dark:bg-orange-900/20' : 'text-gray-600 bg-gray-100 dark:bg-gray-800' }}">
                                        {{ $authRequired ? 'Requise' : 'Non requise' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <form action="{{ route('charging-points.create.store.final') }}" method="POST" class="pt-6 border-t border-gray-200 dark:border-gray-700">
                            @csrf
                            <div class="flex items-center justify-between">
                                <a href="{{ route('charging-points.create.step3') }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-all duration-200">
                                    <i class="fas fa-arrow-left"></i>
                                    Précédent
                                </a>
                                
                                <button type="submit" 
                                        class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-plus-circle"></i>
                                    Créer le point de charge
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar - Right Side (25%) -->
            <div class="lg:col-span-3 order-first lg:order-last">
                <!-- Progress Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-flag-checkered text-emerald-500"></i>
                        Finalisation
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Étape finale</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">4 / 4</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">✓ Configuration terminée</p>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-2xl border border-emerald-200/50 dark:border-emerald-800/50 p-6">
                    <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-emerald-500"></i>
                        Récapitulatif
                    </h3>
                    <div class="space-y-3 text-sm text-emerald-800 dark:text-emerald-200">
                        <div class="flex items-center justify-between">
                            <span>Nom:</span>
                            <span class="font-semibold">{{ Str::limit($allData['name'] ?? 'N/A', 20) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Opérateur:</span>
                            <span class="font-semibold">{{ Str::limit($operator->name ?? 'N/A', 15) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Business Profile:</span>
                            <span class="font-semibold">{{ Str::limit($businessProfile->name ?? 'N/A', 15) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Puissance:</span>
                            <span class="font-semibold">{{ $allData['power_output'] ?? '0' }} kW</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Connecteurs:</span>
                            <span class="font-semibold">{{ count($allData['connectors'] ?? []) }} configuré(s)</span>
                        </div>
                        <div class="pt-2 border-t border-emerald-200 dark:border-emerald-700">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">
                                <i class="fas fa-info-circle"></i>
                                Toutes les informations peuvent être modifiées après création.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Add loading state to submit button
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="create.store"]');
    const submitBtn = form?.querySelector('button[type="submit"]');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création en cours...';
        });
    }
});
</script>
@endsection
