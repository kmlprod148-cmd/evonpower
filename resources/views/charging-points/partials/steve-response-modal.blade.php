<!-- Modal pour afficher la réponse de l'API Steve - Design Moderne -->
<div id="steve-response-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 py-8 text-center sm:p-0">
        <!-- Backdrop avec effet blur -->
        <div class="fixed inset-0 bg-gradient-to-br from-gray-900 via-black to-gray-900 bg-opacity-95 backdrop-blur-sm transition-opacity duration-300" onclick="document.getElementById('steve-response-modal').classList.add('hidden')"></div>
        
        <!-- Modal Content -->
        <div class="relative inline-block bg-gradient-to-br from-gray-900 via-gray-800 to-black border border-green-500/30 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full max-h-[90vh] flex flex-col"
             style="box-shadow: 0 0 30px rgba(34, 197, 94, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);">
            
            <!-- Header avec design moderne -->
            <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-4 border-b border-green-400/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white/20 rounded-lg p-2 mr-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">Réponse de l'API SteVe</h3>
                    </div>
                    <button onclick="document.getElementById('steve-response-modal').classList.add('hidden'); document.getElementById('download-buttons-container').innerHTML = '';" 
                            class="text-white/80 hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Body avec scroll -->
            <div class="px-6 py-4 overflow-auto flex-1 bg-gradient-to-b from-gray-900 to-black min-h-0">
                <pre id="steve-response-content" class="text-sm font-mono whitespace-pre-wrap break-words leading-relaxed json-response"></pre>
            </div>
            
            <!-- Footer avec boutons -->
            <div id="download-buttons-container" class="bg-gray-800/50 px-6 py-4 border-t border-gray-700 flex justify-end space-x-3">
                <button onclick="document.getElementById('steve-response-modal').classList.add('hidden'); document.getElementById('download-buttons-container').innerHTML = '';" 
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 text-sm font-semibold rounded-lg border border-gray-600 hover:border-gray-500 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

