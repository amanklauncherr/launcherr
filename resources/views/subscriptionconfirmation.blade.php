{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmation</title>
</head>
<body>
    <p> Subject: 🌍✈️ Welcome to Launcherr! Enjoy 10% Off </p>
    <p> Hi Subscriber, </p>
    <p> Welcome to Launcherr! We're thrilled to have you. <strong>Here’s a 10% discount just for you: LAUNCHERRSVX10.</strong> </p>
    <p> Why Launcherr? </p>
    <p><strong>Exclusive Deals:</strong>  Offers tailored for you. </p>
    <p><strong>Inspiring Spots:</strong>   Hidden gems worldwide. </p>
    <p><strong>Travel Tips:</strong>   Make your trips seamless. </p>
    <p><br></p>
    <p> How to Redeem: </p>
    <p> Visit https://launcherr.co </p>
    <p> Shop our Travel Sized Products </p>
    <p>   Use code  <strong>LAUNCHERRSVX10</strong> at checkout.</p>
    <p><br></p>
    <p> Let's make your travel dreams come true! </p>
    <p>
        Cheers,<br>
        The Launcherr Team 🌟
    </p>
</body>
</html> --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Launcherr!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color:#000000;
            color: #0791be;
            text-align: center;
            padding: 20px 0;
        }
        .header img {
            width: 150px;
        }
        .content {
            padding: 20px;
            color: #050505;
        }
        .content h1 {
            color: #0791be;
        }
        .content p {
            line-height: 1.6;
        }
        .content a {
            color: #ff574b;
            text-decoration: none;
        }
        .content .button {
            display: inline-block;
            background-color: #ff574b;
            color: #ffffff;
            padding: 10px 20px;
            margin: 20px 0;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            background-color: #f4f4f4;
            color: #777777;
            text-align: center;
            padding: 10px 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://res.cloudinary.com/douuxmaix/image/upload/v1721205125/a71sfea2cvab31oqmxvk.png" alt="Launcherr Logo">
        </div>
        <div class="content">
            <h1>Welcome to Launcherr!</h1>
            <h2>Hi {{$name}},</h2>
            <p>We're thrilled to have you. Here’s a 10% discount just for you: <strong>LAUNCHERRSVX10</strong>.</p>
            <h2>Why Launcherr?</h2>
            <ul>
                <li><strong>Exclusive Deals:</strong> Offers tailored for you.</li>
                <li><strong>Inspiring Spots:</strong> Hidden gems worldwide.</li>
                <li><strong>Travel Tips:</strong> Make your trips seamless.</li>
            </ul>
            <h2>How to Redeem:</h2>
            <p>
                <a href="https://launcherr.co" class="button">Visit Launcherr</a>
            </p>
            <p>Shop our Travel Sized Products and use code <strong>LAUNCHERRSVX10</strong> at checkout.</p>
            <p>Let's make your travel dreams come true!</p>
            <p>Cheers,<br>The Launcherr Team</p>
        </div>
        <div class="footer">
            © 2024 Launcherr. All rights reserved.
        </div>
    </div>

</body>

</html>

        {{-- <div>
            {{-- <iframe src="https://lottie.host/embed/4eed464c-d371-404d-b507-11c37cba0aba/yYpTec9UEu.json"></iframe> --}}
            {{-- <img src="https://lottie.host/embed/4eed464c-d371-404d-b507-11c37cba0aba/yYpTec9UEu.json" alt="animation"> --}}
            {{-- <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script> 
            <dotlottie-player 
                src="https://lottie.host/4eed464c-d371-404d-b507-11c37cba0aba/yYpTec9UEu.json" 
                background="transparent" 
                speed="1" 
                style="width: 300px; height: 300px;" 
                loop 
                autoplay>
            </dotlottie-player>
        {{-- </div> --}}
        {{-- <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script> --}}

        <!-- Embed the Lottie animation -->
        {{-- <div>
            <lottie-player 
                src="https://lottie.host/4eed464c-d371-404d-b507-11c37cba0aba/yYpTec9UEu.json"  
                background="transparent"  
                speed="1"  
                style="width: 300px; height: 300px;"  
                loop  
                autoplay>
            </lottie-player>
        </div>  --}}