<?php $__env->startComponent('mail::message'); ?>
# Verify your email to finish signing up

Use the code below to verify your email address for your InkWise account. The code expires in 15 minutes.

<?php $__env->startComponent('mail::panel'); ?>
**<?php echo new \Illuminate\Support\EncodedHtmlString($code); ?>**
<?php echo $__env->renderComponent(); ?>

If you didn't request this, you can safely ignore this email.

Thanks,<br>
InkWise Management
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/emails/customer-email-verification-code.blade.php ENDPATH**/ ?>