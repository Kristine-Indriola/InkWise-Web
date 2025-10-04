@extends('layouts.admin')

@section('title', 'Invitation Templates')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/template/template.js') }}" defer></script>
@endpush

@section('content')
    <main class="dashboard-container templates-page" role="main">
        <section class="templates-container" aria-labelledby="templates-heading">
            <div class="templates-header">
                <div>
                    <h2 id="templates-heading">Invitation Templates</h2>
                    <p>Manage and create beautiful invitation templates</p>
                </div>
                <a href="{{ route('admin.templates.create') }}" class="btn-create">
                    <span aria-hidden="true">+</span>
                    <span>Create Template</span>
                </a>
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if($templates->isEmpty())
            <p class="no-templates mt-gap">
                No templates available yet. Click <b>Create Template</b> to add one.
            </p>
        @else
            <div class="templates-grid mt-gap" role="list">
                @foreach($templates as $template)
                    <article class="template-card" role="listitem">
                        <div class="template-preview">
                            @php
                                $front = $template->front_image ?? $template->preview;
                                $back = $template->back_image ?? null;
                            @endphp
                            @if($front)
                                <img src="{{ \App\Support\ImageResolver::url($front) }}" alt="Preview of {{ $template->name }}">
                            @else
                                <span>No preview</span>
                            @endif
                            @if($back)
                                <img src="{{ \App\Support\ImageResolver::url($back) }}" alt="Back of {{ $template->name }}" class="back-thumb">
                            @endif
                        </div>
                        <div class="template-info">
                            <h3>{{ $template->name }}</h3>
                            <p>{{ $template->category }}</p>
                            <p class="description">{{ $template->description }}</p>
                            @if($template->updated_at)
                                <small>Last updated: {{ $template->updated_at->format('M d, Y H:i') }}</small>
                            @endif
                        </div>
                        <div class="template-actions">
                            <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn-edit">Edit</a>
                            <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('Delete this template?')">Delete</button>
                            </form>
                            <a href="{{ route('admin.products.create.invitation', ['template_id' => $template->id]) }}"
                               class="btn btn-upload"
                               data-template-id="{{ $template->id }}">
                                Upload to Product
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div id="previewModal">
            <span id="closePreview" aria-label="Close preview" role="button">&times;</span>
            <img id="modalImg" src="" alt="Template preview modal">
        </div>
    </main>
@endsection