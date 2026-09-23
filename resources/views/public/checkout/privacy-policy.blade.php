@extends('layouts.guest')

@section('title', __('Privacy Policy'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Privacy Policy') }}</h1>
            <p class="text-gray-600 mt-2">{{ __('Last updated:') }} {{ $lastUpdated }}</p>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-xl shadow-sm p-8">
            
            <!-- Introduction -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('1. Introduction') }}</h2>
                <p class="text-gray-600 leading-relaxed">
                    {{ __('This privacy policy describes how we collect, use, and protect your personal information when you use our electric vehicle charging services.') }}
                    {{ __('We are committed to protecting your privacy and ensuring compliance with the General Data Protection Regulation (GDPR) and applicable Moroccan data protection laws.') }}
                </p>
            </section>

            <!-- Data We Collect -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('2. Data We Collect') }}</h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    {{ __('We collect the following types of personal information:') }}
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>{{ __('Full name') }}</li>
                    <li>{{ __('Email address') }}</li>
                    <li>{{ __('Phone number') }}</li>
                    <li>{{ __('Address (optional)') }}</li>
                    <li>{{ __('Payment information') }}</li>
                    <li>{{ __('Charging session data (duration, energy consumed)') }}</li>
                    <li>{{ __('IP address and device information') }}</li>
                </ul>
            </section>

            <!-- How We Use Your Data -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('3. How We Use Your Data') }}</h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    {{ __('We use your personal information for the following purposes:') }}
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>{{ __('To process your charging session bookings and payments') }}</li>
                    <li>{{ __('To provide customer support') }}</li>
                    <li>{{ __('To send receipts and transaction confirmations') }}</li>
                    <li>{{ __('To improve our services') }}</li>
                    <li>{{ __('To comply with legal obligations') }}</li>
                </ul>
            </section>

            <!-- Legal Basis -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('4. Legal Basis for Processing') }}</h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    {{ __('We process your personal data based on the following legal grounds:') }}
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li><strong>{{ __('Consent:') }}</strong> {{ __('For marketing communications (with your explicit consent)') }}</li>
                    <li><strong>{{ __('Contract:') }}</strong> {{ __('To fulfill our service agreement for charging sessions') }}</li>
                    <li><strong>{{ __('Legal Obligation:') }}</strong> {{ __('For accounting and tax purposes') }}</li>
                    <li><strong>{{ __('Legitimate Interest:') }}</strong> {{ __('For service improvement and fraud prevention') }}</li>
                </ul>
            </section>

            <!-- Data Sharing -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('5. Data Sharing') }}</h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    {{ __('We may share your data with:') }}
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>{{ __('Payment processors (CMI, Stripe)') }}</li>
                    <li>{{ __('Our partners and integrators operating charging stations') }}</li>
                    <li>{{ __('Legal authorities when required by law') }}</li>
                </ul>
                <p class="text-gray-600 leading-relaxed mt-4">
                    {{ __('We do not sell your personal data to third parties.') }}
                </p>
            </section>

            <!-- Data Retention -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('6. Data Retention') }}</h2>
                <p class="text-gray-600 leading-relaxed">
                    {{ __('We retain your personal data for as long as necessary to fulfill the purposes for which we collected it, including for the purposes of satisfying any legal, accounting, or reporting requirements.') }}
                    {{ __('Typically, we keep transaction data for 5 years for tax and accounting purposes.') }}
                </p>
            </section>

            <!-- Your Rights -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('7. Your Rights (GDPR)') }}</h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    {{ __('Under the GDPR, you have the following rights:') }}
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li><strong>{{ __('Right to Access:') }}</strong> {{ __('You can request a copy of your personal data') }}</li>
                    <li><strong>{{ __('Right to Rectification:') }}</strong> {{ __('You can ask us to correct inaccurate data') }}</li>
                    <li><strong>{{ __('Right to Erasure:') }}</strong> {{ __('You can request deletion of your data (under certain conditions)') }}</li>
                    <li><strong>{{ __('Right to Restrict Processing:') }}</strong> {{ __('You can ask us to limit how we use your data') }}</li>
                    <li><strong>{{ __('Right to Data Portability:') }}</strong> {{ __('You can request your data in a portable format') }}</li>
                    <li><strong>{{ __('Right to Object:') }}</strong> {{ __('You can object to certain processing activities') }}</li>
                    <li><strong>{{ __('Right to Withdraw Consent:') }}</strong> {{ __('You can withdraw consent at any time') }}</li>
                </ul>
            </section>

            <!-- Security -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('8. Security') }}</h2>
                <p class="text-gray-600 leading-relaxed">
                    {{ __('We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.') }}
                    {{ __('All payment transactions are encrypted using industry-standard SSL/TLS protocols.') }}
                </p>
            </section>

            <!-- Contact -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('9. Contact Us') }}</h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    {{ __('If you have any questions about this privacy policy or wish to exercise your rights, please contact us:') }}
                </p>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700"><strong>{{ $companyName }}</strong></p>
                    <p class="text-gray-600">{{ __('Email:') }} <a href="mailto:{{ $companyEmail }}" class="text-green-600 hover:underline">{{ $companyEmail }}</a></p>
                </div>
            </section>

            <!-- Changes to Policy -->
            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('10. Changes to This Policy') }}</h2>
                <p class="text-gray-600 leading-relaxed">
                    {{ __('We may update this privacy policy from time to time.') }}
                    {{ __('We will notify you of any material changes by posting the new policy on this page and updating the "last updated" date.') }}
                </p>
            </section>

        </div>

        <!-- Back Button -->
        <div class="mt-8 text-center">
            <a href="{{ url()->previous() }}" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('Back') }}
            </a>
        </div>

    </div>
</div>
@endsection
