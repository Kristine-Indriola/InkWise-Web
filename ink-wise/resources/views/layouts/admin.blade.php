<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - @yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


    <link rel="stylesheet" href="{{ asset('css/admin-css/create_account.css') }}">

</head>


<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Admin Panel</a>
            <div>
                <a class="nav-link d-inline text-white" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="nav-link d-inline text-white" href="{{ route('admin.templates.index') }}">Templates</a>
                <a class="nav-link d-inline text-white" href="{{ route('admin.logout') }}">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>
</body>
</html>
