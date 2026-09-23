@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h1 class="mt-6 text-3xl font-extrabold text-gray-900">
                Access Denied
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                You don't have permission to access this resource.
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="text-center">
                <div class="mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">
                        What happened?
                    </h2>
                    <p class="text-sm text-gray-600">
                        @if(session('policy_error'))
                            {{ session('policy_error') }}
                        @else
                            You attempted to access a resource that you don't have permission to view or modify.
                        @endif
                    </p>
                </div>

                <div class="mb-6">
                    <h3 class="text-md font-medium text-gray-900 mb-2">
                        Your Role: 
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if(auth()->user()->hasRole('admin')) bg-red-100 text-red-800
                            @elseif(auth()->user()->hasRole('integrator')) bg-blue-100 text-blue-800
                            @elseif(auth()->user()->hasRole('partner')) bg-purple-100 text-purple-800
                            @elseif(auth()->user()->hasRole('operator')) bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                        </span>
                    </h3>
                </div>

                <div class="mb-6">
                    <h3 class="text-md font-medium text-gray-900 mb-2">
                        What you can do:
                    </h3>
                    <ul class="text-sm text-gray-600 text-left space-y-1">
                        @if(auth()->user()->hasRole('admin'))
                            <li>• Access all resources in the system</li>
                            <li>• Manage integrators, partners, and operators</li>
                            <li>• View system-wide statistics</li>
                        @elseif(auth()->user()->hasRole('integrator'))
                            <li>• Manage your own integrator profile</li>
                            <li>• Manage your partners and their resources</li>
                            <li>• Manage your operators</li>
                            <li>• View your integrator statistics</li>
                        @elseif(auth()->user()->hasRole('partner'))
                            <li>• Manage your own partner profile</li>
                            <li>• Manage your groups and charging points</li>
                            <li>• View your partner statistics</li>
                        @elseif(auth()->user()->hasRole('operator'))
                            <li>• Manage your own operator profile</li>
                            <li>• Manage charging points in your integrator's network</li>
                            <li>• View your operator statistics</li>
                        @else
                            <li>• Contact your administrator for access</li>
                            <li>• Check if you have the correct role assigned</li>
                        @endif
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ url()->previous() }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Go Back
                    </a>
                    
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z" />
                        </svg>
                        Dashboard
                    </a>
                </div>

                @if(config('app.debug'))
                    <div class="mt-6 p-4 bg-gray-100 rounded-md">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Debug Information:</h4>
                        <div class="text-xs text-gray-600">
                            <p><strong>URL:</strong> {{ request()->fullUrl() }}</p>
                            <p><strong>Method:</strong> {{ request()->method() }}</p>
                            <p><strong>User ID:</strong> {{ auth()->id() }}</p>
                            <p><strong>User Roles:</strong> {{ auth()->user()->getRoleNames()->implode(', ') }}</p>
                            @if(session('policy_error'))
                                <p><strong>Policy Error:</strong> {{ session('policy_error') }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
