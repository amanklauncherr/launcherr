<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Flight Booking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 24px;
            color: #333;
            text-align: center;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            color: #555;
        }
        .details {
            margin-top: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .details th {
            background-color: #f2f2f2;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Flight Booking Details</h1>
        <p>Dear User,</p>
        <p>Your flight booking has been successfully completed. Please find the details below:</p>
        
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
                <tr>
                    <th>Download Ticket</th>
                    <td>
                        <a href="{{ $pdf_url }}" class="button" target="_blank">Download PDF</a>
                    </td>
                </tr>
            </table>
        </div>

        <p>If you have any questions or require further assistance, please contact us.</p>
        <p>Thank you for choosing our service!</p>
    </div>
</body>
</html>
