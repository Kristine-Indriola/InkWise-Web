document.addEventListener("DOMContentLoaded", () => {
    // ========================
    // Canvas & Zoom Controls
    // ========================
    const zoomOutBtn = document.getElementById("zoomOutBtn");
    const zoomInBtn = document.getElementById("zoomInBtn");
    const zoomDisplay = document.querySelector(".zoom-controls span");
    const canvas = document.getElementById("templateCanvas");
    const ctx = canvas.getContext("2d");

    let zoomLevel = 100;
    function updateZoom() {
        canvas.style.transform = `scale(${zoomLevel / 100})`;
        zoomDisplay.textContent = `${zoomLevel}%`;
        repositionEditor();
        draw();
    }
    zoomOutBtn?.addEventListener("click", () => {
        if (zoomLevel > 10) {
            zoomLevel -= 10;
            updateZoom();
            
        }
    });
    zoomInBtn?.addEventListener("click", () => {
        if (zoomLevel < 300) {
            zoomLevel += 10;
            updateZoom();
        }
    });

    // ========================
    // State
    // ========================
    let fonts = []; // Removed static list, now populated from API
    let textBoxes = [];
    let mediaBoxes = [];
    let selectedBox = null;
    let selectedMediaBox = null;
    let dragging = false, resizing = false, dragOffset = {x:0, y:0}, dragBox = null, dragType = null;
    let draggingMedia = false, resizingMedia = false, dragOffsetMedia = {x:0, y:0}, dragMediaBox = null;
    let editingTextarea = null, editingBox = null;
    let previewImage = null; // Preview image background
    let svgImage = null; // SVG overlay image

    // ========================
    // Design Data Loading
    // ========================
    function loadDesignData(designData) {
        // Clear existing boxes
        textBoxes = [];
        mediaBoxes = [];
        
        // Process pages (usually just one page)
        if (designData.pages && Array.isArray(designData.pages)) {
            designData.pages.forEach(page => {
                if (page.nodes && Array.isArray(page.nodes)) {
                    page.nodes.forEach(node => {
                        if (node.type === 'text') {
                            // Add text box
                            textBoxes.push({
                                x: (node.frame?.x || 0),
                                y: (node.frame?.y || 0),
                                w: (node.frame?.width || 100),
                                h: (node.frame?.height || 50),
                                text: node.content || '',
                                fontSize: node.fontSize || 24,
                                color: node.fill || '#000000',
                                fontFamily: node.fontFamily || 'Arial, sans-serif',
                                textAlign: node.textAlign || 'left',
                                fontStyle: node.fontStyle || 'normal',
                                fontWeight: node.fontWeight || '400',
                                textTransform: node.textTransform || 'none',
                                textDecoration: node.textDecoration || '',
                            });
                        } else if (node.type === 'image') {
                            // Add image box
                            const img = new Image();
                            img.onload = function() {
                                draw(); // Redraw when image loads
                            };
                            img.src = node.src || '';
                            
                            mediaBoxes.push({
                                type: 'image',
                                img: img,
                                x: (node.frame?.x || 0),
                                y: (node.frame?.y || 0),
                                w: (node.frame?.width || 100),
                                h: (node.frame?.height || 100),
                                locked: false,
                            });
                        } else if (node.type === 'shape') {
                            // For shapes, we'll render them via SVG overlay
                            // This will be handled in the draw function
                        }
                    });
                }
            });
        }
        
        draw(); // Initial draw
    }

    // ========================
    // Drawing
    // ========================
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw preview image as background if available
        if (previewImage && previewImage.complete && previewImage.naturalWidth > 0) {
            ctx.drawImage(previewImage, 0, 0, canvas.width, canvas.height);
        }
        
        // Draw SVG overlay if available
        if (svgImage && svgImage.complete && svgImage.naturalWidth > 0) {
            ctx.drawImage(svgImage, 0, 0, canvas.width, canvas.height);
        }
        
        drawMedia();
        textBoxes.forEach(box => {
            ctx.save();
            ctx.strokeStyle = selectedBox === box ? "#2563eb" : "#aaa";
            ctx.lineWidth = selectedBox === box ? 2 : 1;
            ctx.strokeRect(
                box.x * (zoomLevel/100),
                box.y * (zoomLevel/100),
                box.w * (zoomLevel/100),
                box.h * (zoomLevel/100)
            );
            // Build font including style (italic) if present
            const fontStyle = box.fontStyle && box.fontStyle !== 'normal' ? box.fontStyle + ' ' : '';
            const fontWeight = box.fontWeight || '400';
            ctx.font = `${fontStyle}${fontWeight} ${box.fontSize * (zoomLevel/100)}px ${box.fontFamily}`;
            ctx.fillStyle = box.color;
            ctx.textBaseline = "top";
            ctx.textAlign = box.textAlign || "left";
            ctx.save();
            ctx.beginPath();
            ctx.rect(
                box.x * (zoomLevel/100) + 8,
                box.y * (zoomLevel/100) + 8,
                (box.w - 16) * (zoomLevel/100),
                (box.h - 16) * (zoomLevel/100)
            );
            ctx.clip();
            // Respect text transform (uppercase)
            const drawText = (box.textTransform === 'uppercase') ? (box.text || '').toUpperCase() : (box.text || '');
            // Draw text with wrapping handled by canvas fillText width param
            ctx.fillText(
                drawText,
                box.x * (zoomLevel/100) + 8,
                box.y * (zoomLevel/100) + 8,
                (box.w - 16) * (zoomLevel/100)
            );
            // Underline support: draw a line under the last line
            if (box.textDecoration && box.textDecoration.includes('underline')) {
                // Approximate underline position
                const textMetrics = ctx.measureText(drawText);
                // compute baseline Y
                const baselineY = box.y * (zoomLevel/100) + 8 + (box.fontSize * (zoomLevel/100));
                ctx.beginPath();
                ctx.strokeStyle = box.color || '#000';
                ctx.lineWidth = Math.max(1, (box.fontSize * (zoomLevel/100)) / 12);
                // draw across the box width
                let startX = box.x * (zoomLevel/100) + 8;
                let endX = box.x * (zoomLevel/100) + (box.w - 16) * (zoomLevel/100);
                ctx.moveTo(startX, baselineY);
                ctx.lineTo(endX, baselineY);
                ctx.stroke();
            }
            ctx.restore();
            // Draw resize handle
            ctx.fillStyle = "#2563eb";
            ctx.fillRect(
                box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 8,
                box.y * (zoomLevel/100) + box.h * (zoomLevel/100) - 8,
                8, 8
            );
            // Draw delete (X) icon
            ctx.font = "bold 18px Arial";
            ctx.fillStyle = "#e11d48";
            ctx.textAlign = "right";
            ctx.textBaseline = "top";
            ctx.fillText(
                "✕",
                box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 12,
                box.y * (zoomLevel/100) + 4
            );
            // Draw four corner handles
            const corners = [
                [box.x, box.y], // top-left
                [box.x + box.w, box.y], // top-right
                [box.x, box.y + box.h], // bottom-left
                [box.x + box.w, box.y + box.h] // bottom-right
            ];
            ctx.fillStyle = "#2563eb";
            corners.forEach(([cx, cy]) => {
                ctx.fillRect(
                    cx * (zoomLevel/100) - 4,
                    cy * (zoomLevel/100) - 4,
                    8, 8
                );
            });
            ctx.restore();
        });
    }
    function drawMedia() {
        mediaBoxes.forEach(box => {
            ctx.save();
            if (box.type === "image" && box.img) {
                ctx.drawImage(
                    box.img,
                    box.x * (zoomLevel/100),
                    box.y * (zoomLevel/100),
                    box.w * (zoomLevel/100),
                    box.h * (zoomLevel/100)
                );
            }
            if (box.type === "video") {
                ctx.strokeStyle = "#aaa";
                ctx.strokeRect(
                    box.x * (zoomLevel/100),
                    box.y * (zoomLevel/100),
                    box.w * (zoomLevel/100),
                    box.h * (zoomLevel/100)
                );
                ctx.font = "bold 16px Arial";
                ctx.fillStyle = "#888";
                ctx.fillText("Video", box.x * (zoomLevel/100) + 10, box.y * (zoomLevel/100) + 24);
            }
            // Draw resize handle (Expand-arrows-alt icon placeholder)
            ctx.fillStyle = "#2563eb";
            ctx.beginPath();
            ctx.arc(
                box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 12,
                box.y * (zoomLevel/100) + box.h * (zoomLevel/100) - 12,
                12, 0, 2 * Math.PI
            );
            ctx.fill();
            ctx.font = "20px Arial";
            ctx.fillStyle = "#fff";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText("⤢", box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 12, box.y * (zoomLevel/100) + box.h * (zoomLevel/100) - 12);

            // Draw lock/unlock icon (Fontisto icons as placeholder)
            ctx.font = "28px Arial";
            ctx.textAlign = "right";
            ctx.textBaseline = "top";
            ctx.fillStyle = "#2563eb";
            if (box.locked) {
                ctx.fillText("🔒", (box.x + box.w) * (zoomLevel/100) - 10, box.y * (zoomLevel/100) + 2);
                // For HTML icon overlay, see below
            } else {
                ctx.fillText("🔓", (box.x + box.w) * (zoomLevel/100) - 10, box.y * (zoomLevel/100) + 2);
            }

            // Draw cross-circle icon (delete)
            ctx.font = "28px Arial";
            ctx.fillStyle = "#e11d48";
            ctx.textAlign = "left";
            ctx.textBaseline = "top";
            ctx.fillText("⨯", box.x * (zoomLevel/100) + 4, box.y * (zoomLevel/100) + 4);

            // Draw arrow-up-right-and-arrow-down-left-from-center icon (placeholder)
            ctx.font = "22px Arial";
            ctx.fillStyle = "#2563eb";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText("⇲", box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 32, box.y * (zoomLevel/100) + box.h * (zoomLevel/100) - 32);

            ctx.restore();
        });
    }

    // ========================
    // Unified Canvas Mouse Events
    // ========================
    canvas.addEventListener("mousedown", function(e) {
        if (editingTextarea) return;
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        // --- Text Box Delete ---
        for (let i = textBoxes.length - 1; i >= 0; i--) {
            const box = textBoxes[i];
            const iconX = box.x + box.w - 24;
            const iconY = box.y + 4;
            if (mx > iconX && mx < iconX + 20 && my > iconY && my < iconY + 20) {
                saveState();
                textBoxes.splice(i, 1);
                selectedBox = null;
                hideFloatingToolbar();
                draw();
                return;
            }
        }

        // --- Media Lock Toggle ---
        for (let i = mediaBoxes.length - 1; i >= 0; i--) {
            const box = mediaBoxes[i];
            const lockIconX = box.x + box.w - 24;
            const lockIconY = box.y + 2;
            if (mx > lockIconX && mx < lockIconX + 22 && my > lockIconY && my < lockIconY + 22) {
                box.locked = !box.locked;
                draw();
                return;
            }
        }

        // --- Text Box Drag/Resize ---
        selectedBox = null;
        dragType = null;
        dragBox = null;
        dragging = false;
        resizing = false;
        for (let i = textBoxes.length - 1; i >= 0; i--) {
            const box = textBoxes[i];
            // Corners
            const corners = [
                {name: "tl", x: box.x, y: box.y},
                {name: "tr", x: box.x + box.w, y: box.y},
                {name: "bl", x: box.x, y: box.y + box.h},
                {name: "br", x: box.x + box.w, y: box.y + box.h}
            ];
            for (const corner of corners) {
                if (mx > corner.x - 8 && mx < corner.x + 8 && my > corner.y - 8 && my < corner.y + 8) {
                    selectedBox = box;
                    resizing = true;
                    dragBox = box;
                    dragType = corner.name;
                    dragOffset = {x: mx - box.x, y: my - box.y};
                    draw();
                    showFloatingToolbar();
                    return;
                }
            }
            // Inside box
            if (mx > box.x && mx < box.x + box.w && my > box.y && my < box.y + box.h) {
                saveState();
                selectedBox = box;
                dragging = true;
                dragType = "move";
                dragBox = box;
                dragOffset = {x: mx - box.x, y: my - box.y};
                draw();
                showFloatingToolbar();
                return;
            }
        }

        // --- Media Drag/Resize ---
        selectedMediaBox = null;
        draggingMedia = false;
        resizingMedia = false;
        dragMediaBox = null;
        for (let i = mediaBoxes.length - 1; i >= 0; i--) {
            const box = mediaBoxes[i];
            if (box.locked) continue;
            // Resize handle
            if (mx > box.x + box.w - 8 && mx < box.x + box.w && my > box.y + box.h - 8 && my < box.y + box.h) {
                selectedMediaBox = box;
                resizingMedia = true;
                dragMediaBox = box;
                dragOffsetMedia = {x: mx - box.x, y: my - box.y};
                return;
            }
            // Inside box
            if (mx > box.x && mx < box.x + box.w && my > box.y && my < box.y + box.h) {
                selectedMediaBox = box;
                draggingMedia = true;
                dragMediaBox = box;
                dragOffsetMedia = {x: mx - box.x, y: my - box.y};
                return;
            }
        }

        draw();
        // If nothing selected, hide floating toolbar
        if (!selectedBox) hideFloatingToolbar();
    });
    canvas.addEventListener("mousemove", function(e) {
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        // Text Drag/Resize
        if (dragging && dragType === "move" && dragBox) {
            dragBox.x = mx - dragOffset.x;
            dragBox.y = my - dragOffset.y;
            draw();
            if (selectedBox) positionFloatingToolbarForBox(selectedBox);
        }
        if (resizing && dragBox) {
            let newX = dragBox.x, newY = dragBox.y, newW = dragBox.w, newH = dragBox.h;
            if (dragType === "tl") {
                newW += newX - mx;
                newH += newY - my;
                newX = mx;
                newY = my;
            } else if (dragType === "tr") {
                newW = mx - dragBox.x;
                newH += newY - my;
                newY = my;
            } else if (dragType === "bl") {
                newW += newX - mx;
                newX = mx;
                newH = my - dragBox.y;
            } else if (dragType === "br") {
                newW = mx - dragBox.x;
                newH = my - dragBox.y;
            }
            dragBox.x = newX;
            dragBox.y = newY;
            dragBox.w = Math.max(40, newW);
            dragBox.h = Math.max(28, newH);
            draw();
            if (selectedBox) positionFloatingToolbarForBox(selectedBox);
        }

        // Media Drag/Resize
        if (draggingMedia && dragMediaBox) {
            dragMediaBox.x = mx - dragOffsetMedia.x;
            dragMediaBox.y = my - dragOffsetMedia.y;
            draw();
        }
        if (resizingMedia && dragMediaBox) {
            dragMediaBox.w = Math.max(40, mx - dragMediaBox.x);
            dragMediaBox.h = Math.max(30, my - dragMediaBox.y);
            draw();
        }
    });
    document.addEventListener("mouseup", function() {
        dragging = false;
        resizing = false;
        dragType = null;
        dragBox = null;
        draggingMedia = false;
        resizingMedia = false;
        dragMediaBox = null;
    });
    canvas.addEventListener("dblclick", function(e) {
        if (editingTextarea) return;
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        for (let i = textBoxes.length - 1; i >= 0; i--) {
            const box = textBoxes[i];
            if (mx > box.x && mx < box.x + box.w && my > box.y && my < box.y + box.h) {
                selectedBox = box;
                editingBox = box;
                showInlineEditor(box);
                showFloatingToolbar();
                return;
            }
        }
    });

    // ========================
    // Undo/Redo Functionality
    // ========================
    let undoStack = [];
    let redoStack = [];

    function saveState() {
        undoStack.push({
            textBoxes: JSON.parse(JSON.stringify(textBoxes)),
            mediaBoxes: JSON.parse(JSON.stringify(mediaBoxes))
        });
        redoStack = [];
    }

    function restoreState(state) {
        if (!state) return;
        textBoxes = JSON.parse(JSON.stringify(state.textBoxes));
        mediaBoxes = JSON.parse(JSON.stringify(state.mediaBoxes));
        draw();
        hideFloatingToolbar();
    }

    document.getElementById("undoBtn")?.addEventListener("click", function() {
        if (undoStack.length > 0) {
            redoStack.push({
                textBoxes: JSON.parse(JSON.stringify(textBoxes)),
                mediaBoxes: JSON.parse(JSON.stringify(mediaBoxes))
            });
            const prevState = undoStack.pop();
            restoreState(prevState);
        }
    });

    document.getElementById("redoBtn")?.addEventListener("click", function() {
        if (redoStack.length > 0) {
            undoStack.push({
                textBoxes: JSON.parse(JSON.stringify(textBoxes)),
                mediaBoxes: JSON.parse(JSON.stringify(mediaBoxes))
            });
            const nextState = redoStack.pop();
            restoreState(nextState);
        }
    });

    // ========================
    // Inline Editor Functions
    // ========================
    function showInlineEditor(box) {
        if (editingTextarea) return;
        editingTextarea = document.createElement("textarea");
        editingTextarea.value = box.text;
        editingTextarea.style.position = "absolute";
        editingTextarea.style.left = (canvas.offsetLeft + box.x * (zoomLevel/100)) + "px";
        editingTextarea.style.top = (canvas.offsetTop + box.y * (zoomLevel/100)) + "px";
        editingTextarea.style.width = (box.w * (zoomLevel/100)) + "px";
        editingTextarea.style.height = (box.h * (zoomLevel/100)) + "px";
        editingTextarea.style.fontSize = (box.fontSize * (zoomLevel/100)) + "px";
        editingTextarea.style.fontFamily = box.fontFamily;
        editingTextarea.style.color = box.color;
        editingTextarea.style.zIndex = 10;
        editingTextarea.style.background = "rgba(255,255,255,0.95)";
        editingTextarea.style.border = "1px solid #2563eb";
        editingTextarea.style.resize = "none";
        editingTextarea.style.overflow = "hidden";
        editingTextarea.style.padding = "2px 4px";
        editingTextarea.style.boxSizing = "border-box";
        editingTextarea.style.outline = "none";
        editingTextarea.style.borderRadius = "6px";
        editingTextarea.style.boxShadow = "0 2px 8px rgba(0,0,0,0.08)";
        editingTextarea.rows = 1;

        // Append to .editor-canvas instead of body
        const editorCanvas = canvas.parentNode;
        editorCanvas.appendChild(editingTextarea);
        editingTextarea.focus();

        editingTextarea.addEventListener("blur", function() {
            box.text = editingTextarea.value;
            editorCanvas.removeChild(editingTextarea);
            editingTextarea = null;
            editingBox = null;
            draw();
        });
    }

    function repositionEditor() {
        if (!editingTextarea || !editingBox) return;
        // Position relative to canvas container
        editingTextarea.style.left = (canvas.offsetLeft + editingBox.x * (zoomLevel/100)) + "px";
        editingTextarea.style.top = (canvas.offsetTop + editingBox.y * (zoomLevel/100)) + "px";
        editingTextarea.style.width = (editingBox.w * (zoomLevel/100)) + "px";
        editingTextarea.style.height = (editingBox.h * (zoomLevel/100)) + "px";
        editingTextarea.style.fontSize = (editingBox.fontSize * (zoomLevel/100)) + "px";
    }

    // ========================
    // Small Floating Toolbar Integration
    // ========================
    const floatingToolbar = document.querySelector('.small-floating-toolbar');
    const toolbarFontSize = document.getElementById('fontSizeToolbar');
    const boldToolbar = document.getElementById('boldToolbar');
    const italicToolbar = document.getElementById('italicToolbar');
    const underlineToolbar = document.getElementById('underlineToolbar');
    const alignLeftToolbar = document.getElementById('alignLeftToolbar');
    const alignCenterToolbar = document.getElementById('alignCenterToolbar');
    const alignJustifyToolbar = document.getElementById('alignJustifyToolbar');
    const uppercaseToolbar = document.getElementById('uppercaseToolbar');
    const colorPickerInput = document.getElementById('colorPickerInput');

    function hideFloatingToolbar() {
        if (floatingToolbar) floatingToolbar.style.display = 'none';
        const cp = document.getElementById('colorPickerDropdown'); if (cp) cp.style.display = 'none';
    }

    function positionFloatingToolbarForBox(box) {
        if (!floatingToolbar || !box) return;
        const scale = zoomLevel / 100;
        const left = canvas.offsetLeft + (box.x * scale) + (box.w * scale) / 2;
        const top = canvas.offsetTop + (box.y * scale) - 46; // place above box
        floatingToolbar.style.transform = '';
        floatingToolbar.style.left = (left - 20) + 'px';
        floatingToolbar.style.top = Math.max(6, top) + 'px';
        floatingToolbar.style.display = 'flex';
    }

    function showFloatingToolbar() {
        if (!floatingToolbar) return;
        if (!selectedBox) { hideFloatingToolbar(); return; }
        if (toolbarFontSize) toolbarFontSize.value = selectedBox.fontSize || 20;
        if (boldToolbar) boldToolbar.classList.toggle('active', (parseInt(selectedBox.fontWeight || '400') >= 700));
        if (italicToolbar) italicToolbar.classList.toggle('active', (selectedBox.fontStyle === 'italic'));
        if (underlineToolbar) underlineToolbar.classList.toggle('active', (selectedBox.textDecoration && selectedBox.textDecoration.includes('underline')));
        // Show toolbar and position it above the selected box
        positionFloatingToolbarForBox(selectedBox);
    }

    // Toolbar control bindings (modify selectedBox properties and redraw)
    if (toolbarFontSize) toolbarFontSize.addEventListener('input', function(){ if (selectedBox) { selectedBox.fontSize = parseInt(this.value,10) || 12; draw(); if (editingTextarea) editingTextarea.style.fontSize = (selectedBox.fontSize * (zoomLevel/100)) + 'px'; }});
    if (boldToolbar) boldToolbar.addEventListener('click', function(){ if (!selectedBox) return; selectedBox.fontWeight = (parseInt(selectedBox.fontWeight||'400')>=700) ? '400' : '700'; draw(); });
    if (italicToolbar) italicToolbar.addEventListener('click', function(){ if (!selectedBox) return; selectedBox.fontStyle = (selectedBox.fontStyle === 'italic') ? 'normal' : 'italic'; draw(); });
    if (underlineToolbar) underlineToolbar.addEventListener('click', function(){ if (!selectedBox) return; selectedBox.textDecoration = (selectedBox.textDecoration && selectedBox.textDecoration.includes('underline')) ? '' : 'underline'; draw(); });
    if (alignLeftToolbar) alignLeftToolbar.addEventListener('click', function(){ if (selectedBox) { selectedBox.textAlign = 'left'; draw(); }});
    if (alignCenterToolbar) alignCenterToolbar.addEventListener('click', function(){ if (selectedBox) { selectedBox.textAlign = 'center'; draw(); }});
    if (alignJustifyToolbar) alignJustifyToolbar.addEventListener('click', function(){ if (selectedBox) { selectedBox.textAlign = 'justify'; draw(); }});
    if (uppercaseToolbar) uppercaseToolbar.addEventListener('click', function(){ if (!selectedBox) return; selectedBox.textTransform = (selectedBox.textTransform === 'uppercase') ? 'none' : 'uppercase'; draw(); });
    if (colorPickerInput) colorPickerInput.addEventListener('input', function(){ if (selectedBox) { selectedBox.color = this.value; draw(); if (editingTextarea) editingTextarea.style.color = this.value; }});
    // symbol toolbar handler: append a common symbol at the end of text
    const symbolToolbar = document.getElementById('symbolToolbar');
    if (symbolToolbar) symbolToolbar.addEventListener('click', function(){ if (!selectedBox) return; const symbol = '★'; if (typeof selectedBox.text !== 'string') selectedBox.text = ''; selectedBox.text = selectedBox.text + symbol; if (editingTextarea && editingBox === selectedBox) editingTextarea.value = selectedBox.text; showFloatingToolbar(); draw(); });


    // ========================
    // Add Text Box
    // ========================
    function addTextBox(text = "New Text", fontSize = 20, fontWeight = "normal") {
        saveState();
        textBoxes.push({
            text,
            x: 60,
            y: 60,
            w: 180,
            h: 40,
            fontSize,
            fontWeight,
            fontFamily: fonts[0],
            color: "#222"
        });
        // select newly added box and show toolbar
        selectedBox = textBoxes[textBoxes.length - 1];
        draw();
        showFloatingToolbar();
    }

    // ========================
    // Floating Panel: All Tools
    // ========================
    const sidebarItems = document.querySelectorAll(".editor-sidebar li");
    const floatingPanel = document.getElementById("floatingPanel");
    const panels = {
        "Text": `
            <h3>Text Tools</h3>
            <input type="search" id="fontSearch" placeholder="Search fonts...">
            <div class="font-list" id="fontList"></div>
            <label style="display:block;margin:8px 0 4px 0;font-size:13px;">Font Size</label>
            <input type="number" id="fontSizeInput" min="8" max="120" value="20" style="width:70px;margin-bottom:10px;">
            <label style="display:block;margin:8px 0 4px 0;font-size:13px;">Text Color</label>
            <input type="color" id="textColorPicker" value="#222" style="margin-bottom:10px;">
            <div id="colorPalette" style="display:flex;flex-wrap:wrap;margin-bottom:10px;"></div>
            <button class="btn full" id="addTextBox">+ Add Text Box</button>
            <div style="margin-top:10px;">
                <button class="btn full" id="addHeading">Add Heading</button>
                <button class="btn full" id="addSubHeading">Add Sub Heading</button>
                <button class="btn full" id="addBodyText">Add Body Text</button>
            </div>
        `,
        "Images": `
            <h3>Image/Video Tools</h3>
            <input type="search" id="imageSearch" placeholder="Search images...">
            <button class="btn full" id="searchImageBtn">Search Image</button>
            <input type="file" accept="image/*,video/*" id="uploadMedia">
            <button class="btn full" id="uploadMediaBtn">Upload Image/Video</button>
            <div class="font-list" id="mediaList"></div>
            <div id="imageResults" style="margin-top:10px;"></div>
        `,
        "Graphics": `
            <h3>Graphics Tools</h3>
            <input type="search" id="graphicSearch" placeholder="Search graphics...">
            <p><strong>Shapes</strong></p>
            <div class="shape-grid">
                <button title="Circle">⚪</button>
                <button title="Square">⬛</button>
                <button title="Triangle">🔺</button>
                <button title="Line">➖</button>
            </div>
            <p><strong>Icons</strong></p>
            <div class="icon-grid">
                <button>⭐</button>
                <button>❤️</button>
                <button>💍</button>
                <button>🍷</button>
                <button>🎂</button>
                <button>🎶</button>
                <button>🌸</button>
                <button>🌙</button>
                <button>🔥</button>
                <button>💒</button>
            </div>
        `
    };

    sidebarItems.forEach(item => {
        item.addEventListener("click", () => {
            sidebarItems.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
            const panelContent = panels[item.textContent.trim()];
            if (panelContent) {
                floatingPanel.innerHTML = panelContent;
                floatingPanel.style.display = "block";
                // Text Panel
                if (item.textContent.trim() === "Text") {
                    const fontList = document.getElementById("fontList");
                    const fontSearch = document.getElementById("fontSearch");
                    const addTextBoxBtn = document.getElementById("addTextBox");
                    const addHeadingBtn = document.getElementById("addHeading");
                    const addSubHeadingBtn = document.getElementById("addSubHeading");
                    const addBodyTextBtn = document.getElementById("addBodyText");
                    const textColorPicker = document.getElementById("textColorPicker");
                    const fontSizeInput = document.getElementById("fontSizeInput");
                    // Add this function to fetch fonts from Google Fonts API
                    async function fetchFonts() {
                        // Try to load from localStorage first
                        const cachedFonts = localStorage.getItem('googleFontsList');
                        if (cachedFonts) {
                            return JSON.parse(cachedFonts);
                        }
                        try {
                            const response = await fetch('https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyCSdMyA37wm0nt9gJIZSjTrxEHgXwxBMeM');
                            const data = await response.json();
                            const fontFamilies = data.items.map(item => item.family);
                            // Cache for future use
                            localStorage.setItem('googleFontsList', JSON.stringify(fontFamilies));
                            return fontFamilies;
                        } catch (error) {
                            console.error('Failed to fetch fonts:', error);
                            // Fallback list
                            return [
                                "Nunito", "Roboto", "Montserrat", "Poppins", "Oswald", "Playfair Display",
                                "Great Vibes", "Lobster", "Dancing Script", "Merriweather"
                            ];
                        }
                    }
                    (async () => {
                        fonts = await fetchFonts();
                        renderFonts();
                    })();
                    function renderFonts(filter = "") {
                        fontList.innerHTML = "";
                        fonts
                            .filter(f => f.toLowerCase().includes(filter.toLowerCase()))
                            .forEach(f => {
                                const div = document.createElement("div");
                                div.textContent = f;
                                div.style.fontFamily = f;
                                div.style.cursor = "pointer";
                                div.style.padding = "2px 6px";
                                div.style.borderRadius = "4px";
                                div.onmouseover = () => div.style.background = "#e0e7ff";
                                div.onmouseout = () => div.style.background = "";
                                div.addEventListener("click", () => {
                                    if (selectedBox) {
                                        selectedBox.fontFamily = f;
                                        draw();
                                        if (editingTextarea) {
                                            editingTextarea.style.fontFamily = f;
                                        }
                                    }
                                });
                                fontList.appendChild(div);
                            });
                    }
                    renderFonts();
                    fontSearch.addEventListener("input", e => renderFonts(e.target.value));
                    setTimeout(() => fontSearch.focus(), 100);
                    if (textColorPicker) {
                        textColorPicker.addEventListener("input", e => {
                            if (selectedBox) {
                                selectedBox.color = e.target.value;
                                draw();
                                if (editingTextarea) editingTextarea.style.color = e.target.value;
                            }
                        });
                    }
                    if (fontSizeInput) {
                        fontSizeInput.addEventListener("input", e => {
                            if (selectedBox) {
                                selectedBox.fontSize = parseInt(e.target.value, 10) || 20;
                                draw();
                                if (editingTextarea) editingTextarea.style.fontSize = (selectedBox.fontSize * (zoomLevel/100)) + "px";
                            }
                        });
                    }
                    if (addTextBoxBtn) addTextBoxBtn.addEventListener("click", () => addTextBox("New Text", 20, "bold"));
                    if (addHeadingBtn) addHeadingBtn.addEventListener("click", () => addTextBox("Heading Text", 32, "bold"));
                    if (addSubHeadingBtn) addSubHeadingBtn.addEventListener("click", () => addTextBox("Sub Heading", 24, "600"));
                    if (addBodyTextBtn) addBodyTextBtn.addEventListener("click", () => addTextBox("A little bit of body text...", 16, "normal"));
                }
                // Images Panel
                if (item.textContent.trim() === "Images") {
                    const uploadMedia = document.getElementById("uploadMedia");
                    const uploadMediaBtn = document.getElementById("uploadMediaBtn");
                    const mediaList = document.getElementById("mediaList");
                    let uploadedImages = [];

                    uploadMediaBtn.addEventListener("click", () => uploadMedia.click());
                    uploadMedia.addEventListener("change", function(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        const url = URL.createObjectURL(file);
                        if (file.type.startsWith("image/")) {
                            const img = new window.Image();
                            img.onload = function() {
                                mediaBoxes.push({
                                    type: "image",
                                    img,
                                    x: 100, y: 100,
                                    w: 200, h: 150,
                                    locked: false
                                });
                                uploadedImages.push({img, url});
                                draw(); // Only draw after image is loaded
                                renderMediaList();
                            };
                            img.src = url;
                        }
                    });

                    function renderMediaList() {
                        mediaList.innerHTML = "";
                        uploadedImages.forEach((media, idx) => {
                            const thumb = document.createElement("img");
                            thumb.src = media.url;
                            thumb.alt = "Uploaded image preview";
                            thumb.style.width = "48px";
                            thumb.style.height = "48px";
                            thumb.style.objectFit = "cover";
                            thumb.style.margin = "6px";
                            thumb.style.cursor = "pointer";
                            thumb.title = "Insert again";
                            thumb.setAttribute("tabindex", "0");
                            thumb.setAttribute("aria-label", "Insert image");
                            thumb.onclick = () => {
                                mediaBoxes.push({
                                    type: "image",
                                    img: media.img,
                                    x: 120 + Math.random()*60,
                                    y: 120 + Math.random()*60,
                                    w: 200,
                                    h: 150,
                                    locked: false
                                });
                                draw();
                            };
                            mediaList.appendChild(thumb);
                        });
                    }

                    // --- Google Custom Search API Integration ---
                    const imageSearch = document.getElementById("imageSearch");
                    const searchImageBtn = document.getElementById("searchImageBtn");
                    const imageResults = document.getElementById("imageResults");
                    imageResults.style.maxHeight = "260px"; // Set max height for scrolling
                    imageResults.style.overflowY = "auto";  // Enable vertical scroll
                    const GOOGLE_API_KEY = "AIzaSyBRCDdZjTcR4brOsHV_OBsDO11We11BVi0";
                    const GOOGLE_CX = "c5ae3ced1c423443c"; // <-- Replace with your Custom Search Engine ID

                    searchImageBtn.addEventListener("click", async () => {
                        const query = imageSearch.value.trim();
                        if (!query) return;
                        imageResults.innerHTML = "Searching...";
                        try {
                            const res = await fetch(`https://www.googleapis.com/customsearch/v1?key=${GOOGLE_API_KEY}&cx=${GOOGLE_CX}&searchType=image&q=${encodeURIComponent(query)}&num=8`);
                            const data = await res.json();
                            imageResults.innerHTML = "";
                            if (data.items && data.items.length) {
                                data.items.forEach(item => {
                                    const img = document.createElement("img");
                                    img.src = item.link;
                                    img.style.width = "60px";
                                    img.style.height = "60px";
                                    img.style.objectFit = "cover";
                                    img.style.margin = "4px";
                                    img.style.cursor = "pointer";
                                    img.title = "Insert image";
                                    img.onclick = () => {
                                        const canvasImg = new window.Image();
                                        canvasImg.crossOrigin = "anonymous";
                                        canvasImg.onload = function() {
                                            mediaBoxes.push({
                                                type: "image",
                                                img: canvasImg,
                                                x: 120 + Math.random()*60,
                                                y: 120 + Math.random()*60,
                                                w: 200,
                                                h: 150,
                                                locked: false
                                            });
                                            draw();
                                        };
                                        canvasImg.src = item.link;
                                    };
                                    imageResults.appendChild(img);
                                });
                            } else {
                                imageResults.innerHTML = "No images found.";
                            }
                        } catch (err) {
                            imageResults.innerHTML = "Error loading images.";
                        }
                    });
                }
                // Graphics Panel
                if (item.textContent.trim() === "Graphics") {
                    // You can add logic for shapes/icons here
                }
            } else {
                floatingPanel.style.display = "none";
            }
        });
    });
    sidebarItems[0]?.click();

    // Quick Insert Text from sidebar quick panel
    const insertQuickTextBtn = document.getElementById('insertQuickText');
    if (insertQuickTextBtn) {
        insertQuickTextBtn.addEventListener('click', function() {
            const quickTextInput = document.getElementById('quickTextInput');
            const quickFontSelect = document.getElementById('quickFontSelect');
            const quickTextColor = document.getElementById('quickTextColor');
            const text = quickTextInput ? quickTextInput.value.trim() : '';
            const font = quickFontSelect ? quickFontSelect.value : (fonts[0] || 'Arial');
            const color = quickTextColor ? quickTextColor.value : '#222';
            if (!text) return;
            addTextBox(text, 20, 'normal');
            // apply font and color to last added box
            const box = textBoxes[textBoxes.length - 1];
            if (box) {
                box.fontFamily = font;
                box.color = color;
            }
            draw();
        });
    }

    // ========================
    // Layer Controls (Bring Forward / Send Backward)
    // ========================
    function highlightBox(box) {
        // Add a temporary highlight property
        box._highlight = true;
        draw();
        setTimeout(() => {
            box._highlight = false;
            draw();
        }, 400);
    }

    document.getElementById("bringForwardBtn").onclick = () => {
        if (selectedBox) {
            const idx = textBoxes.indexOf(selectedBox);
            if (idx < textBoxes.length - 1) {
                [textBoxes[idx], textBoxes[idx+1]] = [textBoxes[idx+1], textBoxes[idx]];
                highlightBox(selectedBox);
            }
        }
        if (selectedMediaBox) {
            const idx = mediaBoxes.indexOf(selectedMediaBox);
            if (idx < mediaBoxes.length - 1) {
                [mediaBoxes[idx], mediaBoxes[idx+1]] = [mediaBoxes[idx+1], mediaBoxes[idx]];
                highlightBox(selectedMediaBox);
            }
        }
        draw();
    };
    document.getElementById("sendBackwardBtn").onclick = () => {
        if (selectedBox) {
            const idx = textBoxes.indexOf(selectedBox);
            if (idx > 0) {
                [textBoxes[idx], textBoxes[idx-1]] = [textBoxes[idx-1], textBoxes[idx]];
                highlightBox(selectedBox);
            }
        }
        if (selectedMediaBox) {
            const idx = mediaBoxes.indexOf(selectedMediaBox);
            if (idx > 0) {
                [mediaBoxes[idx], mediaBoxes[idx-1]] = [mediaBoxes[idx-1], mediaBoxes[idx]];
                highlightBox(selectedMediaBox);
            }
        }
        draw();
    };

    // In your draw() function, add highlight effect:
    textBoxes.forEach(box => {
        ctx.save();
        if (box._highlight) {
            ctx.shadowColor = "#8c52ff";
            ctx.shadowBlur = 16;
        }
        // ...existing drawing code...
        ctx.restore();
    });
    mediaBoxes.forEach(box => {
        ctx.save();
        if (box._highlight) {
            ctx.shadowColor = "#8c52ff";
            ctx.shadowBlur = 16;
        }
        // ...existing drawing code...
        ctx.restore();
    });

    // ========================
    // Export / Download as Image
    // ========================
    document.getElementById("exportBtn").addEventListener("click", () => {
        const imgData = canvas.toDataURL("image/png");
        const link = document.createElement('a');
        link.href = imgData;
        link.download = "template.png";
        link.click();
    });

    // ========================
    // Export / Download as PDF
    // ========================
    document.getElementById("exportPdfBtn").addEventListener("click", () => {
        const pdf = new jsPDF({
            orientation: "portrait",
            unit: "px",
            format: [canvas.width, canvas.height]
        });
        pdf.addImage(canvas.toDataURL("image/png"), "PNG", 0, 0, canvas.width, canvas.height);
        pdf.save("template.pdf");
    });

    // ========================
    // Coordinate Display
    // ========================
    const coordDisplay = document.createElement('div');
    coordDisplay.style.position = 'absolute';
    coordDisplay.style.right = '10px';
    coordDisplay.style.top = '10px';
    coordDisplay.style.background = '#fff';
    coordDisplay.style.padding = '2px 8px';
    coordDisplay.style.borderRadius = '4px';
    coordDisplay.style.fontSize = '12px';
    coordDisplay.style.boxShadow = '0 1px 3px rgba(0,0,0,.08)';
    coordDisplay.style.zIndex = 100;
    canvas.parentNode.appendChild(coordDisplay);

    canvas.addEventListener('mousemove', function(e) {
        const rect = canvas.getBoundingClientRect();
        const mouseX = Math.round((e.clientX - rect.left) * (canvas.width / rect.width));
        const mouseY = Math.round((e.clientY - rect.top) * (canvas.height / rect.height));
        coordDisplay.textContent = `X: ${mouseX}, Y: ${mouseY}`;
    });
    canvas.addEventListener('mouseleave', function() {
        coordDisplay.textContent = '';
    });

    // ========================
    // Draggable Floating Panel
    // ========================
    let isDraggingPanel = false, panelOffsetX = 0, panelOffsetY = 0;
    floatingPanel.addEventListener('mousedown', function(e) {
        if (e.target === floatingPanel) {
            isDraggingPanel = true;
            panelOffsetX = e.offsetX;
            panelOffsetY = e.offsetY;
        }
    });
    document.addEventListener('mousemove', function(e) {
        if (isDraggingPanel) {
            floatingPanel.style.left = (e.clientX - panelOffsetX) + 'px';
            floatingPanel.style.top = (e.clientY - panelOffsetY) + 'px';
        }
    });
    document.addEventListener('mouseup', function() {
        isDraggingPanel = false;
    });

    function overlayMediaIcons() {
        // Remove old icons
        document.querySelectorAll('.canvas-icon').forEach(el => el.remove());
        mediaBoxes.forEach((box, idx) => {
            const scale = zoomLevel / 100;
            const left = canvas.offsetLeft + (box.x + box.w - 32) * scale;
            const top = canvas.offsetTop + (box.y + 8) * scale;

            // Lock/Unlock icon
            const lockIcon = document.createElement('i');
            lockIcon.className = `canvas-icon fi ${box.locked ? 'fi-sr-lock' : 'fi-sr-unlock'}`;
            lockIcon.style.position = 'absolute';
            lockIcon.style.left = left + 'px';
            lockIcon.style.top = top + 'px';
            lockIcon.style.fontSize = '32px';
            lockIcon.style.color = '#2563eb';
            lockIcon.style.zIndex = 20;
            lockIcon.style.cursor = 'pointer';
            lockIcon.title = box.locked ? "Unlock" : "Lock";
lockIcon.setAttribute("aria-label", box.locked ? "Unlock" : "Lock");

            // Cross-circle icon (delete)
            const crossIcon = document.createElement('i');
            crossIcon.className = 'canvas-icon fi fi-sr-cross-circle';
            crossIcon.style.position = 'absolute';
            crossIcon.style.left = (canvas.offsetLeft + box.x * scale + 4) + 'px';
            crossIcon.style.top = (canvas.offsetTop + box.y * scale + 4) + 'px';
            crossIcon.style.fontSize = '32px';
            crossIcon.style.color = '#e11d48';
            crossIcon.style.zIndex = 20;
            crossIcon.style.cursor = 'pointer';
            crossIcon.title = "Delete";
crossIcon.setAttribute("aria-label", "Delete");

            canvas.parentNode.appendChild(lockIcon);
            canvas.parentNode.appendChild(crossIcon);
        });
    }

    document.addEventListener("keydown", function(e) {
        // Ctrl+Z for Undo
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "z") {
            e.preventDefault();
            document.getElementById("undoBtn")?.click();
        }
        // Ctrl+Y for Redo
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "y") {
            e.preventDefault();
            document.getElementById("redoBtn")?.click();
        }
    });
    
    // Load preview image and design data if available
    const bootstrapScript = document.getElementById('inkwise-builder-bootstrap');
    if (bootstrapScript) {
        try {
            const bootstrapData = JSON.parse(bootstrapScript.textContent);
            
            // Load preview image
            const previewUrl = bootstrapData.template?.preview_front;
            if (previewUrl) {
                previewImage = new Image();
                previewImage.onload = function() {
                    draw(); // Redraw canvas when preview image loads
                };
                previewImage.src = previewUrl;
            }
            
            // Load SVG overlay
            const svgUrl = bootstrapData.template?.svg_path;
            if (svgUrl) {
                svgImage = new Image();
                svgImage.onload = function() {
                    draw(); // Redraw canvas when SVG loads
                };
                svgImage.src = svgUrl;
            }
            
            // Load design data
            const designData = bootstrapData.template?.design;
            if (designData && designData.pages) {
                loadDesignData(designData);
            }
        } catch (error) {
            console.error('Failed to load bootstrap data:', error);
        }
    }
    
});
console.log(window.TEMPLATE_ID, window.CSRF_TOKEN);
// Add this at the top-level scope, outside DOMContentLoaded
window.addImageToCanvas = function(img) {
    // Default position and size for new images
    const x = 100 + Math.random() * 80;
    const y = 100 + Math.random() * 80;
    const w = Math.min(img.width, 200);
    const h = Math.min(img.height, 150);

    // Add to mediaBoxes array
    mediaBoxes.push({
        type: "image",
        img: img,
        x: x,
        y: y,
        w: w,
        h: h,
        locked: false
    });

    draw();
};

