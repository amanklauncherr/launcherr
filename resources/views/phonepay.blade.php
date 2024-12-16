<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhonePe Payment</title>
</head>
<body>
    <h2>Pay with PhonePe</h2>
    
    <form action="{{ route('phonepay') }}" method="POST">
        @csrf
        <label for="bookingRef">Booking Ref : </label>
        <input type="number" id="bookingRef" name="bookingRef" required>
        <label for="amount">Amount (INR): </label>
        <input type="number" id="amount" name="amount" required>
        <br><br>
        <button type="submit">Proceed to Pay</button>
    </form>
</body>
</html>
