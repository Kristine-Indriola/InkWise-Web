<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise System - Admin Login</title>
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
        }

        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .login-container input[type="email"],
        .login-container input[type="password"] {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
        }

        .login-container button {
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        .login-container button:hover {
            background-color: #2980b9;
        }

        .show-password {
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .show-password input {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="video-background">
        <source src="https://imgur.com/AbadHPL.mp4" type="video/mp4">
    </video>

    <div class="login-container">
        <h1>Admin Login</h1>
        <form method="POST" action="{{ route('admin.login.submit') }}">
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
            if (this.checked) {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        });
    </script>
</body>
</html>
