@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/notify-customers.css') }}">
@endpush

@section('title', 'Notify Customers')

@section('content')
<main class="materials-page admin-page-shell staff-notify-customers-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Notify Customers</h1>
            <p class="page-subtitle">Send notifications to customers about updates, promotions, or important announcements.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-notify-customers-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <section class="notify-customers-form" aria-label="Send notification">
        <div class="form-wrapper">
            <form action="#" method="POST" class="staff-notify-form">
                @csrf
                <div class="form-group">
                    <label for="message" class="form-label">Notification Message</label>
                    <textarea id="message" name="message" class="form-control" rows="6" placeholder="Enter your notification message here..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fi fi-rr-paper-plane"></i>
                        <span>Send Notification</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertBanner = document.querySelector('.staff-notify-customers-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }
        });
    </script>
@endpush
