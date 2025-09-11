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
                    <td>
                        <img src="{{ $customer->profile_picture ?? 'https://via.placeholder.com/50' }}" 
                             alt="Profile" width="50">
                    </td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->contact_number ?? '-' }}</td>
                    <td>{{ $customer->address ?? '-' }}</td>
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
