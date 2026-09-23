@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Colonne principale : Formulaire -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h1 class="text-2xl font-medium mb-6">Ajouter un intégrateur</h1>

                    @if ($errors->any())
                    <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('integrators.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Informations générales -->
                        <div class="mb-6">
                            <h2 class="text-lg font-medium text-gray-700 mb-4">Informations générales</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise*</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email professionnel*</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label for="active" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                                    <select name="active" id="active"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                        <option value="1" {{ old('active', 1) == 1 ? 'selected' : '' }}>Actif</option>
                                        <option value="0" {{ old('active') == 0 ? 'selected' : '' }}>Inactif</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('integrators.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Annuler
                            </a>
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Colonne droite : Récapitulatif -->
            <div class="lg:col-span-1">
                <div class="recap-card sticky top-6">
                    <h3 class="recap-title">Récapitulatif en temps réel</h3>
                    <p class="recap-item"><strong>Nom :</strong> <span id="recap-name">-</span></p>
                    <p class="recap-item"><strong>Email :</strong> <span id="recap-email">-</span></p>
                    <p class="recap-item"><strong>Téléphone :</strong> <span id="recap-phone">-</span></p>
                    <p class="recap-item"><strong>Statut :</strong> <span id="recap-status">-</span></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .recap-card {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .recap-title {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .recap-item {
        font-size: 0.875rem;
        color: #4b5563;
        margin-bottom: 0.5rem;
    }

    .sticky {
        position: sticky;
        top: 1.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fieldsToWatch = ['name', 'email', 'phone', 'active'];
        const recapFields = {
            name: document.getElementById('recap-name'),
            email: document.getElementById('recap-email'),
            phone: document.getElementById('recap-phone'),
            active: document.getElementById('recap-status'),
        };

        fieldsToWatch.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function () {
                    if (field.type === 'select-one') {
                        recapFields[fieldId].textContent = field.options[field.selectedIndex].text;
                    } else {
                        recapFields[fieldId].textContent = field.value || '-';
                    }
                });
            }
        });

        // Initialiser le récapitulatif
        fieldsToWatch.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                if (field.type === 'select-one') {
                    recapFields[fieldId].textContent = field.options[field.selectedIndex].text;
                } else {
                    recapFields[fieldId].textContent = field.value || '-';
                }
            }
        });
    });
</script>
@endpush