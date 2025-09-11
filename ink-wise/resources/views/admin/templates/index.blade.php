@extends('layouts.admin')

@section('title', 'Invitation Templates')

{{-- Page-specific CSS --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/template.css') }}">
@endpush

{{-- Page-specific JS --}}
@push('scripts')
<script src="{{ asset('js/admin/template.js') }}"></script>
@endpush

@section('content')

    <!-- Header Container -->
    <div class="templates-container">
        <div class="templates-header">
            <div>
                <h2>Invitation Templates</h2>
                <p>Manage and create beautiful invitation templates</p>
            </div>
            <a href="{{ route('admin.templates.create') }}" class="btn-create">
                + Create Template
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Templates Grid -->
    @if($templates->isEmpty())
        <p class="no-templates mt-gap">
            No templates available yet. Click <b>Create Template</b> to add one.
        </p>
    @else
        <div class="templates-grid mt-gap">
            @foreach($templates as $template)
                <div class="template-card">
                    
                    <!-- Preview -->
                    <div class="template-preview">
    @if($template->preview)
        <img src="{{ asset('storage/' . $template->preview) }}" alt="Preview" style="max-width:100%;max-height:100px;border-radius:8px;">
    @else
        📑
    @endif
    @php
        $design = json_decode($template->design, true);
    @endphp
    @if($design)
        <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
            <span style="display:inline-block;width:22px;height:22px;border-radius:5px;background:{{ $design['primary_color'] ?? '#ccc' }};"></span>
            <span style="display:inline-block;width:22px;height:22px;border-radius:5px;background:{{ $design['secondary_color'] ?? '#ccc' }};"></span>
        </div>
    @endif
</div>
                    <!-- Info -->
                    <div class="template-info">
    <h3>{{ $template->name }}</h3>
    <p>Created {{ $template->created_at->diffForHumans() }}</p>
    @php
        $design = json_decode($template->design, true);
    @endphp
    @if($design)
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <span style="font-size:12px;color:#06b6d4;">{{ $design['paper_type'] ?? '' }}</span>
            <span style="font-size:12px;color:#0891b2;">{{ $design['trim_type'] ?? '' }}</span>
            <span style="font-size:12px;color:#888;">{{ $design['category'] ?? '' }} | {{ $design['size'] ?? '' }}</span>
        </div>
        <p style="font-size:13px;color:#6b7280;">{{ $design['description'] ?? '' }}</p>
    @endif
                        <!-- Actions -->
                        <div class="template-actions">
                            <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn-edit">Edit</a>
                            <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn-use">Use</a>
                            <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('Delete this template?')">Delete</button>
                            </form>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

@endsection