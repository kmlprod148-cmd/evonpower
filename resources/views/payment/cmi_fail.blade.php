<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
</head>
<body>
    <h1>Payment Failed!</h1>
    <p>There was an issue processing your payment.</p>
    <p>Details:</p>
    <pre>{{ json_encode($request, JSON_PRETTY_PRINT) }}</pre>
    <a href="{{ url('/') }}">Go to Home</a>
</body>
</html>