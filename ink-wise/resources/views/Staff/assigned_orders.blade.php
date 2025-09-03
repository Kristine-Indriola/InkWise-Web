@extends('layouts.Staffapp')

@section('content')
  <h1 class="text-2xl font-semibold mb-4">Assigned Orders</h1>
  <div class="bg-white p-6 rounded-lg shadow">
    <table class="w-full table-auto border-collapse">
      <thead>
        <tr class="bg-gray-200 text-left">
          <th class="p-2">Order ID</th>
          <th class="p-2">customer</th>
          <th class="p-2">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="p-2">#00123</td>
          <td class="p-2">John Doe</td>
          <td class="p-2 text-yellow-600">In Progress</td>
        </tr>
        <tr>
          <td class="p-2">#00124</td>
          <td class="p-2">Jane Smith</td>
          <td class="p-2 text-green-600">Completed</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
