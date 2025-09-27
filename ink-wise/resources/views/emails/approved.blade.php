@component('mail::message')
# Hello {{ $user->name }},

Good news! 🎉  
Your account has been **approved by the owner** and you can now log in.

@component('mail::button', ['url' => url('/login')])
Login Now
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
