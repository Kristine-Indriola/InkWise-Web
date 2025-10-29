@extends('layouts.staffapp')

@php
    $templateType = request('type', 'invitation');
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Invitation';

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
        /* Style for imported SVG previews */
        .svg-preview.imported {
            border: 2px solid #28a745 !important;
            background: #f8fff9 !important;
        }
        .svg-preview.imported::before {
            content: "✓ Imported from Figma";
            position: absolute;
            top: -20px;
            left: 0;
            font-size: 12px;
            color: #28a745;
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #28a745;
        }
        .preview-box {
            position: relative;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/template/template.js') }}" defer></script>
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

        // SVG preview handling for front and back
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

        setupPreview('custom_front_image', 'front-preview');
        setupPreview('custom_back_image', 'back-preview');

            // Debug form submission
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('.create-form');
                const submitBtn = document.querySelector('.btn-submit');
                
                console.log('Form found:', form);
                console.log('Submit button found:', submitBtn);
                
                if (form) {
                    form.addEventListener('submit', function(e) {
                        console.log('Form submit event triggered');
                        console.log('Form data:', new FormData(form));
                        
                        // Check required fields
                        const requiredFields = form.querySelectorAll('[required]');
                        requiredFields.forEach(field => {
                            console.log(`Required field ${field.name}: ${field.value}`);
                        });
                    });
                }
                
                if (submitBtn) {
                    submitBtn.addEventListener('click', function(e) {
                        console.log('Submit button clicked');
                        console.log('Form action:', form ? form.action : 'No form');
                        console.log('Button type:', this.type);
                        console.log('Button disabled:', this.disabled);
                        
                        // Check for form validation
                        if (form && !form.checkValidity()) {
                            console.log('Form validation failed');
                            form.reportValidity();
                            e.preventDefault();
                            return false;
                        }
                        
                        console.log('Form should submit');
                    });
                }
                
                // Handle form submission with imported SVG data
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const frontInput = document.getElementById('custom_front_image');
                        const backInput = document.getElementById('custom_back_image');
                        
                        // If we have imported SVG paths, add them as hidden inputs
                        if (frontInput && frontInput.dataset.importedPath) {
                            let hiddenFront = document.getElementById('imported_front_path');
                            if (!hiddenFront) {
                                hiddenFront = document.createElement('input');
                                hiddenFront.type = 'hidden';
                                hiddenFront.name = 'imported_front_path';
                                hiddenFront.id = 'imported_front_path';
                                form.appendChild(hiddenFront);
                            }
                            hiddenFront.value = frontInput.dataset.importedPath;
                        }
                        
                        if (backInput && backInput.dataset.importedPath) {
                            let hiddenBack = document.getElementById('imported_back_path');
                            if (!hiddenBack) {
                                hiddenBack = document.createElement('input');
                                hiddenBack.type = 'hidden';
                                hiddenBack.name = 'imported_back_path';
                                hiddenBack.id = 'imported_back_path';
                                form.appendChild(hiddenBack);
                            }
                            hiddenBack.value = backInput.dataset.importedPath;
                        }
                    });
                }
            });        // Figma integration functions
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
                        <h6>No template frames found</h6>
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
                    // Instead of redirecting, load the imported SVGs into the preview areas
                    if (data.imported.length === 1) {
                        const imported = data.imported[0];
                        await loadImportedTemplate(imported);
                        alert('Successfully imported template! You can now preview and customize it below.');
                    } else {
                        alert('Successfully imported ' + data.imported.length + ' template(s)!');
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

        // Load imported template data into the form
        async function loadImportedTemplate(templateData) {
            console.log('Loading imported template:', templateData);
            
            // Switch back to manual upload mode
            document.getElementById('manual-import').checked = true;
            toggleImportMethod('manual');
            
            // Hide Figma import section completely for now
            document.getElementById('figma-import-section').style.display = 'none';
            
            // Also hide the import method selection radio buttons
            const importMethodSection = document.querySelector('.btn-group[role="group"]');
            if (importMethodSection) {
                importMethodSection.style.display = 'none';
            }
            
            // Pre-fill template name if available
            if (templateData.name) {
                document.getElementById('template_name').value = templateData.name;
            }
            
            // Load SVG previews
            if (templateData.svg_path) {
                await loadSVGPreview(templateData.svg_path, 'front-preview');
                
                // Store the SVG path for form submission
                const frontInput = document.getElementById('custom_front_image');
                if (frontInput) {
                    // Create a custom property to store the imported path
                    frontInput.dataset.importedPath = templateData.svg_path;
                    frontInput.required = false; // Remove required since we have imported data
                }
            }
            
            if (templateData.back_svg_path) {
                await loadSVGPreview(templateData.back_svg_path, 'back-preview');
                
                // Store the back SVG path for form submission
                const backInput = document.getElementById('custom_back_image');
                if (backInput) {
                    backInput.dataset.importedPath = templateData.back_svg_path;
                    backInput.required = false;
                }
            }
            
            // Show success message in the form
            const resultsDiv = document.getElementById('figma-results');
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <h6>Template Imported Successfully!</h6>
                    <p>SVG files have been loaded into the preview areas below. You can now customize the template name and submit to create your template.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showFigmaImport()">Import Another Frame</button>
                </div>
            `;
        }
        
        // Function to show Figma import section again
        function showFigmaImport() {
            document.getElementById('figma-import').checked = true;
            toggleImportMethod('figma');
        }
        
        // Function to load SVG preview from server path
        async function loadSVGPreview(svgPath, previewElementId) {
            try {
                // Construct the full URL to the SVG file
                const svgUrl = svgPath.startsWith('http') ? svgPath : '/storage/' + svgPath.replace('storage/', '');
                
                // Fetch the SVG content
                const response = await fetch(svgUrl);
                if (!response.ok) {
                    throw new Error('Failed to load SVG');
                }
                
                const svgContent = await response.text();
                
                // Display in preview area
                const previewElement = document.getElementById(previewElementId);
                if (previewElement) {
                    previewElement.innerHTML = svgContent;
                    previewElement.classList.add('imported');
                    
                    // Update the corresponding file input label
                    const inputId = previewElementId === 'front-preview' ? 'custom_front_image' : 'custom_back_image';
                    const inputElement = document.getElementById(inputId);
                    if (inputElement) {
                        const label = document.querySelector(`label[for="${inputId}"]`);
                        if (label) {
                            label.innerHTML = label.innerHTML.replace(' *', '') + ' <span class="text-success">(Imported from Figma)</span>';
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading SVG preview:', error);
                const previewElement = document.getElementById(previewElementId);
                if (previewElement) {
                    previewElement.innerHTML = '<span class="text-danger">Error loading SVG</span>';
                }
            }
        }

        // Toggle between manual upload and Figma import
        function toggleImportMethod(method) {
            const manualUpload = document.getElementById('manual-upload-section');
            const figmaImport = document.getElementById('figma-import-section');
            const figmaUrl = document.getElementById('figma_url');
            const frontImage = document.getElementById('custom_front_image');
            const backImage = document.getElementById('custom_back_image');

            if (method === 'figma') {
                manualUpload.style.display = 'none';
                figmaImport.style.display = 'block';
                // Make Figma URL required, remove file requirements
                if (figmaUrl) figmaUrl.setAttribute('required', 'required');
                if (frontImage) frontImage.removeAttribute('required');
                if (backImage) backImage.removeAttribute('required');
            } else {
                manualUpload.style.display = 'block';
                figmaImport.style.display = 'none';
                // Make files required, remove Figma URL requirement
                if (figmaUrl) figmaUrl.removeAttribute('required');
                if (frontImage) frontImage.setAttribute('required', 'required');
                if (backImage) backImage.setAttribute('required', 'required');
            }
        }
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New {{ ucfirst($templateType) }} Template</h2>
            <p class="create-subtitle">Fill in the details to craft a new {{ $templateType }} template</p>
        </div>

    <form action="{{ route('staff.templates.preview') }}" method="POST" class="create-form" enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="design" id="design" value="{{ $previewData['design'] ?? '{}' }}">
            @if($editPreviewId)
                <input type="hidden" name="edit_preview_id" value="{{ $editPreviewId }}">
            @endif

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" value="{{ $previewData['name'] ?? '' }}" required>
                </div>
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding" {{ ($previewData['event_type'] ?? '') === 'Wedding' ? 'selected' : '' }}>Wedding</option>
                        <option value="Birthday" {{ ($previewData['event_type'] ?? '') === 'Birthday' ? 'selected' : '' }}>Birthday</option>
                        <option value="Baptism" {{ ($previewData['event_type'] ?? '') === 'Baptism' ? 'selected' : '' }}>Baptism</option>
                        <option value="Corporate" {{ ($previewData['event_type'] ?? '') === 'Corporate' ? 'selected' : '' }}>Corporate</option>
                    </select>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display">{{ $selectedProductType }}</span>
                        <input type="hidden" id="product_type" name="product_type" value="{{ $selectedProductType }}" required>
                    </div>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme/Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral" value="{{ $previewData['theme_style'] ?? '' }}">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the template design, style, and intended use...">{{ $previewData['description'] ?? '' }}</textarea>
            </div>

            <!-- Import Method Selection -->
            <div class="create-group">
                <label>Import Method</label>
                <div class="btn-group w-100" role="group" aria-label="Import method">
                    <input type="radio" class="btn-check" name="import_method" id="manual-import" autocomplete="off" checked onclick="toggleImportMethod('manual')">
                    <label class="btn btn-outline-primary" for="manual-import">Manual Upload</label>
                    <input type="radio" class="btn-check" name="import_method" id="figma-import" autocomplete="off" onclick="toggleImportMethod('figma')">
                    <label class="btn btn-outline-primary" for="figma-import">Import from Figma</label>
                </div>
            </div>

            <!-- Figma Import Section -->
            <div id="figma-import-section" style="display: none;">
                <div class="create-group">
                    <label for="figma_url">Figma File URL</label>
                    <div class="input-group">
                        <input type="url" id="figma_url" class="form-control" placeholder="https://www.figma.com/design/... or https://www.figma.com/file/...">
                        <button type="button" id="analyze-figma-btn" class="btn btn-outline-primary" onclick="analyzeFigmaUrl()">
                            <i class="fas fa-search me-1"></i>Analyze Figma File
                        </button>
                    </div>
                    <small class="form-text text-muted">Enter the shareable link to your Figma design file</small>
                </div>

                <div id="figma-results" class="create-group">
                    <!-- Figma analysis results will appear here -->
                </div>
            </div>

            <!-- Manual Upload Section -->
            <div id="manual-upload-section">
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="custom_front_image">Front Image *</label>
                        <input type="file" id="custom_front_image" name="front_image" accept="image/*" required>
                        <small class="form-text text-muted">Upload a front image or import from Figma</small>
                    </div>
                    <div class="create-group flex-1">
                        <label for="custom_back_image">Back Image *</label>
                        <input type="file" id="custom_back_image" name="back_image" accept="image/*" required>
                        <small class="form-text text-muted">Upload a back image or import from Figma</small>
                    </div>
                </div>

                <div class="create-row">
                    <div class="create-group flex-1">
                        <div class="preview-box" aria-live="polite">
                            <div class="preview-label">Front SVG Preview</div>
                            <div id="front-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                <span class="muted">No SVG selected</span>
                            </div>
                        </div>
                    </div>
                    <div class="create-group flex-1">
                        <div class="preview-box" aria-live="polite">
                            <div class="preview-label">Back SVG Preview</div>
                            <div id="back-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                                <span class="muted">No SVG selected</span>
                            </div>
                        </div>
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
