@extends('layouts.admin')

@section('title', 'Uploaded Templates')

@push('styles')
    @vite('resources/css/admin/template/template.css')
@endpush

@section('content')
    <main class="dashboard-container templates-page" role="main">
        <section class="templates-container" aria-labelledby="templates-heading">
            <div class="templates-header">
                <div>
                    <h2 id="templates-heading">Uploaded Templates</h2>
                    <p>View all templates that have been uploaded and are ready for use</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('admin.templates.index') }}" class="btn-secondary">
                        <span>← Back to All Templates</span>
                    </a>
                </div>
            </div>

        </section>

        <div class="templates-filters">
            <div class="filter-buttons" role="group" aria-label="Filter uploaded templates">
                <a href="{{ route('admin.templates.uploaded') }}" class="filter-btn active" data-filter="all">
                    All Uploaded
                </a>
                <a href="{{ route('admin.templates.uploaded', ['type' => 'invitation']) }}" class="filter-btn {{ $type === 'invitation' ? 'active' : '' }}" data-filter="invitation">
                    Invitations
                </a>
                <a href="{{ route('admin.templates.uploaded', ['type' => 'giveaway']) }}" class="filter-btn {{ $type === 'giveaway' ? 'active' : '' }}" data-filter="giveaway">
                    Giveaways
                </a>
                <a href="{{ route('admin.templates.uploaded', ['type' => 'envelope']) }}" class="filter-btn {{ $type === 'envelope' ? 'active' : '' }}" data-filter="envelope">
                    Envelopes
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if($templates->isEmpty())
            <div class="empty-state mt-gap">
                <div class="empty-state-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="empty-state-title">No Uploaded Templates Yet</h3>
                <p class="empty-state-description">Templates will appear here once they are uploaded from the main templates page.</p>
                <a href="{{ route('admin.templates.index') }}" class="btn-primary">Go to Templates</a>
            </div>
        @else
            <div class="templates-grid mt-gap" role="list">
                @foreach($templates as $template)
                    <article class="template-card uploaded-template-card" role="listitem">
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
                            <div class="uploaded-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Uploaded</span>
                            </div>
                        </div>
                        <div class="template-info">
                            <div class="template-meta">
                                <span class="template-category">{{ $template->product_type ?? 'Uncategorized' }}</span>
                                @if($template->updated_at)
                                    <span class="template-date">Uploaded {{ $template->updated_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                            <h3 class="template-title">{{ $template->name }}</h3>
                            @if($template->description)
                                <p class="template-description">{{ $template->description }}</p>
                            @endif
                        </div>
                        <div class="template-actions">
                            <div class="action-buttons">
                                <a href="{{ route('admin.products.create.invitation', ['template_id' => $template->id]) }}" class="btn-action btn-create-product" title="Create Product from Template">
                                    Create Product
                                </a>
                            </div>
                            <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this uploaded template?')" title="Delete Template">
                                    Delete
                                </button>
                            </form>
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
