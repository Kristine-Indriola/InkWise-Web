@extends('layouts.admin')

@section('title', 'Template Editor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/template/edit.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/template/image.css') }}">
<script src="{{ asset('js/admin/template/edit.js') }}"></script>
<script src="{{ asset('js/admin/template/image.js') }}"></script>
<script src="https://sdk.canva.com/designbutton/v2/api.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&family=Playfair+Display&family=Montserrat&family=Roboto&family=Great+Vibes&family=Poppins&family=Lobster&family=Dancing+Script&family=Merriweather&family=Oswald&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontisto/css/fontisto/all.min.css">
@endpush

@push('scripts')
@endpush

@section('content')
<div class="editor-container">
    <!-- Topbar -->
    <div class="editor-topbar">
        <!-- Navigation Buttons -->
        <div class="nav-links">
            <a href="{{ route('admin.templates.index') }}" class="btn secondary">Back to Templates</a>
            <a href="{{ route('admin.templates.create') }}" class="btn secondary">Create New</a>
        </div>
        <div class="project-name">
    {{ $template->name ?? 'Untitled' }}
    @php
        $design = json_decode($template->design, true);
    @endphp
    @if($design && !empty($design['category']))
        <span style="font-size:16px;color:#888;"> | {{ $design['category'] }}</span>
    @endif
</div>
        <div class="actions">
    <button class="btn" id="saveBtn" title="Save Template">Save</button>
    <button class="btn" id="undoBtn" title="Undo">↶</button>
    <button class="btn" id="redoBtn" title="Redo">↷</button>
    <button class="btn" id="sizeShapeBtn" title="Size & Shape">Size & Shape</button>
    <button class="btn" id="previewBtn" title="Preview">Preview</button>
    <button class="btn primary" id="uploadBtn" title="upload">upload</button>
</div>
    </div>

    <!-- Body -->
    <div class="editor-body">
        <!-- Left Sidebar -->
        <div class="editor-sidebar">
            <ul>
                <li class="active" title="Text Tools" aria-label="Text Tools">Text</li>
                <li title="Image Tools" aria-label="Image Tools">Images</li>
                <li title="Graphics Tools" aria-label="Graphics Tools">Graphics</li>
            </ul>
            <!-- Drag & Drop Zone for Images -->
            <div id="imageDropZone" tabindex="0" aria-label="Drop images here"
                style="border:2px dashed #2563eb;padding:16px;text-align:center;cursor:pointer;margin-top:18px;display:none;">
                <span>Drag & drop images here<br>or click to select</span>
                <input type="file" id="sidebarImageInput" accept="image/*" multiple style="display:none;">
                <div id="imageUploadSpinner" style="display:none;margin-top:8px;">
                    <i class="fa fa-spinner fa-spin"></i> Uploading...
                </div>
                <div id="imageUploadError" style="color:#e11d48;font-size:13px;display:none;margin-top:8px;"></div>
                <div id="imageUploadNote" style="font-size:12px;color:#888;margin-top:8px;">
        Supported: JPG, PNG, GIF, SVG. Max size: 5MB.
    </div>
            </div>
            <div id="sidebarImageThumbs" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
        </div>

        <!-- Canvas -->
        <div class="editor-canvas">
            <!-- Small Floating Toolbar Above Panel -->
            <div class="small-floating-toolbar" 
                 style="position:absolute;top:18px;left:50%;transform:translateX(-50%);z-index:101;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:6px 12px;display:flex;gap:10px;align-items:center;">
                <input type="number" id="fontSizeToolbar" value="20" min="8" max="120" style="width:60px;" title="Font Size" aria-label="Font Size">
                <button type="button" title="Bold" aria-label="Bold" id="boldToolbar"><i class="fa-solid fa-bold"></i></button>
                <button type="button" title="Italic" aria-label="Italic" id="italicToolbar"><i class="fa-solid fa-italic"></i></button>
                <button type="button" title="Underline" aria-label="Underline" id="underlineToolbar"><i class="fa-solid fa-underline"></i></button>
                <button type="button" title="Align Left" aria-label="Align Left" id="alignLeftToolbar"><i class="fi fi-rr-align-left"></i></button>
                <button type="button" title="Align Center" aria-label="Align Center" id="alignCenterToolbar"><i class="fi fi-rr-align-center"></i></button>
                <button type="button" title="Align Justify" aria-label="Align Justify" id="alignJustifyToolbar"><i class="fi fi-rr-align-justify"></i></button>
                <button type="button" title="Symbol" aria-label="Symbol" id="symbolToolbar"><i class="fi fi-rr-symbol"></i></button>
                <button type="button" title="Uppercase" aria-label="Uppercase" id="uppercaseToolbar"><i class="fa-solid fa-text-height"></i></button>
                <button type="button" title="Text Color" aria-label="Text Color" id="textColorToolbar"><i class="fa-solid fa-fill-drip"></i></button>
            </div>
            <!-- Color Picker Dropdown Modal -->
