<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Language Switcher Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .switcher { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
        .buttons { display: flex; gap: 10px; margin: 15px 0; }
        .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn:hover { border-color: #007bff; }
        .btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .status { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; font-family: monospace; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <h1>🧪 Standalone Language Switcher Test</h1>
    <p>This page tests JavaScript execution completely independently of Laravel/Blade.</p>

    <div class="switcher">
        <h2>Language Switcher (Pure JavaScript)</h2>

        <div class="buttons">
            <button id="btn-fr" class="btn" onclick="switchLanguage('fr')">
                <img src="https://flagcdn.com/w20/fr.png" alt="FR" style="width:20px;">
                Français
                <div id="spinner-fr" class="spinner"></div>
            </button>

            <button id="btn-en" class="btn" onclick="switchLanguage('en')">
                <img src="https://flagcdn.com/w20/gb.png" alt="EN" style="width:20px;">
                English
                <div id="spinner-en" class="spinner"></div>
            </button>

            <button id="btn-ar" class="btn" onclick="switchLanguage('ar')">
                <img src="https://flagcdn.com/w20/ma.png" alt="AR" style="width:20px;">
                العربية
                <div id="spinner-ar" class="spinner"></div>
            </button>
        </div>

        <div id="status" class="status">Ready to test...</div>
    </div>

    <div class="switcher">
        <h3>Debug Information:</h3>
        <div id="debug-info" class="status">JavaScript loading...</div>
    </div>

    <script>
        console.log('🚀 Standalone test JavaScript loaded');

        let currentLocale = 'en';
        let isSwitching = false;

        // Update debug info
        function updateDebugInfo() {
            const info = document.getElementById('debug-info');
            info.innerHTML = `
                Current Locale: ${currentLocale}<br>
                Is Switching: ${isSwitching}<br>
                Timestamp: ${new Date().toLocaleString()}<br>
                User Agent: ${navigator.userAgent.substring(0, 50)}...
            `;
        }

        // Switch language function
        async function switchLanguage(locale) {
            console.log('🎯 switchLanguage called with:', locale);

            if (isSwitching || currentLocale === locale) {
                console.log('❌ Blocked - switching:', isSwitching, 'current:', currentLocale);
                updateStatus('❌ Switch blocked', 'error');
                return;
            }

            console.log('✅ Starting switch to:', locale);
            isSwitching = true;
            updateStatus('⏳ Switching language...', '');
            updateDebugInfo();

            // Show spinner
            const spinner = document.getElementById(`spinner-${locale}`);
            const button = document.getElementById(`btn-${locale}`);
            spinner.style.display = 'block';
            button.disabled = true;

            // Disable all buttons
            ['fr', 'en', 'ar'].forEach(lang => {
                document.getElementById(`btn-${lang}`).disabled = true;
            });

            try {
                console.log('🌐 Making AJAX request to:', `/simple-debug-lang/${locale}`);

                const response = await fetch(`/simple-debug-lang/${locale}`);
                console.log('📡 Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('📦 Response data:', data);

                if (data.success) {
                    // Success
                    currentLocale = locale;
                    updateStatus(`✅ Success: ${data.message}`, 'success');

                    // Update document attributes
                    document.documentElement.setAttribute('lang', locale);
                    document.documentElement.setAttribute('dir', data.direction || 'ltr');

                    // Update button styles
                    updateButtonStyles();

                    console.log('🎉 Language switched successfully');
                } else {
                    throw new Error(data.message || 'Switch failed');
                }

            } catch (error) {
                console.error('💥 Error:', error);
                updateStatus(`❌ Error: ${error.message}`, 'error');
            } finally {
                // Hide spinner
                spinner.style.display = 'none';

                // Re-enable buttons
                ['fr', 'en', 'ar'].forEach(lang => {
                    document.getElementById(`btn-${lang}`).disabled = false;
                });

                isSwitching = false;
                updateDebugInfo();
            }
        }

        function updateButtonStyles() {
            ['fr', 'en', 'ar'].forEach(lang => {
                const btn = document.getElementById(`btn-${lang}`);
                if (lang === currentLocale) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        function updateStatus(message, type) {
            const statusEl = document.getElementById('status');
            statusEl.innerHTML = message;
            statusEl.className = 'status ' + type;
        }

        // Initialize on load
        window.addEventListener('load', function() {
            console.log('📱 Page loaded, initializing...');
            updateDebugInfo();
            updateButtonStyles();
            updateStatus('✅ Ready! Click a language button to test.', 'success');
        });

        // Test if JavaScript is working
        console.log('🔧 JavaScript execution test - this should appear in console');
        updateDebugInfo();
    </script>
</body>
</html>
