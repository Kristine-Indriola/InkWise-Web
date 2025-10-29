@extends('layouts.staffapp')

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
                // Don't prevent default - let the form submit naturally
                // The form will handle file uploads properly via multipart/form-data
                
                // Basic validation
                const nameField = document.getElementById('name');
                const frontImageField = document.getElementById('front_image');
                
                if (!nameField || !nameField.value.trim()) {
                    e.preventDefault();
                    alert('Please provide a template name.');
                    return false;
                }
                
                const importMethod = document.querySelector('input[name="import_method"]:checked')?.value || 'manual';
                
                if (importMethod === 'manual') {
                    if (!frontImageField || !frontImageField.files || frontImageField.files.length === 0) {
                        e.preventDefault();
                        alert('Please select an SVG file.');
                        return false;
                    }
                }
                
                // Set design data for envelope
                const designInput = document.getElementById('design');
                if (designInput) {
                    designInput.value = JSON.stringify({
                        text: "Envelope Design",
                        type: "envelope"
                    });
                }
                
                // Let the form submit normally
                return true;
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
                resultsDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <h6>No frames found</h6>
                        <p>Make sure your Figma file contains frames with names that include:</p>
                        <ul>
                            <li><strong>Template</strong>, <strong>Invitation</strong>, <strong>Giveaway</strong>, or <strong>Envelope</strong></li>
                            <li>Common keywords like: <em>card, design, layout, front, back, cover</em></li>
                        </ul>
                        <p><small>Frame names are case-insensitive. Examples: "Wedding Invitation", "Giveaway Card", "Envelope Template"</small></p>
                    </div>
                `;
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
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading Preview...';

            try {
                // First get the preview/SVG content
                const response = await fetch('{{ route("staff.figma.preview") }}', {
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

                if (data.success && data.previews && data.previews.length > 0) {
                    // Use the first preview (most common case)
                    const preview = data.previews[0];
                    
                    // Populate the SVG preview container (single container for envelope)
                    populateSvgPreview(preview);
                    
                    // Switch back to manual upload mode to show the preview
                    toggleImportMethod('manual');
                    
                    // Update form data
                    updateFormWithFigmaData(preview, figmaAnalyzedData);
                    
                    // Hide the Figma import section
                    document.getElementById('figma-import-section').style.display = 'none';
                    
                    alert('Figma frame loaded successfully! Review the preview and submit the form to create the template.');
                } else {
                    alert('Preview failed: ' + (data.message || 'Unknown error'));
                    console.error('Preview errors:', data.errors);
                }
            } catch (error) {
                console.error('Preview error:', error);
                alert('Preview failed: ' + error.message);
            } finally {
                importBtn.disabled = false;
                importBtn.innerHTML = 'Import Selected Frames';
            }
        }

        function populateSvgPreview(preview) {
            const previewContainer = document.getElementById('svg-preview-side');
            
            // Update preview with front SVG (envelopes typically only have front)
            if (preview.front_svg && previewContainer) {
                previewContainer.innerHTML = preview.front_svg;
                previewContainer.style.display = 'flex';
                previewContainer.style.alignItems = 'center';
                previewContainer.style.justifyContent = 'center';
            }
        }

        function updateFormWithFigmaData(preview, figmaData) {
            // Update the name field if it's empty
            const nameField = document.getElementById('name');
            if (nameField && !nameField.value.trim()) {
                nameField.value = preview.name;
            }
            
            // Store Figma data in hidden fields
            let figmaUrlField = document.getElementById('figma_url_hidden');
            if (!figmaUrlField) {
                figmaUrlField = document.createElement('input');
                figmaUrlField.type = 'hidden';
                figmaUrlField.id = 'figma_url_hidden';
                figmaUrlField.name = 'figma_url';
                document.querySelector('form').appendChild(figmaUrlField);
            }
            figmaUrlField.value = document.getElementById('figma_url').value;
            
            let figmaFileKeyField = document.getElementById('figma_file_key_hidden');
            if (!figmaFileKeyField) {
                figmaFileKeyField = document.createElement('input');
                figmaFileKeyField.type = 'hidden';
                figmaFileKeyField.id = 'figma_file_key_hidden';
                figmaFileKeyField.name = 'figma_file_key';
                document.querySelector('form').appendChild(figmaFileKeyField);
            }
            figmaFileKeyField.value = figmaData.file_key;
            
            // Store SVG content in hidden field
            let frontSvgField = document.getElementById('front_svg_content');
            if (!frontSvgField) {
                frontSvgField = document.createElement('input');
                frontSvgField.type = 'hidden';
                frontSvgField.id = 'front_svg_content';
                frontSvgField.name = 'front_svg_content';
                document.querySelector('form').appendChild(frontSvgField);
            }
            frontSvgField.value = preview.front_svg || '';
        }

        // Toggle between manual upload and Figma import
        function toggleImportMethod(method) {
            const manualUpload = document.getElementById('manual-upload-section');
            const figmaImport = document.getElementById('figma-import-section');
            const figmaUrlField = document.getElementById('figma_url');
            const frontImageField = document.getElementById('front_image');

            if (method === 'figma') {
                manualUpload.style.display = 'none';
                figmaImport.style.display = 'block';
                // Set figma_url as required and remove required from file inputs
                if (figmaUrlField) figmaUrlField.required = true;
                if (frontImageField) frontImageField.required = false;
            } else {
                manualUpload.style.display = 'block';
                figmaImport.style.display = 'none';
                // Set file inputs as required and remove required from figma_url
                if (figmaUrlField) figmaUrlField.required = false;
                if (frontImageField) frontImageField.required = true;
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
