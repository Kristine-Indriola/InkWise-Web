<?php
    $showLoginModal = $errors->has('email') || $errors->has('password') || session('auth_error') || session('error');
?>

<div id="loginModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 px-2 <?php echo e($showLoginModal ? '' : 'hidden'); ?>">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative transform transition-all scale-95 hover:scale-100 duration-300">

        <!-- Close button -->
        <button id="closeLogin" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition text-base font-bold">
            ✖
        </button>

        <!-- Modal Header -->
        <div class="text-center mb-5">
            <h2 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-blue-400 bg-clip-text text-transparent">
                Sign In
            </h2>
            <p class="text-gray-500 text-sm mt-1">Welcome back! Please enter your details.</p>
        </div>

        <!-- Login Form -->
        <form method="POST" action="<?php echo e(route('customer.login')); ?>" class="space-y-4">
            <?php echo csrf_field(); ?>

            <?php if(session('auth_error')): ?>
            <p class="text-sm text-red-500"><?php echo e(session('auth_error')); ?></p>
            <?php endif; ?>

            <?php if(session('error')): ?>
            <p class="text-sm text-red-500"><?php echo e(session('error')); ?></p>
            <?php endif; ?>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-sm text-red-500 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 text-base">
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-sm text-red-500 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Forgot Password -->
            <div class="text-right">
                <a href="<?php echo e(route('customer.password.request')); ?>" class="text-sm text-indigo-600 hover:underline">Forgot Password?</a>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300 text-indigo-600">
                <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                Sign In
            </button>
        </form> 

        

        <!-- Switch to Register -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Don’t have an account? 
            <a href="<?php echo e(route('customer.register')); ?>" id="openRegisterFromLogin" class="text-indigo-600 hover:underline">Register</a>
        </p>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\InkWise-Web\ink-wise\resources\views/auth/customer/login.blade.php ENDPATH**/ ?>