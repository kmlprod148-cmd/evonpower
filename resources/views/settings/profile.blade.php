@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <div class="text-gray-500 text-sm mb-1">Paramètres > Profil</div>
        <h1 class="text-2xl font-medium">Profil utilisateur</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-medium mb-4">Informations personnelles</h2>
            <form action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    <div class="md:col-span-2">
                        <div class="flex flex-col items-center">
                            <div class="mb-4">
                                <div class="relative">
                                    <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-100">
                                        @if(auth()->user()->avatar)
                                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <label for="avatar" class="absolute bottom-0 right-0 bg-white rounded-full p-1 border border-gray-300 cursor-pointer hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <input type="file" id="avatar" name="avatar" class="hidden" accept="image/*">
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 text-center">Cliquez pour changer votre photo de profil</p>
                                @error('avatar')
                                    <p class="mt-1 text-xs text-red-600 text-center">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500">Membre depuis</p>
                                <p class="font-medium">{{ auth()->user()->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                                <input type="text" name="name" id="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->name }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                                <input type="email" name="email" id="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->email }}">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                                <input type="tel" name="phone" id="phone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->phone ?? '' }}">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1">Fonction</label>
                                <input type="text" name="job_title" id="job_title" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->job_title ?? '' }}">
                                @error('job_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-md font-medium mb-3">Informations complémentaires</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input type="text" name="address" id="address" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->address ?? '' }}">
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                            <input type="text" name="city" id="city" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->city ?? '' }}">
                            @error('city')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                            <input type="text" name="postal_code" id="postal_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ auth()->user()->postal_code ?? '' }}">
                            @error('postal_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                            <select name="country" id="country" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                <option value="">Sélectionnez un pays</option>
                                <option value="FR" {{ (auth()->user()->country ?? '') == 'FR' ? 'selected' : '' }}>France</option>
                                <option value="BE" {{ (auth()->user()->country ?? '') == 'BE' ? 'selected' : '' }}>Belgique</option>
                                <option value="CH" {{ (auth()->user()->country ?? '') == 'CH' ? 'selected' : '' }}>Suisse</option>
                                <option value="CA" {{ (auth()->user()->country ?? '') == 'CA' ? 'selected' : '' }}>Canada</option>
                                <!-- Add more countries as needed -->
                            </select>
                            @error('country')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Biographie</label>
                        <textarea name="bio" id="bio" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">{{ auth()->user()->bio ?? '' }}</textarea>
                        <p class="mt-1 text-sm text-gray-500">Brève description qui apparaîtra sur votre profil public.</p>
                        @error('bio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-md font-medium mb-3">Préférences</h3>
                    
                    <!-- Theme Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Apparence</label>
                        <div class="flex items-center space-x-4" x-data="{
                            theme: '{{ auth()->user()->theme ?? 'system' }}',
                            async updateTheme(newTheme) {
                                this.theme = newTheme;
                                // Update localStorage
                                localStorage.setItem('theme', newTheme);
                                // Apply theme immediately
                                if (newTheme === 'system') {
                                    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                                    document.documentElement.classList.toggle('dark', systemDark);
                                } else {
                                    document.documentElement.classList.toggle('dark', newTheme === 'dark');
                                }
                                // Save to server
                                try {
                                    await fetch('{{ route('settings.theme.update') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ theme: newTheme })
                                    });
                                } catch (e) {
                                    console.error('Failed to save theme preference:', e);
                                }
                            }
                        }">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="theme" value="light" 
                                    x-model="theme" 
                                    @change="updateTheme('light')"
                                    class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300">
                                <span class="ml-2 flex items-center">
                                    <svg class="w-5 h-5 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Clair</span>
                                </span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="theme" value="dark" 
                                    x-model="theme" 
                                    @change="updateTheme('dark')"
                                    class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300">
                                <span class="ml-2 flex items-center">
                                    <svg class="w-5 h-5 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Sombre</span>
                                </span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="theme" value="system" 
                                    x-model="theme" 
                                    @change="updateTheme('system')"
                                    class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300">
                                <span class="ml-2 flex items-center">
                                    <svg class="w-5 h-5 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Système</span>
                                </span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Choisissez votre thème d'interface. "Système" utilise les paramètres de votre appareil.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="newsletter" id="newsletter" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" {{ auth()->user()->newsletter ?? false ? 'checked' : '' }}>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="newsletter" class="font-medium text-gray-700">S'abonner à la newsletter</label>
                                <p class="text-gray-500">Recevez des mises à jour sur les nouvelles fonctionnalités et améliorations.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="marketing_emails" id="marketing_emails" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" {{ auth()->user()->marketing_emails ?? false ? 'checked' : '' }}>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="marketing_emails" class="font-medium text-gray-700">Emails marketing</label>
                                <p class="text-gray-500">Recevez des informations sur nos offres spéciales et événements.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <a href="{{ route('settings.index') }}" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Annuler
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    @if(auth()->user()->hasRole('user') && !auth()->user()->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner']))
    <!-- Section Gestion de Solde pour les clients publics -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium">Gestion de mon Solde</h2>
                <a href="{{ route('credit-recharge.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Recharger mon solde
                </a>
            </div>
            
            @php
                $wallet = auth()->user()->wallet ?? auth()->user()->getOrCreateWallet();
                $balance = $wallet->balance ?? 0;
                $formattedBalance = $wallet->getFormattedBalance() ?? number_format($balance, 2, ',', ' ') . ' EUR';
            @endphp
            
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Solde actuel</p>
                        <p class="text-3xl font-bold">{{ $formattedBalance }}</p>
                    </div>
                    <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">Comment recharger mon solde ?</p>
                        <p class="mb-2">En tant que client public, vous pouvez demander une recharge de solde qui sera confirmée par un administrateur.</p>
                        <p>Une fois confirmée, le montant sera automatiquement ajouté à votre solde.</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('credit-recharge.index') }}" 
                   class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Demander une recharge</p>
                        <p class="text-sm text-gray-500">Créer une nouvelle demande de recharge</p>
                    </div>
                </a>
                
                <a href="{{ route('credit-recharge.index') }}" 
                   class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Historique des recharges</p>
                        <p class="text-sm text-gray-500">Voir toutes mes demandes de recharge</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarInput = document.getElementById('avatar');
        
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.w-32.h-32 img') || document.createElement('img');
                    img.src = e.target.result;
                    img.alt = "{{ auth()->user()->name }}";
                    img.className = "w-full h-full object-cover";
                    
                    const svg = document.querySelector('.w-32.h-32 svg');
                    if (svg) {
                        svg.replaceWith(img);
                    } else if (!document.querySelector('.w-32.h-32 img')) {
                        document.querySelector('.w-32.h-32').appendChild(img);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endpush