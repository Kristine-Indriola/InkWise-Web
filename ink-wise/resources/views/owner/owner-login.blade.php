<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise System - Owner Login</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
            background-color: #f4f6f8;
        }

        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: brightness(0.7);
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.92);
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 380px;
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
            font-size: 24px;
            letter-spacing: 0.5px;
        }

        .login-container input[type="email"],
        .login-container input[type="password"] {
            padding: 12px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            font-size: 15px;
        }

        .login-container button {
            margin-top: 18px;
            padding: 12px;
            background-color: #3498db;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }

        .login-container button:hover {
            background-color: #2980b9;
        }

        .show-password {
            display: flex;
            align-items: center;
            margin-top: 6px;
            font-size: 14px;
            color: #555;
        }

        .show-password input {
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="video-background">
        <source src="https://imgur.com/AbadHPL.mp4" type="video/mp4">
    </video>

    <div class="login-container">
        <h1>Owner Login</h1>
        <form method="POST" action="{{ route('owner.login.submit') }}">
            @csrf
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <div class="show-password">
                <input type="checkbox" id="togglePassword"> Show Password
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('change', function() {
            const passwordField = document.querySelector('input[name="password"]');
            passwordField.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>
