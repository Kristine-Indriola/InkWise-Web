<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $template->name ?? 'Template Editor' }}</title>

    @vite([
        'resources/css/admin/template/edit.css',
        'resources/css/admin/template/image.css',
        'resources/js/admin/template/editor.js',
    ])
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&family=Playfair+Display&family=Montserrat&family=Roboto&family=Great+Vibes&family=Poppins&family=Lobster&family=Dancing+Script&family=Merriweather&family=Oswald&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fontisto@3.0.4/css/fontisto/fontisto.min.css">
    <script src="https://sdk.canva.com/designbutton/v2/api.js"></script>
    
    <!-- Added: force sidebar left and make all buttons white -->
    <style>
    /* Layout: ensure sidebar is on the left */
    .editor-root { display: flex; gap: 16px; align-items: flex-start; }

    /* Sidebar (left) */
    .editor-sidebar {
        order: 0;
        width: 220px;
        min-width: 180px;
        background: #f8f9fb;
        padding: 12px;
        border-right: 1px solid #e5e7eb;
    }

    /* Sidebar color button & modal */
    .sidebar-color-btn {
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 8px;
        border-radius:6px;
        cursor:pointer;
        border:1px solid #e5e7eb;
        background:#fff;
        color:#111827;
        margin-top:10px;
    }
    .sidebar-color-modal {
        display:none;
        position:absolute;
        left:12px;
        top:88px;
        z-index:300;
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:8px;
        box-shadow:0 6px 20px rgba(0,0,0,0.08);
        padding:10px;
        min-width:200px;
    }
    .sidebar-color-modal .preset-color { cursor:pointer; width:28px; height:28px; border-radius:50%; display:inline-block; margin-right:8px; border:1px solid #ddd; }
    .sidebar-color-modal .modal-actions { margin-top:10px; display:flex; gap:8px; justify-content:flex-end; }
    /* make sure modal doesn't overflow small sidebars */
    @media(max-width:480px){ .sidebar-color-modal{ left:6px; right:6px; } }

    /* Main editor panel */
    .editor-panel { order: 1; flex: 1; }

    /* Pages column on right */
    .editor-pages { order: 2; width: 120px; }

    /* Make all buttons white */
    .btn, button, .btn.primary, .btn.secondary, a.btn {
        background: #ffffff !important;
        color: #111827 !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: none !important;
    }

    /* Keep primary slightly emphasized */
    .btn.primary { font-weight: 600; }

    /* Anchor buttons look consistent */
    a.btn { text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; }

    /* Ensure floating toolbars contrast on white buttons */
    .small-floating-toolbar, .floating-panel { background: #ffffff !important; color: #111827; }
    /* Make sure icon fonts in the small toolbar are visible and sized appropriately */
    .small-floating-toolbar i, .small-floating-toolbar .fa, .small-floating-toolbar .fi {
        color: #111827 !important;
        font-size: 16px;
        line-height: 1;
    }
    /* Toolbar buttons: remove white button chrome so icons show clearly */
    .small-floating-toolbar button {
        background: transparent !important;
        border: none !important;
        padding: 6px !important;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Font Awesome 6 uses font-weight to select style; ensure solid icons render */
    .small-floating-toolbar .fa, .small-floating-toolbar .fas, .small-floating-toolbar .fa-solid {
        font-weight: 900 !important;
        font-style: normal !important;
    }

    /* Ensure Fontisto icons render by forcing its font-family in the toolbar */
    .small-floating-toolbar .fi {
        font-family: 'fontisto' !important;
        font-style: normal !important;
        font-weight: normal !important;
    }

    /* Small tweaks to keep icons centered and visible */
    .small-floating-toolbar i {
        display: inline-block;
        width: 20px;
        text-align: center;
        vertical-align: middle;
        font-size: 16px;
    }

    /* Flip animation for page switching */
    .editor-canvas { perspective: 1400px; position: relative; }
    .editor-canvas .flip-inner { transition: transform 600ms ease; transform-style: preserve-3d; }
    .editor-canvas.flipping .flip-inner { transform: rotateY(180deg); }
    .editor-canvas .flip-inner canvas { backface-visibility: hidden; transform-style: preserve-3d; }
    </style>
    
</head>
<body>

<div class="editor-root">
    <div class="editor-sidebar" aria-label="Editor sidebar">
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px">
            <li class="active" title="Text Tools" aria-label="Text Tools">Text</li>
            <li title="Image Tools" aria-label="Image Tools">Images</li>
            <li title="Graphics Tools" aria-label="Graphics Tools">Graphics</li>
            <li id="colorToolsTab" title="Color Tools" aria-label="Color Tools">Color</li>
        </ul>
        <!-- Text tools panel (shows when Text tab selected) -->
        <div id="textToolsPanel" class="text-tools-panel" style="display:none;margin-top:12px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;">
            <div style="font-weight:600;margin-bottom:8px;">Text Tools</div>
            <label style="font-size:12px;color:#666;display:block;margin-bottom:6px">Quick Text</label>
            <input id="quickTextInput" type="text" placeholder="Type to add text" style="width:100%;padding:6px;border:1px solid #ddd;border-radius:4px;margin-bottom:8px;">
            <label style="font-size:12px;color:#666;display:block;margin-bottom:6px">Font</label>
            <select id="quickFontSelect" style="width:100%;padding:6px;border:1px solid #ddd;border-radius:4px;margin-bottom:8px;">
                <option value="Roboto">Roboto</option>
                <option value="Montserrat">Montserrat</option>
                <option value="Playfair Display">Playfair Display</option>
                <option value="Poppins">Poppins</option>
            </select>
            <label style="font-size:12px;color:#666;display:block;margin-bottom:6px">Color</label>
            <input id="quickTextColor" type="color" value="#111827" style="width:100%;height:36px;border:none;padding:0;background:transparent;">
            <button id="insertQuickText" class="btn" style="width:100%;margin-top:10px;">Insert Text</button>
        </div>
        <div id="imageDropZone" tabindex="0" aria-label="Drop images here" style="border:2px dashed var(--accent);padding:16px;text-align:center;cursor:pointer;margin-top:18px;display:none;">
            <span>Drag & drop images here<br>or click to select</span>
            <input type="file" id="sidebarImageInput" accept="image/*" multiple style="display:none;">
            <div id="imageUploadSpinner" style="display:none;margin-top:8px;">
                <i class="fa fa-spinner fa-spin"></i> Uploading...
            </div>
            <div id="imageUploadError" style="color:#e11d48;font-size:13px;display:none;margin-top:8px;"></div>
            <div id="imageUploadNote" style="font-size:12px;color:#888;margin-top:8px;">Supported: JPG, PNG, GIF, SVG. Max size: 5MB.</div>
        </div>
        <div id="sidebarImageThumbs" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
    </div>

    <div class="editor-panel">
        <div class="editor-topbar" role="banner">
            <div class="nav-links">
                <a href="{{ route('admin.templates.index') }}" class="btn secondary">Back to Templates</a>
                <a href="{{ route('admin.templates.create') }}" class="btn secondary">Create New</a>
            </div>
            <div class="project-name">
                {{ $template->name ?? 'Untitled' }}
                @php $design = json_decode($template->design, true); @endphp
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
                <button class="btn" id="canvaBtn" title="Edit in Canva">Edit in Canva</button>
            </div>
        </div>

        <div class="editor-canvas" role="main">
            <div class="small-floating-toolbar" style="position:absolute;top:18px;left:50%;transform:translateX(-50%);z-index:101;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:6px 12px;display:flex;gap:10px;align-items:center;">
                <input type="number" id="fontSizeToolbar" value="20" min="8" max="120" style="width:60px;" title="Font Size" aria-label="Font Size">
                <button type="button" title="Bold" aria-label="Bold" id="boldToolbar"><i class="fa-solid fa-bold"></i></button>
                <button type="button" title="Italic" aria-label="Italic" id="italicToolbar"><i class="fa-solid fa-italic"></i></button>
                <button type="button" title="Underline" aria-label="Underline" id="underlineToolbar"><i class="fa-solid fa-underline"></i></button>
                <button type="button" title="Align Left" aria-label="Align Left" id="alignLeftToolbar"><i class="fa-solid fa-align-left"></i></button>
                <button type="button" title="Align Center" aria-label="Align Center" id="alignCenterToolbar"><i class="fa-solid fa-align-center"></i></button>
                <button type="button" title="Align Justify" aria-label="Align Justify" id="alignJustifyToolbar"><i class="fa-solid fa-align-justify"></i></button>
                <button type="button" title="Symbol" aria-label="Symbol" id="symbolToolbar"><i class="fa-solid fa-font"></i></button>
                <button type="button" title="Uppercase" aria-label="Uppercase" id="uppercaseToolbar"><i class="fa-solid fa-text-height"></i></button>
                <button type="button" title="Text Color" aria-label="Text Color" id="textColorToolbar"><i class="fa-solid fa-fill-drip"></i></button>
            </div>

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

            <template id="assetThumbTemplate">
                <div class="asset-thumb" style="cursor:pointer;border-radius:6px;overflow:hidden;border:1px solid rgba(255,255,255,0.06);">
                    <img src="" alt="asset" style="width:100%;height:100%;object-fit:cover;display:block;">
                </div>
            </template>

            <div class="floating-panel" id="floatingPanel">
                <div class="small-floating-container" id="smallFloatingContainer" style="position: relative; width: 100%; height: 50px; background: rgba(255,255,255,0.9); border: 1px solid #ccc; border-radius: 4px; padding: 5px; margin-bottom: 10px;"></div>
            </div>
            <div class="zoom-controls" style="margin-top:12px;">
                <button type="button" id="zoomOutBtn" title="Zoom Out">-</button>
                <span>100%</span>
                <button type="button" id="zoomInBtn" title="Zoom In">+</button>
            </div>
        </div>
    </div>

    <div class="editor-pages" style="width:120px;">
        <div class="page-thumb active" data-page="front">Front</div>

        <button id="sizeShapeCancel" class="btn">Cancel</button>
        <button id="sizeShapeApply" class="btn primary">Apply</button>
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
<script>
// Asset search and insertion
(function(){
    var btn = document.getElementById('assetSearchBtn');
    var input = document.getElementById('assetSearchInput');
    var typeSelect = document.getElementById('assetType');
    var results = document.getElementById('assetResults');
    var templateId = window.TEMPLATE_ID;
    var template = document.getElementById('assetThumbTemplate');

    function renderAssets(list){
        results.innerHTML = '';
        if (!list || !list.length) {
            results.innerHTML = '<div style="color:#fff">No assets found</div>';
            return;
        }
        list.forEach(function(item){
            var clon = template.content.cloneNode(true);
            var div = clon.querySelector('.asset-thumb');
            var img = clon.querySelector('img');
            img.src = item.url;
            img.alt = item.name;
            div.title = item.name;
            div.addEventListener('click', function(){
                // image insertion
                if (typeSelect.value === 'image') {
                    if (window.addImageToCanvas) {
                        var image = new Image();
                        image.crossOrigin = 'anonymous';
                        image.onload = function(){ window.addImageToCanvas(image); };
                        image.src = item.url;
                    } else {
                        alert('No canvas insertion handler found (addImageToCanvas)');
                    }
                } else if (typeSelect.value === 'video') {
                    // For videos, open in new tab or create a placeholder
                    window.open(item.url, '_blank');
                } else {
                    // elements: could be svg or json shapes — try to fetch and add
                    fetch(item.url).then(function(res){ return res.text(); }).then(function(body){
                        if (window.addElementToCanvas) {
                            window.addElementToCanvas(body, item.name);
                        } else {
                            alert('Element loaded; no handler to place it on canvas.');
                        }
                    }).catch(function(){ alert('Failed to load element'); });
                }
            });
            results.appendChild(clon);
        });
    }

    function doSearch(){
        var q = (input && input.value) ? input.value : '';
        var t = (typeSelect && typeSelect.value) ? typeSelect.value : 'image';
        results.innerHTML = '<div style="color:#fff">Searching...</div>';
        fetch('{{ url("/admin/templates") }}/' + templateId + '/assets/search?type=' + encodeURIComponent(t) + '&q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function(r){ return r.json(); }).then(function(json){
            if (json && json.success) renderAssets(json.data || []);
            else results.innerHTML = '<div style="color:#fff">No assets found</div>';
        }).catch(function(){ results.innerHTML = '<div style="color:#fff">Search failed</div>'; });
    }

    if (btn) btn.addEventListener('click', doSearch);
    if (input) input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });
})();
</script>
<script>
// Front/Back page logic and Size & Shape modal
(function(){
    var pages = document.querySelectorAll('.editor-pages .page-thumb');
    var currentPage = 'front';
    var canvas = document.getElementById('templateCanvas');
    var sizeBtn = document.getElementById('sizeShapeBtn');
    var modal = document.getElementById('sizeShapeModal');
    var widthInput = document.getElementById('canvasWidth');
    var heightInput = document.getElementById('canvasHeight');
    var shapeSelect = document.getElementById('canvasShape');
    var cancelBtn = document.getElementById('sizeShapeCancel');
    var applyBtn = document.getElementById('sizeShapeApply');

    // simple in-memory page store
    var pageStore = {
        front: { src: null },
        back: { src: null }
    };

    pages.forEach(function(p){
        p.addEventListener('click', function(){
            pages.forEach(function(x){ x.classList.remove('active'); });
            p.classList.add('active');
            var page = p.getAttribute('data-page');
            // save current canvas image to store
            try { pageStore[currentPage].src = canvas.toDataURL('image/png'); } catch(e){}
            currentPage = page;
            // load page image if exists
            if (pageStore[page] && pageStore[page].src) {
                var img = new Image(); img.onload = function(){
                    var ctx = canvas.getContext('2d'); ctx.clearRect(0,0,canvas.width,canvas.height); ctx.drawImage(img,0,0,canvas.width,canvas.height);
                }; img.src = pageStore[page].src;
            } else {
                // clear canvas
                var ctx = canvas.getContext('2d'); ctx.clearRect(0,0,canvas.width,canvas.height);
            }
        });
    });

    // Size & Shape modal
    if (sizeBtn) sizeBtn.addEventListener('click', function(){
        // prefill values
        widthInput.value = canvas.width;
        heightInput.value = canvas.height;
        modal.style.display = 'block'; modal.setAttribute('aria-hidden','false');
    });
    if (cancelBtn) cancelBtn.addEventListener('click', function(){ modal.style.display = 'none'; modal.setAttribute('aria-hidden','true'); });
    if (applyBtn) applyBtn.addEventListener('click', function(){
        var w = parseInt(widthInput.value) || canvas.width;
        var h = parseInt(heightInput.value) || canvas.height;
        var shape = shapeSelect.value;
        // create temp canvas to preserve content
        var tmp = document.createElement('canvas'); tmp.width = w; tmp.height = h; var tctx = tmp.getContext('2d');
        // draw existing canvas into tmp with scaling
        tctx.fillStyle = '#ffffff'; tctx.fillRect(0,0,w,h);
        tctx.drawImage(canvas, 0, 0, w, h);
        // apply to real canvas
        canvas.width = w; canvas.height = h;
        var ctx = canvas.getContext('2d'); ctx.clearRect(0,0,w,h); ctx.drawImage(tmp,0,0);
        // apply shape via CSS
        if (shape === 'rounded') { canvas.style.borderRadius = '18px'; }
        else if (shape === 'oval') { canvas.style.borderRadius = Math.min(w,h)/2 + 'px'; }
        else { canvas.style.borderRadius = '4px'; }

        modal.style.display = 'none'; modal.setAttribute('aria-hidden','true');
    });
})();
</script>

