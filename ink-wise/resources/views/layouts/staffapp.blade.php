<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Cinzel:wght@600&display=swap" rel="stylesheet">

  <!-- Font Awesome (icons) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    .logo-script { font-family: 'Great Vibes', cursive; }
    .logo-serif  { font-family: 'Cinzel', serif; }

    /* small tweak so FA icons are centered in menu */
    .sidebar .menu-icon { width: 1.25rem; display: inline-flex; justify-content: center; }
  </style>
</head>
<body class="bg-gray-100 font-sans">

  <div class="flex h-screen">
   
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r flex flex-col sidebar">
      <div class="p-4 text-2xl font-bold text-purple-700 border-b flex items-center">
        <span class="logo-script text-4xl text-purple-500">I</span>
        <span class="logo-serif text-2xl ml-1 text-blue-600">nkwise</span>
      </div>
<!--
      <div class="p-4 flex items-center border-b">
        <img src="https://via.placeholder.com/40" alt="avatar" class="rounded-full mr-3">
        <div>
          <p class="font-semibold">Staff Profile</p>
          <span class="text-green-500 text-sm">● Online</span>
        </div>
      </div>
-->
      <nav class="flex-1 p-4">
        <ul class="space-y-2">
    <li>
        <a href="{{ route('staff.profile.edit') }}" class="flex items-center p-2 text-gray-700 hover:bg-purple-200 rounded-lg">
  <i class="fa-solid fa-user-circle mr-2"></i> Profile
</a>

    </li>
    <!-- Existing menu items below -->
          <li>
            <a href="{{ route('staff.dashboard') }}"
               class="flex items-center p-2 rounded hover:bg-gray-100
                      {{ request()->routeIs('staff.dashboard') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
              <span class="menu-icon mr-3"><i class="fa-solid fa-gauge-high"></i></span>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="{{ route('staff.assigned.orders') }}"
               class="flex items-center p-2 rounded hover:bg-gray-100
                      {{ request()->routeIs('staff.assigned.orders') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
              <span class="menu-icon mr-3"><i class="fa-solid fa-clipboard-list"></i></span>
              <span>Assigned Orders</span>
            </a>
          </li>

          <li>
            <a href="{{ route('staff.order.list') }}"
               class="flex items-center p-2 rounded hover:bg-gray-100
                      {{ request()->routeIs('staff.order.list') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
              <span class="menu-icon mr-3"><i class="fa-solid fa-list"></i></span>
              <span>Order List</span>
            </a>
          </li>

          <li>
            <a href="{{ route('staff.customer.profile') }}"
               class="flex items-center p-2 rounded hover:bg-gray-100
                      {{ request()->routeIs('staff.customer.profile') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
              <span class="menu-icon mr-3"><i class="fa-solid fa-users"></i></span>
              <span>Customer Profiles</span>
            </a>
          </li>

          <li>
            <a href="{{ route('staff.notify.customers') }}"
               class="flex items-center p-2 rounded hover:bg-gray-100
                      {{ request()->routeIs('staff.notify.customers') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
              <span class="menu-icon mr-3"><i class="fa-solid fa-bell"></i></span>
              <span>Notify Customers</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
     
      <!-- Top Bar -->
      <header class="flex justify-between items-center bg-white p-4 border-b">
        <h1 class="text-xl font-bold">Welcome, Staff!</h1>
        <div class="flex items-center space-x-4">
          <button class="relative text-yellow-600" aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            <span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs px-1 rounded-full">1</span>
          </button>

          <button class="text-gray-600" aria-label="Settings">
            <i class="fa-solid fa-gear"></i>
          </button>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded">Logout</button>
          </form>
        </div>
      </header>
     
      <!-- Page Content -->
      <section class="p-6">
        @yield('content')
      </section>
    </main>
  </div>

</body>
</html>
