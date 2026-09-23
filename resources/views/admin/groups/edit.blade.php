@extends('layouts.app')

@section('title', 'Modifier : ' . $group->name)
@section('page-title', 'Modifier le groupe')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.groups.show', $group->id) }}" class="text-emerald-200 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold">Modifier : {{ $group->name }}</h1>
                <p class="text-emerald-100 text-sm mt-0.5">Mettez à jour la configuration du groupe</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 rounded-xl p-4">
            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.groups.update', $group->id) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Informations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du groupe <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $group->name) }}" required
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <input type="radio" name="type" value="business" {{ old('type', $group->type) === 'business' ? 'checked' : '' }} class="text-emerald-600">
                            <span class="text-sm text-gray-900 dark:text-white">Business</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <input type="radio" name="type" value="private" {{ old('type', $group->type) === 'private' ? 'checked' : '' }} class="text-emerald-600">
                            <span class="text-sm text-gray-900 dark:text-white">Privé</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mode de consommation</label>
                    <select name="consumption_mode" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="prepaid" @selected(old('consumption_mode', $group->consumption_mode) === 'prepaid')>Prépayé</option>
                        <option value="postpaid" @selected(old('consumption_mode', $group->consumption_mode) === 'postpaid')>Postpayé</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ville</label>
                    <input type="text" name="city" value="{{ old('city', $group->city) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Partenaire</label>
                    @if($userPartner)
                        {{-- Display partner as read-only if user is associated with a partner --}}
                        <div class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white flex items-center">
                            <input type="hidden" name="partner_id" value="{{ $userPartner }}">
                            <span>{{ $partners->where('id', $userPartner)->first()?->name ?? 'Partenaire #' . $userPartner }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lié à votre profil partenaire</p>
                    @else
                        {{-- Show dropdown only for admins without a partner --}}
                        <select name="partner_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                            <option value="">Aucun</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" @selected(old('partner_id', $group->partner_id) == $partner->id)>{{ $partner->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Intégrateur</label>
                    <select name="integrator_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="">Aucun</option>
                        @foreach($integrators as $integrator)
                            <option value="{{ $integrator->id }}" @selected(old('integrator_id', $group->integrator_id) == $integrator->id)>{{ $integrator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="2"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">{{ old('description', $group->description) }}</textarea>
                </div>
            </div>
        </div>

        @if($pricingPlans->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Plans tarifaires</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto">
                @foreach($pricingPlans as $plan)
                    <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <input type="checkbox" name="pricing_plan_ids[]" value="{{ $plan->id }}"
                               {{ in_array($plan->id, old('pricing_plan_ids', $selectedPlans)) ? 'checked' : '' }}
                               class="text-emerald-600 rounded focus:ring-emerald-500">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $plan->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $plan->main_type_label }} — {{ $plan->formatted_main_value ?? '—' }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex items-center justify-between">
            <form method="POST" action="{{ route('admin.groups.destroy', $group->id) }}" onsubmit="return confirm('Supprimer ce groupe ?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 rounded-lg transition-colors">
                    <i class="fas fa-trash mr-1.5"></i> Supprimer
                </button>
            </form>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.groups.show', $group->id) }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                    Annuler
                </a>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
