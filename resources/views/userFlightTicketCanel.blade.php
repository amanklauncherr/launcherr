<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Flight Ticket Cancellation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            background-color: #ffffff;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            color: #555;
        }
        .details {
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td {
            padding: 10px;
            border: 1px solid #e0e0e0;
            text-align: left;
        }
        .details th {
            background-color: #f7f7f7;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Flight Ticket Cancellation Notice</h2>

        <p>Dear Customer,</p>
        <p>We inform you that your flight ticket has been cancelled. Flight Ticket of your booking below:</p>

        <div class="details">
            <table>
                <tr>
                    <th>PNR</th>
                    <td>{{ $Pnr }}</td>
                </tr>
                <tr>
                    <th>Booking Reference</th>
                    <td>{{ $BookingRef }}</td>
                </tr>
            </table>
        </div>

        <p>If you have any questions or need assistance, please contact us at our support center.</p>

        <p>Thank you for choosing us.</p>
    </div>
</body>
</html>
