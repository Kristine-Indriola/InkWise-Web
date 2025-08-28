<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise System - Login</title>
    <style>
        body {
    margin: 0;
    font-family: 'Arial', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
    background: url('/admin-image/background.png') no-repeat center center fixed;
    background-size: cover;
}



        .login-container {
            background-color: rgba(255, 255, 255, 0.8); /* White background with transparency */
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

        .login-container form {
            display: flex;
            flex-direction: column;
        }

        .login-container input[type="text"],
        .login-container input[type="password"],
        .login-container input[type="checkbox"] {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .login-container button {
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #2980b9;
        }

        .login-container .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }

        .login-container .footer-text a {
            color: #3498db;
            text-decoration: none;
        }

        .login-container .footer-text a:hover {
            text-decoration: underline;
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
    <!-- Video background -->

    <div class="login-container">
        <h1>Welcome to InkWise</h1>
        <form id="loginForm">
    <input type="text" name="email" placeholder="Enter Email" required>
    <input type="password" name="password" placeholder="Enter Password" required>
    <div class="show-password">
        <input type="checkbox" id="togglePassword"> Show Password
    </div>
    <button type="submit">Login</button>
</form>

        <div class="footer-text">
            <p>Don't have an account? <a href="sign_up.php">Sign up</a></p>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('change', function() {
            const passwordField = document.getElementById('password');
            if (this.checked) {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (username === "admin" && password === "admin123") {
                alert("Login successful! Welcome, " + username + "!");
                window.location.href = "dashboard.php"; // Redirect to admin dashboard
            } else if (username === "employee" && password === "employee123") {
                alert("Login successful! Welcome, " + username + "! Access limited to Create Order and Inventory.");
                window.location.href = "employee_dashboard.php"; // Redirect to restricted employee page
            } else {
                alert("Invalid username or password. Please try again.");
            }
        });
    </script>
</body>
</html>
