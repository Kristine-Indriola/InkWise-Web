<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/alpinejs" defer></script>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Cinzel:wght@600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    .logo-script { font-family: 'Great Vibes', cursive; }
    .logo-serif  { font-family: 'Cinzel', serif; }
    .sidebar .menu-icon { width: 1.25rem; display: inline-flex; justify-content: center; }
    .notif-badge {
      background: red;
      color: white;
      font-size: 0.75rem;
      padding: 0.1rem 0.4rem;
      border-radius: 9999px;
      margin-left: 0.25rem;
    }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex h-screen" x-data="{ open: true }">
   
  <!-- Sidebar -->
  <aside :class="open ? 'w-48' : 'w-16'" 
       class="relative border-r flex flex-col sidebar transition-all duration-300 text-sm"
       style="background: linear-gradient(135deg, #acd9b5, #6f94d6);">
    
    <!-- Logo (always visible) -->
    <div class="flex justify-center items-center py-4">
        <img src="{{ asset('images/logo.png') }}" 
         alt="Logo" 
         class="w-24 h-24 object-contain">

    </div>

    <!-- Menu -->
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('staff.profile.edit') }}"
                   class="flex items-center p-2 text-gray-700 hover:bg-purple-200 rounded-lg">
                   <span class="menu-icon mr-2"><i class="fa-solid fa-user-circle"></i></span>
                   <span x-show="open" x-transition>Profile</span>
                </a>
            </li>

            <li>
                <a href="{{ route('staff.dashboard') }}"
                   class="flex items-center p-2 rounded hover:bg-purple-200
                          {{ request()->routeIs('staff.dashboard') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
                   <span class="menu-icon mr-3"><i class="fa-solid fa-gauge-high"></i></span>
                   <span x-show="open" x-transition>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="{{ route('staff.assigned.orders') }}"
                   class="flex items-center p-2 rounded hover:bg-purple-200
                          {{ request()->routeIs('staff.assigned.orders') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
                   <span class="menu-icon mr-3"><i class="fa-solid fa-clipboard-list"></i></span>
                   <span x-show="open" x-transition>Assigned Orders</span>
                </a>
            </li>

            <li>
                <a href="{{ route('staff.order.list') }}"
                   class="flex items-center p-2 rounded hover:bg-purple-200
                          {{ request()->routeIs('staff.order.list') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
                   <span class="menu-icon mr-3"><i class="fa-solid fa-list"></i></span>
                   <span x-show="open" x-transition>Order List</span>
                </a>
            </li>

            <!-- Added: Customer Profiles -->
            <li>
                <a href="{{ route('staff.customer_profile') }}"
                   class="flex items-center p-2 rounded hover:bg-purple-200
                          {{ request()->routeIs('staff.customer_profile') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
                   <span class="menu-icon mr-3"><i class="fa-solid fa-users"></i></span>
                   <span x-show="open" x-transition>Customer Profiles</span>
                </a>
            </li>

            <!-- Added: Notify Customers -->
            <li>
                <a href="{{ route('staff.notify.customers') }}"
                   class="flex items-center p-2 rounded hover:bg-purple-200
                          {{ request()->routeIs('staff.notify.customers') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
                   <span class="menu-icon mr-3"><i class="fa-solid fa-bell"></i></span>
                   <span x-show="open" x-transition>Notify Customers</span>
                </a>
            </li>

            <!-- Added: Materials -->
            <li>
                <a href="{{ route('staff.materials.index') }}"
                   class="flex items-center p-2 rounded hover:bg-purple-200
                          {{ request()->routeIs('staff.materials.*') ? 'bg-gray-100 text-purple-600 font-semibold' : 'text-gray-700' }}">
                   <span class="menu-icon mr-3"><i class="fa-solid fa-boxes-stacked"></i></span>
                   <span x-show="open" x-transition>Materials</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Arrow Toggle (center edge) -->
    <button @click="open = !open"
            class="absolute top-1/2 -right-3 transform -translate-y-1/2 bg-purple-500 text-white rounded-full p-1 shadow hover:bg-purple-600 transition">
        <i :class="open ? 'fa-solid fa-angle-left' : 'fa-solid fa-angle-right'"></i>
    </button>
</aside>

  <!-- Main Content -->
  <main class="flex-1 flex flex-col">
     
      <!-- Top Bar -->
      <header class="flex justify-between items-center p-4 border-b"
      style="background: linear-gradient(135deg, #acd9b5, #6f94d6);">
        <h1 class="text-xl font-bold">Welcome, Staff!</h1>
        <div class="flex items-center space-x-4">
          <a href="{{ route('staff.staff.materials.notification') }}" class="nav-link">
            🔔
            @php
                $lowCount = \App\Models\Material::whereHas('inventory', function($q) {
                    $q->whereColumn('stock_level', '<=', 'reorder_level')
                      ->where('stock_level', '>', 0);
                })->count();

                $outCount = \App\Models\Material::whereHas('inventory', function($q) {
                    $q->where('stock_level', '<=', 0);
                })->count();

                $notifCount = $lowCount + $outCount;
            @endphp

            @if($notifCount > 0)
                <span class="notif-badge">{{ $notifCount }}</span>
            @endif
          </a>

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
