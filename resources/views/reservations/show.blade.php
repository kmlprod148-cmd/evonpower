@extends('layouts.app')

@section('title', __('messages.reservation_details') . ' #' . $reservation->id)
@section('page-title', __('messages.reservation_details') . ' #' . $reservation->id)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-primary-100 dark:bg-primary-900/30 rounded-lg p-3">
                        <i class="fas fa-file-invoice text-2xl text-primary-600 dark:text-primary-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('messages.reservation_details') }} #{{ $reservation->id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $reservation->chargingPoint->name ?? 'Borne inconnue' }} - {{ $reservation->start_time ? $reservation->start_time->format('d/m/Y H:i') : 'N/A' }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @php
                    $statusValue = $reservation->status?->value ?? $reservation->status ?? '';
                    $statusLabel = $reservation->getDisplayStatusLabel();
                    $statusColor = ($reservation->isPaid() && in_array($statusValue, ['pending', 'pending_confirmation'], true))
                        ? 'success'
                        : ($reservation->status ? $reservation->status->color() : 'gray');
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900 dark:text-{{ $statusColor }}-200">
                    <i class="fas fa-circle text-xs mr-1.5 animate-pulse"></i>
                    {{ $statusLabel }}
                </span>
            </div>
        </div>
    </div>
    <!-- Alert Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700 dark:text-green-300 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($reservation->isPaidByBalance())
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-wallet text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-semibold text-green-800 dark:text-green-200">Paiement par solde effectué</p>
                    <p class="text-xs text-green-700 dark:text-green-300 mt-1">Réservation et transaction confirmées • Montant débité de votre solde</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <!-- Reservation Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-map-marker-alt text-primary-600 dark:text-primary-400 mr-2"></i>
                        {{ __('messages.station_information') }}
                    </h3>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Station:</span> {{ $reservation->chargingPoint->station->location ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">{{ __('messages.charging_point') }}:</span> {{ $reservation->chargingPoint->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Plan Tarifaire:</span> {{ $reservation->tariffPlan->name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-clock text-primary-600 dark:text-primary-400 mr-2"></i>
                        {{ __('messages.reservation_details') }}
                    </h3>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Début:</span> {{ $reservation->start_time->format('d/m/Y H:i') }}
                        </p>
                        @if($reservation->end_time)
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Fin:</span> {{ $reservation->end_time->format('d/m/Y H:i') }}
                            </p>
                        @endif
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Durée Estimée:</span> {{ $reservation->estimated_duration ?? 'N/A' }} minutes
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Énergie Estimée:</span> {{ $reservation->estimated_energy ?? 'N/A' }} kWh
                        </p>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-gray-200 dark:border-gray-700">
            
            <!-- Financial Information -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-wallet text-primary-600 dark:text-primary-400 mr-2"></i>
                    {{ __('messages.financial_info') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('messages.costs_and_tariffs') }}</h4>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Montant Payé:</span> €{{ number_format($reservation->amount, 2) }}
                            </p>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <x-reservation-amounts :reservation="$reservation" :showBreakdown="true" size="default" />
                            </div>
                            @if($reservation->tariffPlan)
                                @if($reservation->tariffPlan->activation_fee > 0)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Frais d'activation:</span> €{{ number_format($reservation->tariffPlan->activation_fee, 2) }}
                                    </p>
                                @endif
                                @if($reservation->tariffPlan->base_rate > 0)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Tarif de base:</span> €{{ number_format($reservation->tariffPlan->base_rate, 2) }}
                                    </p>
                                @endif
                                @if($reservation->reservation_type === 'kwh' && $reservation->tariffPlan->price_per_kwh > 0)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Prix par kWh:</span> €{{ number_format($reservation->tariffPlan->price_per_kwh, 2) }}
                                    </p>
                                @endif
                                @if($reservation->reservation_type === 'minute' && $reservation->tariffPlan->price_per_minute > 0)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Prix par minute:</span> €{{ number_format($reservation->tariffPlan->price_per_minute, 2) }}
                                    </p>
                                @endif
                                @php
                                    $subtotal = ($reservation->estimated_cost ?? 0) / (1 + ((optional($reservation->tariffPlan->vatRate)->rate ?? 0) / 100));
                                    $vatAmount = ($reservation->estimated_cost ?? 0) - $subtotal;
                                @endphp
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Sous-total (HT):</span> €{{ number_format($subtotal, 2) }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">TVA ({{ (optional($reservation->tariffPlan->vatRate)->rate ?? 0) }}%):</span> €{{ number_format($vatAmount, 2) }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Transaction</h4>
                        <div class="space-y-2">
                            @if($reservation->transaction && $reservation->transaction->status)
                                @php
                                    $status = $reservation->transaction->status;
                                    // Réservation payée par solde → toujours afficher "Approuvé automatiquement" (évite "En attente" si sync en retard)
                                    $reservationStatusValue = $reservation->status->value ?? $reservation->status ?? '';
                                    $forceApprovedLabel = $reservation->isPaid()
                                        && in_array($reservationStatusValue, ['confirmed', 'active', 'completed'], true);
                                    // Si c'est une chaîne, convertir en enum ou utiliser une logique de mapping
                                    if (is_string($status)) {
                                        try {
                                            $statusEnum = \App\Enums\TransactionStatus::from($status);
                                            $statusColor = $forceApprovedLabel ? 'success' : $statusEnum->color();
                                            $statusLabel = $forceApprovedLabel ? 'Approuvé automatiquement' : $statusEnum->label();
                                        } catch (\ValueError $e) {
                                            // Fallback si le statut n'est pas dans l'enum
                                            $statusColor = match($status) {
                                                'completed', 'approved', 'paid' => 'success',
                                                'pending' => 'warning',
                                                'cancelled', 'canceled', 'failed', 'rejected' => 'danger',
                                                'in_progress' => 'info',
                                                default => 'secondary'
                                            };
                                            $statusLabel = $forceApprovedLabel ? 'Approuvé automatiquement' : match($status) {
                                                'completed' => 'Terminé',
                                                'pending' => 'En attente',
                                                'in_progress' => 'En cours',
                                                'cancelled', 'canceled' => 'Annulé',
                                                'failed' => 'Échoué',
                                                'approved' => 'Approuvé',
                                                'rejected' => 'Rejeté',
                                                'paid' => 'Payé',
                                                default => ucfirst($status)
                                            };
                                            if ($forceApprovedLabel) {
                                                $statusColor = 'success';
                                            }
                                        }
                                    } else {
                                        // Si c'est déjà un enum
                                        $statusColor = $forceApprovedLabel ? 'success' : $status->color();
                                        $statusLabel = $forceApprovedLabel ? 'Approuvé automatiquement' : $status->label();
                                    }
                                @endphp
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Statut Transaction:</span> 
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900 dark:text-{{ $statusColor }}-200">
                                        {{ $statusLabel }}
                                    </span>
                                </p>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Statut Transaction:</span> N/A
                                </p>
                            @endif
                            @if($reservation->transaction)
                                <a href="{{ route('transactions.show', $reservation->transaction->id) }}" 
                                   class="inline-flex items-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                                    <i class="fas fa-info-circle mr-2"></i>Voir les détails de la transaction
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($reservation->transaction && $reservation->transaction->repartition_breakdown)
                <hr class="my-6 border-gray-200 dark:border-gray-700">
                
                <!-- Fee Breakdown Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-handshake text-primary-600 dark:text-primary-400 mr-2"></i>
                        Détails des Parts et Frais
                    </h3>
                        
                        @php
                            $breakdown = $reservation->transaction->repartition_breakdown;
                            $isNewFormat = isset($breakdown['distribution']) && isset($breakdown['fees']);
                            
                            // Calculer les frais détaillés avec notre service
                            $feeCalculationService = app(\App\Services\DetailedFeeCalculationService::class);
                            $detailedCalculation = $feeCalculationService->calculateReservationFees($reservation);
                            
                            // Debug: Log des données calculées
                            \Log::info('Détails de réservation calculés', [
                                'reservation_id' => $reservation->id,
                                'detailed_calculation' => $detailedCalculation,
                                'is_new_format' => $isNewFormat
                            ]);
                        @endphp
                        
                        @if($isNewFormat)
                            <!-- Nouveau format avec frais détaillés -->
                            <div class="space-y-4">
                                <!-- Résumé -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                    <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Résumé de la Transaction</h4>
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        <strong>Montant Total:</strong> {{ number_format($detailedCalculation['total_amount'], 2) }} EUR
                                    </p>
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        <strong>Revenu net disponible:</strong> {{ number_format($detailedCalculation['distribution']['available_revenue'], 2) }} EUR
                                    </p>
                                </div>
                                
                                <!-- Détail des frais -->
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Détail des Frais</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Frais d'activation:</span>
                                            <span class="font-semibold">{{ number_format($detailedCalculation['fees']['activation_fee'] ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Frais de recharge:</span>
                                            <span class="font-semibold">{{ number_format($detailedCalculation['fees']['recharge_fees'] ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Frais de transaction:</span>
                                            <span class="font-semibold">{{ number_format($detailedCalculation['fees']['transaction_fees'] ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-2">
                                            <span class="font-semibold text-gray-900">Total frais (Admin):</span>
                                            <span class="font-bold text-red-600">{{ number_format($detailedCalculation['fees']['total_fees'] ?? 0, 2) }} EUR</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Répartition des parts -->
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Répartition des Parts (Logique Corrigée)</h4>
                                    
                                    @if(isset($detailedCalculation['business_profile_info']))
                                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <h5 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Informations sur les Business Profiles</h5>
                                        <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                                            <p><strong>Scénario 1 - Parts Admin:</strong> Business Profile Intégrateur: 
                                                <span class="font-semibold">{{ $detailedCalculation['business_profile_info']['integrator_business_profile_name'] ?? 'Aucun profil trouvé' }}</span>
                                            </p>
                                            <p><strong>Scénario 2 - Parts Intégrateur:</strong> Business Profile Opérateur: 
                                                <span class="font-semibold">{{ $detailedCalculation['business_profile_info']['operator_business_profile_name'] ?? 'Aucun profil trouvé' }}</span>
                                            </p>
                                            <p><strong>Méthode de calcul:</strong> 
                                                <span class="font-semibold">{{ $detailedCalculation['business_profile_info']['calculation_method'] ?? 'default' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    @else
                                    <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                        <h5 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">⚠️ Informations sur les Business Profiles</h5>
                                        <div class="text-xs text-yellow-700 dark:text-yellow-300">
                                            <p>Aucune information sur les business profiles disponible. Utilisation des valeurs par défaut.</p>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <!-- Part Admin -->
                                        <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded border border-red-200">
                                            <div class="flex items-center mb-2">
                                                <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center mr-2">
                                                    <svg class="h-3 w-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900">Admin</span>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-lg font-bold text-red-600">{{ number_format($detailedCalculation['distribution']['admin_part'] ?? 0, 2) }} EUR</div>
                                                <div class="text-xs text-red-600">{{ number_format($detailedCalculation['percentages']['admin_percentage'] ?? 0, 1) }}% du total</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Part Intégrateur -->
                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded border border-blue-200">
                                            <div class="flex items-center mb-2">
                                                <div class="w-6 h-6 bg-blue-500 rounded flex items-center justify-center mr-2">
                                                    <svg class="h-3 w-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900">Intégrateur</span>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-lg font-bold text-blue-600">{{ number_format($detailedCalculation['distribution']['integrator_part'] ?? 0, 2) }} EUR</div>
                                                <div class="text-xs text-blue-600">{{ number_format($detailedCalculation['percentages']['integrator_percentage'] ?? 0, 1) }}% du revenu net</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Part Opérateur (Partenaire) -->
                                        <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded border border-green-200">
                                            <div class="flex items-center mb-2">
                                                <div class="w-6 h-6 bg-green-500 rounded flex items-center justify-center mr-2">
                                                    <svg class="h-3 w-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900">Opérateur (Partenaire)</span>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-lg font-bold text-green-600">{{ number_format(($detailedCalculation['distribution']['partner_part'] ?? 0) + ($detailedCalculation['distribution']['operator_part'] ?? 0), 2) }} EUR</div>
                                                <div class="text-xs text-green-600">{{ number_format(($detailedCalculation['percentages']['partner_percentage'] ?? 0) + ($detailedCalculation['percentages']['operator_percentage'] ?? 0), 1) }}% du revenu net</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if(config('app.debug'))
                                    <!-- Section Debug (visible seulement en développement) -->
                                    <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                        <h6 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">🐛 Debug Info (Développement)</h6>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                            <p><strong>Reservation ID:</strong> {{ $reservation->id }}</p>
                                            <p><strong>Charging Point ID:</strong> {{ $reservation->chargingPoint->id ?? 'N/A' }}</p>
                                            <p><strong>Integrator ID:</strong> {{ $reservation->chargingPoint->integrator_id ?? 'N/A' }}</p>
                                            <p><strong>Partner ID:</strong> {{ $reservation->chargingPoint->partner_id ?? 'N/A' }}</p>
                                            <p><strong>Business Profile ID:</strong> {{ $reservation->chargingPoint->business_profile_id ?? 'N/A' }}</p>
                                            <p><strong>Total Amount:</strong> {{ $detailedCalculation['total_amount'] ?? 'N/A' }} EUR</p>
                                            <p><strong>Available Revenue:</strong> {{ $detailedCalculation['distribution']['available_revenue'] ?? 'N/A' }} EUR</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- Ancien format simple -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $breakdown = $reservation->transaction->repartition_breakdown;
                                    // Décoder le JSON si c'est une chaîne
                                    if (is_string($breakdown)) {
                                        $breakdown = json_decode($breakdown, true) ?? [];
                                    }
                                    // S'assurer que c'est un tableau
                                    if (!is_array($breakdown)) {
                                        $breakdown = [];
                                    }
                                @endphp
                                @foreach($breakdown as $party => $amount)
                                    @if(is_numeric($amount))
                                        <p class="mb-2 text-sm"><strong class="font-semibold">{{ ucfirst($party) }}:</strong> {{ number_format($amount, 2) }} EUR</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endif

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <!-- Actions Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-cogs text-primary-600 dark:text-primary-400 mr-2"></i>
                    Actions
                </h3>
                @php
                // Vérifier si l'utilisateur est un client (user ou client, sans rôle système)
                $user = auth()->user();
                $userRoles = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
                $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
                $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
                $isClientOnly = $user && !$hasSystemRole && !empty(array_intersect($userRoles, ['user', 'client']));
                $isPending = $reservation->status && in_array($reservation->status->value ?? $reservation->status, ['pending', 'pending_confirmation']);
                $needsPayment = $isPending && !$reservation->isPaid();
                
                // Obtenir le solde du wallet pour les clients (refresh pour affichage à jour)
                $wallet = null;
                $balance = 0;
                $formattedBalance = '0.00 EUR';
                $estimatedCost = $reservation->estimated_cost ?? $reservation->amount ?? 0;
                $hasSufficientBalance = false;
                
                if ($isClientOnly && $user) {
                    $wallet = $user->getOrCreateWallet();
                    $wallet->refresh();
                    $balance = $wallet->balance ?? 0;
                    $formattedBalance = $wallet->getFormattedBalance() ?? '0.00 EUR';
                    $hasSufficientBalance = $balance >= $estimatedCost;
                }
            @endphp

            @if($isClientOnly && $needsPayment)
                <!-- Actions de paiement pour les clients -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 rounded-lg mb-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Paiement requis
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <p class="mb-2">
                                    <strong>Montant à payer:</strong> {{ number_format($estimatedCost, 2) }} EUR
                                </p>
                                <p>
                                    <strong>Votre solde:</strong> {{ $formattedBalance }}
                                    @if(!$hasSufficientBalance)
                                        <span class="text-red-600 dark:text-red-400 font-semibold">(Insuffisant)</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ route('payment.choose', $reservation->id) }}"
                       class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 shadow-md">
                        <i class="fas fa-credit-card mr-2"></i> 
                        {{ $hasSufficientBalance ? 'Payer par carte' : 'Choisir le paiement' }}
                    </a>
                    
                    @if(!$hasSufficientBalance)
                        <!-- Recharger le wallet -->
                        <a href="{{ route('credit-recharge.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors duration-200 shadow-md">
                            <i class="fas fa-plus-circle mr-2"></i> Recharger mon wallet
                        </a>
                    @endif
                </div>
            @elseif(!$isClientOnly && $isPending && !$reservation->isPaid())
                <!-- Actions pour les admins (pas de bouton Approuver si déjà payé par solde) -->
                <div class="flex flex-wrap gap-3 justify-center" x-data="{ approving: false, refusing: false }">
                    <!-- Approuver -->
                    <form action="{{ route('reservations.confirm', $reservation) }}" method="POST" class="inline" @submit="approving = true">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Enums\ReservationStatus::CONFIRMED->value }}">
                        <button type="submit"
                                :disabled="approving || refusing"
                                :class="approving ? 'bg-green-400 cursor-wait' : 'bg-green-600 hover:bg-green-700'"
                                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50">
                            <template x-if="!approving">
                                <span class="flex items-center"><i class="fas fa-check mr-2"></i> {{ __('Approuver') }}</span>
                            </template>
                            <template x-if="approving">
                                <span class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i> {{ __('Approbation...') }}</span>
                            </template>
                        </button>
                    </form>

                    <!-- Refuser -->
                    <form action="{{ route('reservations.cancel', $reservation) }}" method="POST" class="inline" 
                          @submit="if(!confirm('{{ __('Êtes-vous sûr de vouloir refuser cette réservation ?') }}')) { $event.preventDefault(); return false; } refusing = true;">
                        @csrf
                        <button type="submit"
                                :disabled="approving || refusing"
                                :class="refusing ? 'bg-red-400 cursor-wait' : 'bg-red-600 hover:bg-red-700'"
                                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50">
                            <template x-if="!refusing">
                                <span class="flex items-center"><i class="fas fa-times mr-2"></i> {{ __('Refuser') }}</span>
                            </template>
                            <template x-if="refusing">
                                <span class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i> {{ __('Refus...') }}</span>
                            </template>
                        </button>
                    </form>
                </div>
            @else
                <div class="text-center py-4">
                    @php
                        $reservationStatusValue = $reservation->status->value ?? $reservation->status ?? '';
                        $paymentStatusValue = strtoupper((string) ($reservation->payment_status ?? ''));
                    @endphp
                    @if($reservationStatusValue === \App\Enums\ReservationStatus::ACTIVE->value)
                        <div class="inline-flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-lg">
                            <i class="fas fa-bolt mr-2 text-blue-600"></i>
                            <span>Cette reservation est en cours.</span>
                        </div>
                    @elseif($reservation->isApproved())
                        <div class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg">
                            <i class="fas fa-check-circle mr-2 text-green-600"></i>
                            <span>{{ __('Cette réservation a été approuvée') }}</span>
                            @if($reservation->confirmed_at)
                                <span class="ml-2 text-sm text-green-600 dark:text-green-400">
                                    ({{ $reservation->confirmed_at->format('d/m/Y H:i') }})
                                </span>
                            @endif
                        </div>
                    @elseif($reservation->status === \App\Enums\ReservationStatus::CANCELED)
                        <div class="inline-flex items-center px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg">
                            <i class="fas fa-times-circle mr-2 text-red-600"></i>
                            <span>{{ __('Cette réservation a été refusée') }}</span>
                        </div>
                    @elseif($reservation->status === \App\Enums\ReservationStatus::COMPLETED)
                        <div class="inline-flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-lg">
                            <i class="fas fa-flag-checkered mr-2 text-blue-600"></i>
                            <span>{{ __('Cette réservation est terminée') }}</span>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-2"></i>
                            @if($reservation->isPaid())
                                Le paiement est confirme. Aucune approbation supplementaire n'est requise.
                            @else
                                {{ __('Aucune action disponible pour cette réservation.') }}
                            @endif
                        </p>
                    @endif
                </div>
            @endif
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-center space-x-3">
            @php
                $user = auth()->user();
                $userRoles = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
                $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
                $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
                $isClientOnly = $user && !$hasSystemRole && !empty(array_intersect($userRoles, ['user', 'client']));
            @endphp
            
            @if($isClientOnly)
                <a href="{{ route('reservations.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Retour aux Réservations
                </a>
            @else
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au Tableau de Bord
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
