<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Language Switcher Test</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Language Switcher Test</h1>

        <!-- Current Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Current Status</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>App Locale:</strong> <span id="current-locale">en</span></div>
                <div><strong>Document Direction:</strong> <span id="doc-dir">ltr</span></div>
            </div>
            <div class="mt-4 p-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700">
                <p class="text-sm"><strong>Note:</strong> Language switching automatically reloads the page to apply changes instantly.</p>
            </div>
        </div>

        <!-- Language Switcher Component -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Instant Language Switcher (AJAX)</h2>
            
            <div class="simple-language-switcher">
                <div class="flex items-center space-x-1">
                    <button id="lang-fr" onclick="switchLanguage('fr')" class="lang-btn px-3 py-1 rounded border-2 bg-white border-gray-300 text-sm">FR</button>
                    <button id="lang-en" onclick="switchLanguage('en')" class="lang-btn px-3 py-1 rounded border-2 bg-white border-gray-300 text-sm">EN</button>
                    <button id="lang-ar" onclick="switchLanguage('ar')" class="lang-btn px-3 py-1 rounded border-2 bg-white border-gray-300 text-sm">AR</button>
                </div>
            </div>

            <script>
            let isSwitching = false;

            function switchLanguage(locale) {
                if (isSwitching) {
                    return;
                }
                
                isSwitching = true;
                console.log('Switching to locale:', locale);
                
                // Use force language route
                fetch('/force-lang/' + locale)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Force language result:', data);
                        if (data.success) {
                            // If successful, reload the page
                            window.location.reload();
                        } else {
                            alert('Error setting language: ' + data.message);
                            isSwitching = false;
                        }
                    })
                    .catch(error => {
                        console.error('Force language error:', error);
                        alert('Error: ' + error.message);
                        isSwitching = false;
                    });
            }
            </script>
        </div>

        <!-- DIRECT INLINE TEST -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">DIRECT INLINE TEST</h2>

            <div class="flex space-x-4 mb-4">
                <button id="lang-fr-direct" onclick="switchLanguageDirect('fr')" class="px-4 py-2 bg-blue-500 text-white rounded">
                    French
                </button>
                <button id="lang-en-direct" onclick="switchLanguageDirect('en')" class="px-4 py-2 bg-green-500 text-white rounded">
                    English
                </button>
                <button id="lang-ar-direct" onclick="switchLanguageDirect('ar')" class="px-4 py-2 bg-red-500 text-white rounded">
                    Arabic
                </button>
            </div>

            <script>
            let isSwitchingDirect = false;

            function switchLanguageDirect(locale) {
                if (isSwitchingDirect) {
                    return;
                }
                
                isSwitchingDirect = true;
                console.log('DIRECT: Switching to locale:', locale);
                
                // Use force language route
                fetch('/force-lang/' + locale)
                    .then(response => response.json())
                    .then(data => {
                        console.log('DIRECT: Force language result:', data);
                        if (data.success) {
                            // If successful, reload the page
                            window.location.reload();
                        } else {
                            alert('DIRECT: Error setting language: ' + data.message);
                            isSwitchingDirect = false;
                        }
                    })
                    .catch(error => {
                        console.error('DIRECT: Force language error:', error);
                        alert('DIRECT: Error: ' + error.message);
                        isSwitchingDirect = false;
                    });
            }
            </script>
        </div>

        <!-- Debug Section -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <div class="space-y-2">
                <button onclick="checkLocale()" class="px-4 py-2 bg-purple-500 text-white rounded">
                    Check Current Locale
                </button>
                <button onclick="testLanguageChange('fr')" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Test French
                </button>
                <button onclick="testLanguageChange('en')" class="px-4 py-2 bg-green-500 text-white rounded">
                    Test English
                </button>
                <button onclick="testLanguageChange('ar')" class="px-4 py-2 bg-red-500 text-white rounded">
                    Test Arabic
                </button>
                <button onclick="forceLanguageChange('fr')" class="px-4 py-2 bg-purple-500 text-white rounded">
                    Force French
                </button>
                <button onclick="forceLanguageChange('en')" class="px-4 py-2 bg-orange-500 text-white rounded">
                    Force English
                </button>
                <button onclick="forceLanguageChange('ar')" class="px-4 py-2 bg-pink-500 text-white rounded">
                    Force Arabic
                </button>
                <div id="debug-info" class="mt-4 p-3 bg-gray-100 rounded text-sm"></div>
            </div>
        </div>

        <!-- Test Messages -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Test Messages</h2>
            <div class="space-y-2">
                <p><strong>Welcome:</strong> <span id="welcome-msg">Welcome</span></p>
                <p><strong>Language:</strong> <span id="language-msg">Language</span></p>
                <p><strong>Dashboard:</strong> <span id="dashboard-msg">Dashboard</span></p>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('languageChanged', function(event) {
            console.log('Language changed:', event.detail);
        });

        function checkLocale() {
            fetch('/debug-locale')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('debug-info').innerHTML = 
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('debug-info').innerHTML = 
                        '<p style="color: red;">Error: ' + error.message + '</p>';
                });
        }

        function testLanguageChange(locale) {
            console.log('Testing language change to:', locale);
            fetch('/simple-lang-test/' + locale)
                .then(response => response.json())
                .then(data => {
                    console.log('Language test result:', data);
                    document.getElementById('debug-info').innerHTML = 
                        '<pre>Language Test Result:\n' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    console.error('Language test error:', error);
                    document.getElementById('debug-info').innerHTML = 
                        '<p style="color: red;">Language Test Error: ' + error.message + '</p>';
                });
        }

        function forceLanguageChange(locale) {
            console.log('Forcing language change to:', locale);
            fetch('/force-lang/' + locale)
                .then(response => response.json())
                .then(data => {
                    console.log('Force language result:', data);
                    document.getElementById('debug-info').innerHTML = 
                        '<pre>Force Language Result:\n' + JSON.stringify(data, null, 2) + '</pre>';
                    if (data.success) {
                        // Reload page after 2 seconds to show the change
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Force language error:', error);
                    document.getElementById('debug-info').innerHTML = 
                        '<p style="color: red;">Force Language Error: ' + error.message + '</p>';
                });
        }
    </script>
</body>
</html>