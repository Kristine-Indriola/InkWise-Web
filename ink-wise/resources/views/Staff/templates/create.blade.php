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

        /* Section styling */
        .create-section {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            background: #fafafa;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .section-title:before {
            content: "📋";
            margin-right: 8px;
        }
        .section-description {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Make the preview area scale inside the square */
        .svg-preview svg{width:100%;height:100%;object-fit:contain}
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
        .preview-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            display: block;
        }

        /* Video preview styling */
        .video-preview-container {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #d1d5db;
        }
        .video-preview-container video {
            border-radius: 6px;
        }

        /* Form styling improvements */
        .form-control {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 8px 12px;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .create-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            margin-top: 24px;
        }
        .btn-submit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-submit:hover {
            background: #2563eb;
        }
        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .w-100 {
            width: 100% !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function setupPreview(fileInputId, previewId, side) {
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

        function setupVideoPreview() {
            const videoInput = document.getElementById('template_video');
            const videoPreviewSection = document.getElementById('video-preview-section');
            const videoElement = document.getElementById('video-preview');

            if (!videoInput || !videoPreviewSection || !videoElement) return;

            videoInput.addEventListener('change', function(ev) {
                const file = ev.target.files && ev.target.files[0];
                if (!file) {
                    videoPreviewSection.style.display = 'none';
                    videoElement.src = '';
                    return;
                }

                // Check if it's a video file
                if (!file.type.startsWith('video/')) {
                    alert('Please select a valid video file.');
                    videoInput.value = '';
                    videoPreviewSection.style.display = 'none';
                    return;
                }

                // Check file size (limit to 50MB for videos)
                if (file.size > 50 * 1024 * 1024) {
                    alert('Video file is too large (max 50MB).');
                    videoInput.value = '';
                    videoPreviewSection.style.display = 'none';
                    return;
                }

                // Create object URL for preview
                const videoURL = URL.createObjectURL(file);
                videoElement.src = videoURL;
                videoPreviewSection.style.display = 'block';

                // Clean up object URL when video loads
                videoElement.addEventListener('loadeddata', function() {
                    URL.revokeObjectURL(videoURL);
                });
            });
        }

        // Load existing preview data if editing
        function loadPreviewData() {
            @if($editPreviewId && $previewData)
                // Populate form fields
                const formData = @json($previewData);
                if (formData.name) document.getElementById('name').value = formData.name;
                if (formData.event_type) document.getElementById('event_type').value = formData.event_type;
                if (formData.theme_style) document.getElementById('theme_style').value = formData.theme_style;
                if (formData.description) document.getElementById('description').value = formData.description;

                // Show video preview if exists
                if (formData.template_video_path) {
                    const videoSection = document.getElementById('video-preview-section');
                    const videoElement = document.getElementById('video-preview');
                    if (videoSection && videoElement) {
                        videoElement.src = '{{ asset("storage") }}/' + formData.template_video_path;
                        videoSection.style.display = 'block';
                    }
                }

                // Show SVG previews if they exist
                if (formData.front_image_path) {
                    loadImagePreview('front-preview', '{{ asset("storage") }}/' + formData.front_image_path);
                }
                if (formData.back_image_path) {
                    loadImagePreview('back-preview', '{{ asset("storage") }}/' + formData.back_image_path);
                }
            @endif
        }

        function loadImagePreview(previewId, imageUrl) {
            const previewContainer = document.getElementById(previewId);
            if (!previewContainer) return;

            // For SVG files, fetch and display
            if (imageUrl.toLowerCase().includes('.svg')) {
                fetch(imageUrl)
                    .then(response => response.text())
                    .then(svgContent => {
                        const cleaned = svgContent.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');
                        previewContainer.innerHTML = cleaned;
                    })
                    .catch(err => {
                        console.error('Failed to load SVG preview:', err);
                        previewContainer.innerHTML = '<span class="muted">Preview not available</span>';
                    });
            } else {
                // For other image types
                previewContainer.innerHTML = `<img src="${imageUrl}" style="max-width:100%;max-height:100%;object-fit:contain;" alt="Preview">`;
            }
        }

        // Debug form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            const submitBtn = document.querySelector('.btn-submit');

            console.log('Form found:', form);
            console.log('Submit button found:', submitBtn);

            // Setup previews for all file inputs
            setupVideoPreview();
            setupPreview('front_image', 'front-preview', 'front');
            setupPreview('back_image', 'back-preview', 'back');

            // Load existing preview data if editing
            loadPreviewData();

            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');
                    const formData = new FormData(form);

                    // Check if we have uploaded files
                    const hasVideoFile = formData.get('template_video') && formData.get('template_video').size > 0;
                    const hasFrontFile = formData.get('front_image') && formData.get('front_image').size > 0;
                    const hasBackFile = formData.get('back_image') && formData.get('back_image').size > 0;

                    const hasAnyDesign = hasFrontFile || hasBackFile;

                    // Only require design content if template name is provided
                    const templateName = formData.get('name');
                    if (templateName && templateName.trim() && !hasAnyDesign) {
                        console.warn('Template has name but no design content');
                        // Allow submission but warn user
                        if (!confirm('You have not uploaded any design files. Do you want to create a template preview without designs? You can add designs later.')) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    console.log('Form validation passed - has design content');
                    console.log('Video file:', hasVideoFile ? 'Present' : 'Missing');
                    console.log('Front file:', hasFrontFile ? 'Present' : 'Missing');
                    console.log('Back file:', hasBackFile ? 'Present' : 'Missing');
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    console.log('========== SUBMIT BUTTON CLICKED ==========');
                    console.log('Form found:', !!form);
                    console.log('Form action:', form ? form.action : 'No form');
                    console.log('Button type:', this.type);
                    console.log('Button disabled:', this.disabled);
                    console.log('Form method:', form ? form.method : 'N/A');

                    // Check form fields
                    if (form) {
                        const formData = new FormData(form);
                        console.log('Template name:', formData.get('name'));
                        console.log('Product type:', formData.get('product_type'));
                        console.log('Video file size:', formData.get('template_video') ? formData.get('template_video').size : 0);
                        console.log('Front file size:', formData.get('front_image') ? formData.get('front_image').size : 0);
                        console.log('Back file size:', formData.get('back_image') ? formData.get('back_image').size : 0);
                    }

                    // Check for form validation
                    if (form && !form.checkValidity()) {
                        console.log('❌ Form validation failed - showing validation messages');
                        form.reportValidity();
                        e.preventDefault();
                        return false;
                    }

                    console.log('✅ Form validation passed - allowing submission');
                });
            }
        });
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Preview Template Design</h2>
            <p class="create-subtitle">Upload your design files to preview how the template will look before creating it</p>
        </div>

    <form action="{{ route('staff.templates.store') }}" method="POST" class="create-form" enctype="multipart/form-data">
            @csrf

            <!-- Template Information -->
            <div class="create-section">
                <h3 class="section-title">Template Information</h3>

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
                    <textarea id="description" name="description" rows="3" placeholder="Describe the template design, style, and intended use...">{{ $previewData['description'] ?? '' }}</textarea>
                </div>
            </div>

            <!-- Design Files Upload -->
            <div class="create-section">
                <h3 class="section-title">Design Files</h3>
                <p class="section-description">Upload your template design files for preview</p>

                <!-- Video Upload -->
                <div class="create-group">
                    <label for="template_video">
                        <i class="fas fa-video text-primary me-2"></i>Template Video (MP4, MOV, AVI)
                    </label>
                    <input type="file" id="template_video" name="template_video" accept=".mp4,.mov,.avi,.webm" class="form-control">
                    <small class="form-text text-muted">Upload a video showcasing your template design</small>
                </div>

                <!-- SVG Designs -->
                <div class="create-row">
                    <div class="create-group flex-1">
                        <label for="front_image">
                            <i class="fas fa-file-alt text-success me-2"></i>Front Design (SVG)
                        </label>
                        <input type="file" id="front_image" name="front_image" accept=".svg,.svg+xml,image/svg+xml" class="form-control" required>
                        <small class="form-text text-muted">Upload SVG file for the front design</small>
                    </div>
                    <div class="create-group flex-1">
                        <label for="back_image">
                            <i class="fas fa-file text-info me-2"></i>Back Design (SVG)
                        </label>
                        <input type="file" id="back_image" name="back_image" accept=".svg,.svg+xml,image/svg+xml" class="form-control">
                        <small class="form-text text-muted">Upload SVG file for the back design (optional)</small>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="create-section">
                <h3 class="section-title">Live Preview</h3>

                <!-- Video Preview -->
                <div class="create-group" id="video-preview-section" style="display: none;">
                    <label>Video Preview</label>
                    <div class="video-preview-container">
                        <video id="video-preview" controls style="max-width: 100%; max-height: 300px; border: 1px solid #d1d5db; border-radius: 8px;">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>

                <!-- SVG Previews -->
                <div class="create-row">
                    <div class="create-group flex-1">
                        <div class="preview-box">
                            <label class="preview-label">Front Design Preview</label>
                            <div id="front-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto;border-radius:8px">
                                <span class="muted">No SVG selected</span>
                            </div>
                        </div>
                    </div>
                    <div class="create-group flex-1">
                        <div class="preview-box">
                            <label class="preview-label">Back Design Preview</label>
                            <div id="back-preview" class="svg-preview" style="width:100%;aspect-ratio:1/1;border:1px solid #d1d5db;padding:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:auto;border-radius:8px">
                                <span class="muted">No SVG selected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="create-actions">
                <a href="{{ route('staff.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create Preview</button>
            </div>
        </form>
    </section>
</main>
@endsection
