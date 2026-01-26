<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkWise System - Login</title>
    <!-- Add Poppins font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo e(asset('adminimage/inkwise.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/login.css')); ?>">

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
                <img src="<?php echo e(asset('adminimage/inkwise.png')); ?>"
                    alt="InkWise Logo"
                    loading="lazy"
                    onerror="this.onerror=null;this.src='<?php echo e(asset('images/default-logo.png')); ?>';"
                    style="opacity:0; transition:opacity 1.2s cubic-bezier(.77,0,.175,1);">
            </div>
        </div>

        <!-- Move InkWise title here, outside the form -->
        <div class="brand">InkWise</div>

        
        <?php if($errors->has('login_error')): ?>
            <div class="error-message" role="alert" aria-live="assertive">
                <?php echo e($errors->first('login_error')); ?>

            </div>
        <?php endif; ?>

        
        <?php if(session('success')): ?>
            <div class="success-message" role="alert" aria-live="polite">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('login.submit')); ?>" aria-labelledby="login-heading" class="login-form" novalidate>
            <?php echo csrf_field(); ?>
            <h1 id="login-heading" style="display: none;">Login to InkWise System</h1>
            <div class="form-field">
                <label for="emailField" class="field-label">Email</label>
                <div class="input-wrapper">
                    <input id="emailField" type="email" name="email" placeholder="name@example.com" required autocomplete="username" aria-label="Email address">
                </div>
            </div>

            <div class="form-field">
                <label for="passwordField" class="field-label">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" placeholder="Enter Password" required id="passwordField" class="password-field" autocomplete="current-password" aria-label="Password">
                </div>
                <div class="password-meta">
                    <div class="show-password">
                        <input type="checkbox" id="togglePassword" aria-label="Show password">
                        <label for="togglePassword">Show Password</label>
                    </div>
                    <div id="password-strength" class="password-strength" aria-live="polite" aria-hidden="true">
                        <span class="password-strength__bar" role="presentation"></span>
                        <span class="password-strength__label">Strength</span>
                    </div>
                </div>
            </div>

            <button type="submit" aria-describedby="login-button-desc">Login</button>
            <div id="login-button-desc" style="display: none;">Submit your email and password to log in</div>
        </form>

        <div class="role-hint" aria-label="Login instructions">
            Use your registered email and password. <br>
            (Works for <strong>Owner</strong> and <strong>Staff</strong> accounts)
        </div>
    </div>

    <script src="<?php echo e(asset('js/admin/login.js')); ?>"></script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\InkWise-Web\ink-wise\resources\views/auth/login.blade.php ENDPATH**/ ?>