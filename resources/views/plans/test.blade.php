<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Pricing Plan Form</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Test Pricing Plan Forms</h1>

        <!-- Debug Test Form -->
        <div class="mb-8 p-4 border border-blue-200 rounded-lg bg-blue-50">
            <h2 class="text-xl font-semibold mb-4 text-blue-700">Debug Test Form</h2>
            <form action="{{ route('plans.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="debug_name" class="block text-sm font-medium text-gray-700">Plan Name (Debug):</label>
                    <input type="text" id="debug_name" name="name" value="Debug Plan" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="debug_description" class="block text-sm font-medium text-gray-700">Description:</label>
                    <textarea id="debug_description" name="description" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">This is a debug test plan.</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rate Type:</label>
                    <div class="mt-1 space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="rate_type" value="minute" class="form-radio" checked>
                            <span class="ml-2">À la minute</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="rate_type" value="kwh" class="form-radio">
                            <span class="ml-2">Par kWh</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="debug_price_per_minute" class="block text-sm font-medium text-gray-700">Price Per Minute:</label>
                    <input type="number" step="0.01" id="debug_price_per_minute" name="price_per_minute" value="0.15" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="debug_price_per_kwh" class="block text-sm font-medium text-gray-700">Price Per kWh:</label>
                    <input type="number" step="0.01" id="debug_price_per_kwh" name="price_per_kwh" value="0.30" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="debug_tva_rate" class="block text-sm font-medium text-gray-700">TVA Rate:</label>
                    <input type="number" step="0.01" id="debug_tva_rate" name="tva_rate" value="0.20" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="debug_priority" class="block text-sm font-medium text-gray-700">Priority:</label>
                    <input type="number" id="debug_priority" name="priority" value="1" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="debug_is_active" name="is_active" value="1" checked class="form-checkbox h-4 w-4 text-blue-600 rounded">
                    <label for="debug_is_active" class="ml-2 block text-sm text-gray-900">Is Active</label>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Test Submit (Debug Mode)</button>
            </form>
        </div>

        <!-- Quick Fix Test Form (Select dropdown) -->
        <div class="p-4 border border-green-200 rounded-lg bg-green-50">
            <h2 class="text-xl font-semibold mb-4 text-green-700">Quick Fix Test Form (Select Dropdown)</h2>
            <form action="{{ route('plans.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="quick_fix_name" class="block text-sm font-medium text-gray-700">Plan Name (Quick Fix):</label>
                    <input type="text" id="quick_fix_name" name="name" value="Quick Fix Plan" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="quick_fix_rate_type" class="block text-sm font-medium text-gray-700">Rate Type (Select):</label>
                    <select id="quick_fix_rate_type" name="rate_type" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <option value="">Select Rate Type</option>
                        <option value="minute">À la minute</option>
                        <option value="kwh">Par kWh</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Test Submit (Quick Fix)</button>
            </form>
        </div>
    </div>
</body>
</html>