@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/customer-profiles.css') }}">
    <style>
        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(79, 70, 229, 0.05);
            transform: scale(1.01);
        }
        .clickable-row:active {
            background-color: rgba(79, 70, 229, 0.1);
        }
    </style>
@endpush

@section('title', 'Customer Profiles')

@section('content')
@php
    $customersCollection = $customers instanceof \Illuminate\Support\Collection ? $customers : collect($customers);
@endphp

<main class="materials-page admin-page-shell staff-customer-profiles-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Customer Profiles</h1>
            <p class="page-subtitle">View and manage customer information and registration details.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-customer-profiles-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <section class="customer-profiles-table" aria-label="Customer list">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Profile Picture</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Contact Number</th>
                        <th scope="col">Address</th>
                        <th scope="col">Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customersCollection as $customer)
                        <tr class="clickable-row" data-href="{{ route('staff.customer_profile.show', $customer->user_id) }}" role="button" tabindex="0">
                            <td>{{ $customer->user_id }}</td>
                            <td class="profile-pic-cell">
                                <img src="{{ $customer->profile_picture ?? 'https://via.placeholder.com/50' }}" alt="Profile" class="profile-pic">
                            </td>
                            <td class="customer-name">{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->contact_number ?? '—' }}</td>
                            <td>{{ $customer->address ?? '—' }}</td>
                            <td>{{ $customer->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertBanner = document.querySelector('.staff-customer-profiles-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }

            // Make table rows clickable
            document.querySelectorAll('.clickable-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    window.location.href = this.dataset.href;
                });
                
                // Handle keyboard navigation
                row.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        window.location.href = this.dataset.href;
                    }
                });
            });
        });
    </script>
@endpush
