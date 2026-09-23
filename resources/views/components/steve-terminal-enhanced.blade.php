{{-- Enhanced Steve Terminal with API Response Screen --}}
<div class="relative">
    {{-- Include the API Response Screen --}}
    <x-api-response-screen id="steve-api-responses" position="bottom-right" />

    {{-- Original Steve Terminal --}}
    <div id="steveTerminal" class="bg-black text-green-400 font-mono text-sm rounded-lg shadow-2xl border border-gray-700 overflow-hidden"
         style="width: 800px; height: 500px;">

        {{-- Terminal Header --}}
        <div class="flex items-center justify-between px-4 py-2 bg-gray-900 border-b border-gray-700">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span class="text-white text-sm font-semibold">SteVe Terminal</span>
            </div>
            <div class="text-xs text-gray-500">EVON Charging Management</div>
        </div>

        {{-- Terminal Content --}}
        <div id="terminalContent" class="p-4 h-full overflow-auto bg-black"
             style="height: calc(100% - 120px);">
            <div id="terminalLines">
                <div class="text-green-400 mb-2">
                    <span class="text-cyan-400">steve@evon:</span><span class="text-yellow-400">~</span>$ Welcome to SteVe API Terminal
                </div>
                <div class="text-green-400 mb-2">
                    <span class="text-cyan-400">steve@evon:</span><span class="text-yellow-400">~</span>$ Type 'help' for available commands
                </div>
                <div class="text-green-400">
                    <span class="text-cyan-400">steve@evon:</span><span class="text-yellow-400">~</span>$ <span id="commandPrompt"></span>
                </div>
            </div>
        </div>

        {{-- Terminal Input --}}
        <div class="px-4 py-3 bg-gray-900 border-t border-gray-700">
            <div class="flex items-center space-x-2">
                <span class="text-green-400 text-sm">
                    <span class="text-cyan-400">steve@evon:</span><span class="text-yellow-400">~</span>$
                </span>
                <input type="text"
                       id="terminalInput"
                       class="flex-1 bg-transparent text-green-400 font-mono text-sm outline-none border-none"
                       placeholder="Enter SteVe command..."
                       autocomplete="off">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const terminalInput = document.getElementById('terminalInput');
    const terminalLines = document.getElementById('terminalLines');
    const commandPrompt = document.getElementById('commandPrompt');
    let commandHistory = [];
    let historyIndex = -1;

    // Available commands
    const commands = {
        'help': {
            description: 'Show available commands',
            action: function() {
                return `Available SteVe API Commands:

• help                    - Show this help message
• status                  - Get SteVe server status
• connectors              - List all connectors
• transactions            - Get active transactions
• start <id> <tag>        - Start charging session
• stop <id> <transaction>  - Stop charging session
• clear                  - Clear terminal
• exit                   - Close terminal

Example: start CP001 RFID123`;
            }
        },
        'status': {
            description: 'Get SteVe server status',
            action: async function() {
                showApiLoading('Checking SteVe server status...');

                try {
                    const response = await fetch('/steve/status');
                    const data = await response.json();

                    hideApiLoading();
                    showApiResponse(data, 'SteVe Server Status', response.ok ? 'success' : 'error');

                    return `Server Status: ${response.ok ? 'ONLINE' : 'OFFLINE'}
Response: ${JSON.stringify(data, null, 2)}`;
                } catch (error) {
                    hideApiLoading();
                    showApiResponse(error.message, 'SteVe Server Status', 'error');
                    return `Error: ${error.message}`;
                }
            }
        },
        'connectors': {
            description: 'List all connectors',
            action: async function() {
                showApiLoading('Fetching connectors...');

                try {
                    const response = await fetch('/steve/connectors');
                    const data = await response.json();

                    hideApiLoading();
                    showApiResponse(data, 'Connector List', response.ok ? 'success' : 'error');

                    return `Found ${data.length || 0} connectors
Response: ${JSON.stringify(data, null, 2)}`;
                } catch (error) {
                    hideApiLoading();
                    showApiResponse(error.message, 'Connector List', 'error');
                    return `Error: ${error.message}`;
                }
            }
        },
        'transactions': {
            description: 'Get active transactions',
            action: async function() {
                showApiLoading('Fetching active transactions...');

                try {
                    const response = await fetch('/steve/transactions');
                    const data = await response.json();

                    hideApiLoading();
                    showApiResponse(data, 'Active Transactions', response.ok ? 'success' : 'error');

                    return `Found ${data.length || 0} active transactions
Response: ${JSON.stringify(data, null, 2)}`;
                } catch (error) {
                    hideApiLoading();
                    showApiResponse(error.message, 'Active Transactions', 'error');
                    return `Error: ${error.message}`;
                }
            }
        },
        'start': {
            description: 'Start charging session',
            action: async function(args) {
                if (!args || args.length < 2) {
                    return 'Usage: start <connector_id> <rfid_tag>';
                }

                const [connectorId, rfidTag] = args;
                showApiLoading(`Starting charging session on ${connectorId}...`);

                try {
                    const response = await fetch('/steve/start', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            connector_id: connectorId,
                            rfid_tag: rfidTag
                        })
                    });

                    const data = await response.json();

                    hideApiLoading();
                    showApiResponse(data, `Start Charging: ${connectorId}`, response.ok ? 'success' : 'error');

                    return `Charging session ${response.ok ? 'started' : 'failed'}
Response: ${JSON.stringify(data, null, 2)}`;
                } catch (error) {
                    hideApiLoading();
                    showApiResponse(error.message, `Start Charging: ${connectorId}`, 'error');
                    return `Error: ${error.message}`;
                }
            }
        },
        'stop': {
            description: 'Stop charging session',
            action: async function(args) {
                if (!args || args.length < 2) {
                    return 'Usage: stop <connector_id> <transaction_id>';
                }

                const [connectorId, transactionId] = args;
                showApiLoading(`Stopping charging session on ${connectorId}...`);

                try {
                    const response = await fetch('/steve/stop', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            connector_id: connectorId,
                            transaction_id: transactionId
                        })
                    });

                    const data = await response.json();

                    hideApiLoading();
                    showApiResponse(data, `Stop Charging: ${connectorId}`, response.ok ? 'success' : 'error');

                    return `Charging session ${response.ok ? 'stopped' : 'stop failed'}
Response: ${JSON.stringify(data, null, 2)}`;
                } catch (error) {
                    hideApiLoading();
                    showApiResponse(error.message, `Stop Charging: ${connectorId}`, 'error');
                    return `Error: ${error.message}`;
                }
            }
        },
        'clear': {
            description: 'Clear terminal',
            action: function() {
                terminalLines.innerHTML = `
                    <div class="text-green-400 mb-2">
                        <span class="text-cyan-400">steve@evon:</span><span class="text-yellow-400">~</span>$ Terminal cleared
                    </div>
                    <div class="text-green-400">
                        <span class="text-cyan-400">steve@evon:</span><span class="text-yellow-400">~</span>$ <span id="commandPrompt"></span>
                    </div>
                `;
                return '';
            }
        }
    };

    // Add terminal line
    function addTerminalLine(text, className = '') {
        const line = document.createElement('div');
        line.className = `text-green-400 mb-1 ${className}`;
        line.textContent = text;
        terminalLines.appendChild(line);
        terminalLines.scrollTop = terminalLines.scrollHeight;
    }

    // Process command
    async function processCommand(command) {
        const parts = command.trim().split(' ');
        const cmd = parts[0].toLowerCase();
        const args = parts.slice(1);

        if (commands[cmd]) {
            try {
                const result = await commands[cmd].action(args);
                if (result) {
                    addTerminalLine(result);
                }
            } catch (error) {
                addTerminalLine(`Error executing command: ${error.message}`, 'text-red-400');
            }
        } else if (command.trim() !== '') {
            addTerminalLine(`Command not found: ${cmd}. Type 'help' for available commands.`, 'text-red-400');
        }
    }

    // Handle input
    terminalInput.addEventListener('keydown', async function(e) {
        if (e.key === 'Enter') {
            const command = terminalInput.value;
            if (command.trim() !== '') {
                addTerminalLine(`$ ${command}`);
                commandHistory.push(command);
                historyIndex = commandHistory.length;
                terminalInput.value = '';
                await processCommand(command);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (historyIndex > 0) {
                historyIndex--;
                terminalInput.value = commandHistory[historyIndex];
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++;
                terminalInput.value = commandHistory[historyIndex];
            } else {
                historyIndex = commandHistory.length;
                terminalInput.value = '';
            }
        }
    });

    // Focus input on click
    document.getElementById('steveTerminal').addEventListener('click', function() {
        terminalInput.focus();
    });

    // Initial focus
    terminalInput.focus();
});
</script>
