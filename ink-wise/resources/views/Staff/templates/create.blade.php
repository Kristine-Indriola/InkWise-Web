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
        
        /* Frame selection styling */
        .frame-item {
            transition: all 0.2s ease;
        }
        .frame-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .frame-item.border-primary {
            background: linear-gradient(45deg, #e3f2fd, #ffffff);
        }
        .frame-item.border-secondary {
            background: linear-gradient(45deg, #f5f5f5, #ffffff);
        }
        .frame-checkbox:checked + label {
            font-weight: bold;
        }
        .preview-label {
            font-weight: 600;
            margin-bottom: 8px;
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

            if (frontBtn) {
                console.log('Front button onclick:', frontBtn.onclick);
                console.log('Front button disabled:', frontBtn.disabled);
            }

            if (backBtn) {
                console.log('Back button onclick:', backBtn.onclick);
                console.log('Back button disabled:', backBtn.disabled);
            }
        });

        // Figma integration functions
        async function analyzeFigmaUrl(side = 'front') {
            const figmaUrl = document.getElementById(`figma_url_${side}`).value.trim();
            const analyzeBtn = document.getElementById(`analyze-figma-${side}-btn`);
            const resultsDiv = document.getElementById(`figma-results-${side}`);

            if (!figmaUrl) {
                alert(`Please enter a Figma URL for ${side} design`);
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
                    // Store analyzed data with side information
                    if (!window.figmaAnalyzedData) {
                        window.figmaAnalyzedData = {};
                    }
                    window.figmaAnalyzedData[side] = data;
                    displayFigmaResults(data, side);
                } else {
                    resultsDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to analyze Figma file') + '</div>';
                }
            } catch (error) {
                console.error('Figma analysis error:', error);
                resultsDiv.innerHTML = '<div class="alert alert-danger">Error analyzing Figma file: ' + error.message + '</div>';
            } finally {
                analyzeBtn.disabled = false;
                const btnText = side === 'front' ? 'Analyze Front Design' : 'Analyze Back Design';
                analyzeBtn.innerHTML = `<i class="fas fa-search me-1"></i>${btnText}`;
            }
        }

        function displayFigmaResults(data, side = 'front') {
            const resultsDiv = document.getElementById(`figma-results-${side}`);

            if (!data.frames || data.frames.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <h6>No frames found</h6>
                        <p>This Figma file doesn't contain any frames, components, or design elements. Please ensure your Figma file has at least one design element.</p>
                    </div>
                `;
                return;
            }

            const sideTitle = side.charAt(0).toUpperCase() + side.slice(1);
            let html = `<div class="alert alert-success">Found ${data.frames.length} eligible frame(s) for ${sideTitle} design:</div>`;
            html += '<div class="frames-list mt-3">';

            // Show all frames for both front and back - no filtering needed
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
            html += '<div class="mt-3">';
            const btnClass = side === 'front' ? 'btn-primary' : 'btn-secondary';
            html += `<button type="button" class="${btnClass} btn" onclick="importSingleFrame('${side}')"><i class="fas fa-download me-1"></i>Import ${sideTitle} Frame</button>`;
            html += `<small class="text-muted ms-3">Select any frame for the ${side} design</small>`;
            html += '</div>';

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

            // Provide feedback about what's being imported
            const frameNames = frames.map(f => f.name).join(', ');
            console.log('Importing frames:', frameNames);

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
                    
                    // Populate the SVG preview containers
                    populateSvgPreviews(preview);
                    
                    // Switch back to manual upload mode to show the previews
                    toggleImportMethod('manual');
                    
                    // Update form data
                    updateFormWithFigmaData(preview, figmaAnalyzedData);
                    
                    // Hide the Figma import section
                    document.getElementById('figma-import-section').style.display = 'none';
                    
                    // Create detailed success message
                    let successMessage = `✅ Successfully imported: ${preview.name}\n`;
                    if (preview.front_svg && preview.back_svg) {
                        successMessage += '📄 Front and back designs loaded\n';
                    } else if (preview.front_svg) {
                        successMessage += '📄 Front design loaded\n';
                    } else if (preview.back_svg) {
                        successMessage += '📄 Back design loaded\n';
                    }
                    successMessage += '\nReview the previews below and submit the form to create the template.';
                    
                    alert(successMessage);
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

        function populateSingleSidePreview(side, preview) {
            console.log(`Populating ${side} preview:`, {
                side: side,
                preview: preview,
                has_front_svg: !!preview.front_svg,
                has_back_svg: !!preview.back_svg
            });
            
            const previewElement = document.getElementById(`${side}-preview`);
            const svgContent = side === 'front' ? preview.front_svg : preview.back_svg;

            console.log(`${side} SVG content:`, svgContent ? 'Present' : 'Missing');
            
            if (svgContent) {
                previewElement.innerHTML = svgContent;
                previewElement.style.display = 'flex';
                previewElement.style.alignItems = 'center';
                previewElement.style.justifyContent = 'center';
                previewElement.style.border = side === 'front' ? '2px solid #28a745' : '2px solid #6c757d';
                previewElement.style.backgroundColor = side === 'front' ? '#f8fff9' : '#f8f9fa';

                // Update label
                const label = previewElement.previousElementSibling;
                if (label && label.classList.contains('preview-label')) {
                    const sideTitle = side.charAt(0).toUpperCase() + side.slice(1);
                    label.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i>${sideTitle} SVG Preview - Loaded`;
                }
            } else {
                // If no SVG content for this side, show a message
                previewElement.innerHTML = `<span class="muted">No ${side} design imported</span>`;
                previewElement.style.border = '1px solid #d1d5db';
                previewElement.style.backgroundColor = '#fff';

                const label = previewElement.previousElementSibling;
                if (label && label.classList.contains('preview-label')) {
                    const sideTitle = side.charAt(0).toUpperCase() + side.slice(1);
                    label.innerHTML = `<i class="fas fa-info-circle text-muted me-1"></i>${sideTitle} SVG Preview - Optional`;
                }
            }
        }

        function populateSvgPreviews(preview) {
            const frontPreview = document.getElementById('front-preview');
            const backPreview = document.getElementById('back-preview');
            
            // Update front preview
            if (preview.front_svg) {
                frontPreview.innerHTML = preview.front_svg;
                frontPreview.style.display = 'flex';
                frontPreview.style.alignItems = 'center';
                frontPreview.style.justifyContent = 'center';
                frontPreview.style.border = '2px solid #28a745';
                frontPreview.style.backgroundColor = '#f8fff9';
                
                // Add success indicator to front preview label
                const frontLabel = frontPreview.previousElementSibling;
                if (frontLabel && frontLabel.classList.contains('preview-label')) {
                    frontLabel.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Front SVG Preview - Loaded';
                }
            } else {
                frontPreview.innerHTML = '<span class="muted">No front design imported</span>';
            }
            
            // Update back preview
            if (preview.back_svg) {
                backPreview.innerHTML = preview.back_svg;
                backPreview.style.display = 'flex';
                backPreview.style.alignItems = 'center';
                backPreview.style.justifyContent = 'center';
                backPreview.style.border = '2px solid #6c757d';
                backPreview.style.backgroundColor = '#f8f9fa';
                
                // Add success indicator to back preview label
                const backLabel = backPreview.previousElementSibling;
                if (backLabel && backLabel.classList.contains('preview-label')) {
                    backLabel.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Back SVG Preview - Loaded';
                }
            } else {
                backPreview.innerHTML = '<span class="muted">No back design imported</span>';
                backPreview.style.border = '1px solid #d1d5db';
                backPreview.style.backgroundColor = '#fff';
                
                // Update back preview label
                const backLabel = backPreview.previousElementSibling;
                if (backLabel && backLabel.classList.contains('preview-label')) {
                    backLabel.innerHTML = '<i class="fas fa-info-circle text-muted me-1"></i>Back SVG Preview - Optional';
                }
            }
            
            console.log('Previews populated:', {
                front: !!preview.front_svg,
                back: !!preview.back_svg
            });
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
            
            // Store SVG content in hidden fields
            let frontSvgField = document.getElementById('front_svg_content');
            if (!frontSvgField) {
                frontSvgField = document.createElement('input');
                frontSvgField.type = 'hidden';
                frontSvgField.id = 'front_svg_content';
                frontSvgField.name = 'front_svg_content';
                document.querySelector('.create-form').appendChild(frontSvgField);
            }
            frontSvgField.value = preview.front_svg || '';
            
            let backSvgField = document.getElementById('back_svg_content');
            if (!backSvgField) {
                backSvgField = document.createElement('input');
                backSvgField.type = 'hidden';
                backSvgField.id = 'back_svg_content';
                backSvgField.name = 'back_svg_content';
                document.querySelector('.create-form').appendChild(backSvgField);
            }
            backSvgField.value = preview.back_svg || '';
        }

        function updateFormWithSingleSideData(side, preview, figmaData) {
            // Update the name field if it's empty (use the frame name)
            const nameField = document.getElementById('name');
            if (nameField && !nameField.value.trim()) {
                nameField.value = preview.name;
            }
            
            // Store Figma data in hidden fields for this side
            let figmaUrlField = document.getElementById(`figma_url_${side}_hidden`);
            if (!figmaUrlField) {
                figmaUrlField = document.createElement('input');
                figmaUrlField.type = 'hidden';
                figmaUrlField.id = `figma_url_${side}_hidden`;
                figmaUrlField.name = `figma_url_${side}`;
                document.querySelector('.create-form').appendChild(figmaUrlField);
            }
            figmaUrlField.value = document.getElementById(`figma_url_${side}`).value;
            
            let figmaFileKeyField = document.getElementById(`figma_file_key_${side}_hidden`);
            if (!figmaFileKeyField) {
                figmaFileKeyField = document.createElement('input');
                figmaFileKeyField.type = 'hidden';
                figmaFileKeyField.id = `figma_file_key_${side}_hidden`;
                figmaFileKeyField.name = `figma_file_key_${side}`;
                document.querySelector('.create-form').appendChild(figmaFileKeyField);
            }
            figmaFileKeyField.value = figmaData.file_key;
            
            // Store SVG content in the appropriate hidden field
            const svgFieldName = `${side}_svg_content`;
            let svgField = document.getElementById(svgFieldName);
            if (!svgField) {
                svgField = document.createElement('input');
                svgField.type = 'hidden';
                svgField.id = svgFieldName;
                svgField.name = svgFieldName;
                document.querySelector('.create-form').appendChild(svgField);
            }
            
            // Set the SVG content based on the side
            const svgContent = side === 'front' ? preview.front_svg : preview.back_svg;
            svgField.value = svgContent || '';
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
            <h2 id="create-template-heading">Create New {{ ucfirst($templateType) }} Template</h2>
            <p class="create-subtitle">Fill in the details to craft a new {{ $templateType }} template</p>
        </div>

    <form action="{{ route('staff.templates.store') }}" method="POST" class="create-form" enctype="multipart/form-data">
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
            </div>

            <!-- Preview Section (always visible) -->
            <div class="create-row">
                <div class="create-group flex-1">
                    <div class="preview-box" aria-live="polite">
                        <div class="preview-label">Front SVG Preview</div>
                        <div id="front-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                            <span class="muted">Upload SVG or import from Figma to see preview</span>
                        </div>
                    </div>
                </div>
                <div class="create-group flex-1">
                    <div class="preview-box" aria-live="polite">
                        <div class="preview-label">Back SVG Preview</div>
                        <div id="back-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto">
                            <span class="muted">Upload SVG or import from Figma to see preview</span>
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
