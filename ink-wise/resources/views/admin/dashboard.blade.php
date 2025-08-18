<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - InkWise</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background: #f4f6f8;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #2c3e50;
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            color: #ecf0f1;
            padding: 10px;
            text-decoration: none;
            display: block;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background: #34495e;
        }

        /* Main Content */
        .main {
            flex: 1;
            padding: 20px;
        }

        .main h1 {
            color: #2c3e50;
        }

        .card {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
        }

        .logout {
            margin-top: auto;
            background: #e74c3c;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }

        .logout a {
            color: #fff;
            text-decoration: none;
        }

        .logout:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>InkWise</h2>
        <a href="#">📊 Dashboard</a>
        <a href="#">📦 Orders</a>
        <a href="#">🎨 Invitations</a>
        <a href="#">📑 Giveaways</a>
        <a href="{{ route('admin.templates.index') }}">Templates</a> 
        <a href="#">👥 Users</a>
        <div class="logout">
            <a href="{{ route('admin.logout') }}">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h1>Welcome, Admin!</h1>
        <p>This is your InkWise dashboard. You can manage orders, invitations, giveaways, and users here.</p>

        <div class="card">
            <h2>Recent Orders</h2>
            <p>No new orders yet.</p>
        </div>

        <div class="card">
            <h2>System Stats</h2>
            <ul>
                <li>Total Orders: 0</li>
                <li>Total Users: 0</li>
                <li>Pending Orders: 0</li>
            </ul>
        </div>
    </div>

</body>
</html>
