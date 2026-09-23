@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <!-- Breadcrumb Navigation -->
    <div class="text-gray-500 text-sm mb-4">
        <a href="{{ route('groups.index') }}" class="hover:text-gray-700">Groupes</a> &gt; 
        <a href="{{ route('groups.show', $group->id) }}" class="hover:text-gray-700">{{ $group->name ?? $group->title }}</a> &gt; 
        <span>Modifier</span>
    </div>
    
    <!-- Page Title -->
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Modifier le groupe</h1>

    <!-- Form Card -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <form action="{{ route('groups.update', $group->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-6">
                <!-- Form Fields Grid -->
                <div class="grid grid-cols-1 gap-6">
                    <!-- Name Input -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $group->name ?? $group->title) }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description Textarea -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea 
                            name="description" 
                            id="description" 
                            rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('description') border-red-500 @enderror"
                        >{{ old('description', $group->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Type, Consumption Mode and City Inputs -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Type Select -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select 
                                name="type" 
                                id="type"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('type') border-red-500 @enderror"
                            >
                                <option value="public" {{ old('type', $group->type) == 'public' ? 'selected' : '' }}>Public</option>
                                <option value="private" {{ old('type', $group->type) == 'private' ? 'selected' : '' }}>Privé</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Consumption Mode Select -->
                        <div>
                            <label for="consumption_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode de consommation</label>
                            <select 
                                name="consumption_mode" 
                                id="consumption_mode"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('consumption_mode') border-red-500 @enderror"
                            >
                                <option value="prepaid" {{ old('consumption_mode', $group->consumption_mode ?? 'prepaid') == 'prepaid' ? 'selected' : '' }}>Recharge prépayée</option>
                                <option value="postpaid" {{ old('consumption_mode', $group->consumption_mode ?? 'prepaid') == 'postpaid' ? 'selected' : '' }}>Recharge postpayée</option>
                            </select>
                            @error('consumption_mode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City Input -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                            <input 
                                type="text" 
                                name="city" 
                                id="city" 
                                value="{{ old('city', $group->city) }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('city') border-red-500 @enderror"
                            >
                            @error('city')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Address Input -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                        <input
                            type="text" 
                            name="address" 
                            id="address" 
                            value="{{ old('address', $group->address) }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('address') border-red-500 @enderror"
                        >
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Postal Code Input -->
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            id="postal_code" 
                            value="{{ old('postal_code', $group->postal_code ?? $group->zip_code) }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('postal_code') border-red-500 @enderror"
                        >
                        @error('postal_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Country Input -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                        <input 
                            type="text" 
                            name="country" 
                            id="country" 
                            value="{{ old('country', $group->country ?? 'Maroc') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 @error('country') border-red-500 @enderror"
                        >
                        @error('country')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @if(auth()->user()->hasRole('admin'))
                <!-- Permissions Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Permissions</h3>
                    <div class="space-y-2">
                        @foreach($permissions as $key => $permissionGroup)
                            <div class="font-semibold">{{ ucfirst($key) }}</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($permissionGroup as $permission)
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            id="permission-{{ $permission->id }}"
                                            value="{{ $permission->name }}"
                                            class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                            {{ in_array($permission->name, $groupPermissions) ? 'checked' : '' }}
                                        >
                                        <label for="permission-{{ $permission->id }}" class="ml-2 block text-sm text-gray-900">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Form Actions -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <a href="{{ route('groups.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Annuler
                </a>

                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-green-600 bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection