@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header with X icon -->
    <div class="flex items-center p-4 border-b">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor" onclick="window.location.href='{{ route('groups.index') }}'">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span class="text-sm font-medium">Créer un nouveau groupe</span>
    </div>

    <div class="p-6 md:p-10">
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Erreur!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        
        <!-- Progress Indicator -->
        <div class="flex justify-center mb-10">
            <div class="flex items-center space-x-4">
                <!-- Step 1: Type and Name (Active) -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
                        <div class="w-2 h-2 bg-white rounded-full"></div>
                    </div>
                    <span class="text-sm font-medium text-green-500">Type et nom</span>
                </div>
                
                <!-- Divider -->
                <div class="w-20 h-0.5 bg-gray-200"></div>
                
                <!-- Step 2: Coordinates -->
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <span class="text-sm text-gray-500">2</span>
                    </div>
                    <span class="text-sm text-gray-500">Coordonnées</span>
                </div>
            </div>
        </div>

        <form action="{{ route('groups.store.step1') }}" method="POST" class="space-y-8">
            @csrf
            
            <!-- Group Type Selection -->
            <div>
                <h2 class="text-xl font-semibold mb-6">Sélectionnez le type de groupe</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Private Group Option -->
                    <label class="group-type-label @if(old('type', 'private') == 'private') selected @endif">
                        <input type="radio" name="type" value="private" class="sr-only" 
                               @if(old('type', 'private') == 'private') checked @endif>
                        <div class="border rounded-lg p-6 transition-all 
                                    @if(old('type', 'private') == 'private') 
                                        border-green-500 bg-green-50 
                                    @else 
                                        border-gray-200 hover:border-gray-300 
                                    @endif">
                            <div class="flex items-center mb-4">
                                <div class="mr-4">
                                    <div class="w-24 h-24 bg-green-50 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Groupe Privé</h3>
                                    <div class="radio-indicator w-5 h-5 rounded-full border-2 
                                                @if(old('type', 'private') == 'private') 
                                                    border-green-500 
                                                @else 
                                                    border-gray-300 
                                                @endif">
                                        @if(old('type', 'private') == 'private')
                                            <div class="w-3 h-3 bg-green-500 rounded-full m-0.5"></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">
                                Sélectionnez cette équipe si vous avez des clients professionnels (ex: associations de logement, hôtels, parkings, etc.) qui souhaitent gérer leurs bornes de recharge ou leur flotte de véhicules électriques, définir des prix de recharge spécifiques et gérer les membres de l'équipe.
                            </p>
                        </div>
                    </label>

                    <!-- Public Group Option -->
                    <label class="group-type-label @if(old('type') == 'public') selected @endif">
                        <input type="radio" name="type" value="public" class="sr-only"
                               @if(old('type') == 'public') checked @endif>
                        <div class="border rounded-lg p-6 transition-all 
                                    @if(old('type') == 'public') 
                                        border-green-500 bg-green-50 
                                    @else 
                                        border-gray-200 hover:border-gray-300 
                                    @endif">
                            <div class="flex items-center mb-4">
                                <div class="mr-4">
                                    <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Groupe Public</h3>
                                    <div class="radio-indicator w-5 h-5 rounded-full border-2 
                                                @if(old('type') == 'public') 
                                                    border-green-500 
                                                @else 
                                                    border-gray-300 
                                                @endif">
                                        @if(old('type') == 'public')
                                            <div class="w-3 h-3 bg-green-500 rounded-full m-0.5"></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">
                                Sélectionnez cette équipe si vous avez des clients professionnels (ex: associations de logement, hôtels, parkings, etc.) qui souhaitent gérer leurs bornes de recharge ou leur flotte de véhicules électriques, définir des prix de recharge spécifiques et gérer les membres de l'équipe.
                            </p>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Group Name Section -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Nom du groupe</h2>
                <label for="name" class="block text-sm font-medium text-gray-700 sr-only">Nom du groupe</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 
                           focus:border-green-500 focus:ring-2 focus:ring-green-100 
                           @error('name') border-red-500 @enderror" 
                    placeholder="Entrez le nom de votre groupe"
                    required
                >
                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Hidden User ID field -->
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
            
            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-green-500 text-white px-8 py-3 rounded-lg 
                                             hover:bg-green-600 transition-colors">
                    Suivant
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');
    const groupTypeLabels = document.querySelectorAll('.group-type-label');
    
    groupTypeLabels.forEach(label => {
        label.addEventListener('change', function() {
            console.log('Group type changed');
            // Reset all labels
            groupTypeLabels.forEach(l => {
                const container = l.querySelector('div');
                const radioIndicator = l.querySelector('.radio-indicator');
                
                container.classList.remove('border-green-500', 'bg-green-50');
                container.classList.add('border-gray-200', 'hover:border-gray-300');
                
                radioIndicator.classList.remove('border-green-500');
                radioIndicator.classList.add('border-gray-300');
                radioIndicator.innerHTML = '';
            });
            
            // Style selected label
            const selectedContainer = this.querySelector('div');
            const selectedRadioIndicator = this.querySelector('.radio-indicator');
            
            selectedContainer.classList.remove('border-gray-200', 'hover:border-gray-300');
            selectedContainer.classList.add('border-green-500', 'bg-green-50');
            
            selectedRadioIndicator.classList.remove('border-gray-300');
            selectedRadioIndicator.classList.add('border-green-500');
            selectedRadioIndicator.innerHTML = '<div class="w-3 h-3 bg-green-500 rounded-full m-0.5"></div>';
        });
    });
});
</script>
@endpush
