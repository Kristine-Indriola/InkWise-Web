@extends('layouts.admin')

@section('title', 'Create New Template')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/template.css') }}">
<style>
    .create-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 2px 8px #06b6d420;
    }
    .create-form label {
        color: black;
        font-weight: 600;
    }
    .create-form input[type="text"],
    .create-form textarea,
    .create-form select {
        border: 1px solid black;
        border-radius: 8px;
        padding: 0.5rem;
        background: #fff;
        color: black;
    }
    .btn-submit {
        background: #06b6d4;
        color: #fff;
        border: none;
    }
    .btn-cancel {
        background: #e0f7fa;
        color: #0891b2;
        border: 1px solid #06b6d4;
    }
    .btn-submit:hover, .btn-cancel:hover {
        opacity: 0.9;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin/template.js') }}"></script>
@endpush

@section('content')
<div class="create-container">
    <h2>Create New Template</h2>
    <p class="create-subtitle">Fill in the details to create a new invitation template</p>

    <form action="{{ route('admin.templates.store') }}" method="POST" class="create-form">
        @csrf

        <!-- Name + Category -->
        <div class="create-row">
            <div class="create-group flex-1">
                <label for="name">Template Name</label>
                <input type="text" id="name" name="name" placeholder="Enter template name" required>
            </div>
            <div class="create-group flex-1">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">Select category</option>
                    <option value="Wedding">Wedding</option>
                    <option value="Birthday">Birthday</option>
                    <option value="Baptism">Baptism</option>
                    <option value="Corporate">Corporate</option>
                </select>
            </div>
        </div>

        <!-- Description -->
        <div class="create-group">
            <label for="description">Design Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Describe the template design, style, and intended use..."></textarea>
        </div>

        <!-- Paper Type + Trim Type -->
        <div class="create-row">
            <div class="create-group flex-1">
                <label for="paper_type">Type of Paper</label>
                <select id="paper_type" name="paper_type" required>
                    <option value="">Select paper type</option>
                    <option value="Matte">Matte</option>
                    <option value="Glossy">Glossy</option>
                    <option value="Linen">Linen</option>
                    <option value="Pearl">Pearl</option>
                    <option value="Recycled">Recycled</option>
                </select>
            </div>
            <div class="create-group flex-1">
                <label for="trim_type">Type of Trim</label>
                <select id="trim_type" name="trim_type" required>
                    <option value="">Select trim type</option>
                    <option value="Square">Square</option>
                    <option value="Rounded">Rounded</option>
                    <option value="Scalloped">Scalloped</option>
                    <option value="Ticket">Ticket</option>
                </select>
            </div>
        </div>

        <!-- Size -->
        <div class="create-group flex-1">
            <label for="size">Template Size</label>
            <select id="size" name="size">
                <option value="5x7">Standard (5x7)</option>
                <option value="4x6">Postcard (4x6)</option>
                <option value="A5">A5 (148 × 210 mm)</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="create-actions">
            <a href="{{ route('admin.templates.index') }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit">Create & Edit Template</button>
        </div>

    </form>
</div>
@endsection
