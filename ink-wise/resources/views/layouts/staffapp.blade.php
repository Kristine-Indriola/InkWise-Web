<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <div class="flex h-screen">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r flex flex-col">
      <div class="p-4 text-2xl font-bold text-purple-700 border-b"> 
        <span class="italic">Inkwise</span>
      </div>
      <div class="p-4 flex items-center border-b">
        <img src="https://via.placeholder.com/40" class="rounded-full mr-3">
        <div>
          <p class="font-semibold">Staff Profile</p>
          <span class="text-green-500 text-sm">● Online</span>
        </div>
      </div>
      <nav class="flex-1 p-4">
        <ul class="space-y-2">
          <li><a href="{{ route('staff.dashboard') }}" class="flex items-center p-2 rounded hover:bg-gray-200"> Dashboard</a></li>
          <li><a href="{{ route('staff.assigned.orders') }}" class="flex items-center p-2 rounded hover:bg-gray-200"> Assigned Orders</a></li>
          <li><a href="{{ route('staff.order.list') }}" class="flex items-center p-2 rounded hover:bg-gray-200"> Order List</a></li>
          <li><a href="{{ route('staff.customer.profile') }}" class="flex items-center p-2 rounded hover:bg-gray-200"> Customer Profiles</a></li>
          <li><a href="{{ route('staff.notify.customers') }}" class="flex items-center p-2 rounded hover:bg-gray-200"> Notify Customers</a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
      
      <!-- Top Bar -->
      <header class="flex justify-between items-center bg-white p-4 border-b">
        <h1 class="text-xl font-bold">Welcome, Staff!</h1>
        <div class="flex items-center space-x-4">
          <button class="relative">
            🔔
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-1 rounded-full">1</span>
          </button>
          <button>⚙</button>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded">Logout</button>
          </form>
        </div>
      </header>

      <!-- Dashboard Stats -->
      <section class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow text-center border border-purple-400">
          <p class="text-3xl font-bold">12</p>
          <p class="text-gray-500">Total Orders</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow text-center border border-purple-400">
          <p class="text-3xl font-bold">5</p>
          <p class="text-gray-500">Assigned Orders</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow text-center border border-purple-400">
          <p class="text-3xl font-bold">8</p>
          <p class="text-gray-500">Customers</p>
        </div>
      </section>

      <!-- Page Content -->
      <section class="p-6">
        @yield('content')
      </section>
    </main>
  </div>

</body>
</html>
