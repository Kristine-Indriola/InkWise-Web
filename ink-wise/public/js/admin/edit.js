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
            // Draw resize handle
            ctx.fillStyle = "#2563eb";
            ctx.fillRect(
                box.x * (zoomLevel/100) + box.w * (zoomLevel/100) - 8,
                box.y * (zoomLevel/100) + box.h * (zoomLevel/100) - 8,
                8, 8
            );
            // Draw lock/unlock icon (simple padlock emoji)
            ctx.font = "18px Arial";
            ctx.textAlign = "right";
            ctx.textBaseline = "top";
            ctx.fillStyle = "#555";
            ctx.fillText(
                box.locked ? "🔒" : "🔓",
                (box.x + box.w) * (zoomLevel/100) - 10,
                box.y * (zoomLevel/100) + 2
            );
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
        draw();
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
                                draw();
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
                            thumb.style.width = "40px";
                            thumb.style.height = "40px";
                            thumb.style.objectFit = "cover";
                            thumb.style.margin = "4px";
                            thumb.style.cursor = "pointer";
                            thumb.title = "Insert again";
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
    // Save & Next Button
    // ========================
    document.getElementById('saveBtn')?.addEventListener('click', function() {
        const imgData = canvas.toDataURL("image/png");
        console.log("Saving...", window.TEMPLATE_ID, window.CSRF_TOKEN, window.TEMPLATES_INDEX_URL);
        fetch(`/admin/templates/${window.TEMPLATE_ID}/save-canvas`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.CSRF_TOKEN
            },
            body: JSON.stringify({ canvas_image: imgData })
        })
        .then(res => res.json())
        .then(data => {
            console.log("Save response:", data);
            if (data.success) {
                alert('Template saved! Returning to templates list...');
                window.location.href = window.TEMPLATES_INDEX_URL;
            } else {
                alert('Save failed!');
            }
        });
    });

    document.getElementById('nextBtn')?.addEventListener('click', function() {
        window.location.href = window.TEMPLATES_INDEX_URL;
    });
});