<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders - InkWise</title>

  <!-- Google Fonts: Nunito -->
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
  <link rel="stylesheet" href="{{ asset('css/customerprofile.css') }}">
  <link rel="stylesheet" href="{{ asset('css/customer/customertemplates.css') }}">

  <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>
  <script src="{{ asset('js/customerprofile.js') }}" defer></script>

  <!-- Alpine.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>

  <style>
    /* General Styles */
    body {
      font-family: 'Nunito', sans-serif;
      background: #f9fafb;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 5px;
      color: #1f2937;
    }

    .subtitle {
      color: #6b7280;
      margin-bottom: 30px;
    }

    /* Orders Table */
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-top: 20px;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
    }
    

    .orders-table th,
    .orders-table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }

    .orders-table thead {
      background-color: #f3f4f6;
      font-weight: 600;
    }

    .orders-table tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    .orders-table tbody tr:hover {
      background-color: #f0f8ff;
    }

    /* Status Labels */
    .status {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 700;
    }

    .status.processing {
      background-color: #fef3c7;
      color: #b45309;
    }

    .status.shipped {
      background-color: #d1fae5;
      color: #065f46;
    }

    .status.delivered {
      background-color: #cce5ff;
      color: #004085;
    }

    /* Links */
    a.details {
      text-decoration: none;
      color: #4f46e5;
      font-weight: 600;
    }

    a.details:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800">

  <!-- Header -->
  <header class="shadow animate-fade-in-down">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
      <!-- Logo -->
      <div class="flex items-center animate-bounce-slow">
           <span class="text-5xl font-bold logo-i"style="font-family: Edwardian Script ITC;" >I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: black;">nkwise</span>
        </div>

      <!-- Navigation -->
      <nav class="hidden md:flex space-x-6">
        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-[#f6b3b2]">Home</a>
        <a href="{{ route('templates.wedding') }}" class="text-gray-700 hover:text-[#f6b3b2]">Wedding</a>
        <a href="{{ route('templates.birthday') }}" class="text-gray-700 hover:text-[#f6b3b2]">Birthday</a>
        <a href="{{ route('templates.baptism') }}" class="text-gray-700 hover:text-[#f6b3b2]">Baptism</a>
        <a href="{{ route('templates.corporate') }}" class="text-gray-700 hover:text-[#f6b3b2]">Corporate</a>
      </nav>

      <!-- Search + User -->
      <div class="flex items-center space-x-3">
        <form action="{{ route('dashboard') }}" method="GET" class="hidden md:flex">
          <input type="text" name="query" placeholder="Search..."
            class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
        </form>

        
       @auth
    <div class="relative">
        <!-- Dropdown Button -->
        <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
            {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
        </button>
        @endauth
      </div>
    </div>
  </header>

  <!-- Welcome -->
  <div class="welcome-section text-center my-6">
    <h1 class="page-title">My Orders</h1>
    <p class="subtitle">Track and manage all your orders in one place.</p>
  </div>

  <!-- Layout -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 md:grid-cols-5 gap-6">
    
    <!-- Sidebar -->
    <aside class="sidebar rounded-2xl p-4 md:col-span-1 h-full bg-white shadow">
  <nav class="space-y-2">
    <a href="{{ route('customerprofile.dashboard') }}" 
       class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition hover:bg-gray-100">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-5 0-9 2.5-9 5.5A1.5 1.5 0 0 0 4.5 21h15a1.5 1.5 0 0 0 1.5-1.5C21 16.5 17 14 12 14Z"/>
      </svg>
      <span class="font-medium">Edit Profile</span>
    </a>

    <!-- My Orders: stays highlighted -->
    <a href="{{ route('customer.my_orders') }}" 
       class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition active">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z"/>
      </svg>
      <span class="font-medium">My Orders</span>
    </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3h10a2 2 0 0 1 2 2v14l-7-3-7 3V5a2 2 0 0 1 2-2z"/></svg>
          <span class="font-medium">Order History</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 4h14v2H5zm0 7h14v2H5zm0 7h9v2H5z"/></svg>
          <span class="font-medium">Saved Design</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-1 2h2v3h-2z"/></svg>
          <span class="font-medium">Feedback & Ratings</span>
        </a>
        <form method="POST" action="{{ route('customer.logout') }}">
          @csrf
          <button type="submit" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full text-left">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V8l-5 4 5 4v-3zM20 3h-8a2 2 0 0 0-2 2v3h2V5h8v14h-8v-3h-2v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>
            <span class="font-medium">Log Out</span>
          </button>
        </form>
      </nav>
    </aside>

    <!-- Orders Table -->
    <section class="md:col-span-4">
      <div class="card bg-white p-6 md:p-8 border border-gray-100">
  <h2 class="text-xl font-semibold mb-6">My Orders</h2>
  <table class="orders-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Item</th>
            <th>Category</th>
            <th>Status</th>
            <th>Date Ordered</th>
            <th>Expected Delivery</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr class="processing">
            <td>Order #12345</td>
            <td>Wedding Invitation Pack</td>
            <td>Wedding</td>
            <td><span class="status processing">Processing</span></td>
            <td>Aug 25, 2025</td>
            <td>Sep 02, 2025</td>
            <td><a href="#" class="details">View Details →</a></td>
          </tr>
          <tr class="shipped">
            <td>Order #12346</td>
            <td>Birthday Invitation Pack</td>
            <td>Birthday</td>
            <td><span class="status shipped">Shipped</span></td>
            <td>Aug 20, 2025</td>
            <td>Aug 28, 2025</td>
            <td><a href="#" class="details">View Details →</a></td>
          </tr>
          <tr class="delivered">
            <td>Order #12347</td>
            <td>Corporate Event Flyer</td>
            <td>Corporate</td>
            <td><span class="status delivered">Delivered</span></td>
            <td>Aug 15, 2025</td>
            <td>Aug 22, 2025</td>
            <td><a href="#" class="details">View Details →</a></td>
          </tr>
        </tbody>
      </table>
</div>
    </section>

  </main>
</body>
</html>
