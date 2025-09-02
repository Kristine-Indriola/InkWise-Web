@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-4">My Orders</h1>
    <p class="text-gray-600 mb-6">Here you can see all your current orders.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Example order card -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
            <h2 class="font-semibold text-lg">Order #12345</h2>
            <p class="text-gray-500">Wedding Invitation Pack</p>
            <p class="text-gray-500">Status: <span class="font-medium">Processing</span></p>
        </div>

        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
            <h2 class="font-semibold text-lg">Order #12346</h2>
            <p class="text-gray-500">Birthday Invitation Pack</p>
            <p class="text-gray-500">Status: <span class="font-medium">Shipped</span></p>
        </div>
    </div>
</div>
@endsection
