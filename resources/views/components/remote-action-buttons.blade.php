@props([
    'chargingPointId',
])

<div class="bg-white shadow-sm hover:shadow-md rounded-lg mb-4 md:mb-6 transition-shadow border border-gray-100">
    <div class="p-4 md:p-5 border-b border-gray-200">
        <h3 class="text-base md:text-lg font-semibold text-gray-900">{{ __('messages.remote_actions') }}</h3>
        <p class="text-xs md:text-sm text-gray-600 mt-0.5">{{ __('messages.control_charging_point_remotely') }}</p>
    </div>
    <div class="p-3 md:p-4">
        <ul class="space-y-2">
                            <li>
                                <button 
                                    id="start-charging-btn" 
                                    onclick="startChargingAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="start">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-xs md:text-sm">{{ __('messages.start_charging_remote') }}</span>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="stop-charging-btn" 
                                    onclick="stopChargingAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="stop">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span class="text-xs md:text-sm">{{ __('messages.stop_charging') }}</span>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="unlock-connector-btn" 
                                    onclick="unlockConnectorAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="unlock">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        <span class="text-xs md:text-sm">{{ __('messages.unlock_connector') }}</span>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="reset-charging-point-btn" 
                                    onclick="resetChargingPointAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="reset">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span class="text-xs md:text-sm">{{ __('messages.reset') }}</span>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="update-config-btn" 
                                    onclick="updateChargingPointConfigAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="update-config">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="text-xs md:text-sm">{{ __('messages.update_parameters') }}</span>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="diagnostic-btn" 
                                    onclick="getDiagnosticAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-white hover:bg-green-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="diagnostic">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span class="text-xs md:text-sm">Diagnostic</span>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button 
                                    id="get-logs-btn" 
                                    onclick="getLogsAction({{ $chargingPointId }})" 
                                    class="w-full text-left block px-3 py-2 md:px-4 md:py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors text-sm font-medium remote-action-btn"
                                    data-action="get-logs">
                                    <div class="flex items-center">
                                        <svg class="h-3.5 w-3.5 md:h-4 md:w-4 mr-2 md:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-xs md:text-sm">Récupérer le log</span>
                                    </div>
                                </button>
                            </li>
        </ul>
    </div>
</div>
