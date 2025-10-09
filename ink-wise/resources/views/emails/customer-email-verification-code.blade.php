@component('mail::message')
# Verify your email to finish signing up

Use the code below to verify your email address for your InkWise account. The code expires in 15 minutes.

@component('mail::panel')
**{{ $code }}**
@endcomponent

If you didn't request this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
