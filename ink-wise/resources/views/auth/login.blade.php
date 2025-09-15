<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise System - Login</title>
    <!-- Add Poppins font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
            background: linear-gradient(120deg, #9dc2ec 0%, #f4f3e1 100%);
        }

        /* --- Animated Logo Sequence --- */
        .logo-anim-container {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 220px;
            height: 220px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            pointer-events: none;
        }
        .octagon {
            position: absolute;
            width: 70px;
            height: 70px;
            opacity: 0;
            transform: scale(0.5);
            animation: tumble-in 1.3s cubic-bezier(.77,0,.175,1) forwards;
            transition: opacity 0.8s cubic-bezier(.4,0,.2,1), transform 0.8s cubic-bezier(.4,0,.2,1);
            clip-path: polygon(
                30% 0%, 70% 0%,
                100% 30%, 100% 70%,
                70% 100%, 30% 100%,
                0% 70%, 0% 30%
            );
        }
        /* 5 main octagons with gradients */
        .octagon.hex1 { left: 60px; top: 0; background: linear-gradient(90deg, #000000, #3533cd); animation-delay: 0.1s;}
        .octagon.hex2 { left: 130px; top: 60px; background: linear-gradient(90deg, #5170ff, #ff66c4); animation-delay: 0.35s;}
        .octagon.hex3 { left: 100px; top: 140px; background: linear-gradient(90deg, #8c52ff, #5ce1e6); animation-delay: 0.6s;}
        .octagon.hex4 { left: 0; top: 140px; background: linear-gradient(90deg, #ff3131, #ff914d); animation-delay: 0.85s;}
        .octagon.hex5 { left: -30px; top: 60px; background: linear-gradient(90deg, #ffde59, #ff914d); animation-delay: 1.1s;}
        /* 2 small octagons */
        .octagon.hex6 {
            left: 25px; top: 30px;
            width: 32px; height: 32px;
            background: #5ce1e6;
            animation-delay: 1.35s;
            z-index: 2;
        }
        .octagon.hex7 {
            left: 140px; top: 30px;
            width: 32px; height: 32px;
            background: #ffde59;
            animation-delay: 1.6s;
            z-index: 2;
        }

        @keyframes tumble-in {
            0% {
                opacity: 0;
                transform: scale(0.5) rotate(-180deg) translateY(-80px);
            }
            60% {
                opacity: 1;
                transform: scale(1.1) rotate(20deg) translateY(10px);
            }
            80% {
                transform: scale(0.95) rotate(-10deg) translateY(-5px);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0deg) translateY(0);
            }
        }
        .logo-drop {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 100px;
            height: 100px;
            transform: translate(-50%, -50%) scale(0.5);
            opacity: 0;
            z-index: 3;
            border-radius: 50%;
            background: transparent;
            box-shadow: 0 2px 16px #9dc2ec30;
            animation: logo-drop-in 1.1s cubic-bezier(.77,0,.175,1) 2s forwards;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.8s cubic-bezier(.4,0,.2,1), transform 0.8s cubic-bezier(.4,0,.2,1);
        }
        .logo-drop img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: contain;
            background: transparent;
            box-shadow: none;
            display: block;
        }
        @keyframes logo-drop-in {
            0% {
                opacity: 0;
                transform: translate(-50%, -60%) scale(0.5) rotate(-60deg);
            }
            60% {
                opacity: 1;
                transform: translate(-50%, -60%) scale(1.1) rotate(10deg);
            }
            80% {
                transform: translate(-50%, -60%) scale(0.95) rotate(-5deg);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -60%) scale(1) rotate(0deg);
            }
        }

        /* --- Final Logo on Form --- */
        .logo-final {
            position: absolute;
            left: 50%;
            top: -100px;
            transform: translateX(-50%);
            width: 270px;   /* increased from 240px */
            height: 270px;  /* increased from 240px */
            z-index: 11;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 0;
            transition: opacity 1s cubic-bezier(.77,0,.175,1), top 0.8s cubic-bezier(.4,0,.2,1);
        }
        .logo-final .circle {
            background: transparent;
            border-radius: 50%;
            width: 250px;   /* increased from 220px */
            height: 250px;  /* increased from 220px */
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 32px #9dc2ec30;
            border: none;
        }
        .logo-final img {
            width: 200px;   /* increased from 180px */
            height: 200px;  /* increased from 180px */
            border-radius: 50%;
            object-fit: contain;
            background: transparent;
            box-shadow: none;
            display: block;
            transition: box-shadow 0.5s cubic-bezier(.4,0,.2,1);
        }
        .logo-final .brand {
            margin-top: 18px;           /* More space below the logo */
            font-family: 'Poppins', Arial, sans-serif;
            font-weight: 700;
            font-size: 30px;            /* Bigger and more prominent */
            color: #232a3a;
            text-align: center;
            letter-spacing: 2px;        /* More spacing between letters */
            text-shadow: 0 4px 16px #9dc2ec30;
            user-select: none;
            transition: color 0.4s cubic-bezier(.4,0,.2,1);
            line-height: 1.1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.18);
            padding: 40px 30px 30px 30px;
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(115, 115, 115, 0.22), 0 2px 8px 0 rgba(157, 194, 236, 0.13);
            width: 340px;
            min-width: 0;
            max-width: 92vw;
            min-height: 340px;
            position: relative;
            backdrop-filter: blur(18px) saturate(160%);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
            border: 2px solid #73737330;
            z-index: 1;
            margin-top: 120px;
            transition: box-shadow 0.7s cubic-bezier(.77,0,.175,1), transform 0.7s cubic-bezier(.77,0,.175,1), background 0.7s cubic-bezier(.77,0,.175,1);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-container:focus-within {
            box-shadow: 0 16px 56px 0 rgba(53, 51, 205, 0.18), 0 2px 16px 0 rgba(157, 194, 236, 0.18);
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #232a3a;
            font-size: 22px;
            letter-spacing: 0.5px;
            font-family: 'Poppins', Arial, sans-serif;
            font-weight: 700;
            transition: color 0.4s cubic-bezier(.4,0,.2,1);
        }

        .login-container form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .login-container form input[type="email"],
        .login-container form input[type="password"],
        .login-container form input[type="text"] {
            padding: 12px;
            margin: 6px 0;
            border: 1.5px solid #9dc2ec;
            border-radius: 7px;
            width: 100%;
            font-size: 15px;
            box-sizing: border-box;
            height: 40px;
            min-height: 40px;
            line-height: 1.5;
            font-family: 'Poppins', Arial, sans-serif;
            background: rgba(255,255,255,0.22);
            color: #232a3a;
            transition: border 0.4s cubic-bezier(.77,0,.175,1), box-shadow 0.4s cubic-bezier(.77,0,.175,1), background 0.4s cubic-bezier(.77,0,.175,1);
            margin-top: 18px; /* add more space above input fields */
        }
        .login-container form input[type="email"] {
            margin-top: 28px; /* extra space for the first input only */
        }
        .login-container input[type="email"]:focus,
        .login-container input[type="password"]:focus,
        .login-container input[type="text"]:focus {
            border: 1.5px solid #3533cd;
            box-shadow: 0 0 0 2px #9dc2ec33;
            outline: none;
            background: #f4f3e1;
        }
        .login-container input[type="email"]::placeholder,
        .login-container input[type="password"]::placeholder,
        .login-container input[type="text"]::placeholder {
            color: #6a8bbd;
            font-family: 'Poppins', Arial, sans-serif;
            transition: color 0.3s cubic-bezier(.4,0,.2,1);
        }

        .login-container button {
            margin-top: 12px;
            padding: 12px;
            background: linear-gradient(90deg, #9dc2ec 0%, #f4f3e1 100%);
            color: #232a3a;
            font-size: 16px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            font-family: 'Poppins', Arial, sans-serif;
            box-shadow: 0 2px 8px #9dc2ec30;
            transition: background 0.4s cubic-bezier(.77,0,.175,1), box-shadow 0.4s cubic-bezier(.77,0,.175,1), color 0.4s cubic-bezier(.77,0,.175,1);
        }

        .login-container button:hover {
            background: linear-gradient(90deg, #f4f3e1 0%, #9dc2ec 100%);
            color: #232a3a;
            box-shadow: 0 4px 16px #9dc2ec40;
        }

        .show-password {
            display: flex;
            align-items: center;
            margin-top: 4px;
            font-size: 14px;
            color: #6a8bbd;
            font-family: 'Poppins', Arial, sans-serif;
            transition: color 0.3s cubic-bezier(.4,0,.2,1);
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
            color: #232a3a;
            margin-top: 10px;
            font-family: 'Poppins', Arial, sans-serif;
            transition: color 0.3s cubic-bezier(.4,0,.2,1);
            font-weight: 400; /* Make all text normal weight */
        }
        .role-hint strong {
            font-weight: 600; /* Only Owner and Staff will be bold */
        }

        /* Responsive */
        @media (max-width: 500px) {
            .login-container {
                padding: 30px 4vw 20px 4vw;
                width: 98vw;
                min-width: 0;
                min-height: 0;
            }
            .logo-final, .logo-final .circle {
                width: 100px;
                height: 100px;
            }
            .logo-final img {
                width: 70px;
                height: 70px;
            }
            .logo-final .brand {
                font-size: 18px;
                margin-top: 10px;
                letter-spacing: 1px;
            }
        }

        @media (max-width: 700px) {
            .logo-final, .logo-final .circle {
                width: 120px;
                height: 120px;
            }
            .logo-final .circle {
                width: 110px;
                height: 110px;
            }
            .logo-final img {
                width: 80px;
                height: 80px;
            }
            .logo-final .brand {
                font-size: 18px;
                margin-top: 10px;
            }
        }

        /* Bee-to-circle morph animation */
        .octagon.bee-move {
            animation: bee-to-circle 1.2s cubic-bezier(.77,0,.175,1) forwards;
        }
        @keyframes bee-to-circle {
            0% { opacity: 1; }
            80% {
                opacity: 1;
                border-radius: 30%;
                transform: scale(1.1) rotate(0deg) translateY(0);
            }
            100% {
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) scale(0.7) rotate(0deg);
                border-radius: 50%;
                opacity: 0.2;
            }
        }
        /* Fade in logo image */
        .logo-final img.show-logo {
            opacity: 1 !important;
        }
    </style>
</head>
<body>
    <!-- Animated Bee-to-Circle Geometric Sequence -->
    <div class="logo-anim-container" id="logoAnimContainer">
        <div class="octagon hex1"></div>
        <div class="octagon hex2"></div>
        <div class="octagon hex3"></div>
        <div class="octagon hex4"></div>
        <div class="octagon hex5"></div>
        <div class="octagon hex6"></div>
        <div class="octagon hex7"></div>
    </div>

    <div class="login-container" id="loginContainer">
        <!-- Final logo lands here after animation -->
        <div class="logo-final" id="logoFinal">
            <div class="circle">
                <img src="{{ asset('adminimage/inkwise.png') }}"
                    alt="InkWise Logo"
                    onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=I+W&background=6a2ebc&color=fff&bold=true';"
                    style="opacity:0; transition:opacity 1.2s cubic-bezier(.77,0,.175,1);">
            </div>
        </div>

        <!-- Move InkWise title here, outside the form -->
        <div class="brand">InkWise</div>

        {{-- 🔴 Show error messages --}}
        @if($errors->has('login_error'))
            <div style="color: #ff6a88; margin-bottom: 12px; text-align: center; font-size: 14px;">
                {{ $errors->first('login_error') }}
            </div>
        @endif

        {{-- 🟢 Show success messages (like logout) --}}
        @if(session('success'))
            <div style="color: #4caf50; margin-bottom: 12px; text-align: center; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <input type="email" name="email" placeholder="Enter Email" required autocomplete="username">
            <input type="password" name="password" placeholder="Enter Password" required id="passwordField" class="password-field" autocomplete="current-password">
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
        // Hide logo-final until animation ends
        document.getElementById('logoFinal').style.opacity = 0;

        window.addEventListener('DOMContentLoaded', function() {
            // Animate octagons to morph and move to center
            setTimeout(function() {
                document.querySelectorAll('.logo-anim-container .octagon').forEach(function(oct) {
                    oct.classList.add('bee-move');
                });
            }, 1700); // Start morph after initial tumble

            // Fade out octagons and fade in logo image
            setTimeout(function() {
                document.getElementById('logoAnimContainer').style.display = 'none';
                document.getElementById('logoFinal').style.opacity = 1;
                // Fade in logo image
                document.querySelector('.logo-final img').classList.add('show-logo');
            }, 2900); // after all animation
        });

        document.getElementById('togglePassword').addEventListener('change', function() {
            const passwordField = document.getElementById('passwordField');
            if (this.checked) {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        });

        // Toggle (scale) the form only when mouse is over the form, no movement
        const loginContainer = document.getElementById('loginContainer');
        loginContainer.addEventListener('mouseenter', function() {
            loginContainer.style.transform = 'scale(1.03)';
        });
        loginContainer.addEventListener('mouseleave', function() {
            loginContainer.style.transform = '';
        });
    </script>
</body>
</html>
