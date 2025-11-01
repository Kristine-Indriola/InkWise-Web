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

        /* Import method styling */
        .import-method-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fafafa;
        }
        .import-method-section .alert {
            margin-bottom: 20px;
        }
        .import-method-section .create-row {
            margin-bottom: 20px;
        }
        .import-method-section .create-row:last-child {
            margin-bottom: 0;
        }
        .w-100 {
            width: 100% !important;
        }

        /* Import Method Buttons */
        .import-method-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }

        .import-method-btn {
            display: flex;
            align-items: center;
            padding: 20px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            color: #6b7280;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            min-height: 80px;
        }

        .import-method-btn:hover {
            border-color: #d1d5db;
            background: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .import-method-btn.active {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .import-method-btn.active:hover {
            border-color: #2563eb;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        }

        .import-method-btn .btn-content {
            flex: 1;
        }

        .import-method-btn .btn-content strong {
            display: block;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .import-method-btn .btn-content small {
            font-size: 12px;
            opacity: 0.8;
        }

        .import-method-btn i {
            font-size: 24px;
            margin-right: 12px;
            opacity: 0.8;
        }

        .import-method-btn.active i {
            opacity: 1;
            color: #3b82f6;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            .import-method-buttons {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .import-method-btn {
                padding: 16px 12px;
                min-height: 70px;
            }

            .import-method-btn .btn-content strong {
                font-size: 14px;
            }
        }

        /* Frame Selection Buttons */
        .frame-selection-btn {
            display: block;
            width: 100%;
            padding: 16px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .frame-selection-btn:hover {
            border-color: #d1d5db;
            background: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .frame-selection-btn.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }

        .frame-selection-btn.selected:hover {
            border-color: #2563eb;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        }

        .frame-btn-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .frame-btn-icon {
            font-size: 20px;
            opacity: 0.7;
            flex-shrink: 0;
        }

        .frame-selection-btn.selected .frame-btn-icon {
            opacity: 1;
            color: #3b82f6;
        }

        .frame-btn-details {
            flex: 1;
            min-width: 0;
        }

        .frame-btn-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .frame-btn-meta {
            font-size: 12px;
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .frame-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }

        .frame-badge.primary {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .frame-badge.secondary {
            background: #f3f4f6;
            color: #374151;
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

        // File upload inputs removed - previews populated via Figma import only

        // Debug form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            const submitBtn = document.querySelector('.btn-submit');

            console.log('Form found:', form);
            console.log('Submit button found:', submitBtn);

            // Setup preview for manual upload file inputs
            setupPreview('front_image', 'front-preview');
            setupPreview('back_image', 'back-preview');

            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');
                    const formData = new FormData(form);

                    // Check if we have either uploaded files or Figma SVG content
                    const hasFrontFile = formData.get('front_image') && formData.get('front_image').size > 0;
                    const hasBackFile = formData.get('back_image') && formData.get('back_image').size > 0;
                    const hasFrontSvg = formData.get('front_svg_content');
                    const hasBackSvg = formData.get('back_svg_content');

                    const hasAnyDesign = hasFrontFile || hasBackFile || hasFrontSvg || hasBackSvg;

                    if (!hasAnyDesign) {
                        e.preventDefault();
                        alert('Please upload at least one SVG file or import at least one design from Figma first.');
                        return false;
                    }

                    console.log('Form validation passed - has design content');
                    console.log('Front file:', hasFrontFile ? 'Present' : 'Missing');
                    console.log('Back file:', hasBackFile ? 'Present' : 'Missing');
                    console.log('Front SVG:', hasFrontSvg ? 'Present' : 'Missing');
                    console.log('Back SVG:', hasBackSvg ? 'Present' : 'Missing');
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

            // Setup import method button toggles
            const importMethodButtons = document.querySelectorAll('.import-method-btn');
            importMethodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const method = this.getAttribute('data-method');

                    // Remove active class from all buttons
                    importMethodButtons.forEach(btn => btn.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Toggle the import method sections
                    toggleImportMethod(method);
                });
            });

            // Test Figma buttons
            const frontBtn = document.getElementById('analyze-figma-front-btn');
            const backBtn = document.getElementById('analyze-figma-back-btn');
            console.log('Front analyze button found:', frontBtn);
            console.log('Back analyze button found:', backBtn);

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
                const frameId = `frame_${side}_${frame.id}`;
                const badgeClass = side === 'front' ? 'primary' : 'secondary';
                const iconClass = side === 'front' ? 'fas fa-file-alt text-primary' : 'fas fa-file text-secondary';
                html += `
                    <button type="button" class="frame-selection-btn" id="${frameId}"
                            data-frame='${JSON.stringify(frame).replace(/'/g, "&apos;")}'
                            data-side="${side}"
                            onclick="selectFrame('${frameId}', '${side}')">
                        <div class="frame-btn-content">
                            <i class="frame-btn-icon ${iconClass}"></i>
                            <div class="frame-btn-details">
                                <div class="frame-btn-title">${frame.name}</div>
                                <div class="frame-btn-meta">
                                    <span>Size: ${frame.bounds.width}x${frame.bounds.height}</span>
                                    <span class="frame-badge ${badgeClass}">${sideTitle}</span>
                                </div>
                            </div>
                        </div>
                    </button>
                `;
            });

            html += '</div>';
            html += '<button type="button" class="btn btn-primary mt-3" onclick="importSelectedFrames()">Import Selected Frames</button>';

            resultsDiv.innerHTML = html;
        }

        // Frame selection handling
        function selectFrame(frameId, side) {
            // Remove selected class from all buttons in this side
            const allButtons = document.querySelectorAll(`#figma-results-${side} .frame-selection-btn`);
            allButtons.forEach(btn => btn.classList.remove('selected'));

            // Add selected class to clicked button
            const selectedButton = document.getElementById(frameId);
            selectedButton.classList.add('selected');

            // Store the selection (you could store this in a variable if needed)
            console.log('Selected frame:', frameId, 'for side:', side);
        }

        async function importSingleFrame(side) {
            const selectedButton = document.querySelector(`#figma-results-${side} .frame-selection-btn.selected`);
            if (!selectedButton) {
                alert(`Please select a frame for the ${side} design`);
                return;
            }

            const frame = JSON.parse(selectedButton.getAttribute('data-frame').replace(/&apos;/g, "'"));
            console.log('Importing frame for', side, frame);
            const importBtn = document.querySelector(`button[onclick="importSingleFrame('${side}')"]`);
            
            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importing...';

            try {
                // Get the Figma data for this side
                const figmaData = window.figmaAnalyzedData[side];
                
                const response = await fetch('{{ route("staff.figma.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file_key: figmaData.file_key,
                        frames: [frame]
                    })
                });

                const data = await response.json();

                if (data.success && data.previews && data.previews.length > 0) {
                    const preview = data.previews[0];
                    
                    // For single frame import, the API returns SVG in front_svg, but we need to assign it to the correct side
                    const correctedPreview = {
                        ...preview,
                        front_svg: side === 'front' ? preview.front_svg : null,
                        back_svg: side === 'back' ? preview.front_svg : null,
                        name: preview.name
                    };
                    
                    console.log(`Importing ${side} design:`, {
                        original_preview: preview,
                        corrected_preview: correctedPreview,
                        side: side
                    });
                    
                    // Populate the specific side preview
                    populateSingleSidePreview(side, correctedPreview);
                    
                    // Update form data for this side
                    updateFormWithSingleSideData(side, correctedPreview, figmaData);
                    
                    // Show success message
                    alert(`✅ Successfully imported ${side} design: ${frame.name}`);
                }
            } catch (error) {
                console.error('Import error:', error);
                alert('Failed to import frame: ' + error.message);
            } finally {
                const sideTitle = side.charAt(0).toUpperCase() + side.slice(1);
                importBtn.disabled = false;
                importBtn.innerHTML = `<i class="fas fa-download me-1"></i>Import ${sideTitle} Frame`;
            }
        }

        async function importSelectedFrames() {
            const checkedBoxes = document.querySelectorAll('.frame-selection-btn.selected');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one frame to import');
                return;
            }

            if (checkedBoxes.length > 2) {
                alert('Please select maximum 2 frames (front and back)');
                return;
            }

            const frames = Array.from(checkedBoxes).map(button => {
                const frame = JSON.parse(button.getAttribute('data-frame').replace(/&apos;/g, "'"));
                frame.side = button.getAttribute('data-side') || 'auto';
                return frame;
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
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Choose how to import your template designs. You can either upload SVG files manually or import from Figma. At least one design (front or back) is required.
                </div>

                <!-- Import Method Toggle Buttons -->
                <div class="import-method-buttons mb-4">
                    <button type="button" id="btn-method-manual" class="import-method-btn active" data-method="manual">
                        <i class="fas fa-upload me-2"></i>
                        <div class="btn-content">
                            <strong>Manual SVG Upload</strong>
                            <small>Upload files directly</small>
                        </div>
                    </button>
                    <button type="button" id="btn-method-figma" class="import-method-btn" data-method="figma">
                        <i class="fab fa-figma me-2"></i>
                        <div class="btn-content">
                            <strong>Import from Figma</strong>
                            <small>Use Figma designs</small>
                        </div>
                    </button>
                </div>

                <input type="hidden" name="import_method" value="manual">
            </div>

            <!-- Manual Upload Section -->
            <div id="manual-upload-section" class="import-method-section" style="display: block;">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-upload me-2"></i>
                    <strong>Manual Upload:</strong> Upload SVG files directly for your template designs. At least one design file is required.
                </div>

                <!-- File Upload Inputs Row -->
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="front_image">
                            <i class="fas fa-file-alt text-primary me-1"></i>Front Design (SVG)
                        </label>
                        <input type="file" id="front_image" name="front_image" accept=".svg,.svg+xml,image/svg+xml" class="form-control">
                        <small class="form-text text-muted">Upload SVG file for the front design</small>
                    </div>
                    <div class="create-group flex-1">
                        <label for="back_image">
                            <i class="fas fa-file text-secondary me-1"></i>Back Design (SVG) <small class="text-muted">(optional)</small>
                        </label>
                        <input type="file" id="back_image" name="back_image" accept=".svg,.svg+xml,image/svg+xml" class="form-control">
                        <small class="form-text text-muted">Upload SVG file for the back design</small>
                    </div>
                </div>
            </div>

            <!-- Figma Import Section -->
            <div id="figma-import-section" class="import-method-section" style="display: none;">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Figma Import:</strong> Enter your Figma file URLs below and analyze them to import designs. You can import separate designs for front and back, or use the same file for both.
                </div>

                <!-- Figma URL Inputs Row -->
                <div class="create-row mb-4">
                    <div class="create-group flex-1">
                        <label for="figma_url_front">
                            <i class="fas fa-file-alt text-primary me-1"></i>Front Design Figma URL
                        </label>
                        <input type="url" id="figma_url_front" class="form-control" placeholder="https://www.figma.com/design/... or https://www.figma.com/file/...">
                        <small class="form-text text-muted">Shareable link to Figma file containing front design</small>
                    </div>
                    <div class="create-group flex-1">
                        <label for="figma_url_back">
                            <i class="fas fa-file text-secondary me-1"></i>Back Design Figma URL <small class="text-muted">(optional)</small>
                        </label>
                        <input type="url" id="figma_url_back" class="form-control" placeholder="https://www.figma.com/design/... or https://www.figma.com/file/...">
                        <small class="form-text text-muted">Shareable link to Figma file containing back design</small>
                    </div>
                </div>

                <!-- Analyze Buttons Row -->
                <div class="create-row mb-4">
                    <div class="create-group flex-1">
                        <button type="button" id="analyze-figma-front-btn" class="btn btn-primary w-100" onclick="analyzeFigmaUrl('front')">
                            <i class="fas fa-search me-2"></i>Analyze Front Design
                        </button>
                    </div>
                    <div class="create-group flex-1">
                        <button type="button" id="analyze-figma-back-btn" class="btn btn-secondary w-100" onclick="analyzeFigmaUrl('back')">
                            <i class="fas fa-search me-2"></i>Analyze Back Design
                        </button>
                    </div>
                </div>

                <!-- Analysis Results -->
                <div class="create-row">
                    <div class="create-group flex-1">
                        <div id="figma-results-front">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <p>Click "Analyze Front Design" to load available frames</p>
                            </div>
                        </div>
                    </div>
                    <div class="create-group flex-1">
                        <div id="figma-results-back">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-file fa-2x mb-2"></i>
                                <p>Click "Analyze Back Design" to load available frames</p>
                            </div>
                        </div>
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
