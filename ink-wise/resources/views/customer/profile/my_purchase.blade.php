@extends('layouts.customerprofile')

@section('title', 'My Purchases')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <!-- Tabs -->
    <div class="flex border-b text-base font-semibold mb-4">
        <button class="px-4 py-2 border-b-2 border-[#a6b7ff] text-[#a6b7ff] focus:outline-none">All</button>
        <button class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff]">To Pay</button>
        <button class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff]">To Ship</button>
        <button class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff]">To Receive</button>
        <button class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff]">Completed</button>
        <button class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff]">Cancelled</button>
        <button class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff]">Return/Refund</button>
    </div>

    
    <!-- Purchase Card -->
    <div class="bg-white border rounded-xl mb-4 shadow-sm">
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <div></div>
            <div class="flex items-center gap-2">
                <span class="text-green-600 flex items-center text-xs">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2l4-4"/></svg>
                    Parcel has been delivered
                </span>
                <span class="text-[#f4511e] text-xs font-semibold">RATED</span>
            </div>
        </div>
        <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
            <img src="{{ asset('customerimages/image/weddinginvite.png') }}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
            <div class="flex-1">
                <div class="font-semibold text-lg text-[#a6b7ff]">Elegant Wedding Invitation</div>
                <div class="text-sm text-gray-500">Theme: Rustic Floral</div>
                <div class="text-sm text-gray-500">Quantity: 50 pcs</div>
                <div class="text-sm text-gray-500">Paper: Premium Matte</div>
                <div class="text-sm text-gray-500">Add-ons: Wax Seal, Envelope</div>
            </div>
            <div class="text-right">
                <div class="text-lg font-bold text-gray-700">₱2,500</div>
            </div>
        </div>
        <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
            <div class="text-sm text-gray-500 mb-2 md:mb-0">
                Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱2,500</span>
            </div>
            <div class="flex gap-2">
                <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold">Order Again</button>
                <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#d3b7ff]">Contact Shop</button>
                <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#d3b7ff]">View Shop Rating</button>
            </div>
        </div>
    </div>
</div>
@endsection