<div id="colorPickerDropdown" style="display:none;position:absolute;top:60px;left:50%;transform:translateX(-50%);z-index:102;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:10px;min-width:180px;">
    <div style="font-weight:600;font-size:15px;margin-bottom:8px;">Pick Text Color</div>
    <input type="color" id="colorPickerInput" style="width:100%;height:40px;border:none;cursor:pointer;">
    <div id="presetColors" style="display:flex;gap:8px;margin-top:10px;">
        <div class="preset-color" style="width:28px;height:28px;border-radius:50%;background:#222;cursor:pointer;" data-color="#222"></div>
        <div class="preset-color" style="width:28px;height:28px;border-radius:50%;background:#e11d48;cursor:pointer;" data-color="#e11d48"></div>
        <div class="preset-color" style="width:28px;height:28px;border-radius:50%;background:#2563eb;cursor:pointer;" data-color="#2563eb"></div>
        <div class="preset-color" style="width:28px;height:28px;border-radius:50%;background:#f59e42;cursor:pointer;" data-color="#f59e42"></div>
        <div class="preset-color" style="width:28px;height:28px;border-radius:50%;background:#10b981;cursor:pointer;" data-color="#10b981"></div>
        <div class="preset-color" style="width:28px;height:28px;border-radius:50%;background:#fff;cursor:pointer;border:1px solid #ccc;" data-color="#fff"></div>
    </div>
</div>
            
            <canvas id="templateCanvas" width="500" height="700"></canvas>
            
            <!-- Floating Tool Panel -->
            <div class="floating-panel" id="floatingPanel">
                <!-- Small Floating Container at the top -->
                <div class="small-floating-container" id="smallFloatingContainer" style="position: relative; width: 100%; height: 50px; background: rgba(255,255,255,0.9); border: 1px solid #ccc; border-radius: 4px; padding: 5px; margin-bottom: 10px;">
                    <!-- Add content here, e.g., quick tools -->
                </div>
                
            </div>
            <div class="zoom-controls">
                <button type="button" id="zoomOutBtn" title="Zoom Out">-</button>
                <span>100%</span>
                <button type="button" id="zoomInBtn" title="Zoom In">+</button>
            </div>
        </div>

        
        <!-- Right Sidebar -->
        <div class="editor-pages">
            <div class="page-thumb active">Front</div>
            <div class="page-thumb">Back</div>
        </div>
    </div>
</div>

<script>
window.TEMPLATE_ID = {{ $template->id }};
window.CSRF_TOKEN = "{{ csrf_token() }}";
window.TEMPLATES_INDEX_URL = "{{ route('admin.templates.index') }}";
window.UPLOAD_PREVIEW_URL = "{{ route('admin.templates.uploadPreview', $template->id) }}";
</script>

<script>
const textColorToolbar = document.getElementById("textColorToolbar");
const colorPickerDropdown = document.getElementById("colorPickerDropdown");
const colorPickerInput = document.getElementById("colorPickerInput");

