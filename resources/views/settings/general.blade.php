@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <div class="text-gray-500 text-sm mb-1">Paramètres > Général</div>
        <h1 class="text-2xl font-medium">Paramètres généraux</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <form action="{{ route('settings.general.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'application</label>
                    <input type="text" name="app_name" id="app_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ $settings['app_name'] ?? config('app.name') }}">
                    @error('app_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise</label>
                    <input type="text" name="company_name" id="company_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ $settings['company_name'] ?? '' }}">
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email de contact</label>
                    <input type="email" name="contact_email" id="contact_email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50" value="{{ $settings['contact_email'] ?? '' }}">
                    @error('contact_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Langue par défaut</label>
                    <select name="language" id="language" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <option value="fr" {{ ($settings['language'] ?? 'fr') == 'fr' ? 'selected' : '' }}>Français</option>
                        <option value="en" {{ ($settings['language'] ?? '') == 'en' ? 'selected' : '' }}>English</option>
                        <option value="es" {{ ($settings['language'] ?? '') == 'es' ? 'selected' : '' }}>Español</option>
                        <option value="de" {{ ($settings['language'] ?? '') == 'de' ? 'selected' : '' }}>Deutsch</option>
                    </select>
                    @error('language')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Fuseau horaire</label>
                    <select name="timezone" id="timezone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <option value="Europe/Paris" {{ ($settings['timezone'] ?? 'Europe/Paris') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris</option>
                        <option value="Europe/London" {{ ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                        <option value="America/New_York" {{ ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                        <option value="Asia/Tokyo" {{ ($settings['timezone'] ?? '') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo</option>
                        <option value="Australia/Sydney" {{ ($settings['timezone'] ?? '') == 'Australia/Sydney' ? 'selected' : '' }}>Australia/Sydney</option>
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">Format de date</label>
                    <select name="date_format" id="date_format" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <option value="d/m/Y" {{ ($settings['date_format'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY (31/12/2023)</option>
                        <option value="m/d/Y" {{ ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY (12/31/2023)</option>
                        <option value="Y-m-d" {{ ($settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD (2023-12-31)</option>
                        <option value="d.m.Y" {{ ($settings['date_format'] ?? '') == 'd.m.Y' ? 'selected' : '' }}>DD.MM.YYYY (31.12.2023)</option>
                    </select>
                    @error('date_format')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                    <div class="mt-1 flex items-center">
                        <span class="inline-block h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                            @if(isset($settings['logo']) && !empty($settings['logo']))
                                <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Logo" class="h-full w-full object-contain">
                            @else
                                <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            @endif
                        </span>
                        <button type="button" class="ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Changer
                        </button>
                        <input type="file" name="logo" id="logo" class="hidden">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">PNG, JPG, GIF jusqu'à 2MB</p>
                    @error('logo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" {{ isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'checked' : '' }}>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="maintenance_mode" class="font-medium text-gray-700">Mode maintenance</label>
                        <p class="text-gray-500">Activer le mode maintenance rendra l'application inaccessible aux utilisateurs non-administrateurs.</p>
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

    <!-- Section des taux de TVA -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-medium text-gray-800">Taux de TVA</h2>
            <button type="button" id="addVatRateBtn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Ajouter un taux
            </button>
        </div>

        <p class="text-sm text-gray-600 mb-4">Configurez ici les différents taux de TVA disponibles dans l'application. Ces taux seront proposés lors de la création de plans tarifaires.</p>

        @if(session('vat_success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('vat_success') }}</p>
        </div>
        @endif

        @if(session('vat_error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ session('vat_error') }}</p>
        </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taux (%)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Par défaut</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="vatRatesTable">
                    @foreach($vatRates ?? [] as $vatRate)
                    <tr id="vat-rate-row-{{ $vatRate->id }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $vatRate->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($vatRate->rate, 2) }}%</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <input type="radio" name="default_vat_rate" value="{{ $vatRate->id }}" 
                                       {{ $vatRate->is_default ? 'checked' : '' }}
                                       data-id="{{ $vatRate->id }}"
                                       class="default-vat-radio focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300">
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <button type="button" 
                                        class="toggle-status relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 {{ $vatRate->is_active ? 'bg-green-500' : 'bg-gray-200' }}"
                                        role="switch" 
                                        aria-checked="{{ $vatRate->is_active ? 'true' : 'false' }}"
                                        data-id="{{ $vatRate->id }}">
                                    <span class="sr-only">Activer/Désactiver</span>
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $vatRate->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" class="edit-vat-rate text-indigo-600 hover:text-indigo-900 mr-3" data-id="{{ $vatRate->id }}">Modifier</button>
                            @if(!$vatRate->is_default)
                            <button type="button" class="delete-vat-rate text-red-600 hover:text-red-900" data-id="{{ $vatRate->id }}">Supprimer</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(empty($vatRates) || count($vatRates) === 0)
        <div class="text-center py-4">
            <p class="text-gray-500">Aucun taux de TVA défini. Cliquez sur "Ajouter un taux" pour commencer.</p>
        </div>
        @endif
    </div>
</div>

<!-- Modal pour ajouter/modifier un taux de TVA -->
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity hidden" id="vatRateModal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="vatRateForm" class="px-6 py-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Ajouter un taux de TVA</h3>
                    <p class="mt-1 text-sm text-gray-600">Définissez les informations du taux de TVA.</p>
                </div>
                
                <div class="mt-6 space-y-4">
                    <input type="hidden" id="vatRateId" value="">
                    
                    <div>
                        <label for="vatRateName" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" name="name" id="vatRateName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Ex: TVA standard">
                        <p class="mt-1 text-sm text-red-600 hidden" id="nameError"></p>
                    </div>
                    
                    <div>
                        <label for="vatRateValue" class="block text-sm font-medium text-gray-700">Taux (%)</label>
                        <input type="number" name="rate" id="vatRateValue" step="0.01" min="0" max="100" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="20.00">
                        <p class="mt-1 text-sm text-red-600 hidden" id="rateError"></p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="is_default" id="vatRateDefault" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="vatRateDefault" class="font-medium text-gray-700">Définir comme taux par défaut</label>
                            <p class="text-gray-500">Ce taux sera sélectionné automatiquement lors de la création de nouveaux plans.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="is_active" id="vatRateActive" class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" checked>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="vatRateActive" class="font-medium text-gray-700">Actif</label>
                            <p class="text-gray-500">Seuls les taux actifs seront disponibles lors de la création de plans.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-500 text-base font-medium text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Enregistrer
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:w-auto sm:text-sm" id="cancelVatRate">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity hidden" id="deleteConfirmModal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Supprimer ce taux de TVA</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Êtes-vous sûr de vouloir supprimer ce taux de TVA ? Cette action est irréversible et pourrait affecter les plans tarifaires existants.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm" id="confirmDelete">
                    Supprimer
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" id="cancelDelete">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Code existant pour le logo
        const logoButton = document.querySelector('button[type="button"]');
        const logoInput = document.getElementById('logo');
        
        if (logoButton && logoInput) {
            logoButton.addEventListener('click', function() {
                logoInput.click();
            });
            
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.querySelector('span.inline-block img') || document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'h-full w-full object-contain';
                        img.alt = 'Logo';
                        
                        const svg = document.querySelector('span.inline-block svg');
                        if (svg) {
                            svg.replaceWith(img);
                        } else if (!document.querySelector('span.inline-block img')) {
                            document.querySelector('span.inline-block').appendChild(img);
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Nouveau code pour les taux de TVA
        const vatRateModal = document.getElementById('vatRateModal');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const addVatRateBtn = document.getElementById('addVatRateBtn');
        const vatRateForm = document.getElementById('vatRateForm');
        const cancelVatRate = document.getElementById('cancelVatRate');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        
        let deleteVatRateId = null;

        // Ouvrir le modal pour ajouter un taux
        if (addVatRateBtn) {
            addVatRateBtn.addEventListener('click', function() {
                openVatRateModal();
            });
        }

        // Fermer le modal
        if (cancelVatRate) {
            cancelVatRate.addEventListener('click', function() {
                closeVatRateModal();
            });
        }

        // Soumettre le formulaire d'ajout/modification
        if (vatRateForm) {
            vatRateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitVatRateForm();
            });
        }

        // Fermer le modal de confirmation de suppression
        if (cancelDelete) {
            cancelDelete.addEventListener('click', function() {
                closeDeleteConfirmModal();
            });
        }

        // Confirmer la suppression
        if (confirmDelete) {
            confirmDelete.addEventListener('click', function() {
                if (deleteVatRateId) {
                    deleteVatRate(deleteVatRateId);
                }
            });
        }

        // Écouter les clics sur les boutons d'édition
        document.querySelectorAll('.edit-vat-rate').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                editVatRate(id);
            });
        });

        // Écouter les clics sur les boutons de suppression
        document.querySelectorAll('.delete-vat-rate').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                openDeleteConfirmModal(id);
            });
        });

        // Écouter les changements sur les boutons radio "par défaut"
        document.querySelectorAll('.default-vat-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                const id = this.getAttribute('data-id');
                setDefaultVatRate(id);
            });
        });

        // Écouter les clics sur les toggles de statut
        document.querySelectorAll('.toggle-status').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const isActive = this.getAttribute('aria-checked') === 'true';
                toggleVatRateStatus(id, !isActive);
            });
        });
// Fonction pour ouvrir le modal d'ajout/modification
        function openVatRateModal(vatRate = null) {
            const modalTitle = document.getElementById('modalTitle');
            const vatRateId = document.getElementById('vatRateId');
            const vatRateName = document.getElementById('vatRateName');
            const vatRateValue = document.getElementById('vatRateValue');
            const vatRateDefault = document.getElementById('vatRateDefault');
            const vatRateActive = document.getElementById('vatRateActive');
            
            // Réinitialiser les erreurs
            document.getElementById('nameError').classList.add('hidden');
            document.getElementById('rateError').classList.add('hidden');
            
            if (vatRate) {
                // Mode édition
                modalTitle.textContent = 'Modifier un taux de TVA';
                vatRateId.value = vatRate.id;
                vatRateName.value = vatRate.name;
                vatRateValue.value = vatRate.rate;
                vatRateDefault.checked = vatRate.is_default;
                vatRateActive.checked = vatRate.is_active;
            } else {
                // Mode ajout
                modalTitle.textContent = 'Ajouter un taux de TVA';
                vatRateForm.reset();
                vatRateId.value = '';
            }
            
            vatRateModal.classList.remove('hidden');
        }
        
        // Fonction pour fermer le modal
        function closeVatRateModal() {
            vatRateModal.classList.add('hidden');
        }
        
        // Fonction pour soumettre le formulaire d'ajout/modification
        function submitVatRateForm() {
            const vatRateId = document.getElementById('vatRateId').value;
            const vatRateName = document.getElementById('vatRateName').value;
            const vatRateValue = document.getElementById('vatRateValue').value;
            const vatRateDefault = document.getElementById('vatRateDefault').checked;
            const vatRateActive = document.getElementById('vatRateActive').checked;
            
            // Validation basique
            let hasError = false;
            
            if (!vatRateName.trim()) {
                document.getElementById('nameError').textContent = 'Le nom est requis';
                document.getElementById('nameError').classList.remove('hidden');
                hasError = true;
            }
            
            if (!vatRateValue.trim() || isNaN(parseFloat(vatRateValue)) || parseFloat(vatRateValue) < 0 || parseFloat(vatRateValue) > 100) {
                document.getElementById('rateError').textContent = 'Veuillez entrer un taux valide entre 0 et 100';
                document.getElementById('rateError').classList.remove('hidden');
                hasError = true;
            }
            
            if (hasError) return;
            
            // Préparer les données
            const formData = {
                name: vatRateName,
                rate: parseFloat(vatRateValue),
                is_default: vatRateDefault,
                is_active: vatRateActive
            };
            
            // Déterminer l'URL et la méthode
            let url = '/settings/vat-rates';
            let method = 'POST';
            
            if (vatRateId) {
                url += `/${vatRateId}`;
                method = 'PUT';
            }
            
            // Envoyer la requête
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    closeVatRateModal();
                    
                    // Recharger la page pour afficher les changements
                    window.location.reload();
                } else {
                    // Afficher les erreurs
                    if (data.errors) {
                        if (data.errors.name) {
                            document.getElementById('nameError').textContent = data.errors.name[0];
                            document.getElementById('nameError').classList.remove('hidden');
                        }
                        
                        if (data.errors.rate) {
                            document.getElementById('rateError').textContent = data.errors.rate[0];
                            document.getElementById('rateError').classList.remove('hidden');
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue. Veuillez réessayer.');
            });
        }
        
        // Fonction pour ouvrir le modal de confirmation de suppression
        function openDeleteConfirmModal(id) {
            deleteVatRateId = id;
            deleteConfirmModal.classList.remove('hidden');
        }
        
        // Fonction pour fermer le modal de confirmation de suppression
        function closeDeleteConfirmModal() {
            deleteVatRateId = null;
            deleteConfirmModal.classList.add('hidden');
        }
        
        // Fonction pour supprimer un taux de TVA
        function deleteVatRate(id) {
            fetch(`/settings/vat-rates/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                closeDeleteConfirmModal();
                
                if (data.success) {
                    // Supprimer la ligne du tableau
                    const row = document.getElementById(`vat-rate-row-${id}`);
                    if (row) {
                        row.remove();
                    }
                    
                    // Afficher un message de succès
                    const successMessage = document.createElement('div');
                    successMessage.className = 'bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4';
                    successMessage.role = 'alert';
                    successMessage.innerHTML = `<p>${data.message}</p>`;
                    
                    const tableContainer = document.querySelector('#vatRatesTable').parentNode;
                    tableContainer.parentNode.insertBefore(successMessage, tableContainer);
                    
                    // Supprimer le message après quelques secondes
                    setTimeout(() => {
                        successMessage.remove();
                    }, 5000);
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la suppression.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                closeDeleteConfirmModal();
                alert('Une erreur est survenue. Veuillez réessayer.');
            });
        }
        
        // Fonction pour récupérer les détails d'un taux de TVA
        function editVatRate(id) {
            fetch(`/settings/vat-rates/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.vatRate) {
                        openVatRateModal(data.vatRate);
                    } else {
                        alert('Impossible de récupérer les informations du taux de TVA.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue. Veuillez réessayer.');
                });
        }
        
        // Fonction pour définir un taux de TVA comme taux par défaut
        function setDefaultVatRate(id) {
            fetch(`/settings/vat-rates/${id}/default`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Une erreur est survenue.');
                    // Recharger la page pour réinitialiser l'état des radios
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue. Veuillez réessayer.');
                // Recharger la page pour réinitialiser l'état des radios
                window.location.reload();
            });
        }
        
        // Fonction pour activer/désactiver un taux de TVA
        function toggleVatRateStatus(id, isActive) {
            fetch(`/settings/vat-rates/${id}/toggle`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ is_active: isActive })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'apparence du toggle
                    const toggleButton = document.querySelector(`.toggle-status[data-id="${id}"]`);
                    const toggleSpan = toggleButton.querySelector('span[aria-hidden="true"]');
                    
                    if (isActive) {
                        toggleButton.classList.add('bg-green-500');
                        toggleButton.classList.remove('bg-gray-200');
                        toggleSpan.classList.add('translate-x-5');
                        toggleSpan.classList.remove('translate-x-0');
                    } else {
                        toggleButton.classList.remove('bg-green-500');
                        toggleButton.classList.add('bg-gray-200');
                        toggleSpan.classList.remove('translate-x-5');
                        toggleSpan.classList.add('translate-x-0');
                    }
                    
                    toggleButton.setAttribute('aria-checked', isActive ? 'true' : 'false');
                } else {
                    alert(data.message || 'Une erreur est survenue.');
                    // Recharger la page pour réinitialiser l'état des toggles
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue. Veuillez réessayer.');
                // Recharger la page pour réinitialiser l'état des toggles
                window.location.reload();
            });
        }
    });
</script>
@endpush