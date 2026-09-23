<!DOCTYPE html>
<html>
<head>
    <title>Test Production</title>
</head>
<body>
    <h1>Test de production</h1>
    @if(true)
        <p>Test conditionnel fonctionnel</p>
    @endif
    
    @foreach([1, 2, 3] as $item)
        <p>Item: {{ $item }}</p>
    @endforeach
</body>
</html>