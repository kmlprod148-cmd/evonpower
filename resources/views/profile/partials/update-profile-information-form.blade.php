<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Phone Field -->
        <div>
            <x-input-label for="phone" :value="__('Téléphone')" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <!-- Address Field -->
        <div>
            <x-input-label for="address" :value="__('Adresse')" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $user->address)" autocomplete="street-address" />
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <!-- City and Postal Code Row -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="city" :value="__('Ville')" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $user->city)" autocomplete="address-level2" />
                <x-input-error class="mt-2" :messages="$errors->get('city')" />
            </div>

            <div>
                <x-input-label for="postal_code" :value="__('Code postal')" />
                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $user->postal_code)" autocomplete="postal-code" />
                <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
            </div>
        </div>

        <!-- Country Field -->
        <div>
            <x-input-label for="country" :value="__('Pays')" />
            <select id="country" name="country" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" autocomplete="country">
                <option value="">{{ __('Sélectionner un pays') }}</option>
                <option value="FR" {{ old('country', $user->country) == 'FR' ? 'selected' : '' }}>France</option>
                <option value="BE" {{ old('country', $user->country) == 'BE' ? 'selected' : '' }}>Belgique</option>
                <option value="CH" {{ old('country', $user->country) == 'CH' ? 'selected' : '' }}>Suisse</option>
                <option value="LU" {{ old('country', $user->country) == 'LU' ? 'selected' : '' }}>Luxembourg</option>
                <option value="CA" {{ old('country', $user->country) == 'CA' ? 'selected' : '' }}>Canada</option>
                <option value="US" {{ old('country', $user->country) == 'US' ? 'selected' : '' }}>États-Unis</option>
                <option value="GB" {{ old('country', $user->country) == 'GB' ? 'selected' : '' }}>Royaume-Uni</option>
                <option value="DE" {{ old('country', $user->country) == 'DE' ? 'selected' : '' }}>Allemagne</option>
                <option value="ES" {{ old('country', $user->country) == 'ES' ? 'selected' : '' }}>Espagne</option>
                <option value="IT" {{ old('country', $user->country) == 'IT' ? 'selected' : '' }}>Italie</option>
                <option value="PT" {{ old('country', $user->country) == 'PT' ? 'selected' : '' }}>Portugal</option>
                <option value="NL" {{ old('country', $user->country) == 'NL' ? 'selected' : '' }}>Pays-Bas</option>
                <option value="OTHER" {{ old('country', $user->country) == 'OTHER' ? 'selected' : '' }}>Autre</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('country')" />
        </div>

        <!-- Language and Timezone Row -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="language" :value="__('Langue')" />
                <select id="language" name="language" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                    <option value="fr" {{ old('language', $user->language ?? 'fr') == 'fr' ? 'selected' : '' }}>Français</option>
                    <option value="en" {{ old('language', $user->language ?? 'fr') == 'en' ? 'selected' : '' }}>English</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('language')" />
            </div>

            <div>
                <x-input-label for="timezone" :value="__('Fuseau horaire')" />
                <select id="timezone" name="timezone" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                    <option value="Europe/Paris" {{ old('timezone', $user->timezone ?? 'Europe/Paris') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris (CET)</option>
                    <option value="Europe/London" {{ old('timezone', $user->timezone ?? 'Europe/Paris') == 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT)</option>
                    <option value="America/New_York" {{ old('timezone', $user->timezone ?? 'Europe/Paris') == 'America/New_York' ? 'selected' : '' }}>America/New_York (EST)</option>
                    <option value="America/Los_Angeles" {{ old('timezone', $user->timezone ?? 'Europe/Paris') == 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles (PST)</option>
                    <option value="Asia/Tokyo" {{ old('timezone', $user->timezone ?? 'Europe/Paris') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (JST)</option>
                    <option value="UTC" {{ old('timezone', $user->timezone ?? 'Europe/Paris') == 'UTC' ? 'selected' : '' }}>UTC</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