<script>
// Sidebar color modal: toggle / apply theme accent
+(function(){
+    var sidebarBtn = document.getElementById('sidebarColorBtn');
+    var sidebarModal = document.getElementById('sidebarColorModal');
+    var sidebarColorInput = document.getElementById('sidebarThemeColor');
+    var presetEls = document.querySelectorAll('#sidebarPresetColors .preset-color');
+    var applyBtn = document.getElementById('sidebarColorApply');
+    var cancelBtn = document.getElementById('sidebarColorCancel');
+
+    if (!sidebarBtn || !sidebarModal || !sidebarColorInput) return;
+
+    function openModal(){
+        sidebarModal.style.display = 'block';
+        sidebarModal.setAttribute('aria-hidden','false');
+        sidebarBtn.setAttribute('aria-expanded','true');
+    }
+    function closeModal(){
+        sidebarModal.style.display = 'none';
+        sidebarModal.setAttribute('aria-hidden','true');
+        sidebarBtn.setAttribute('aria-expanded','false');
+    }
+
+    sidebarBtn.addEventListener('click', function(e){
+        e.stopPropagation();
+        if (sidebarModal.style.display === 'block') closeModal(); else openModal();
+    });
+
+    // clicking outside closes modal
+    document.addEventListener('click', function(e){
+        if (!sidebarModal.contains(e.target) && e.target !== sidebarBtn) closeModal();
+    });
+
+    // preset colors
+    presetEls.forEach(function(el){
+        el.addEventListener('click', function(){
+            var c = el.getAttribute('data-color');
+            sidebarColorInput.value = c;
+        });
+    });
+
+    // apply: set CSS variable --accent and update small preview
+    applyBtn.addEventListener('click', function(){
+        var val = sidebarColorInput.value || '#2563eb';
+        document.documentElement.style.setProperty('--accent', val);
+        // update preview swatch inside button
+        var sw = sidebarBtn.querySelector('span');
+        if (sw) sw.style.background = val;
+        // update any components that referenced var(--accent)
+        var dz = document.getElementById('imageDropZone');
+        if (dz) dz.style.borderColor = val;
+        closeModal();
+    });
+    // cancel / close
+    cancelBtn.addEventListener('click', function(){ closeModal(); });
+
+    // keyboard: Esc to close
+    document.addEventListener('keydown', function(e){
+        if (e.key === 'Escape') closeModal();
+    });
+})();
</script>

<script>
// Canva integration
document.addEventListener("DOMContentLoaded", function() {
    const canvaBtn = document.getElementById('canvaBtn');
    if (canvaBtn && window.Canva) {
        const canvaButton = new Canva.DesignButton({
            apiKey: 'YOUR_CANVA_API_KEY', // Replace with your Canva Design Button API key from https://www.canva.com/developers/
            design: {
                type: 'Poster',
                dimensions: {
                    width: 500,
                    height: 700,
                },
            },
            onDesignPublish: (opts) => {
                // Handle the published design
                console.log('Design published:', opts);
                // You can save the design or update the canvas here
                // For example, if opts.exportUrl has an image URL, load it into the canvas
                if (opts.exportUrl) {
                    const img = new Image();
                    img.onload = function() {
                        // Draw on canvas or something
                        const canvas = document.getElementById('templateCanvas');
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    };
                    img.src = opts.exportUrl;
                }
            },
        });
        canvaButton.attach(canvaBtn);
    }
});
</script>

