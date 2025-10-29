@component('mail::message')
# Reset your InkWise password

Use the code below to reset your password for your InkWise account. The code expires in 15 minutes.

@component('mail::panel')
**{{ $code }}**
@endcomponent

If you didn't request this password reset, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
