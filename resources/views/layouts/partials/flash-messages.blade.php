<!-- Flash Messages Component -->
@if(session('success'))
    <x-evon.alert type="success" :dismissible="true" class="mb-6">
        <x-slot:title>{{ __('messages.success') }}</x-slot:title>
        {{ session('success') }}
    </x-evon.alert>
@endif

@if(session('error'))
    <x-evon.alert type="danger" :dismissible="true" class="mb-6">
        <x-slot:title>{{ __('messages.error') }}</x-slot:title>
        {{ session('error') }}
    </x-evon.alert>
@endif

@if(session('warning'))
    <x-evon.alert type="warning" :dismissible="true" class="mb-6">
        <x-slot:title>{{ __('messages.warning') }}</x-slot:title>
        {{ session('warning') }}
    </x-evon.alert>
@endif

@if(session('info'))
    <x-evon.alert type="info" :dismissible="true" class="mb-6">
        <x-slot:title>{{ __('messages.info') }}</x-slot:title>
        {{ session('info') }}
    </x-evon.alert>
@endif

@if($errors->any())
    <x-evon.alert type="danger" :dismissible="true" class="mb-6">
        <x-slot:title>{{ __('messages.validation_errors') }}</x-slot:title>
        <ul class="mt-2 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li class="text-sm">{{ $error }}</li>
            @endforeach
        </ul>
    </x-evon.alert>
@endif

