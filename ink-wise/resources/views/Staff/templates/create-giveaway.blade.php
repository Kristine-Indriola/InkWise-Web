@extends('layouts.staffapp')

@php
    $templateType = 'giveaway';
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Giveaway';

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
        // Helper to read CSRF token from meta or hidden input
        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute) {
                const v = meta.getAttribute('content');
                if (v) return v;
            }
            const hidden = document.querySelector('input[name="_token"]');
            return hidden ? hidden.value : '';
        }

        // Figma integration variables
        let figmaAnalyzedData = null;
        let selectedFrames = [];

        // SVG preview handling for front design
        function setupPreview(fileInputId, previewId) {
            const fileInput = document.getElementById(fileInputId);
            const previewContainer = document.getElementById(previewId);
            if (!fileInput || !previewContainer) return;

            function clearPreview() {
                previewContainer.innerHTML = '<span class="muted">No SVG selected</span>';
            }

            function showError(msg) {
                previewContainer.innerHTML = '<div class="preview-error" style="color:#b91c1c;">' + msg + '</div>';
            }

            fileInput.addEventListener('change', function(ev) {
                const file = ev.target.files && ev.target.files[0];
                if (!file) {
                    clearPreview();
                    return;
                }

                // Only show preview for SVG files
                if (!/svg/i.test(file.type) && !file.name.toLowerCase().endsWith('.svg')) {
                    clearPreview();
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

        // Debug form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            const submitBtn = document.querySelector('.btn-submit');

            console.log('Giveaway form found:', form);
            console.log('Submit button found:', submitBtn);

            // Setup preview for manual upload file input
            setupPreview('front_image', 'svg-preview-side');

            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Giveaway form submit event triggered');
                    const formData = new FormData(form);

                    // Check if we have either uploaded file or Figma SVG content
                    const hasFrontFile = formData.get('front_image') && formData.get('front_image').size > 0;
                    const hasFrontSvg = formData.get('front_svg_content');

                    const hasAnyDesign = hasFrontFile || hasFrontSvg;

                    if (!hasAnyDesign) {
                        e.preventDefault();
                        alert('Please upload an SVG file or import a design from Figma first.');
                        return false;
                    }

                    console.log('Giveaway form validation passed - has design content');
                    console.log('Front file:', hasFrontFile ? 'Present' : 'Missing');
                    console.log('Front SVG:', hasFrontSvg ? 'Present' : 'Missing');
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    console.log('Giveaway submit button clicked');
                    console.log('Form action:', form ? form.action : 'No form');
                    console.log('Button type:', this.type);
                    console.log('Button disabled:', this.disabled);

                    // Check for form validation
                    if (form && !form.checkValidity()) {
                        console.log('Giveaway form validation failed');
                        form.reportValidity();
                        e.preventDefault();
                        return false;
                    }

                    console.log('Giveaway form should submit');
                });
            }

            // Test Figma buttons
            const figmaBtn = document.getElementById('analyze-figma-btn');
            console.log('Figma analyze button found:', figmaBtn);

            if (figmaBtn) {
                console.log('Figma button onclick:', figmaBtn.onclick);
                console.log('Figma button disabled:', figmaBtn.disabled);
            }
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
                    // Store analyzed data
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
                        <p>This Figma file doesn't contain any frames. Please ensure your Figma file has at least one frame.</p>
                    </div>
                `;
                return;
            }

            let html = `<div class="alert alert-success">Found ${data.frames.length} eligible frame(s) for giveaway design:</div>`;
            html += '<div class="frames-list mt-3">';

            // Show all frames for giveaway - no filtering needed
            data.frames.forEach(frame => {
                const frameId = `frame_${frame.id}`;
                html += `
                    <div class="frame-item border rounded p-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input frame-checkbox" type="radio"
                                   name="frame_giveaway" id="${frameId}" value="${frame.id}"
                                   data-frame='${JSON.stringify(frame).replace(/'/g, "&apos;")}'>
                            <label class="form-check-label" for="${frameId}">
                                <strong>${frame.name}</strong>
                                <br><small class="text-muted">Size: ${frame.bounds.width}x${frame.bounds.height}</small>
                            </label>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            html += '<div class="mt-3">';
            html += `<button type="button" class="btn btn-primary" onclick="importSelectedFrame()"><i class="fas fa-download me-1"></i>Import Giveaway Frame</button>`;
            html += `<small class="text-muted ms-3">Select any frame for the giveaway design</small>`;
            html += '</div>';

            resultsDiv.innerHTML = html;
        }

        async function importSelectedFrame() {
            const selectedRadio = document.querySelector('input[name="frame_giveaway"]:checked');
            if (!selectedRadio) {
                alert('Please select a frame for the giveaway design');
                return;
            }

            const frame = JSON.parse(selectedRadio.getAttribute('data-frame').replace(/&apos;/g, "'"));
            console.log('Importing giveaway frame:', frame);
            const importBtn = document.querySelector('button[onclick="importSelectedFrame()"]');

            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importing...';

            try {
                const response = await fetch('{{ route("staff.figma.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file_key: figmaAnalyzedData.file_key,
                        frames: [frame]
                    })
                });

                const data = await response.json();

                if (data.success && data.previews && data.previews.length > 0) {
                    const preview = data.previews[0];

                    // Populate the preview
                    populateSvgPreview(preview);

                    // Switch back to manual upload mode to show the preview
                    toggleImportMethod('manual');

                    // Update form data
                    updateFormWithFigmaData(preview, figmaAnalyzedData);

                    // Hide the Figma import section
                    document.getElementById('figma-import-section').style.display = 'none';

                    // Show success message
                    alert(`✅ Successfully imported giveaway design: ${frame.name}`);
                }
            } catch (error) {
                console.error('Import error:', error);
                alert('Failed to import frame: ' + error.message);
            } finally {
                const btnText = 'Import Giveaway Frame';
                importBtn.disabled = false;
                importBtn.innerHTML = `<i class="fas fa-download me-1"></i>${btnText}`;
            }
        }

        function populateSvgPreview(preview) {
            const previewContainer = document.getElementById('svg-preview-side');

            // Update preview with front SVG (giveaways only have front)
            if (preview.front_svg && previewContainer) {
                previewContainer.innerHTML = preview.front_svg;
                previewContainer.style.display = 'flex';
                previewContainer.style.alignItems = 'center';
                previewContainer.style.justifyContent = 'center';
                previewContainer.style.border = '2px solid #28a745';
                previewContainer.style.backgroundColor = '#f8fff9';

                // Update label
                const label = previewContainer.previousElementSibling;
                if (label && label.classList.contains('preview-label')) {
                    label.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i>SVG Preview - Loaded`;
                }
            } else {
                // If no SVG content, show a message
                previewContainer.innerHTML = `<span class="muted">No design imported</span>`;
                previewContainer.style.border = '1px solid #d1d5db';
                previewContainer.style.backgroundColor = '#fff';

                const label = previewContainer.previousElementSibling;
                if (label && label.classList.contains('preview-label')) {
                    label.innerHTML = `<i class="fas fa-info-circle text-muted me-1"></i>SVG Preview - Optional`;
                }
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
                document.querySelector('.create-form').appendChild(figmaUrlField);
            }
            figmaUrlField.value = document.getElementById('figma_url').value;

            let figmaFileKeyField = document.getElementById('figma_file_key_hidden');
            if (!figmaFileKeyField) {
                figmaFileKeyField = document.createElement('input');
                figmaFileKeyField.type = 'hidden';
                figmaFileKeyField.id = 'figma_file_key_hidden';
                figmaFileKeyField.name = 'figma_file_key';
                document.querySelector('.create-form').appendChild(figmaFileKeyField);
            }
            figmaFileKeyField.value = figmaData.file_key;

            // Store SVG content in hidden field
            let frontSvgField = document.getElementById('front_svg_content');
            if (!frontSvgField) {
                frontSvgField = document.createElement('input');
                frontSvgField.type = 'hidden';
                frontSvgField.id = 'front_svg_content';
                frontSvgField.name = 'front_svg_content';
                document.querySelector('.create-form').appendChild(frontSvgField);
            }
            frontSvgField.value = preview.front_svg || '';
        }

        // Toggle between manual upload and Figma import
        function toggleImportMethod(method) {
            const manualUpload = document.getElementById('manual-upload-section');
            const figmaImport = document.getElementById('figma-import-section');
            const importMethodField = document.querySelector('input[name="import_method"]');

            if (method === 'figma') {
                manualUpload.style.display = 'none';
                figmaImport.style.display = 'block';
                if (importMethodField) importMethodField.value = 'figma';
            } else {
                manualUpload.style.display = 'block';
                figmaImport.style.display = 'none';
                if (importMethodField) importMethodField.value = 'manual';
            }
        }
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New Giveaway Template</h2>
            <p class="create-subtitle">Upload a single SVG to create a giveaway template</p>
        </div>

    <form action="{{ $editPreviewId ? route('staff.templates.store') : route('staff.templates.preview') }}" method="POST" class="create-form" enctype="multipart/form-data">
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
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Choose how to import your giveaway design. You can either upload an SVG file manually or import from Figma. A design is required.
                </div>
                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="import_method_radio" id="method_manual" value="manual" checked onclick="toggleImportMethod('manual')">
                        <label class="form-check-label" for="method_manual">
                            Manual SVG Upload
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="import_method_radio" id="method_figma" value="figma" onclick="toggleImportMethod('figma')">
                        <label class="form-check-label" for="method_figma">
                            Import from Figma
                        </label>
                    </div>
                </div>
                <input type="hidden" name="import_method" value="manual">
            </div>

            <!-- Manual Upload Section -->
            <div id="manual-upload-section" style="display: block;">
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="front_image">Front Design (SVG)</label>
                        <input type="file" id="front_image" name="front_image" accept=".svg,.svg+xml,image/svg+xml" class="form-control">
                        <small class="form-text text-muted">Upload SVG file for the giveaway design (required)</small>
                        <div class="preview-box mt-2" aria-live="polite">
                            <div class="preview-label">SVG Preview</div>
                            <div id="svg-preview-side" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                <span class="muted">Upload SVG or import from Figma to see preview</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Figma Import Section -->
            <div id="figma-import-section" style="display: none;">
                <div class="create-group">
                    <h5 class="mb-3"><i class="fas fa-file-alt text-primary me-2"></i>Figma Design Import</h5>
                    <label for="figma_url">Figma File URL</label>
                    <div class="input-group mb-2">
                        <input type="url" id="figma_url" class="form-control" placeholder="https://www.figma.com/design/... or https://www.figma.com/file/...">
                        <button type="button" id="analyze-figma-btn" class="btn btn-outline-primary" onclick="analyzeFigmaUrl()">
                            <i class="fas fa-search me-1"></i>Analyze Figma File
                        </button>
                    </div>
                    <small class="form-text text-muted">Enter the shareable link to your Figma file containing the giveaway design</small>
                    <div id="figma-results" class="mt-3">
                        <!-- Figma analysis results will appear here -->
                    </div>
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
