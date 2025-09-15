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
                <div class="template-card" style="padding:0;">
                    <div class="template-preview" style="height:auto;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:16px;">
                        @if($template->preview)
                            <img src="{{ asset('storage/' . $template->preview) }}"
                                 alt="Preview"
                                 class="preview-thumb"
                                 data-img="{{ asset('storage/' . $template->preview) }}"
                                 style="max-width:100%;max-height:260px;display:block;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:12px;">
                        @endif

                        <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn-edit" style="width:100%;">Edit</a>

                        {{-- Upload button (links to upload page or triggers modal) --}}
                        <a href="javascript:void(0);" class="btn" style="margin-top:8px;width:100%;">Upload</a>

                        {{-- Delete button --}}
                        <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" style="margin-top:8px;width:100%;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-delete" style="width:100%;" onclick="return confirm('Delete this template?')">Delete</button>
                        </form>
                    </div>

                    <div class="template-info" style="margin-bottom:8px;">
                        <h3>{{ $template->name }}</h3>
                        <p>{{ $template->category }}</p>
                        <p>{{ $template->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div id="previewModal" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
        <span id="closePreview" style="position:absolute;top:30px;right:40px;font-size:2.5rem;color:#fff;cursor:pointer;">&times;</span>
        <img id="modalImg" src="" style="max-width:90vw;max-height:90vh;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
    </div>

@endsection