<!DOCTYPE html>
<html>
<head><title>Owner Home</title></head>
<body>
    <h1>Welcome, {{ auth('owner')->user()->email }}</h1>

    <form method="POST" action="{{ route('owner.logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
