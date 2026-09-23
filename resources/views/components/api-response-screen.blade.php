{{-- Black Small Screen for Steve API Responses --}}
@props(['id' => 'api-response-screen', 'position' => 'bottom-right'])

@php
    $positionClasses = [
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'center' => 'top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2'
    ];
@endphp

<div id="{{ $id }}"
     class="fixed {{ $positionClasses[$position] }} z-50 hidden"
     x-data="apiResponseScreen()"
     x-show="isVisible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95">

    {{-- Black Small Screen Container --}}
    <div class="bg-black bg-opacity-90 text-green-400 font-mono text-xs rounded-lg shadow-2xl border border-gray-700 overflow-hidden"
         :class="{ 'w-80 h-48': !isExpanded, 'w-96 h-64': isExpanded }">

        {{-- Header --}}
        <div class="flex items-center justify-between px-3 py-2 bg-gray-900 border-b border-gray-700">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-white text-xs font-semibold ml-2">API Response</span>
            </div>
            <div class="flex items-center space-x-1">
                <button @click="toggleExpand()"
                        class="text-gray-400 hover:text-white p-1 rounded"
                        title="Toggle Size">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              :d="isExpanded ? 'M20 12H4M12 20V4' : 'M12 4v16m8-8H4'"></path>
                    </svg>
                </button>
                <button @click="closeScreen()"
                        class="text-gray-400 hover:text-white p-1 rounded"
                        title="Close">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-3 h-full overflow-auto bg-black">
            {{-- Status Indicator --}}
            <div class="mb-2 flex items-center space-x-2">
                <span class="text-xs text-gray-500">Status:</span>
                <span class="px-2 py-0.5 rounded text-xs font-semibold"
                      :class="{
                          'bg-green-900 text-green-300': status === 'success',
                          'bg-red-900 text-red-300': status === 'error',
                          'bg-yellow-900 text-yellow-300': status === 'warning',
                          'bg-blue-900 text-blue-300': status === 'info'
                      }">
                    <span x-text="statusText"></span>
                </span>
                <span class="text-xs text-gray-500" x-text="timestamp"></span>
            </div>

            {{-- Response Content --}}
            <div class="space-y-1">
                <div class="text-xs text-gray-500 mb-1" x-text="action"></div>
                <pre class="text-green-400 text-xs leading-tight whitespace-pre-wrap overflow-x-auto"
                     x-text="response"
                     style="max-height: 120px; overflow-y: auto;"></pre>
            </div>

            {{-- Loading Indicator --}}
            <div x-show="isLoading" class="mt-2 flex items-center space-x-2">
                <div class="animate-spin rounded-full h-3 w-3 border border-green-400 border-t-transparent"></div>
                <span class="text-xs text-gray-500">Processing...</span>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for API Response Screen --}}
<script>
function apiResponseScreen() {
    return {
        isVisible: false,
        isExpanded: false,
        status: 'info',
        statusText: 'Ready',
        action: '',
        response: '',
        timestamp: '',
        isLoading: false,
        autoHideTimeout: null,

        show(response, action = 'API Call', status = 'success', autoHide = true) {
            this.isVisible = true;
            this.action = action;
            this.response = typeof response === 'string' ? response : JSON.stringify(response, null, 2);
            this.status = status;
            this.timestamp = new Date().toLocaleTimeString();
            this.isLoading = false;

            // Set status text
            switch(status) {
                case 'success':
                    this.statusText = 'Success';
                    break;
                case 'error':
                    this.statusText = 'Error';
                    break;
                case 'warning':
                    this.statusText = 'Warning';
                    break;
                case 'info':
                default:
                    this.statusText = 'Info';
                    break;
            }

            // Auto-hide after 5 seconds for success messages
            if (autoHide && status === 'success') {
                this.clearAutoHide();
                this.autoHideTimeout = setTimeout(() => {
                    this.closeScreen();
                }, 5000);
            }
        },

        showLoading(action = 'Processing...') {
            this.isVisible = true;
            this.action = action;
            this.response = '';
            this.status = 'info';
            this.statusText = 'Processing';
            this.timestamp = new Date().toLocaleTimeString();
            this.isLoading = true;
        },

        hideLoading() {
            this.isLoading = false;
        },

        toggleExpand() {
            this.isExpanded = !this.isExpanded;
        },

        closeScreen() {
            this.isVisible = false;
            this.clearAutoHide();
        },

        clearAutoHide() {
            if (this.autoHideTimeout) {
                clearTimeout(this.autoHideTimeout);
                this.autoHideTimeout = null;
            }
        }
    }
}

// Global function to show API responses
window.showApiResponse = function(response, action, status = 'success', autoHide = true) {
    const screen = document.querySelector('[x-data*="apiResponseScreen"]')._x_dataStack[0];
    screen.show(response, action, status, autoHide);
};

// Global function to show loading
window.showApiLoading = function(action = 'Processing...') {
    const screen = document.querySelector('[x-data*="apiResponseScreen"]')._x_dataStack[0];
    screen.showLoading(action);
};

// Global function to hide loading
window.hideApiLoading = function() {
    const screen = document.querySelector('[x-data*="apiResponseScreen"]')._x_dataStack[0];
    screen.hideLoading();
};
</script>
