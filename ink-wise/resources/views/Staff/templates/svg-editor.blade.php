@extends('layouts.staff')

@section('title', 'SVG Template Editor - ' . ($template->name ?? 'New Template'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        SVG Template Editor
                        @if($template)
                            - {{ $template->name }}
                        @endif
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('staff.templates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Templates
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($template && $template->design)
                        @php
                            $designData = json_decode($template->design, true);
                        @endphp

                        <div class="row">
                            <div class="col-md-8">
                                <div class="svg-editor-container">
                                    <div class="svg-editor-toolbar mb-3">
                                        @if($template->has_back_design)
                                            <div class="btn-group me-3" role="group" aria-label="Design side">
                                                <input type="radio" class="btn-check" name="design-side" id="front-side" autocomplete="off" checked>
                                                <label class="btn btn-outline-primary btn-sm" for="front-side">Front</label>
                                                <input type="radio" class="btn-check" name="design-side" id="back-side" autocomplete="off">
                                                <label class="btn btn-outline-primary btn-sm" for="back-side">Back</label>
                                            </div>
                                        @endif
                                        <button type="button" class="btn btn-primary btn-sm" id="save-svg-btn">
                                            <i class="fas fa-save me-1"></i>Save Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="reset-svg-btn">
                                            <i class="fas fa-undo me-1"></i>Reset
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" id="download-svg-btn">
                                            <i class="fas fa-download me-1"></i>Download SVG
                                        </button>
                                    </div>

                                    <div class="svg-display-area border rounded p-3 bg-light" style="min-height: 600px;">
                                        <!-- Front Design -->
                                        <div class="svg-wrapper front-design" id="front-design">
                                            @if($template->svg_path)
                                                {!! Storage::disk('public')->get($template->svg_path) !!}
                                            @else
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-file-image fa-3x mb-3"></i>
                                                    <p>No front SVG content available</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Back Design -->
                                        @if($template->has_back_design)
                                        <div class="svg-wrapper back-design" id="back-design" style="display: none;">
                                            @if($template->back_svg_path)
                                                {!! Storage::disk('public')->get($template->back_svg_path) !!}
                                            @else
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-file-image fa-3x mb-3"></i>
                                                    <p>No back SVG content available</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="editor-info">
                                    <h5>Template Information</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{ $template->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type:</th>
                                            <td>{{ $template->product_type }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td>
                                                <span class="badge bg-{{ $template->status === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($template->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @if($designData)
                                        <tr>
                                            <th>Text Elements:</th>
                                            <td>{{ count($designData['text_elements'] ?? []) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Changeable Images:</th>
                                            <td>{{ count($designData['changeable_images'] ?? []) }}</td>
                                        </tr>
                                        @endif
                                    </table>

                                    @if($designData && isset($designData['changeable_images']))
                                    <h6 class="mt-4">Changeable Images</h6>
                                    <div class="changeable-images-list">
                                        @foreach($designData['changeable_images'] as $image)
                                        <div class="changeable-image-item mb-2 p-2 border rounded">
                                            <small class="text-muted">{{ $image['id'] ?? 'Unknown ID' }}</small>
                                            <div class="mt-1">
                                                <span class="badge bg-info">{{ $image['type'] ?? 'image' }}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif

                                    <div class="mt-4">
                                        <h6>Instructions</h6>
                                        <ul class="small text-muted">
                                            <li>Click on text elements to edit them</li>
                                            <li>Click on changeable images to replace them</li>
                                            <li>Changes are saved automatically when you click "Save Changes"</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>No Template Data Available</h5>
                            <p class="text-muted">This template doesn't have SVG design data to edit.</p>
                            <a href="{{ route('staff.templates.edit', $template->id ?? '') }}" class="btn btn-primary">
                                Edit Template Details
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/svg-template-editor.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentSide = 'front';
    let frontEditor = null;
    let backEditor = null;

    // Initialize editors for both sides if they exist
    function initializeEditors() {
        const frontSvgElement = document.querySelector('#front-design svg');
        const backSvgElement = document.querySelector('#back-design svg');

        if (frontSvgElement) {
            // Add data attributes for the editor
            @if($designData)
            frontSvgElement.setAttribute('data-svg-data', '{{ json_encode($designData) }}');
            @endif
            frontSvgElement.setAttribute('data-svg-editor', 'true');

            // Initialize front editor
            frontEditor = new SvgTemplateEditor(frontSvgElement, {
                onImageChange: function(element, imageUrl, file) {
                    console.log('Front image changed:', element, imageUrl, file);
                    showNotification('Front image replaced successfully', 'success');
                },
                onTextChange: function(element, oldText, newText) {
                    console.log('Front text changed:', oldText, '->', newText);
                    showNotification('Front text updated successfully', 'success');
                }
            });
        }

        @if($template->has_back_design)
        if (backSvgElement) {
            // For back design, we might need different design data or use the same
            @if($designData)
            backSvgElement.setAttribute('data-svg-data', '{{ json_encode($designData) }}');
            @endif
            backSvgElement.setAttribute('data-svg-editor', 'true');

            // Initialize back editor
            backEditor = new SvgTemplateEditor(backSvgElement, {
                onImageChange: function(element, imageUrl, file) {
                    console.log('Back image changed:', element, imageUrl, file);
                    showNotification('Back image replaced successfully', 'success');
                },
                onTextChange: function(element, oldText, newText) {
                    console.log('Back text changed:', oldText, '->', newText);
                    showNotification('Back text updated successfully', 'success');
                }
            });
        }
        @endif
    }

    // Handle design side switching
    @if($template->has_back_design)
    document.querySelectorAll('input[name="design-side"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const selectedSide = this.id.replace('-side', ''); // 'front' or 'back'
            currentSide = selectedSide;

            // Hide all designs
            document.querySelectorAll('.svg-wrapper').forEach(wrapper => {
                wrapper.style.display = 'none';
            });

            // Show selected design
            const selectedDesign = document.getElementById(selectedSide + '-design');
            if (selectedDesign) {
                selectedDesign.style.display = 'block';
            }

            // Update toolbar text
            const saveBtn = document.getElementById('save-svg-btn');
            const sideText = selectedSide.charAt(0).toUpperCase() + selectedSide.slice(1);
            saveBtn.innerHTML = `<i class="fas fa-save me-1"></i>Save ${sideText} Changes`;
        });
    });
    @endif

    // Initialize editors
    initializeEditors();

    // Save button handler
    document.getElementById('save-svg-btn').addEventListener('click', async function() {
        const currentEditor = currentSide === 'front' ? frontEditor : backEditor;

        if (!currentEditor) {
            showNotification('No SVG editor available for ' + currentSide + ' side', 'error');
            return;
        }

        const saveBtn = this;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        saveBtn.disabled = true;

        try {
            const sideText = currentSide.charAt(0).toUpperCase() + currentSide.slice(1);
            const result = await currentEditor.saveSvg({{ $template->id }}, currentSide);

            if (result.success) {
                showNotification(sideText + ' design saved successfully', 'success');
            } else {
                showNotification('Failed to save ' + currentSide + ' design: ' + result.error, 'error');
            }
        } catch (error) {
            showNotification('Error saving ' + currentSide + ' design: ' + error.message, 'error');
        } finally {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });

    // Reset button handler
    document.getElementById('reset-svg-btn').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset all changes on the ' + currentSide + ' side? This will reload the original SVG.')) {
            location.reload();
        }
    });

    // Download button handler
    document.getElementById('download-svg-btn').addEventListener('click', function() {
        const currentEditor = currentSide === 'front' ? frontEditor : backEditor;

        if (!currentEditor) {
            showNotification('No SVG available to download for ' + currentSide + ' side', 'error');
            return;
        }

        const sideText = currentSide.charAt(0).toUpperCase() + currentSide.slice(1);
        const svgData = currentEditor.getSvgDataUrl();
        const link = document.createElement('a');
        link.href = svgData;
        link.download = '{{ $template->name ?? "template" }} - ' + sideText + '.svg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification(sideText + ' SVG downloaded successfully', 'success');
    });

    // Notification helper
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
});
</script>
@endsection