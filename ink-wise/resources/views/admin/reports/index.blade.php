@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
@endpush

@section('content')
<main class="reports-shell admin-page-shell">
    <header class="page-header reports-page-header">
        <div>
            <h1 class="page-title">Reports &amp; Analytics</h1>
            <p class="page-subtitle">Choose the report workspace you want to drill into — sales momentum or inventory coverage.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('admin.reports.sales') }}" class="btn btn-primary">
                <i class="fi fi-rr-chart-histogram" aria-hidden="true"></i> View sales analytics
            </a>
            <a href="{{ route('admin.reports.inventory') }}" class="pill-link">
                <i class="fi fi-rr-boxes" aria-hidden="true"></i> View inventory analytics
            </a>
        </div>
    </header>

    <section class="reports-detail-grid">
        <article class="reports-card">
            <header>
                <div>
                    <h2>Sales analytics</h2>
                    <p>Review Shopee-style charts, KPIs, and recent order activity.</p>
                </div>
            </header>
            <p>Drill into dynamic timeframes, export CSVs, and inspect the latest 100 orders at a glance.</p>
            <div>
                <a href="{{ route('admin.reports.sales') }}" class="btn btn-primary">
                    Go to sales analytics
                </a>
            </div>
        </article>

        <article class="reports-card">
            <header>
                <div>
                    <h2>Inventory analytics</h2>
                    <p>Monitor stock versus reorder levels to stay ahead of fulfillment risk.</p>
                </div>
            </header>
            <p>Visualize warehouse coverage, flag low/out-of-stock materials, and download stock summaries.</p>
            <div>
                <a href="{{ route('admin.reports.inventory') }}" class="btn btn-secondary">
                    Go to inventory analytics
                </a>
            </div>
        </article>
    </section>
</main>
@endsection
