@extends('layouts.Staffapp')

@section('title', 'Customer Profiles')

@section('content')
<!-- Quick inline CSS so it definitely applies -->
<style>
    .stock { background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,.1); }
    .stock h3 { font-size:1.5rem; margin-bottom:1rem; color:#4b5563; font-weight:600; }
    .search-bar { margin-bottom:20px; display:flex; justify-content:flex-end; }
    .search-bar input { padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; outline:none; }
    table { width:100%; border-collapse:separate; border-spacing:0 12px; }
    thead th { padding:14px 18px; text-align:left; font-weight:600; color:#374151; font-size:.95rem; background:#f3f4f6; }
    tbody td { padding:16px 18px; font-size:.9rem; color:#374151; vertical-align:middle; background:#f9fafb; }
    tbody tr:hover td { background:#f3f4f6; }
    tbody td:first-child { width:60px; text-align:center; border-top-left-radius:10px; border-bottom-left-radius:10px; }
    tbody td:last-child { border-top-right-radius:10px; border-bottom-right-radius:10px; }
    img { border-radius:50%; border:2px solid #e5e7eb; }
    .pagination { margin-top:20px; display:flex; justify-content:center; }
</style>

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
