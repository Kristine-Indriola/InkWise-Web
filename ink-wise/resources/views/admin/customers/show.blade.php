@extends('layouts.admin')

@section('title', 'Customer Profile')

@section('content')
<div class="profile">
    <h2>{{ $customer->customer?->first_name }} {{ $customer->customer?->last_name }}</h2>
    <p>Email: {{ $customer->email }}</p>
    <p>Phone: {{ $customer->customer?->contact_number ?? '-' }}</p>
    <p>Gender: {{ ucfirst($customer->customer?->gender ?? 'N/A') }}</p>
    <p>Birthdate: {{ $customer->customer?->date_of_birth ?? '-' }}</p>

    <h3>Purchases</h3>
    @if($customer->orders && $customer->orders->count() > 0)
        <ul>
            @foreach($customer->orders as $order)
                <li>
                    Order #{{ $order->id }} - {{ $order->status }} - {{ $order->created_at->format('M d, Y') }}
                    <ul>
                        @foreach($order->items as $item)
                            <li>{{ $item->product_name }} (x{{ $item->quantity }})</li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    @else
        <p>No purchases yet.</p>
    @endif
</div>
@endsection
