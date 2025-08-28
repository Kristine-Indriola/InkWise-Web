<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise System - User Login</title>
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
            background: url('{{ asset('admin_image/background.png') }}') no-repeat center center fixed;
            background-size: cover;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
        }

        .login-container button {
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .login-container button:hover {
            background-color: #2980b9;
        }

        .show-password {
            display: flex;
            align-items: center;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .show-password input {
            margin-right: 5px;
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
    
    <div class="login-container">
    <h1>Login</h1>

    <!-- Display login error -->
    @if($errors->has('login_error'))
        <p style="color: red; text-align: center; margin-bottom: 10px;">
            {{ $errors->first('login_error') }}
        </p>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <input type="email" name="email" placeholder="Enter Email" value="{{ old('email') }}" required>
        <input type="password" name="password" placeholder="Enter Password" required>

        <div class="show-password">
            <input type="checkbox" id="togglePassword"> Show Password
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
            const passwordField = document.querySelector('input[name="password"]');
            passwordField.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>
