<div id="steveTerminal" class="fixed bottom-4 right-4 w-80 h-48 bg-black rounded-lg shadow-2xl border border-gray-800 z-50 hidden">
    <!-- Terminal Header -->
    <div class="flex items-center justify-between px-3 py-2 bg-gray-900 rounded-t-lg border-b border-gray-700">
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
        </div>
        <div class="text-xs text-gray-400 font-mono">STEVE API TERMINAL</div>
        <button onclick="closeSteveTerminal()" class="text-gray-400 hover:text-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    
    <!-- Terminal Content -->
    <div class="h-36 overflow-y-auto p-2 font-mono text-xs">
        <div id="terminalContent" class="text-green-400 space-y-1">
            <div class="text-gray-500">$ steve-api ready</div>
            <div class="text-gray-500">$ waiting for commands...</div>
        </div>
    </div>
    
    <!-- Terminal Input -->
    <div class="px-3 py-2 bg-gray-900 rounded-b-lg border-t border-gray-700">
        <div class="flex items-center">
            <span class="text-green-400 mr-2">$</span>
            <input type="text" id="terminalInput" placeholder="Type command..." 
                   class="flex-1 bg-transparent text-green-400 outline-none placeholder-gray-500"
                   onkeypress="handleTerminalInput(event)">
        </div>
    </div>
</div>

<!-- Terminal Toggle Button -->
<button onclick="toggleSteveTerminal()" 
        class="fixed bottom-4 right-4 w-12 h-12 bg-black hover:bg-gray-800 text-green-400 rounded-full shadow-lg border border-gray-700 z-40 transition-all duration-200">
    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M4 12h3"/>
    </svg>
</button>

<script>
let terminalVisible = false;
let terminalHistory = [];
let historyIndex = -1;

function toggleSteveTerminal() {
    const terminal = document.getElementById('steveTerminal');
    terminalVisible = !terminalVisible;
    
    if (terminalVisible) {
        terminal.classList.remove('hidden');
        terminal.classList.add('animate-pulse');
        setTimeout(() => {
            terminal.classList.remove('animate-pulse');
        }, 1000);
    } else {
        terminal.classList.add('hidden');
    }
}

function closeSteveTerminal() {
    const terminal = document.getElementById('steveTerminal');
    terminal.classList.add('hidden');
    terminalVisible = false;
}

function addTerminalLine(text, type = 'info') {
    const terminalContent = document.getElementById('terminalContent');
    const timestamp = new Date().toLocaleTimeString();
    
    let colorClass = 'text-green-400';
    if (type === 'error') colorClass = 'text-red-400';
    if (type === 'warning') colorClass = 'text-yellow-400';
    if (type === 'success') colorClass = 'text-green-300';
    if (type === 'info') colorClass = 'text-blue-400';
    
    const line = document.createElement('div');
    line.className = colorClass;
    line.innerHTML = `<span class="text-gray-500">[${timestamp}]</span> ${text}`;
    
    terminalContent.appendChild(line);
    
    // Scroll to bottom
    terminalContent.scrollTop = terminalContent.scrollHeight;
    
    // Keep only last 50 lines
    const lines = terminalContent.children;
    if (lines.length > 50) {
        terminalContent.removeChild(lines[0]);
    }
}

function handleTerminalInput(event) {
    if (event.key === 'Enter') {
        const input = event.target;
        const command = input.value.trim();
        
        if (command) {
            addTerminalLine(`$ ${command}`, 'info');
            terminalHistory.unshift(command);
            historyIndex = -1;
            
            // Process command
            processTerminalCommand(command);
            
            input.value = '';
        }
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (historyIndex < terminalHistory.length - 1) {
            historyIndex++;
            event.target.value = terminalHistory[historyIndex];
        }
    } else if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (historyIndex > 0) {
            historyIndex--;
            event.target.value = terminalHistory[historyIndex];
        } else {
            event.target.value = '';
        }
    }
}

function processTerminalCommand(command) {
    const parts = command.split(' ');
    const cmd = parts[0].toLowerCase();
    
    switch (cmd) {
        case 'help':
            addTerminalLine('Available commands:', 'info');
            addTerminalLine('  connect <id> - Connect charging point by ID', 'info');
            addTerminalLine('  disconnect <id> - Disconnect charging point', 'info');
            addTerminalLine('  status <id> - Get connection status', 'info');
            addTerminalLine('  test - Test SteVe connectivity', 'info');
            addTerminalLine('  clear - Clear terminal', 'info');
            addTerminalLine('  help - Show this help', 'info');
            break;
            
        case 'connect':
            if (parts[1]) {
                addTerminalLine(`Connecting charging point ${parts[1]}...`, 'info');
                connectById(parts[1]);
            } else {
                addTerminalLine('Usage: connect <charging_point_id>', 'error');
            }
            break;
            
        case 'disconnect':
            if (parts[1]) {
                addTerminalLine(`Disconnecting charging point ${parts[1]}...`, 'info');
                disconnectChargingPoint();
            } else {
                addTerminalLine('Usage: disconnect <charging_point_id>', 'error');
            }
            break;
            
        case 'status':
            if (parts[1]) {
                addTerminalLine(`Getting status for charging point ${parts[1]}...`, 'info');
                getConnectionStatus();
            } else {
                addTerminalLine('Usage: status <charging_point_id>', 'error');
            }
            break;
            
        case 'test':
            addTerminalLine('Testing SteVe connectivity...', 'info');
            testConnectivity();
            break;
            
        case 'clear':
            document.getElementById('terminalContent').innerHTML = '';
            addTerminalLine('Terminal cleared', 'success');
            break;
            
        default:
            addTerminalLine(`Unknown command: ${cmd}. Type 'help' for available commands.`, 'error');
    }
}

// Enhanced API response logging
function logSteVeResponse(action, response, success = true) {
    const timestamp = new Date().toLocaleTimeString();
    const status = success ? 'SUCCESS' : 'ERROR';
    const color = success ? 'text-green-300' : 'text-red-400';
    
    addTerminalLine(`[${timestamp}] ${action}: ${status}`, success ? 'success' : 'error');
    
    if (response) {
        if (typeof response === 'object') {
            addTerminalLine(`Response: ${JSON.stringify(response, null, 2)}`, 'info');
        } else {
            addTerminalLine(`Response: ${response}`, 'info');
        }
    }
}

// Auto-show terminal on API calls
function showTerminalOnAction() {
    if (!terminalVisible) {
        toggleSteveTerminal();
    }
}

// Initialize terminal
document.addEventListener('DOMContentLoaded', function() {
    addTerminalLine('SteVe API Terminal initialized', 'success');
    addTerminalLine('Type "help" for available commands', 'info');
});
</script>
