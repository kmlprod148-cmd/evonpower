<div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 100 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">{{ __('charging_points.create.help_title') }}</h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>{{ __('charging_points.create.help_description') }}</p>
                <div class="mt-2">
                    <a href="{{ route('support.contact') }}" class="font-medium text-blue-600 hover:text-blue-500">
                        {{ __('charging_points.create.contact_support') }} <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>