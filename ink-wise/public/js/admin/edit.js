document.addEventListener("DOMContentLoaded", () => {
    // ========================
    // Canvas & Zoom Controls
    // ========================
    const zoomOutBtn = document.querySelector(".zoom-controls button:first-child");
    const zoomInBtn = document.querySelector(".zoom-controls button:last-child");
    const zoomDisplay = document.querySelector(".zoom-controls span");
    const canvas = document.getElementById("templateCanvas");
    const ctx = canvas.getContext("2d");

    let zoomLevel = 100;
    function updateZoom() {
        canvas.style.transform = `scale(${zoomLevel / 100})`;
        zoomDisplay.textContent = `${zoomLevel}%`;
        repositionEditor();
        repositionMediaEditors();
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
    const fonts = [
        "Nunito", "Playfair Display", "Montserrat", "Roboto",
        "Great Vibes", "Poppins", "Lobster", "Dancing Script",
        "Merriweather", "Oswald", "ITC Edwardian Script", "Eyesome Script"
    ];
    let textBoxes = [];
    let mediaBoxes = [];
    let selectedBox = null;
    let selectedMediaBox = null;
    let dragging = false, resizing = false, dragOffset = {x:0, y:0}, dragBox = null, dragType = null;
    let draggingMedia = false, resizingMedia = false, dragOffsetMedia = {x:0, y:0}, dragMediaBox = null;
    let editingTextarea = null, editingBox = null;

    // ========================
    // Drawing
    // ========================
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
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
            ctx.font = `${box.fontWeight} ${box.fontSize * (zoomLevel/100)}px ${box.fontFamily}`;
            ctx.fillStyle = box.color;
            ctx.textBaseline = "top";
            ctx.textAlign = "left";
            ctx.save();
            ctx.beginPath();
            ctx.rect(
                box.x * (zoomLevel/100) + 8,
                box.y * (zoomLevel/100) + 8,
                (box.w - 16) * (zoomLevel/100),
                (box.h - 16) * (zoomLevel/100)
            );
            ctx.clip();
            ctx.fillText(
                box.text,
                box.x * (zoomLevel/100) + 8,
                box.y * (zoomLevel/100) + 8,
                (box.w - 16) * (zoomLevel/100)
            );
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
            // Draw resize handle
            ctx.fillStyle = "#2563eb";
            ctx.fillRect(
                box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 8,
                box.y * (zoomLevel/100) + box.h * (zoomLevel/100) - 8,
                8, 8
            );
            // Draw lock/unlock icon (simple padlock emoji)
            ctx.font = "20px Arial";
            ctx.textAlign = "right";
            ctx.textBaseline = "top";
            ctx.fillText(
                box.locked ? "🔒" : "🔓",
                box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 10,
                box.y * (zoomLevel/100) + 2
            );
            ctx.restore();
        });
    }

    // ========================
    // Add Text Box
    // ========================
    function addTextBox(text, fontSize, fontWeight) {
        const box = {
            text,
            x: 100 + Math.random()*100,
            y: 100 + Math.random()*100,
            w: 220,
            h: 48,
            fontSize,
            fontWeight,
            fontFamily: fonts[0],
            color: "#222"
        };
        textBoxes.push(box);
        selectedBox = box;
        draw();
    }

    // ========================
    // Mouse Events: Drag, Resize, Select (Text)
    // ========================
    canvas.addEventListener("mousedown", function(e) {
        if (editingTextarea) return;
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        // Check for delete (X) icon click on text boxes
        for (let i = textBoxes.length - 1; i >= 0; i--) {
            const box = textBoxes[i];
            const iconX = box.x + box.w - 24;
            const iconY = box.y + 4;
            if (
                mx > iconX && mx < iconX + 20 &&
                my > iconY && my < iconY + 20
            ) {
                
                textBoxes.splice(i, 1);
                selectedBox = null;
                draw();
                return;
            }
        }

        selectedBox = null;
        dragType = null;
        dragBox = null;
        dragging = false;
        resizing = false;

        for (let i = textBoxes.length - 1; i >= 0; i--) {
            const box = textBoxes[i];
            // Resize handle
            if (mx > box.x + box.w - 8 && mx < box.x + box.w && my > box.y + box.h - 8 && my < box.y + box.h) {
                selectedBox = box;
                resizing = true;
                dragBox = box;
                dragType = "resize";
                dragOffset = {x: mx - box.x, y: my - box.y};
                draw();
                return;
            }
            // Inside box
            if (mx > box.x && mx < box.x + box.w && my > box.y && my < box.y + box.h) {
                selectedBox = box;
                dragging = true;
                dragType = "move";
                dragBox = box;
                dragOffset = {x: mx - box.x, y: my - box.y};
                draw();
                showTextBoxControls(box);
                return;
            }
        }
        draw();
        hideTextBoxControls();
    });
    canvas.addEventListener("mousemove", function(e) {
        if (!dragging && !resizing) return;
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        if (dragging && dragType === "move" && dragBox) {
            dragBox.x = mx - dragOffset.x;
            dragBox.y = my - dragOffset.y;
            draw();
        }
        if (resizing && dragBox) {
            dragBox.w = Math.max(60, mx - dragBox.x);
            dragBox.h = Math.max(28, my - dragBox.y);
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

    // ========================
    // Mouse Events: Drag, Resize, Select (Media)
    // ========================
    canvas.addEventListener("mousedown", function(e) {
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        // Check for lock icon click on media boxes
        for (let i = mediaBoxes.length - 1; i >= 0; i--) {
            const box = mediaBoxes[i];
            const iconX = box.x + box.w - 24;
            const iconY = box.y + 2;
            if (
                mx > iconX && mx < iconX + 22 &&
                my > iconY && my < iconY + 22
            ) {
                box.locked = !box.locked;
                draw();
                return;
            }
        }

        // ...existing code for selecting/moving/resizing...
        // Only allow drag/resize if not locked
        selectedMediaBox = null;
        draggingMedia = false;
        resizingMedia = false;
        dragMediaBox = null;

        for (let i = mediaBoxes.length - 1; i >= 0; i--) {
            const box = mediaBoxes[i];
            if (box.locked) continue; // Skip locked boxes
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
    });
    canvas.addEventListener("mousemove", function(e) {
        if (!draggingMedia && !resizingMedia) return;
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        if (draggingMedia && dragMediaBox) {
            dragMediaBox.x = mx - dragOffsetMedia.x;
            dragMediaBox.y = my - dragOffsetMedia.y;
            if (dragMediaBox.type === "video" && dragMediaBox.video) {
                const scale = zoomLevel/100;
                dragMediaBox.video.style.left = (dragMediaBox.x * scale) + "px";
                dragMediaBox.video.style.top = (dragMediaBox.y * scale) + "px";
            }
            draw();
        }
        if (resizingMedia && dragMediaBox) {
            dragMediaBox.w = Math.max(40, mx - dragMediaBox.x);
            dragMediaBox.h = Math.max(30, my - dragMediaBox.y);
            if (dragMediaBox.type === "video" && dragMediaBox.video) {
                const scale = zoomLevel/100;
                dragMediaBox.video.style.width = (dragMediaBox.w * scale) + "px";
                dragMediaBox.video.style.height = (dragMediaBox.h * scale) + "px";
            }
            draw();
        }
    });

    // ========================
    // Inline Editing (Text)
    // ========================
    canvas.addEventListener("dblclick", function(e) {
        if (editingTextarea) return;
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width) / (zoomLevel/100);
        const my = (e.clientY - rect.top) * (canvas.height / rect.height) / (zoomLevel/100);

        for (let i = textBoxes.length - 1; i >= 0; i--) {
            const box = textBoxes[i];
            if (
                mx > box.x && mx < box.x + box.w &&
                my > box.y && my < box.y + box.h
            ) {
                selectedBox = box;
                editingBox = box;
                showInlineEditor(box);
                return;
            }
        }
    });
    function showInlineEditor(box) {
        if (editingTextarea) {
            document.body.removeChild(editingTextarea);
            editingTextarea = null;
        }
        const canvasRect = canvas.getBoundingClientRect();
        const scale = zoomLevel/100;
        const textarea = document.createElement("textarea");
        textarea.value = box.text;
        textarea.style.position = "absolute";
        textarea.style.left = (canvasRect.left + window.scrollX + box.x * scale) + "px";
        textarea.style.top = (canvasRect.top + window.scrollY + box.y * scale) + "px";
        textarea.style.width = (box.w * scale) + "px";
        textarea.style.height = (box.h * scale) + "px";
        textarea.style.fontSize = (box.fontSize * scale) + "px";
        textarea.style.fontFamily = box.fontFamily;
        textarea.style.fontWeight = box.fontWeight;
        textarea.style.color = box.color;
        textarea.style.background = "rgba(255,255,255,0.9)";
        textarea.style.border = "2px solid #2563eb";
        textarea.style.zIndex = 300;
        textarea.style.resize = "none";
        textarea.style.overflow = "hidden";
        document.body.appendChild(textarea);
        textarea.focus();
        editingTextarea = textarea;

        function saveEdit() {
            box.text = textarea.value;
            document.body.removeChild(textarea);
            editingTextarea = null;
            editingBox = null;
            draw();
        }

        textarea.addEventListener("blur", saveEdit);
        textarea.addEventListener("keydown", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                saveEdit();
            }
        });
    }
    function repositionEditor() {
        if (editingTextarea && editingBox) {
            const canvasRect = canvas.getBoundingClientRect();
            const scale = zoomLevel/100;
            editingTextarea.style.left = (canvasRect.left + window.scrollX + editingBox.x * scale) + "px";
            editingTextarea.style.top = (canvasRect.top + window.scrollY + editingBox.y * scale) + "px";
            editingTextarea.style.width = (editingBox.w * scale) + "px";
            editingTextarea.style.height = (editingBox.h * scale) + "px";
            editingTextarea.style.fontSize = (editingBox.fontSize * scale) + "px";
        }
    }
    function repositionMediaEditors() {
        mediaBoxes.forEach(box => {
            if (box.type === "video" && box.video) {
                const scale = zoomLevel/100;
                box.video.style.left = (box.x * scale) + "px";
                box.video.style.top = (box.y * scale) + "px";
                box.video.style.width = (box.w * scale) + "px";
                box.video.style.height = (box.h * scale) + "px";
            }
        });
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
            <input type="color" id="textColorPicker" value="#222" style="margin-bottom:10px;">
            <button class="btn full" id="addTextBox">+ Add Text Box</button>
            <div style="margin-top:10px;">
                <button class="btn full" id="addHeading">Add Heading</button>
                <button class="btn full" id="addSubHeading">Add Sub Heading</button>
                <button class="btn full" id="addBodyText">Add Body Text</button>
            </div>
        `,
        "Images": `
            <h3>Image/Video Tools</h3>
            <input type="file" accept="image/*,video/*" id="uploadMedia">
            <button class="btn full" id="uploadMediaBtn">Upload Image/Video</button>
            <div class="font-list" id="mediaList"></div>
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
        `,
        "Tables": `
            <h3>Table Tools</h3>
            <button class="btn full" id="addGuestListTable">+ Guest List Table</button>
            <button class="btn full" id="addSeatingChart">+ Seating Chart</button>
        `,
        "Colors": `
            <h3>Color Tools</h3>
            <input type="color" id="colorPicker" value="#2563eb">
            <button class="btn full" id="applyColor">Apply Color</button>
            <div class="shape-grid">
                <button style="background:#f87171"></button>
                <button style="background:#34d399"></button>
                <button style="background:#60a5fa"></button>
                <button style="background:#fbbf24"></button>
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
                    textColorPicker.addEventListener("input", e => {
                        if (selectedBox) {
                            selectedBox.color = e.target.value;
                            draw();
                            if (editingTextarea) editingTextarea.style.color = e.target.value;
                        }
                    });
                    fontSizeInput.addEventListener("input", e => {
                        if (selectedBox) {
                            selectedBox.fontSize = parseInt(e.target.value, 10) || 20;
                            draw();
                            if (editingTextarea) editingTextarea.style.fontSize = (selectedBox.fontSize * (zoomLevel/100)) + "px";
                        }
                    });
                    addTextBoxBtn.addEventListener("click", () => addTextBox("New Text", 20, "bold"));
                    addHeadingBtn.addEventListener("click", () => addTextBox("Heading Text", 32, "bold"));
                    addSubHeadingBtn.addEventListener("click", () => addTextBox("Sub Heading", 24, "600"));
                    addBodyTextBtn.addEventListener("click", () => addTextBox("A little bit of body text...", 16, "normal"));
                }
                // Images Panel
                if (item.textContent.trim() === "Images") {
                    const uploadMedia = document.getElementById("uploadMedia");
                    const uploadMediaBtn = document.getElementById("uploadMediaBtn");
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
                                draw();
                            };
                            img.src = url;
                        } else if (file.type.startsWith("video/")) {
                            const video = document.createElement("video");
                            video.src = url;
                            video.controls = true;
                            video.autoplay = false;
                            video.loop = false;
                            video.style.position = "absolute";
                            video.style.left = "100px";
                            video.style.top = "100px";
                            video.style.width = "200px";
                            video.style.height = "150px";
                            video.style.zIndex = 400;
                            document.body.appendChild(video);
                            mediaBoxes.push({
                                type: "video",
                                video,
                                x: 100, y: 100,
                                w: 200, h: 150,
                                locked: false
                            });
                            draw();
                        }
                    });
                }
                // Graphics Panel
                if (item.textContent.trim() === "Graphics") {
                    // You can add logic for shapes/icons here
                }
                // Tables Panel
                if (item.textContent.trim() === "Tables") {
                    document.getElementById("addGuestListTable").onclick = () => alert("Guest List Table added (stub)");
                    document.getElementById("addSeatingChart").onclick = () => alert("Seating Chart added (stub)");
                }
                // Colors Panel
                if (item.textContent.trim() === "Colors") {
                    const colorPicker = document.getElementById("colorPicker");
                    document.getElementById("applyColor").onclick = () => {
                        if (selectedBox) {
                            selectedBox.color = colorPicker.value;
                            draw();
                        }
                    };
                }
            } else {
                floatingPanel.style.display = "none";
            }
        });
    });
    sidebarItems[0]?.click();

    // ========================
    // Layer Controls (Bring Forward / Send Backward)
    // ========================
    document.getElementById("bringForwardBtn").onclick = () => {
        if (selectedBox) {
            const idx = textBoxes.indexOf(selectedBox);
            if (idx < textBoxes.length - 1) {
                [textBoxes[idx], textBoxes[idx+1]] = [textBoxes[idx+1], textBoxes[idx]];
                draw();
            }
        }
        if (selectedMediaBox) {
            const idx = mediaBoxes.indexOf(selectedMediaBox);
            if (idx < mediaBoxes.length - 1) {
                [mediaBoxes[idx], mediaBoxes[idx+1]] = [mediaBoxes[idx+1], mediaBoxes[idx]];
                draw();
            }
        }
    };
    document.getElementById("sendBackwardBtn").onclick = () => {
        if (selectedBox) {
            const idx = textBoxes.indexOf(selectedBox);
            if (idx > 0) {
                [textBoxes[idx], textBoxes[idx-1]] = [textBoxes[idx-1], textBoxes[idx]];
                draw();
            }
        }
        if (selectedMediaBox) {
            const idx = mediaBoxes.indexOf(selectedMediaBox);
            if (idx > 0) {
                [mediaBoxes[idx], mediaBoxes[idx-1]] = [mediaBoxes[idx-1], mediaBoxes[idx]];
                draw();
            }
        }
    };

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

    // ========================
    // Initial draw
    // ========================
    draw();
});