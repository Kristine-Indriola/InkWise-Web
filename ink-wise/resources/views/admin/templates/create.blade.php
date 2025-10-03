@extends('layouts.admin')

@section('title', 'Create New Template')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/template/template.js') }}" defer></script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New Template</h2>
            <p class="create-subtitle">Fill in the details to craft a new invitation template</p>
        </div>

        <form action="{{ route('admin.templates.store') }}" method="POST" class="create-form">
            @csrf

            <input type="hidden" name="design" id="design" value="{}">

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" required>
                </div>
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday">Birthday</option>
                        <option value="Baptism">Baptism</option>
                        <option value="Corporate">Corporate</option>
                    </select>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="product_type">Product Type</label>
                    <input type="text" id="product_type" name="product_type" placeholder="e.g. Card, Poster">
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme/Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the template design, style, and intended use..."></textarea>
            </div>

            <div class="create-actions">
                <a href="{{ route('admin.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create &amp; Edit Template</button>
            </div>
        </form>
    </section>
</main>
@endsection

