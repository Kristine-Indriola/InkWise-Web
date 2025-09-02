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
            background: linear-gradient(to right, #9dc2ec, #f4f3e1);
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
        .login-container input[type="password"],
        .login-container input[type="text"] {
            padding: 12px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            font-size: 15px;
            box-sizing: border-box;
            height: 40px; /* Fixed height */
            min-height: 40px; /* Fixed minimum height */
            line-height: 1.5;
            font-family: 'Arial', sans-serif;
            transition: none; /* Ensure no transition on height change */
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
            cursor: pointer;
        }

        .show-password label {
            margin: 0;
            cursor: pointer;
        }

        .role-hint {
            text-align: center;
            font-size: 13px;
            color: #555;
            margin-top: 10px;
        }
        
    </style>
</head>
<body>
    <video autoplay muted loop class="video-background">
        <source src="https://imgur.com/AbadHPL.mp4" type="video/mp4">
    </video>

    <div class="login-container">
        <h1>InkWise System - User Login</h1>
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required id="passwordField" class="password-field">
            <div class="show-password">
                <input type="checkbox" id="togglePassword">
                <label for="togglePassword">Show Password</label>
            </div>
            <button type="submit">Login</button>
        </form>
    <div class="role-hint">
        Use your registered email and password. <br>
        (Works for <strong>Owner</strong> and <strong>Staff</strong> accounts)
    </div>


    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('change', function() {
            const passwordField = document.getElementById('passwordField');
            if (this.checked) {
                passwordField.type = 'text'; // Show password
            } else {
                passwordField.type = 'password'; // Hide password
            }
        });
    </script>
</body>
</html>
