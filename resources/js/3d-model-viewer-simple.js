// Simple 3D Model Viewer Component - Fallback Version
class Simple3DViewer {
    constructor(containerId, modelPath) {
        this.containerId = containerId;
        this.modelPath = modelPath;
        this.container = null;
        this.animationId = null;
        this.time = 0;
        
        this.init();
    }
    
    init() {
        try {
            this.container = document.getElementById(this.containerId);
            if (!this.container) {
                console.warn(`Container ${this.containerId} not found`);
                return;
            }
            
            // Show a simple animated placeholder
            this.showPlaceholder();
            this.animate();
            
        } catch (error) {
            console.error('Error initializing Simple3DViewer:', error);
            this.showFallback();
        }
    }
    
    showPlaceholder() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="flex items-center justify-center h-full bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl relative overflow-hidden">
                    <!-- Animated background -->
                    <div class="absolute inset-0 opacity-20">
                        <div class="absolute top-1/4 left-1/4 w-32 h-32 bg-green-400 rounded-full blur-xl animate-pulse"></div>
                        <div class="absolute bottom-1/4 right-1/4 w-24 h-24 bg-blue-400 rounded-full blur-xl animate-pulse" style="animation-delay: 1s;"></div>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-16 h-16 bg-purple-400 rounded-full blur-xl animate-pulse" style="animation-delay: 2s;"></div>
                    </div>
                    
                    <!-- Main content -->
                    <div class="text-center relative z-10">
                        <div class="mb-6">
                            <svg class="mx-auto h-20 w-20 text-green-400 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-green-400 mb-2">EvonPower</h3>
                        <p class="text-gray-300 text-sm mb-4">Recharge électrique intelligente</p>
                        <div class="flex justify-center space-x-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                        </div>
                    </div>
                    
                    <!-- Floating elements -->
                    <div class="absolute top-4 left-4 w-8 h-8 border-2 border-green-400 rounded-full animate-spin"></div>
                    <div class="absolute bottom-4 right-4 w-6 h-6 border-2 border-blue-400 rounded-full animate-spin" style="animation-duration: 3s;"></div>
                </div>
            `;
        }
    }
    
    showFallback() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="flex items-center justify-center h-full bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-green-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2" />
                        </svg>
                        <p class="text-green-400 font-medium">EvonPower</p>
                        <p class="text-gray-400 text-sm mt-2">Recharge électrique intelligente</p>
                    </div>
                </div>
            `;
        }
    }
    
    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());
        this.time += 0.01;
        
        // Add subtle animations to the placeholder
        const container = this.container;
        if (container) {
            const animatedElements = container.querySelectorAll('.animate-pulse, .animate-spin, .animate-bounce');
            animatedElements.forEach((element, index) => {
                // Add subtle movement
                const offset = Math.sin(this.time + index * 0.5) * 2;
                element.style.transform = `translateY(${offset}px)`;
            });
        }
    }
    
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
    }
}

// Safe Model3DViewer initialization
(function() {
    'use strict';
    
    // Check if Three.js is available
    function isThreeJSAvailable() {
        return typeof THREE !== 'undefined' && typeof THREE.GLTFLoader !== 'undefined';
    }
    
    // Initialize the appropriate viewer
    function initializeViewer(containerId, modelPath) {
        if (isThreeJSAvailable() && typeof window.Model3DViewer !== 'undefined') {
            try {
                return new window.Model3DViewer(containerId, modelPath);
            } catch (error) {
                console.warn('Model3DViewer failed, using Simple3DViewer:', error);
                return new Simple3DViewer(containerId, modelPath);
            }
        } else {
            console.log('Three.js not available, using Simple3DViewer');
            return new Simple3DViewer(containerId, modelPath);
        }
    }
    
    // Export the safe viewer
    window.Safe3DViewer = {
        create: initializeViewer,
        Simple3DViewer: Simple3DViewer
    };
    
})();
