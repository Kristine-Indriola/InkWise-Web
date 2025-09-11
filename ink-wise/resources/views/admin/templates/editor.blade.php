@extends('layouts.admin')

@section('title', 'Template Editor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/edit.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&family=Playfair+Display&family=Montserrat&family=Roboto&family=Great+Vibes&family=Poppins&family=Lobster&family=Dancing+Script&family=Merriweather&family=Oswald&display=swap" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('js/admin/edit.js') }}"></script>
@endpush

@section('content')
<div class="editor-container">
    <!-- Topbar -->
    <div class="editor-topbar">
        <!-- Navigation Buttons -->
        <div class="nav-links">
            <a href="{{ route('admin.templates.index') }}" class="btn secondary">Back to Templates</a>
            <a href="{{ route('admin.templates.create') }}" class="btn secondary">Create New</a>
        </div>
        <div class="project-name">
    {{ $template->name ?? 'Untitled' }}
    @php
        $design = json_decode($template->design, true);
    @endphp
    @if($design && !empty($design['category']))
        <span style="font-size:16px;color:#888;"> | {{ $design['category'] }}</span>
    @endif
</div>
        <div class="actions">
            <button class="btn">Save</button>
            <button class="btn">↶ </button>
            <button class="btn">↷</button>
            <button class="btn">Size & Shape</button>
            <button class="btn">Preview</button>
            <button class="btn primary">Next ➝</button>
        </div>
    </div>

    <!-- Body -->
    <div class="editor-body">
        <!-- Left Sidebar -->
        <div class="editor-sidebar">
            <ul>
                <li class="active">Text</li>
                <li>Images</li>
                <li>Graphics</li>
                <li>Tables</li>
                <li>Colors</li>
            </ul>
        </div>

        <!-- Canvas -->
        <div class="editor-canvas">
            <canvas id="templateCanvas" width="500" height="700"></canvas>

            <!-- Floating Tool Panel -->
            <div class="floating-panel" id="floatingPanel">
    <!-- Content loaded by JS -->
    @if($design)
        <div style="margin-bottom:12px;">
            <strong>Colors:</strong>
            <span style="display:inline-block;width:28px;height:28px;border-radius:6px;background:{{ $design['primary_color'] ?? '#ccc' }};border:1px solid #eee;margin-right:8px;" title="Primary Color"></span>
            <span style="display:inline-block;width:28px;height:28px;border-radius:6px;background:{{ $design['secondary_color'] ?? '#ccc' }};border:1px solid #eee;" title="Secondary Color"></span>
        </div>
        <div>
            <strong>Size:</strong>
            <span style="font-size:15px;color:#555;">{{ $design['size'] ?? 'N/A' }}</span>
        </div>
    @endif
            </div>
            

            <div class="zoom-controls">
                <button>-</button>
                <span>100%</span>
                <button>+</button>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="editor-pages">
            <div class="page-thumb active">Front</div>
            <div class="page-thumb">Back</div>
        </div>
    </div>
</div>
@endsection
