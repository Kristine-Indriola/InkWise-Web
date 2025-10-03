@extends('layouts.Staffapp')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-11">
    <div class="p-9 min-w-[220px] min-h-[160px] rounded-lg shadow text-center border border-purple-500 bg-purple-100">
        <p class="text-3xl font-bold text-purple-900">{{ $totalOrders ?? '' }}</p>
        <p class="text-purple-900">Total Orders</p>
    </div>
    <div class="p-9 min-w-[220px] min-h-[160px] rounded-lg shadow text-center border border-teal-500 bg-teal-100">
        <p class="text-3xl font-bold text-teal-900">{{ $assignedOrders ?? '' }}</p>
        <p class="text-teal-900">Assigned Orders</p>
    </div>
    <div class="p-9 min-w-[220px] min-h-[160px] rounded-lg shadow text-center border border-indigo-500 bg-indigo-100">
        <p class="text-3xl font-bold text-indigo-900">{{ $customers ?? '' }}</p>
        <p class="text-indigo-900">Customers</p>
    </div>
</div>
@endsection