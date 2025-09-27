
@section('title', 'Email Verified')

@section('content')
<div class="container">
    <div class="card">
        <h2>✅ Email Verified</h2>
        <p>Your email has been successfully verified. Please wait for the owner to approve your account before you can log in.</p>

        <a href="{{ url('/login') }}" class="btn btn-primary">Go to Login</a>
    </div>
</div>
@endsection
