@extends('layouts.admin')

@section('title', 'Invitation Templates')

@push('styles')
    @vite('resources/css/admin/template/template.css')
@endpush

@push('scripts')
    @vite('resources/js/admin/template/template.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle upload template button clicks
            document.querySelectorAll('.upload-template-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const templateId = this.getAttribute('data-template-id');
                    const templateName = this.getAttribute('data-template-name');
                    
                    if (confirm(`Are you sure you want to upload "${templateName}"? This will mark the template as ready for use.`)) {
                        // Disable button to prevent double-clicks
                        this.disabled = true;
                        this.textContent = 'Uploading...';
                        
                        // Make AJAX request to upload template
                        fetch(`/admin/templates/${templateId}/upload-to-product-uploads`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Template uploaded successfully!');
                                // Redirect to uploaded templates page
                                window.location.href = '{{ route("admin.templates.uploaded") }}';
                            } else {
                                alert('Upload failed: ' + (data.message || 'Unknown error'));
                                // Re-enable button
                                this.disabled = false;
                                this.textContent = 'Upload';
                            }
                        })
                        .catch(error => {
                            console.error('Upload error:', error);
                            alert('Upload failed. Please try again.');
                            // Re-enable button
                            this.disabled = false;
                            this.textContent = 'Upload';
                        });
                    }
                });
            });
        });
    </script>
@endpush

@section('content')
    <main class="dashboard-container templates-page" role="main">
        <section class="templates-container" aria-labelledby="templates-heading">
            <div class="templates-header">
                <div>
                    <h2 id="templates-heading">Invitation Templates</h2>
                    <p>Manage and create beautiful invitation templates</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('admin.templates.uploaded') }}" class="btn-secondary">
                        <span>View Uploaded Templates</span>
                    </a>
                    <div class="create-dropdown-container">
                        <button class="btn-create dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false">
                            <span aria-hidden="true">+</span>
                            <span>Create Template</span>
                            <span class="dropdown-arrow" aria-hidden="true">▼</span>
                        </button>
                        <div class="create-dropdown-menu" role="menu" aria-hidden="true">
                            <a href="{{ route('admin.templates.create', ['type' => 'invitation']) }}" class="dropdown-item" role="menuitem">
                                <span class="item-icon">■</span>
                                <span>Invitation</span>
                            </a>
                            <a href="{{ route('admin.templates.create', ['type' => 'giveaway']) }}" class="dropdown-item" role="menuitem">
                                <span class="item-icon">●</span>
                                <span>Giveaway</span>
                            </a>
                            <a href="{{ route('admin.templates.create', ['type' => 'envelope']) }}" class="dropdown-item" role="menuitem">
                                <span class="item-icon">▬</span>
                                <span>Envelope</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="templates-filters">
                <div class="filter-buttons" role="group" aria-label="Filter templates">
                    <a href="{{ route('admin.templates.index') }}" class="filter-btn {{ !$type ?? true ? 'active' : '' }}" data-filter="all">
                        All
                    </a>
                    <a href="{{ route('admin.templates.index', ['type' => 'invitation']) }}" class="filter-btn {{ $type === 'invitation' ? 'active' : '' }}" data-filter="invitation">
                        Invitations
                    </a>
                    <a href="{{ route('admin.templates.index', ['type' => 'giveaway']) }}" class="filter-btn {{ $type === 'giveaway' ? 'active' : '' }}" data-filter="giveaway">
                        Giveaways
                    </a>
                    <a href="{{ route('admin.templates.index', ['type' => 'envelope']) }}" class="filter-btn {{ $type === 'envelope' ? 'active' : '' }}" data-filter="envelope">
                        Envelopes
                    </a>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if($templates->isEmpty())
            <div class="empty-state mt-gap">
                <div class="empty-state-icon">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h3 class="empty-state-title">No Draft Templates</h3>
                <p class="empty-state-description">All templates have been uploaded. Check the uploaded templates page to see them, or create a new template.</p>
            </div>
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
                            <div class="template-meta">
                                <span class="template-category">{{ $template->product_type ?? 'Uncategorized' }}</span>
                                @if($template->updated_at)
                                    <span class="template-date">{{ $template->updated_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                            <h3 class="template-title">{{ $template->name }}</h3>
                            @if($template->description)
                                <p class="template-description">{{ $template->description }}</p>
                            @endif
                        </div>
                        <div class="template-actions">
                            <div class="action-buttons">
                                <a href="{{ route('admin.templates.edit', $template->id) }}" class="btn-action btn-edit" title="Edit Template Details">
                                    Edit
                                </a>
                                <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn-action btn-design" title="Design Template">
                                    Design
                                </a>
                                <button type="button" class="btn-action btn-upload upload-template-btn" 
                                        data-template-id="{{ $template->id }}" 
                                        data-template-name="{{ $template->name }}"
                                        title="Upload Template">
                                    Upload
                                </button>
                            </div>
                            <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this template?')" title="Delete Template">
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
