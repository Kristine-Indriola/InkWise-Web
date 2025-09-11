<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>InkWise System - Owner Login</title>

    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <title>InkWise System - Login</title>

    <style>

        body, .login-container, .login-container h1, .login-container input, .login-container button, .role-hint {
        font-family: 'Poppins', sans-serif;
        font-size: 22px;

    }
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
            background: linear-gradient(120deg, #a3c7d9 0%, #dce5e2 50%, #f8f5ed 100%);
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
             border-radius: 12px; /* rectangle with slight rounding */
      background: rgba(255, 255, 255, 0.15); /* transparency */
      backdrop-filter: blur(12px); /* glass effect */
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3); /* strong shadow so transparent part is visible */
            backdrop-filter: blur(10px); /* glass effect */
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

    <!-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\auth\login.blade.php -->
<div class="login-container">
    <img src="{{ asset('adminimage/inkwise.png') }}" alt="InkWise Logo" style="display:block; margin:0 auto 18px auto; max-width:120px;">
    <h1>User Login</h1>

    <div class="login-container">
    <h1>InkWise System - User Login</h1>

    {{-- 🔴 Show error messages --}}
    @if($errors->has('login_error'))
        <div style="color: red; margin-bottom: 12px; text-align: center; font-size: 14px;">
            {{ $errors->first('login_error') }}
        </div>
    @endif

    {{-- 🟢 Show success messages (like logout) --}}
    @if(session('success'))
        <div style="color: green; margin-bottom: 12px; text-align: center; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif


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
