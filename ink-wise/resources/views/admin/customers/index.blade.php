@extends('layouts.admin')

@section('title', 'Customer Profiles')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/customer.css') }}">
@endpush

@section('content')
<main class="admin-page-shell customer-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Customer Profiles</h1>
            <p class="page-subtitle">Keep track of registered customers and their contact details.</p>
        </div>
    </header>

    <section class="summary-grid" aria-label="Customer summary">
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Customers</span>
                <span class="summary-card-chip accent">Directory</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($customers->count()) }}</span>
                <span class="summary-card-icon" aria-hidden="true">👥</span>
            </div>
            <span class="summary-card-meta">Accounts on record</span>
        </div>
    </section>

    <section aria-label="Customer list" class="customers-table">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Profile</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Address</th>
                        <th scope="col">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php
                            $profile = $customer->customer;
                            $address = $customer->address;
                            $avatar = $profile && $profile->photo
                                ? \App\Support\ImageResolver::url($profile->photo)
                                : 'https://via.placeholder.com/64?text=User';
                            $fullName = collect([
                                $profile->first_name ?? null,
                                $profile->middle_name ?? null,
                                $profile->last_name ?? null,
                            ])->filter()->implode(' ');
                        @endphp
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>
                                <span class="customer-avatar" aria-hidden="true">
                                    <img src="{{ $avatar }}" alt="{{ $fullName ?: 'Customer avatar' }}">
                                </span>
                            </td>
                            <td class="fw-bold">{{ $fullName ?: '—' }}</td>
                            <td>
                                <span class="text-emphasis">{{ $customer->email ?? '—' }}</span>
                            </td>
                            <td>{{ $profile->contact_number ?? '—' }}</td>
                            <td>
                                @if($address)
                                    @php
                                        $addressParts = collect([
                                            $address->street ?? null,
                                            $address->barangay ?? null,
                                            $address->city ?? null,
                                            $address->province ?? null,
                                        ])->filter()->implode(', ');
                                    @endphp
                                    <span class="address-block">{{ $addressParts ?: '—' }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <time datetime="{{ $customer->created_at->toIso8601String() }}">
                                    {{ $customer->created_at->format('M d, Y') }}
                                </time>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