if (textColorToolbar && colorPickerDropdown && colorPickerInput) {
    textColorToolbar.addEventListener("click", function(e) {
        e.stopPropagation();
        colorPickerDropdown.style.display = 
            (colorPickerDropdown.style.display === "none" || colorPickerDropdown.style.display === "") 
            ? "block" : "none";
    });

    document.addEventListener("click", function(e) {
        if (!colorPickerDropdown.contains(e.target) && e.target !== textColorToolbar) {
            colorPickerDropdown.style.display = "none";
        }
    });

    colorPickerInput.addEventListener("input", function() {
        if (selectedBox) {
            selectedBox.color = colorPickerInput.value;
            draw();
            if (editingTextarea) editingTextarea.style.color = colorPickerInput.value;
        }
        colorPickerDropdown.style.display = "none";
    });

    const presetColors = document.querySelectorAll("#presetColors .preset-color");
presetColors.forEach(el => {
    el.addEventListener("click", function() {
        const color = el.getAttribute("data-color");
        colorPickerInput.value = color;
        if (selectedBox) {
            selectedBox.color = color;
            draw();
            if (editingTextarea) editingTextarea.style.color = color;
        }
        colorPickerDropdown.style.display = "none";
    });
});
}
</script>

<script>
// Show drop zone only for Images tab
document.querySelectorAll('.editor-sidebar li')[1].addEventListener('click', function() {
    document.getElementById('imageDropZone').style.display = 'block';
});

// Drag & Drop logic for sidebar
const imageDropZone = document.getElementById('imageDropZone');
const sidebarImageInput = document.getElementById('sidebarImageInput');
const sidebarImageThumbs = document.getElementById('sidebarImageThumbs');

imageDropZone.addEventListener('click', () => sidebarImageInput.click());
imageDropZone.addEventListener('dragover', e => {
    e.preventDefault();
    imageDropZone.style.background = '#e0e7ff';
});
imageDropZone.addEventListener('dragleave', e => {
    imageDropZone.style.background = '';
});
imageDropZone.addEventListener('drop', e => {
    e.preventDefault();
    imageDropZone.style.background = '';
    handleSidebarImages(e.dataTransfer.files);
});
sidebarImageInput.addEventListener('change', e => {
    handleSidebarImages(e.target.files);
});

function handleSidebarImages(files) {
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        const img = new window.Image();
        img.onload = function() {
            // Show thumbnail in sidebar
            const thumb = document.createElement('img');
            thumb.src = url;
            thumb.alt = 'Image preview';
            thumb.style.width = '48px';
            thumb.style.height = '48px';
            thumb.style.objectFit = 'cover';
            thumb.style.borderRadius = '6px';
            thumb.style.cursor = 'pointer';
            thumb.title = 'Insert image to canvas';
            thumb.onclick = () => {
                // Add image to canvas (requires edit.js integration)
                if (window.addImageToCanvas) window.addImageToCanvas(img);
            };
            sidebarImageThumbs.appendChild(thumb);
        };
        img.src = url;
    });
}

// Floating editor canvas drag & drop
const templateCanvas = document.getElementById('templateCanvas');
templateCanvas.addEventListener('dragover', e => {
    e.preventDefault();
    templateCanvas.style.border = '2px dashed #2563eb';
});
templateCanvas.addEventListener('dragleave', e => {
    templateCanvas.style.border = '';
});
templateCanvas.addEventListener('drop', e => {
    e.preventDefault();
    templateCanvas.style.border = '';
    Array.from(e.dataTransfer.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        const img = new window.Image();
        img.onload = function() {
            // Add image to canvas (requires edit.js integration)
            if (window.addImageToCanvas) window.addImageToCanvas(img);
        };
        img.src = url;
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const uploadBtn = document.getElementById('uploadBtn');
    const templateCanvas = document.getElementById('templateCanvas');

    if (uploadBtn && templateCanvas) {
        uploadBtn.addEventListener('click', function() {
            // Hide floating icons
            const icons = document.querySelectorAll('.canvas-icon');
            icons.forEach(el => el.style.display = 'none');

            // Wait for icons to be hidden before capturing
            setTimeout(() => {
                const imgData = templateCanvas.toDataURL("image/png");
                fetch(window.UPLOAD_PREVIEW_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN
                    },
                    body: JSON.stringify({ preview_image: imgData })
                })
                .then(res => res.json())
                .then(data => {
                    // Restore icons
                    icons.forEach(el => el.style.display = '');
                    if (data.success) {
                        window.location.href = window.TEMPLATES_INDEX_URL;
                    } else {
                        alert('Upload failed!');
                    }
                })
                .catch(() => {
                    icons.forEach(el => el.style.display = '');
                    alert('Upload failed!');
                });
            }, 100); // 100ms delay to ensure icons are hidden
        });
    }
});
</script>
@endsection
