{{-- Steve API Response Screen Demo --}}
@extends('layouts.app')

@section('title', 'SteVe API Response Screen Demo')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">

        {{-- Header --}}
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <h1 class="text-2xl font-bold text-center">
                    SteVe API Response Screen Demo
                </h1>
                <p class="text-center text-blue-100 mt-2">
                    Black small screen displaying server responses during API actions
                </p>
            </div>
        </div>

        {{-- API Response Screen --}}
        <x-api-response-screen id="demo-api-responses" position="bottom-right" />

        {{-- Demo Controls --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- Left Column: Demo Actions --}}
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-800 text-white">
                    <h2 class="text-xl font-semibold">Demo API Actions</h2>
                    <p class="text-gray-300 text-sm mt-1">Click buttons to see API response screen in action</p>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Success Response --}}
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Success Response</h3>
                        <button onclick="demoSuccessResponse()"
                                class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors">
                            Show Success Response
                        </button>
                    </div>

                    {{-- Error Response --}}
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Error Response</h3>
                        <button onclick="demoErrorResponse()"
                                class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors">
                            Show Error Response
                        </button>
                    </div>

                    {{-- Warning Response --}}
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Warning Response</h3>
                        <button onclick="demoWarningResponse()"
                                class="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition-colors">
                            Show Warning Response
                        </button>
                    </div>

                    {{-- Info Response --}}
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Info Response</h3>
                        <button onclick="demoInfoResponse()"
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                            Show Info Response
                        </button>
                    </div>

                    {{-- Loading State --}}
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Loading State</h3>
                        <button onclick="demoLoadingState()"
                                class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition-colors">
                            Show Loading State
                        </button>
                    </div>

                    {{-- Simulated API Call --}}
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Simulated API Call</h3>
                        <button onclick="demoApiCall()"
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition-colors">
                            Simulate API Call
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right Column: Enhanced Steve Terminal --}}
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-800 text-white">
                    <h2 class="text-xl font-semibold">Enhanced SteVe Terminal</h2>
                    <p class="text-gray-300 text-sm mt-1">Terminal with integrated API response display</p>
                </div>

                <div class="p-6">
                    {{-- Include the enhanced Steve terminal --}}
                    <x-steve-terminal-enhanced />
                </div>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mt-8">
            <div class="px-6 py-4 bg-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">How It Works</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">API Response Screen Features:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Black terminal-style design</li>
                            <li>Expandable/collapsible interface</li>
                            <li>Color-coded status indicators</li>
                            <li>Auto-hide for success messages</li>
                            <li>Manual close button</li>
                            <li>Loading state display</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Integration Points:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>SteVe API calls</li>
                            <li>Real-time response display</li>
                            <li>Error handling</li>
                            <li>Loading indicators</li>
                            <li>Status notifications</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-2">Usage in Code:</h4>
                    <pre class="text-sm text-gray-800 bg-white p-3 rounded border overflow-x-auto"><code>// Show API response
showApiResponse(responseData, 'API Action Name', 'success');

// Show loading state
showApiLoading('Processing request...');

// Hide loading state
hideApiLoading();</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Demo functions
function demoSuccessResponse() {
    const mockResponse = {
        status: 'success',
        message: 'Charging session started successfully',
        data: {
            transaction_id: 'TXN-2024-001',
            connector_id: 'CP001',
            start_time: new Date().toISOString()
        }
    };

    showApiResponse(mockResponse, 'Start Charging Session', 'success');
}

function demoErrorResponse() {
    const mockResponse = {
        status: 'error',
        message: 'Failed to start charging session',
        error: 'Connector CP001 is currently occupied',
        code: 'CONNECTOR_BUSY'
    };

    showApiResponse(mockResponse, 'Start Charging Session', 'error', false);
}

function demoWarningResponse() {
    const mockResponse = {
        status: 'warning',
        message: 'Session started with warnings',
        warnings: [
            'Connector voltage is below optimal level',
            'RFID tag validation took longer than expected'
        ]
    };

    showApiResponse(mockResponse, 'Start Charging Session', 'warning');
}

function demoInfoResponse() {
    const mockResponse = {
        status: 'info',
        message: 'Connector status check completed',
        connectors: [
            { id: 'CP001', status: 'available', power: '22kW' },
            { id: 'CP002', status: 'occupied', power: '11kW' },
            { id: 'CP003', status: 'out_of_order', power: '0kW' }
        ]
    };

    showApiResponse(mockResponse, 'Connector Status Check', 'info');
}

function demoLoadingState() {
    showApiLoading('Checking system status...');

    // Simulate API call delay
    setTimeout(() => {
        hideApiLoading();
        showApiResponse({
            status: 'success',
            message: 'System status check completed',
            uptime: '5 days, 12 hours',
            active_sessions: 24
        }, 'System Status Check', 'success');
    }, 3000);
}

function demoApiCall() {
    showApiLoading('Making API call to SteVe server...');

    // Simulate a realistic API call flow
    setTimeout(() => {
        // First, show an info response
        hideApiLoading();
        showApiResponse({
            status: 'connecting',
            message: 'Connecting to SteVe server...',
            server_url: 'http://158.69.27.239:8080'
        }, 'Server Connection', 'info');

        // Then simulate the actual response after another delay
        setTimeout(() => {
            showApiResponse({
                status: 'success',
                message: 'Successfully retrieved connector information',
                data: {
                    total_connectors: 12,
                    available: 8,
                    occupied: 3,
                    out_of_order: 1
                }
            }, 'Get Connectors', 'success');
        }, 2000);
    }, 1500);
}
</script>
@endsection
