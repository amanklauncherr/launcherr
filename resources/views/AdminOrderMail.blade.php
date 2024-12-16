<!DOCTYPE html>
<html>
<head>
    <title>New Order Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-width: 600px;
            margin: auto;
        }
        .header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">New Order Received</div>
        <p class="message">
            {{ $AdminText }}
        </p>
    </div>
</body>
</html>
