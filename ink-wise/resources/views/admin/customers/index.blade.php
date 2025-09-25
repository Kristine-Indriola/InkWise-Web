@extends('layouts.admin')

@section('title', 'Customer Profiles')

@section('content')
<div class="stock">
    <h3>All Customers</h3>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Profile Picture</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>Address</th>
                <th>Registered At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->id }}</td>

                    {{-- Profile Picture --}}
                    <td>
               <img src="{{ $customer->customer && $customer->customer->photo ? \App\Support\ImageResolver::url($customer->customer->photo) : 'https://via.placeholder.com/50' }}"
                             alt="Profile" width="50" height="50"
                             style="border-radius:50%; object-fit:cover;">
                    </td>

                    {{-- Full Name --}}
                    <td>
                        {{ $customer->customer->first_name ?? '' }}
                        {{ $customer->customer->middle_name ?? '' }}
                        {{ $customer->customer->last_name ?? '' }}
                    </td>

                    {{-- Email from users table --}}
                    <td>{{ $customer->email ?? '-' }}</td>

                    {{-- Contact Number --}}
                    <td>{{ $customer->customer->contact_number ?? '-' }}</td>

                    {{-- Address --}}
                    <td>
                        @if($customer->address)
                            {{ $customer->address->street }},
                            {{ $customer->address->barangay }},
                            {{ $customer->address->city }},
                            {{ $customer->address->province }}
                        @else
                            -
                        @endif
                    </td>

                    {{-- Registered Date --}}
                    <td>{{ $customer->created_at->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No customers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
