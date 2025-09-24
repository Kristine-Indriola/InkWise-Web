@extends('layouts.admin')

@section('title', 'Invitation Templates')

{{-- Page-specific CSS --}}

<link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
<script src="{{ asset('js/admin/template/template.js') }}"></script>

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
                    <div class="template-preview">
                        @if($template->preview)
                            <img src="{{ asset('storage/' . $template->preview) }}" alt="Preview" style="max-width:100%;border-radius:8px;">
                        @else
                            <span>No preview</span>
                        @endif
                    </div>
                    <div class="template-info">
                        <h3>{{ $template->name }}</h3>
                        <p>{{ $template->category }}</p>
                        <p>{{ $template->description }}</p>
                        @if($template->updated_at)
                            <small style="color:#888;">Last updated: {{ $template->updated_at->format('M d, Y H:i') }}</small>
                        @endif
                    </div>
                    <div class="template-actions">
                        <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn-edit">Edit</a>
                        <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete" onclick="return confirm('Delete this template?')">Delete</button>
                        </form>
                        <a href="{{ route('admin.products.create.invitation', ['template_id' => $template->id]) }}"
                           class="btn"
                           style="background:#94b9ff;color:#fff;margin-top:8px;">
                            Upload to Product
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Image Preview Modal -->
    <div id="previewModal" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
        <span id="closePreview" style="position:absolute;top:30px;right:40px;font-size:2.5rem;color:#fff;cursor:pointer;">&times;</span>
        <img id="modalImg" src="" style="max-width:90vw;max-height:90vh;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
    </div>

@endsection