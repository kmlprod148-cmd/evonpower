<!DOCTYPE html>
<html>
<head>
    <title>CMI Payment Form</title>
</head>
<body>
    <h1>Payment form CMI</h1>
    <form method="post" action="{{ route('cmi.process') }}">
        @csrf
        <label for="amount">Amount</label>
        <input type="text" name="amount" class="input-control" placeholder="put amount here 10.65" value="10.60"> DHS<br/>
        <button type="submit">Buy</button>
    </form>
</body>
</html>