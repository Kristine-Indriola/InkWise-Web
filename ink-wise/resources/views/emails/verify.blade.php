@component('mail::message')
# Hello {{ $user->name }},

Thank you for registering with us!  
Please click the button below to verify your email address:

@component('mail::button', ['url' => $verificationUrl])
Verify Email
@endcomponent

If you did not create an account, no further action is required.  

Thanks,<br>
{{ config('app.name') }}
@endcomponent
