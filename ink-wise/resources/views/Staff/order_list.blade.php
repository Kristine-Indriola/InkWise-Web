@extends('layouts.Staffapp')

@section('content')
  <h1 class="text-2xl font-semibold mb-4">Order List</h1>
  <div class="bg-white p-6 rounded-lg shadow">
    <ul class="list-disc pl-6">
      <li>Order #00123 - Pending</li>
      <li>Order #00124 - Completed</li>
      <li>Order #00125 - Cancelled</li>
    </ul>
  </div>
@endsection
