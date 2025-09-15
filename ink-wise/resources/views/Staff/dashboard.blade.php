
@extends('layouts.Staffapp')

@section('content')
<div class="flex justify-center items-center min-h-[60vh]">
    <div class="grid grid-cols-2 gap-10 max-w-xl">
        <div class="border-2 rounded-lg p-8 text-center" style="border-color: #a78bfa;">
            <div class="text-4xl font-bold mb-2">12</div>
            <div class="text-lg">Total Orders</div>
        </div>
        <div class="border-2 rounded-lg p-8 text-center" style="border-color: #5eead4;">
            <div class="text-4xl font-bold mb-2">5</div>
            <div class="text-lg">Assigned Orders</div>
        </div>
        <div class="border-2 rounded-lg p-8 text-center col-span-2 mx-auto" style="border-color: #818cf8; width: 50%;">
            <div class="text-4xl font-bold mb-2">8</div>
            <div class="text-lg">Customers</div>
        </div>
    </div>
</div>
@endsection