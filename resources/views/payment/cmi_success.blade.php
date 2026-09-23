<!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>
</head>
<body>
    <h1>Payment Successful!</h1>
    <p>Your payment was processed successfully.</p>
    <p>Details:</p>
    <pre>{{ json_encode($request, JSON_PRETTY_PRINT) }}</pre>
    <a href="{{ url('/') }}">Go to Home</a>
</body>
</html>