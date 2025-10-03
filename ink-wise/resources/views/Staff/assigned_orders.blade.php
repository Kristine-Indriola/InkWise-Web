@extends('layouts.Staffapp')

@section('content')
<h1 class="text-2xl font-bold mb-4">Assigned Orders</h1>

{{-- Flash message --}}
@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="mb-4">
    <input type="text" placeholder="Search by order Id, Customer, Product"
           class="border rounded px-4 py-2 w-96 focus:outline-none focus:ring-2 focus:ring-purple-400" />
</div>
<div class="bg-white p-6 rounded-lg shadow">
    <table class="w-full table-auto border-collapse">
        <thead>
            <tr class="bg-blue-700 text-white">
                <th class="p-2">Order ID</th>
                <th class="p-2">Customer</th>
                <th class="p-2">Product</th>
                <th class="p-2">Due Date</th>
                <th class="p-2">Status</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="border-b">
                    <td class="p-2 font-mono">#{{ $order->order_number }}</td>
                    <td class="p-2">{{ $order->customer->name ?? '-' }}</td>
                    <td class="p-2">{{ $order->product ?? '-' }}</td>
                    <td class="p-2">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs">
                            {{ \Carbon\Carbon::parse($order->due_date)->format('M d, Y') }}
                        </span>
                    </td>
                    <td class="p-2">
                        @if($order->status === 'Completed')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Completed</span>
                        @elseif($order->status === 'In progress')
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">In progress</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">{{ $order->status }}</span>
                        @endif
                    </td>
                    <td class="p-2 space-x-2">
                        {{-- Update Status --}}
                        @if($order->status !== 'Completed')
                            <form action="{{ route('staff.orders.updateStatus', $order->id) }}" method="POST" class="inline">
                                @csrf
                                <select name="status" class="border rounded px-2 py-1 text-sm">
                                    <option value="Pending" {{ $order->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="In progress" {{ $order->status == 'In progress' ? 'selected' : '' }}>In progress</option>
                                    <option value="Completed" {{ $order->status == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Update</button>
                            </form>
                        @endif

                        {{-- Confirm --}}
                        <form action="{{ route('staff.orders.confirm', $order->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Confirm</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-400">No assigned orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
