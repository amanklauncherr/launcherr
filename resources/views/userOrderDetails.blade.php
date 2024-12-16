<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            background-color: #4CAF50;
            color: white;
            padding: 10px 0;
            border-radius: 8px 8px 0 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .section table {
            width: 100%;
            border-collapse: collapse;
        }
        .section table th, .section table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .section table th {
            background-color: #f2f2f2;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>

        <div class="section">
            <h3>Order Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($OrderDetails['products'] as $product)
                        <tr>
                            <td>{{ $product['product_name'] }}</td>
                            <td>{{ $product['quantity'] }}</td>
                            <td>{{ number_format($product['price'], 2) }}</td>
                            <td>{{ number_format($product['sub_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Billing Details</h3>
            <p>
                <strong>Name:</strong> {{ $OrderDetails['billing']['firstName'] }} {{ $OrderDetails['billing']['lastName'] }}<br>
                <strong>Address:</strong> {{ $OrderDetails['billing']['address1'] }}, {{ $OrderDetails['billing']['city'] }}, {{ $OrderDetails['billing']['state'] }} - {{ $OrderDetails['billing']['postcode'] }}<br>
                <strong>Email:</strong> {{ $OrderDetails['billing']['email'] }}<br>
                <strong>Phone:</strong> {{ $OrderDetails['billing']['phone'] }}
            </p>
        </div>

        <div class="section">
            <h3>Shipping Details</h3>
            <p>
                <strong>Name:</strong> {{ $OrderDetails['shipping']['firstName'] }} {{ $OrderDetails['shipping']['lastName'] }}<br>
                <strong>Address:</strong> {{ $OrderDetails['shipping']['address1'] }}, {{ $OrderDetails['shipping']['city'] }}, {{ $OrderDetails['shipping']['state'] }} - {{ $OrderDetails['shipping']['postcode'] }}<br>
                <strong>Phone:</strong> {{ $OrderDetails['shipping']['phone'] }}
            </p>
        </div>

        <div class="section">
            <h3>Payment Summary</h3>
            <table>
                <tr>
                    <th>Total Amount</th>
                    <td>{{ number_format($OrderDetails['subTotal'], 2) }}</td>
                </tr>
                {{-- <tr>
                    <th>GST Amount</th>
                    <td>{{ number_format($OrderDetails['gstAmt'], 2) }}</td>
                </tr>
                <tr>
                    <th>Grand Total</th>
                    <td><strong>{{ number_format($OrderDetails['grand_Total'], 2) }}</strong></td>
                </tr> --}}
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your order!</p>
        </div>
    </div>
</body>
</html>
