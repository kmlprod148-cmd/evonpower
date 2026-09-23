<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Language Switcher Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .debug-section { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .button { padding: 10px 20px; margin: 5px; border: 1px solid #ccc; cursor: pointer; }
        .button.active { background: blue; color: white; }
        .spinner { display: none; }
        .test-result { margin: 10px 0; padding: 10px; background: #e8f5e8; border: 1px solid #4caf50; }
        .error { background: #ffeaea; border: 1px solid #f44336; }
    </style>
</head>
<body>
    <h1>🔍 Ultimate Language Switcher Debug</h1>

    <div class="debug-section">
        <h2>Phase 1: JavaScript Loading Test</h2>
        <div id="js-load-test" class="test-result">Testing...</div>
    </div>

    <div class="debug-section">
        <h2>Phase 2: DOM Elements Test</h2>
        <div id="dom-test" class="test-result">Testing...</div>
    </div>

    <div class="debug-section">
        <h2>Phase 3: Click Handler Test</h2>
        <button id="test-fr" class="button" onclick="testClick('fr')">🇫🇷 French</button>
        <button id="test-en" class="button" onclick="testClick('en')">🇺🇸 English</button>
        <button id="test-ar" class="button" onclick="testClick('ar')">🇸🇦 Arabic</button>
        <div id="click-test" class="test-result">Click a button above</div>
    </div>

    <div class="debug-section">
        <h2>Phase 4: AJAX Test</h2>
        <button class="button" onclick="testAjax()">Test AJAX Request</button>
        <div id="ajax-test" class="test-result">Click to test AJAX</div>
    </div>

    <div class="debug-section">
        <h2>Phase 5: Full Integration Test</h2>
        <div id="full-integration-test" class="test-result">Testing...</div>
    </div>

    <script>
        let debugResults = {};

        // Phase 1: JavaScript Loading
        function testJavaScriptLoading() {
            debugResults.jsLoaded = true;
            document.getElementById('js-load-test').innerHTML = '✅ JavaScript loaded successfully';
            console.log('🔍 Debug: JavaScript loaded');
        }

        // Phase 2: DOM Elements
        function testDOMElements() {
            const elements = {
                fr: !!document.getElementById('lang-fr'),
                en: !!document.getElementById('lang-en'),
                ar: !!document.getElementById('lang-ar')
            };

            debugResults.domElements = elements;

            const allExist = elements.fr && elements.en && elements.ar;
            const resultDiv = document.getElementById('dom-test');

            if (allExist) {
                resultDiv.innerHTML = '✅ All DOM elements exist: ' + JSON.stringify(elements);
                resultDiv.classList.remove('error');
            } else {
                resultDiv.innerHTML = '❌ Missing DOM elements: ' + JSON.stringify(elements);
                resultDiv.classList.add('error');
            }

            console.log('🔍 Debug: DOM elements check', elements);
        }

        // Phase 3: Click Handler
        function testClick(locale) {
            debugResults.lastClick = { locale: locale, timestamp: new Date().toISOString() };

            const resultDiv = document.getElementById('click-test');
            resultDiv.innerHTML = `✅ Click handler works! Locale: ${locale}, Time: ${debugResults.lastClick.timestamp}`;
            resultDiv.classList.remove('error');

            console.log('🔍 Debug: Click handler fired for locale:', locale);

            // Highlight clicked button
            ['test-fr', 'test-en', 'test-ar'].forEach(id => {
                document.getElementById(id).classList.remove('active');
            });
            document.getElementById(`test-${locale}`).classList.add('active');
        }

        // Phase 4: AJAX Test
        async function testAjax() {
            const resultDiv = document.getElementById('ajax-test');
            resultDiv.innerHTML = '⏳ Testing AJAX request...';
            resultDiv.classList.remove('error');

            try {
                console.log('🔍 Debug: Starting AJAX test');

                const response = await fetch('/simple-debug-lang/fr');
                const data = await response.json();

                debugResults.ajaxResponse = { status: response.status, data: data };

                if (response.ok && data.success) {
                    resultDiv.innerHTML = `✅ AJAX works! Response: ${JSON.stringify(data)}`;
                    console.log('🔍 Debug: AJAX success', data);
                } else {
                    resultDiv.innerHTML = `❌ AJAX failed: ${response.status} - ${JSON.stringify(data)}`;
                    resultDiv.classList.add('error');
                    console.error('🔍 Debug: AJAX failed', response.status, data);
                }
            } catch (error) {
                resultDiv.innerHTML = `❌ AJAX error: ${error.message}`;
                resultDiv.classList.add('error');
                debugResults.ajaxError = error.message;
                console.error('🔍 Debug: AJAX error', error);
            }
        }

        // Phase 5: Full Integration Test
        function testFullIntegration() {
            const resultDiv = document.getElementById('full-integration-test');

            const checks = {
                jsLoaded: debugResults.jsLoaded || false,
                domElements: debugResults.domElements ? (debugResults.domElements.fr && debugResults.domElements.en && debugResults.domElements.ar) : false,
                clickHandler: !!debugResults.lastClick,
                ajaxWorks: debugResults.ajaxResponse ? (debugResults.ajaxResponse.status === 200) : false
            };

            const allPass = checks.jsLoaded && checks.domElements && checks.clickHandler && checks.ajaxWorks;

            if (allPass) {
                resultDiv.innerHTML = '🎉 ALL TESTS PASSED! Language switcher should work.';
                resultDiv.classList.remove('error');
            } else {
                const failed = Object.keys(checks).filter(key => !checks[key]);
                resultDiv.innerHTML = `❌ Some tests failed: ${failed.join(', ')}`;
                resultDiv.classList.add('error');
            }

            debugResults.integrationChecks = checks;
            console.log('🔍 Debug: Full integration test results', checks);
        }

        // Run tests on load
        window.addEventListener('load', function() {
            testJavaScriptLoading();
            testDOMElements();
            testFullIntegration();
        });

        // Re-run integration test when other tests complete
        setInterval(testFullIntegration, 2000);
    </script>

    <!-- Include the actual language switcher component -->
    <div class="debug-section">
        <h2>Actual Language Switcher Component</h2>
        @include('components.js-language-switcher')
    </div>
</body>
</html>
