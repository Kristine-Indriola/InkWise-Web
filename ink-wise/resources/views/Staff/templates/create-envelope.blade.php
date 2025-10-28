@extends('layouts.admin')

@php
    $templateType = 'envelope';
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Envelope';

    // Check if editing a preview
    $editPreviewId = request('edit_preview');
    $previewData = null;
    if ($editPreviewId) {
        $previews = session('preview_templates', []);
        foreach ($previews as $preview) {
            if (isset($preview['id']) && $preview['id'] === $editPreviewId) {
                $previewData = $preview;
                break;
            }
        }
    }
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/template/template.css') }}">
    <style>
        /* Make the create container bigger */
        .create-container{max-width:1400px;margin:0 auto;padding:20px}
        /* Make the preview area scale inside the square */
        .svg-preview svg{width:100%;height:100%;object-fit:contain}
    </style>
@endpush

@push('scripts')
    <script>
        // Figma integration variables
        let figmaAnalyzedData = null;
        let selectedFrames = [];

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute) return meta.getAttribute('content') || '';
            const hidden = document.querySelector('input[name="_token"]');
            return hidden ? hidden.value : '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            if (!form) return;

            // SVG preview handling
            const frontFile = document.getElementById('front_image');
            const previewContainer = document.getElementById('svg-preview-side') || document.getElementById('svg-preview');
            function clearPreview() {
                if (!previewContainer) return;
                previewContainer.innerHTML = '<span class="muted">No SVG selected</span>';
            }

            function showError(msg) {
                if (!previewContainer) return;
                previewContainer.innerHTML = '<div class="preview-error" style="color:#b91c1c;">' + msg + '</div>';
            }

            if (frontFile && previewContainer) {
                frontFile.addEventListener('change', function(ev) {
                    const file = ev.target.files && ev.target.files[0];
                    if (!file) {
                        clearPreview();
                        return;
                    }

                    // Basic client-side checks
                    if (!/svg/i.test(file.type) && !file.name.toLowerCase().endsWith('.svg')) {
                        showError('Please select an SVG file.');
                        return;
                    }

                    // limit ~5MB
                    if (file.size > 5 * 1024 * 1024) {
                        showError('SVG is too large (max 5MB).');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(r) {
                        try {
                            const text = r.target.result;
                            // naive sanitization: remove script tags
                            const cleaned = text.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');
                            // inject SVG markup into preview (wrap in a container)
                            previewContainer.innerHTML = cleaned;
                        } catch (err) {
                            console.error(err);
                            showError('Unable to render SVG preview');
                        }
                    };
                    reader.onerror = function() {
                        showError('Failed to read file');
                    };
                    reader.readAsText(file);
                });
            }

            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);

                // For envelope we require name and front_image
                if (!formData.get('name') || !formData.get('front_image')) {
                    alert('Please provide a name and an SVG file.');
                    return;
                }

                // Set design data for envelope
                const designInput = document.getElementById('design');
                if (designInput) {
                    designInput.value = JSON.stringify({
                        text: "Envelope Design",
                        type: "envelope"
                    });
                }

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(async res => {
                    if (!res.ok) {
                        let message = 'Upload failed';
                        try { message = (await res.json()).message || message; } catch (err) {}
                        throw new Error(message);
                    }
                    return res.json();
                }).then(json => {
                    if (json && json.success) {
                        alert('Envelope template uploaded successfully');
                        window.location = json.redirect || '{{ route('staff.templates.index') }}';
                    }
                }).catch(err => {
                    console.error(err);
                    alert('Upload failed: ' + (err.message || 'Unknown'));
                });
            });
        });

        // Figma integration functions
        async function analyzeFigmaUrl() {
            const figmaUrl = document.getElementById('figma_url').value.trim();
            const analyzeBtn = document.getElementById('analyze-figma-btn');
            const resultsDiv = document.getElementById('figma-results');

            if (!figmaUrl) {
                alert('Please enter a Figma URL');
                return;
            }

            // Show loading
            analyzeBtn.disabled = true;
            analyzeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyzing...';
            resultsDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Analyzing Figma file...</div>';

            try {
                const response = await fetch('{{ route("staff.figma.analyze") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ figma_url: figmaUrl })
                });

                const data = await response.json();

                if (data.success) {
                    figmaAnalyzedData = data;
                    displayFigmaResults(data);
                } else {
                    resultsDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to analyze Figma file') + '</div>';
                }
            } catch (error) {
                console.error('Figma analysis error:', error);
                resultsDiv.innerHTML = '<div class="alert alert-danger">Error analyzing Figma file: ' + error.message + '</div>';
            } finally {
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<i class="fas fa-search me-1"></i>Analyze Figma File';
            }
        }

        function displayFigmaResults(data) {
            const resultsDiv = document.getElementById('figma-results');

            if (!data.frames || data.frames.length === 0) {
                resultsDiv.innerHTML = '<div class="alert alert-warning">No eligible frames found in this Figma file. Looking for frames named "Template", "Invitation", "Giveaway", or "Envelope".</div>';
                return;
            }

            let html = '<div class="alert alert-success">Found ' + data.frames.length + ' eligible frame(s):</div>';
            html += '<div class="frames-list mt-3">';

            data.frames.forEach(frame => {
                const frameId = 'frame_' + frame.id;
                html += `
                    <div class="frame-item border rounded p-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input frame-checkbox" type="checkbox"
                                   id="${frameId}" value="${frame.id}"
                                   data-frame='${JSON.stringify(frame).replace(/'/g, "&apos;")}'>
                            <label class="form-check-label" for="${frameId}">
                                <strong>${frame.name}</strong> (${frame.type})
                                <br><small class="text-muted">Size: ${frame.bounds.width}x${frame.bounds.height}</small>
                            </label>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            html += '<button type="button" class="btn btn-primary mt-3" onclick="importSelectedFrames()">Import Selected Frames</button>';

            resultsDiv.innerHTML = html;
        }

        async function importSelectedFrames() {
            const checkedBoxes = document.querySelectorAll('.frame-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one frame to import');
                return;
            }

            const frames = Array.from(checkedBoxes).map(checkbox => {
                return JSON.parse(checkbox.getAttribute('data-frame').replace(/&apos;/g, "'"));
            });

            const importBtn = document.querySelector('button[onclick="importSelectedFrames()"]');
            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importing...';

            try {
                const response = await fetch('{{ route("staff.figma.import") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file_key: figmaAnalyzedData.file_key,
                        frames: frames,
                        figma_url: document.getElementById('figma_url').value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Successfully imported ' + data.imported.length + ' template(s)!');
                    if (data.imported.length === 1) {
                        // Redirect to edit the imported template
                        window.location.href = '{{ route("staff.templates.edit", ":id") }}'.replace(':id', data.imported[0].id);
                    } else {
                        window.location.href = '{{ route("staff.templates.index") }}';
                    }
                } else {
                    alert('Import failed: ' + (data.message || 'Unknown error'));
                    console.error('Import errors:', data.errors);
                }
            } catch (error) {
                console.error('Import error:', error);
                alert('Import failed: ' + error.message);
            } finally {
                importBtn.disabled = false;
                importBtn.innerHTML = 'Import Selected Frames';
            }
        }

        // Toggle between manual upload and Figma import
        function toggleImportMethod(method) {
            const manualUpload = document.getElementById('manual-upload-section');
            const figmaImport = document.getElementById('figma-import-section');

            if (method === 'figma') {
                manualUpload.style.display = 'none';
                figmaImport.style.display = 'block';
            } else {
                manualUpload.style.display = 'block';
                figmaImport.style.display = 'none';
            }
        }
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New Envelope Template</h2>
            <p class="create-subtitle">Upload a single SVG to create an envelope template</p>
        </div>

    <form action="{{ route('staff.templates.preview') }}" method="POST" class="create-form" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="design" id="design" value="{}">
            @if($editPreviewId)
                <input type="hidden" name="edit_preview_id" value="{{ $editPreviewId }}">
            @endif

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" value="{{ $previewData['name'] ?? '' }}" required>
                </div>
                <div class="create-group flex-1">
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display">{{ $selectedProductType }}</span>
                        <input type="hidden" id="product_type" name="product_type" value="{{ $selectedProductType }}" required>
                    </div>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding" {{ ($previewData['event_type'] ?? '') === 'Wedding' ? 'selected' : '' }}>Wedding</option>
                        <option value="Birthday" {{ ($previewData['event_type'] ?? '') === 'Birthday' ? 'selected' : '' }}>Birthday</option>
                        <option value="Baby Shower" {{ ($previewData['event_type'] ?? '') === 'Baby Shower' ? 'selected' : '' }}>Baby Shower</option>
                        <option value="Corporate" {{ ($previewData['event_type'] ?? '') === 'Corporate' ? 'selected' : '' }}>Corporate</option>
                        <option value="Other" {{ ($previewData['event_type'] ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme / Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral" value="{{ $previewData['theme_style'] ?? '' }}">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the design or special instructions (optional)">{{ $previewData['description'] ?? '' }}</textarea>
            </div>

            <!-- Import Method Selection -->
            <div class="create-group">
                <label>Import Method</label>
                <div class="import-method-selection">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="import_method" id="import_manual" value="manual" checked onclick="toggleImportMethod('manual')">
                        <label class="form-check-label" for="import_manual">
                            <i class="fas fa-upload me-1"></i>Manual Upload
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="import_method" id="import_figma" value="figma" onclick="toggleImportMethod('figma')">
                        <label class="form-check-label" for="import_figma">
                            <i class="fab fa-figma me-1"></i>Import from Figma
                        </label>
                    </div>
                </div>
            </div>

            <!-- Manual Upload Section -->
            <div id="manual-upload-section">
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="front_image">Front SVG *</label>
                        <input type="file" id="front_image" name="front_image" accept="image/svg+xml" required>
                    </div>
                    <div class="create-group flex-1">
                        <!-- SVG Preview -->
                        <div class="preview-box" aria-live="polite">
                            <div class="preview-label">SVG Preview</div>
                            <div id="svg-preview-side" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                <span class="muted">No SVG selected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Figma Import Section -->
            <div id="figma-import-section" style="display: none;">
                <div class="create-group">
                    <label for="figma_url">Figma File URL</label>
                    <div class="input-group">
                        <input type="url" id="figma_url" name="figma_url" placeholder="https://www.figma.com/design/... or https://www.figma.com/file/..." class="form-control">
                        <button type="button" id="analyze-figma-btn" class="btn btn-outline-primary" onclick="analyzeFigmaUrl()">
                            <i class="fas fa-search me-1"></i>Analyze Figma File
                        </button>
                    </div>
                    <small class="form-text text-muted">Paste a Figma design URL to analyze and import frames as templates</small>
                </div>

                <div id="figma-results" class="create-group">
                    <!-- Figma analysis results will be displayed here -->
                </div>
            </div>

            <div class="create-actions">
                <a href="{{ route('staff.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create Template</button>
            </div>
        </form>
    </section>
</main>
@endsection
