<!DOCTYPE html>
<html>
<head>
    <title>Simple AJAX Language Test</title>
</head>
<body>
    <h1>Simple AJAX Language Test</h1>
    <div id="current-locale">Loading...</div>
    <button onclick="switchTo('fr')">French</button>
    <button onclick="switchTo('en')">English</button>
    <button onclick="switchTo('ar')">Arabic</button>

    <script>
        async function switchTo(locale) {
            console.log('Switching to:', locale);
            try {
                const response = await fetch(`/debug-lang/${locale}`);
                const data = await response.json();
                console.log('Response:', data);
                document.getElementById('current-locale').textContent = `Current: ${data.locale} - ${data.message}`;
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('current-locale').textContent = `Error: ${error.message}`;
            }
        }

        // Load initial locale
        fetch('/test-lang').then(r => r.json()).then(data => {
            document.getElementById('current-locale').textContent = `Current: ${data.current_locale}`;
        });
    </script>
</body>
</html>
