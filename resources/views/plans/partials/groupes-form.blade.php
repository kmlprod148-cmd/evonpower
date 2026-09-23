<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <!-- Enterprise Selection -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Entreprise associée</label>
        <div class="relative">
            <select name="enterprise_id" class="block w-full border border-gray-200 rounded-lg py-2 px-3 pr-8 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d] bg-gray-50">
                <option value="">Sélectionnez une entreprise</option>
                @foreach($enterprises as $enterprise)
                    <option value="{{ $enterprise->id }}" {{ old('enterprise_id', $pricingPlan->enterprise_id ?? '') == $enterprise->id ? 'selected' : '' }}>
                        {{ $enterprise->name }}
                    </option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        @error('enterprise_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium">Groupes de bornes</h2>
        <div class="flex items-center gap-1 px-2 py-1 bg-[#49ce7d1f] rounded-lg">
            <span class="text-[#49ce7d] font-medium text-sm">{{ isset($selectedGroups) ? count($selectedGroups) : 0 }} sélectionné(s)</span>
        </div>
    </div>
    
    <p class="text-gray-500 text-sm mb-6">Sélectionnez les groupes de bornes auxquels ce plan tarifaire sera appliqué.</p>
    
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Available Groups (Left) -->
        <div class="flex-1">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-700">Groupes disponibles</h3>
                        <div class="relative">
                            <input type="text" id="searchGroups" placeholder="Rechercher" 
                                   class="text-sm rounded-lg border-gray-300 pr-8 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-2 max-h-[300px] overflow-y-auto available-groups">
                    @if(isset($groups) && count($groups) > 0)
                        @foreach($groups as $group)
                            @if(!isset($selectedGroups) || !in_array($group->id, $selectedGroups))
                                <div class="group-item p-2 rounded-lg hover:bg-gray-50 cursor-pointer flex items-center justify-between" data-group-id="{{ $group->id }}" data-group-name="{{ $group->name }}">
                                    <div>
                                        <div class="font-medium text-sm">{{ $group->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $group->charging_points_count ?? 0 }} bornes</div>
                                    </div>
                                    <button type="button" class="add-group text-[#49ce7d] hover:text-[#3db96a]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center p-4 text-sm text-gray-500<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium">Groupes de bornes</h2>
        <div class="flex items-center gap-1 px-2 py-1 bg-[#49ce7d1f] rounded-lg">
            <span class="text-[#49ce7d] font-medium text-sm">{{ isset($selectedGroups) ? count($selectedGroups) : 0 }} sélectionné(s)</span>
        </div>
    </div>
    
    <p class="text-gray-500 text-sm mb-6">Sélectionnez les groupes de bornes auxquels ce plan tarifaire sera appliqué.</p>
    
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Available Groups (Left) -->
        <div class="flex-1">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-700">Groupes disponibles</h3>
                        <div class="relative">
                            <input type="text" id="searchGroups" placeholder="Rechercher" 
                                   class="text-sm rounded-lg border-gray-300 pr-8 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-2 max-h-[300px] overflow-y-auto available-groups">
                    @if(isset($groups) && count($groups) > 0)
                        @foreach($groups as $group)
                            @if(!isset($selectedGroups) || !in_array($group->id, $selectedGroups))
                                <div class="group-item p-2 rounded-lg hover:bg-gray-50 cursor-pointer flex items-center justify-between" data-group-id="{{ $group->id }}" data-group-name="{{ $group->name }}">
                                    <div>
                                        <div class="font-medium text-sm">{{ $group->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $group->charging_points_count ?? 0 }} bornes</div>
                                    </div>
                                    <button type="button" class="add-group text-[#49ce7d] hover:text-[#3db96a]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center p-4 text-sm text-gray-500">Aucun groupe disponible</div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Selected Groups (Right) -->
        <div class="flex-1">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-700">Groupes sélectionnés</h3>
                </div>
                
                <div class="p-2 max-h-[300px] overflow-y-auto selected-groups">
                    @if(isset($selectedGroups) && count($selectedGroups) > 0 && isset($groups))
                        @foreach($groups as $group)
                            @if(in_array($group->id, $selectedGroups))
                                <div class="group-item p-2 rounded-lg hover:bg-gray-50 cursor-pointer flex items-center justify-between" data-group-id="{{ $group->id }}" data-group-name="{{ $group->name }}">
                                    <div>
                                        <div class="font-medium text-sm">{{ $group->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $group->charging_points_count ?? 0 }} bornes</div>
                                    </div>
                                    <button type="button" class="remove-group text-red-500 hover:text-red-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="group_ids[]" value="{{ $group->id }}">
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center p-4 text-sm text-gray-500 empty-message">Aucun groupe sélectionné</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end mt-4">
        <button type="button" id="selectAllGroups" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-2">
            <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Sélectionner tout
        </button>
        <button type="button" id="clearAllGroups" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
            <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            Effacer tout
        </button>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const availableGroups = document.querySelector('.available-groups');
            const selectedGroups = document.querySelector('.selected-groups');
            const searchInput = document.getElementById('searchGroups');
            const selectAllBtn = document.getElementById('selectAllGroups');
            const clearAllBtn = document.getElementById('clearAllGroups');
            
            // Initialize click handlers for existing items
            initializeGroupItems();
            
            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                // Filter available groups
                availableGroups.querySelectorAll('.group-item').forEach(function(group) {
                    const groupName = group.getAttribute('data-group-name').toLowerCase();
                    if (groupName.includes(searchTerm) || searchTerm === '') {
                        group.classList.remove('hidden');
                    } else {
                        group.classList.add('hidden');
                    }
                });
            });
            
            // Select all groups
            selectAllBtn.addEventListener('click', function() {
                availableGroups.querySelectorAll('.group-item:not(.hidden)').forEach(function(group) {
                    moveGroupToSelected(group);
                });
                
                updateSelectedCount();
                checkEmptyMessages();
            });
            
            // Clear all groups
            clearAllBtn.addEventListener('click', function() {
                selectedGroups.querySelectorAll('.group-item').forEach(function(group) {
                    moveGroupToAvailable(group);
                });
                
                updateSelectedCount();
                checkEmptyMessages();
            });
            
            // Initialize event handlers for group items
            function initializeGroupItems() {
                // Add group buttons
                availableGroups.querySelectorAll('.add-group').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const groupItem = this.closest('.group-item');
                        moveGroupToSelected(groupItem);
                        updateSelectedCount();
                        checkEmptyMessages();
                    });
                });
                
                // Remove group buttons
                selectedGroups.querySelectorAll('.remove-group').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const groupItem = this.closest('.group-item');
                        moveGroupToAvailable(groupItem);
                        updateSelectedCount();
                        checkEmptyMessages();
                    });
                });
            }
            
            // Move a group from available to selected
            function moveGroupToSelected(groupItem) {
                const clone = groupItem.cloneNode(true);
                const groupId = groupItem.getAttribute('data-group-id');
                
                // Update the button to be a remove button
                const button = clone.querySelector('button');
                button.className = 'remove-group text-red-500 hover:text-red-700';
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>';
                
                // Add a hidden input for form submission
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'group_ids[]';
                input.value = groupId;
                clone.appendChild(input);
                
                // Add event listener to the remove button
                button.addEventListener('click', function() {
                    moveGroupToAvailable(clone);
                    updateSelectedCount();
                    checkEmptyMessages();
                });
                
                // Remove the empty message if exists
                const emptyMessage = selectedGroups.querySelector('.empty-message');
                if (emptyMessage) {
                    emptyMessage.remove();
                }
                
                // Add to selected groups
                selectedGroups.appendChild(clone);
                
                // Remove from available groups
                groupItem.remove();
            }
            
            // Move a group from selected to available
            function moveGroupToAvailable(groupItem) {
                const clone = groupItem.cloneNode(true);
                
                // Update the button to be an add button
                const button = clone.querySelector('button');
                button.className = 'add-group text-[#49ce7d] hover:text-[#3db96a]';
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>';
                
                // Remove the hidden input
                const input = clone.querySelector('input[type="hidden"]');
                if (input) {
                    input.remove();
                }
                
                // Add event listener to the add button
                button.addEventListener('click', function() {
                    moveGroupToSelected(clone);
                    updateSelectedCount();
                    checkEmptyMessages();
                });
                
                // Add to available groups
                availableGroups.appendChild(clone);
                
                // Remove from selected groups
                groupItem.remove();
            }
            
            // Update the selected count badge
            function updateSelectedCount() {
                const count = selectedGroups.querySelectorAll('.group-item').length;
                const badge = document.querySelector('.flex.items-center.gap-1 span');
                badge.textContent = count + ' sélectionné(s)';
            }
            
            // Check if we need to show empty messages
            function checkEmptyMessages() {
                // Check selected groups
                if (selectedGroups.querySelectorAll('.group-item').length === 0 && !selectedGroups.querySelector('.empty-message')) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.className = 'text-center p-4 text-sm text-gray-500 empty-message';
                    emptyMessage.textContent = 'Aucun groupe sélectionné';
                    selectedGroups.appendChild(emptyMessage);
                }
                
                // Check available groups
                if (availableGroups.querySelectorAll('.group-item').length === 0 && !availableGroups.querySelector('.text-center')) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.className = 'text-center p-4 text-sm text-gray-500';
                    emptyMessage.textContent = 'Aucun groupe disponible';
                    availableGroups.appendChild(emptyMessage);
                }
            }
        });
    </script>
    @endpush
</div>