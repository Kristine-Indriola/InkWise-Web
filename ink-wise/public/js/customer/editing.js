document.addEventListener("DOMContentLoaded", () => {
  const frontBtn = document.getElementById("showFront");
  const backBtn = document.getElementById("showBack");
  const viewToggleButtons = document.querySelectorAll(".view-toggle button");
  const cardFront = document.getElementById("cardFront");
  const cardBack = document.getElementById("cardBack");

  const zoomInBtn = document.getElementById("zoomIn");
  const zoomOutBtn = document.getElementById("zoomOut");
  const zoomLevel = document.getElementById("zoomLevel");
  const canvas = document.querySelector(".canvas");

  const textFieldsContainer = document.getElementById("textFields");
  const addTextBtn = document.getElementById("addTextField");
  const sideButtons = document.querySelectorAll(".side-btn");
  const panels = document.querySelectorAll(".editor-panel");
  const imageInputs = document.querySelectorAll("[data-image-input]");
  const resetImageButtons = document.querySelectorAll("[data-reset-image]");
  const imagePreviews = {
    front: document.querySelector('[data-image-preview="front"]'),
    back: document.querySelector('[data-image-preview="back"]'),
  };

  const SVG_NS = "http://www.w3.org/2000/svg";
  const XLINK_NS = "http://www.w3.org/1999/xlink";
  const ZOOM_MIN = 0.5;
  const ZOOM_MAX = 2;
  const ZOOM_STEP = 0.1;
  const TEXT_BASE_TOP = 24;
  const TEXT_SPACING = 12;
  const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB limit

  const imageState = {
    front: {
      defaultSrc: imagePreviews.front?.dataset?.defaultSrc || cardFront?.dataset?.defaultImage || "",
      currentSrc: "",
    },
    back: {
      defaultSrc: imagePreviews.back?.dataset?.defaultSrc || cardBack?.dataset?.defaultImage || "",
      currentSrc: "",
    },
  };

  const imageLayers = {
    front: null,
    back: null,
  };

  let zoom = 1.0;
  let currentView = "front";

  const svgTextCache = {
    front: new Map(),
    back: new Map(),
  };

  let inlineEditor = null;
  let inlineEditorNode = null;
  let inlineEditorSide = null;

  function escapeAttr(value) {
    if (typeof CSS !== "undefined" && CSS.escape) {
      return CSS.escape(value);
    }
    return String(value).replace(/"/g, '\"').replace(/\]/g, "\\]");
  }

  function getFieldSide(input) {
    return (input?.dataset?.cardSide || "front").toLowerCase();
  }

  function getTextNodeKey(input) {
    return (input?.dataset?.textNode || "").trim();
  }

  function getSvgRoot(side) {
    const card = side === "front" ? cardFront : cardBack;
    if (!card) return null;
    return card.querySelector("svg");
  }

  function ensureImageLayer(side) {
    if (imageLayers[side]) {
      return imageLayers[side];
    }

    const svg = getSvgRoot(side);
    if (!svg) return null;

    let layer = svg.querySelector(`image[data-editable-image="${side}"]`);
    if (!layer) {
      layer = document.createElementNS(SVG_NS, "image");
      layer.setAttribute("data-editable-image", side);
      layer.setAttribute("x", "0");
      layer.setAttribute("y", "0");
      layer.setAttribute("width", "100%");
      layer.setAttribute("height", "100%");
      layer.setAttribute("preserveAspectRatio", "xMidYMid slice");
      layer.style.display = "none";

      const firstTextNode = svg.querySelector("[data-text-node]");
      if (firstTextNode) {
        svg.insertBefore(layer, firstTextNode);
      } else {
        svg.appendChild(layer);
      }
    }

    imageLayers[side] = layer;
    return layer;
  }

  function setImageElementSource(element, src) {
    if (!element) return;
    if (src) {
      element.setAttribute("href", src);
      element.setAttributeNS(XLINK_NS, "xlink:href", src);
      element.style.removeProperty("display");
    } else {
      element.removeAttribute("href");
      element.setAttributeNS(XLINK_NS, "xlink:href", "");
      element.style.display = "none";
    }
  }

  function updatePreview(side, src) {
    const preview = imagePreviews[side];
    if (!preview) return;
    const fallback = imageState[side]?.defaultSrc || preview.dataset.defaultSrc || preview.src;
    preview.src = src || fallback;
  }

  function applyImage(side, src, { skipPreview = false } = {}) {
    if (!imageState[side]) return;

    const layer = ensureImageLayer(side);
    imageState[side].currentSrc = src || "";
    const displaySrc = imageState[side].currentSrc || imageState[side].defaultSrc || "";
    setImageElementSource(layer, displaySrc);

    const card = side === "front" ? cardFront : cardBack;
    if (card) {
      card.dataset.currentImage = displaySrc;
    }

    if (!skipPreview) {
      updatePreview(side, displaySrc);
    } else if (imagePreviews[side] && !imagePreviews[side].getAttribute("src")) {
      updatePreview(side, displaySrc);
    }
  }

  function resetImage(side) {
    if (!imageState[side]) return;
    imageState[side].currentSrc = "";
    applyImage(side, "");
    const input = document.querySelector(`[data-image-input="${side}"]`);
    if (input) {
      input.value = "";
    }
  }

  function showPanel(panelName) {
    panels.forEach(panel => {
      panel.classList.toggle("active", panel.dataset.panel === panelName);
    });
  }

  function parseViewBox(svg) {
    const vb = svg?.getAttribute("viewBox");
    if (!vb) {
      const width = svg?.clientWidth || 0;
      const height = svg?.clientHeight || 0;
      return { x: 0, y: 0, width, height };
    }
    const parts = vb.trim().split(/\s+/).map(Number);
    if (parts.length === 4 && parts.every(n => Number.isFinite(n))) {
      return { x: parts[0], y: parts[1], width: parts[2], height: parts[3] };
    }
    return { x: 0, y: 0, width: svg.clientWidth || 0, height: svg.clientHeight || 0 };
  }

  function applyPositionToText(node, input, side) {
    if (!node || !input) return;
    const svg = getSvgRoot(side);
    if (!svg) return;
    const metrics = parseViewBox(svg);
    const topPercent = Number.parseFloat(input.dataset.topPercent ?? "");
    const leftPercent = Number.parseFloat(input.dataset.leftPercent ?? "");
    const align = (input.dataset.align || "center").toLowerCase();

    if (Number.isFinite(leftPercent)) {
      const x = metrics.x + (metrics.width * leftPercent) / 100;
      node.setAttribute("x", x);
      node.dataset.originalX = x;
    }

    if (Number.isFinite(topPercent)) {
      const y = metrics.y + (metrics.height * topPercent) / 100;
      node.setAttribute("y", y);
      node.dataset.originalY = y;
    }

    const anchors = {
      left: "start",
      center: "middle",
      right: "end",
    };
    node.setAttribute("text-anchor", anchors[align] || "middle");
    node.setAttribute("dominant-baseline", "middle");

    if (input.dataset.fontSize) {
      node.setAttribute("font-size", input.dataset.fontSize);
    }
    if (input.dataset.letterSpacing) {
      node.setAttribute("letter-spacing", input.dataset.letterSpacing);
    }
  }

  function setSvgNodeText(node, value) {
    if (!node) return;
    const lines = String(value ?? "").split(/\r?\n/);
    while (node.firstChild) node.removeChild(node.firstChild);

    const originalX = node.dataset.originalX || node.getAttribute("x") || null;
    const originalY = node.dataset.originalY || node.getAttribute("y") || null;

    lines.forEach((line, idx) => {
      const tspan = document.createElementNS(SVG_NS, "tspan");
      if (originalX) tspan.setAttribute("x", originalX);
      if (idx === 0 && originalY) {
        tspan.setAttribute("y", originalY);
      } else if (idx > 0) {
        tspan.setAttribute("dy", "1.2em");
      }
      tspan.textContent = line || "\u00A0";
      node.appendChild(tspan);
    });
  }

  function getSvgNodeValue(node) {
    if (!node) return "";
    const tspans = node.querySelectorAll("tspan");
    if (tspans.length === 0) {
      return (node.textContent ?? "").replace(/\u00A0/g, "");
    }
    return Array.from(tspans)
      .map(tspan => (tspan.textContent ?? "").replace(/\u00A0/g, ""))
      .join("\n");
  }

  function closeInlineEditor(commit = true) {
    if (!inlineEditor) return;
    const editor = inlineEditor;
    const node = inlineEditorNode;
    const side = inlineEditorSide;
    const nodeKey = node?.getAttribute("data-text-node") || "";

    if (commit && node) {
      const value = editor.value;
      setSvgNodeText(node, value);
      const safeKey = escapeAttr(nodeKey);
      const input = textFieldsContainer?.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
      if (input && input.value !== value) {
        input.value = value;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }

    if (node) {
      node.classList.remove("svg-text-focus");
    }
    if (nodeKey && side) {
      syncFieldHighlight(nodeKey, side, false);
    }

    editor.remove();
    inlineEditor = null;
    inlineEditorNode = null;
    inlineEditorSide = null;
  }

  function startInlineEditor(node, side) {
    if (!node) return;
    const nodeKey = node.getAttribute("data-text-node") || "";
    if (!nodeKey || !textFieldsContainer) return;

    if (currentView !== side) {
      setActiveView(side);
    }

    const safeKey = escapeAttr(nodeKey);
    const input = textFieldsContainer.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
    if (input) {
      requestAnimationFrame(() => {
        try {
          input.focus({ preventScroll: true });
        } catch (err) {
          input.focus();
        }
        input.select?.();
      });
    }
  }

  function prepareSvgNode(node, side) {
    const nodeKey = node.getAttribute("data-text-node");
    if (!nodeKey) return;
    if (node.dataset.prepared === side) {
      svgTextCache[side].set(nodeKey, node);
      return;
    }
    svgTextCache[side].set(nodeKey, node);

    if (!node.dataset.originalX && node.hasAttribute("x")) {
      node.dataset.originalX = node.getAttribute("x");
    }
    if (!node.dataset.originalY && node.hasAttribute("y")) {
      node.dataset.originalY = node.getAttribute("y");
    }

    node.setAttribute("contenteditable", "true");
    node.setAttribute("role", "textbox");
    node.setAttribute("spellcheck", "false");
    if (!node.hasAttribute("aria-label")) {
      node.setAttribute("aria-label", "Edit canvas text");
    }

    node.addEventListener("focus", () => {
      if (currentView !== side) setActiveView(side);
      node.classList.add("svg-text-focus");
      syncFieldHighlight(nodeKey, side, true);
    });

    node.addEventListener("blur", () => {
      node.classList.remove("svg-text-focus");
      syncFieldHighlight(nodeKey, side, false);
    });

    node.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        startInlineEditor(node, side);
      } else if (event.key === "Backspace" || event.key === "Delete") {
        event.preventDefault();
        startInlineEditor(node, side);
      } else if (!event.ctrlKey && !event.metaKey && !event.altKey && event.key.length === 1) {
        event.preventDefault();
        startInlineEditor(node, side);
      }
    });

    node.addEventListener("input", () => handleSvgNodeInput(node, side));

    node.addEventListener("pointerdown", (event) => {
      event.preventDefault();
      event.stopPropagation();
      startInlineEditor(node, side);
    });

    node.dataset.prepared = side;
  }

  function cacheSvgNodes(cardElement, side) {
    if (!cardElement) return;
    const nodes = cardElement.querySelectorAll("[data-text-node]");
    nodes.forEach(node => prepareSvgNode(node, side));
  }

  function syncFieldHighlight(nodeKey, side, highlight) {
    if (!textFieldsContainer) return;
    const safeKey = escapeAttr(nodeKey);
    const input = textFieldsContainer.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
    if (!input) return;
    const wrapper = input.closest(".text-field");
    if (!wrapper) return;
    wrapper.classList.toggle("is-active", highlight);
  }

  function handleSvgNodeInput(node, side) {
    const nodeKey = node.getAttribute("data-text-node") || "";
    const safeKey = escapeAttr(nodeKey);
    const input = textFieldsContainer?.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
    const value = getSvgNodeValue(node);

    if (input && input.value !== value) {
      input.value = value;
      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }
  }

  function ensureSvgNodeForInput(input) {
    const side = getFieldSide(input);
    const nodeKey = getTextNodeKey(input);
    if (!nodeKey) return null;

    const existing = svgTextCache[side].get(nodeKey);
    if (existing) return existing;

    const svg = getSvgRoot(side);
    if (!svg) return null;

    const text = document.createElementNS(SVG_NS, "text");
    text.setAttribute("data-text-node", nodeKey);
    applyPositionToText(text, input, side);
    setSvgNodeText(text, input.value || "");
    svg.appendChild(text);
    prepareSvgNode(text, side);
    return text;
  }

  function updateSvgNodeFromInput(input, value) {
    const side = getFieldSide(input);
    const nodeKey = getTextNodeKey(input);
    if (!nodeKey) return;
    const node = ensureSvgNodeForInput(input);
    if (!node) return;
    setSvgNodeText(node, value);
    if (inlineEditorNode === node && inlineEditor) {
      inlineEditor.value = value;
      inlineEditor.dispatchEvent(new Event("input"));
    }
  }

  function removeSvgNode(nodeKey, side) {
    const node = svgTextCache[side]?.get(nodeKey);
    if (node && node.parentNode) {
      if (inlineEditorNode === node) {
        closeInlineEditor(false);
      }
      node.parentNode.removeChild(node);
    }
    svgTextCache[side]?.delete(nodeKey);
  }

  function refreshFieldState() {
    if (!textFieldsContainer) return;
    textFieldsContainer.querySelectorAll(".text-field").forEach(wrapper => {
      const input = wrapper.querySelector("input");
      const side = getFieldSide(input);
      wrapper.classList.toggle("is-inactive", side !== currentView);
    });
  }

  function refreshPositions() {
    if (!textFieldsContainer) return;
    const grouped = new Map();

    Array.from(textFieldsContainer.querySelectorAll(".text-field")).forEach(wrapper => {
      const input = wrapper.querySelector("input");
      if (!input) return;
      const side = getFieldSide(input);
      if (!grouped.has(side)) grouped.set(side, []);
      grouped.get(side).push({ wrapper, input });
    });

    grouped.forEach((items, side) => {
      items.forEach(({ input }, index) => {
        const fallbackTop = TEXT_BASE_TOP + index * TEXT_SPACING;
        if (!input.dataset.topPercent) {
          input.dataset.topPercent = fallbackTop;
        }
        ensureSvgNodeForInput(input);
        applyPositionToText(svgTextCache[side].get(getTextNodeKey(input)), input, side);
      });
    });

    refreshFieldState();
  }

  function setupTextField(wrapper) {
    if (!wrapper || wrapper.dataset.textPrepared === "true") return;
    const input = wrapper.querySelector("input");
    const delBtn = wrapper.querySelector(".delete-text");
    if (!input) return;

    if (!input.dataset.cardSide) {
      input.dataset.cardSide = currentView;
    }

    if (!input.dataset.textNode) {
      input.dataset.textNode = `field-${Date.now()}-${Math.random().toString(36).slice(2, 5)}`;
    }

    const side = getFieldSide(input);
    const nodeKey = getTextNodeKey(input);
  const defaultValue = input.dataset.defaultValue ?? "";
  const placeholderValue = input.getAttribute("placeholder") || "";
    const existingNode = svgTextCache[side]?.get(nodeKey);
    const existingValue = getSvgNodeValue(existingNode);

    if (!input.value) {
      if (existingValue) {
        input.value = existingValue;
      } else if (defaultValue) {
        input.value = defaultValue;
      }
    }

    if (!input.placeholder && placeholderValue) {
      input.placeholder = placeholderValue;
    } else if (!input.placeholder && defaultValue) {
      input.placeholder = defaultValue;
    }

    const node = ensureSvgNodeForInput(input);
    if (!existingNode && node) {
      setSvgNodeText(node, input.value || defaultValue || "");
    } else if (existingNode && input.value && input.value !== existingValue) {
      setSvgNodeText(existingNode, input.value);
    } else if (existingNode && !existingValue && (defaultValue || input.value)) {
      setSvgNodeText(existingNode, input.value || defaultValue || "");
    }

    input.addEventListener("input", () => {
      updateSvgNodeFromInput(input, input.value);
    });

    input.addEventListener("focus", () => {
      const side = getFieldSide(input);
      if (currentView !== side) setActiveView(side);
      syncFieldHighlight(getTextNodeKey(input), side, true);
    });

    input.addEventListener("blur", () => {
      syncFieldHighlight(getTextNodeKey(input), getFieldSide(input), false);
    });

    if (delBtn && !delBtn.dataset.bound) {
      delBtn.dataset.bound = "true";
      delBtn.addEventListener("click", () => {
        const side = getFieldSide(input);
        const nodeKey = getTextNodeKey(input);
        removeSvgNode(nodeKey, side);
        wrapper.remove();
        refreshPositions();
      });
    }

    wrapper.dataset.textPrepared = "true";
  }

  function initializeTextFields() {
    if (!textFieldsContainer) return;
    Array.from(textFieldsContainer.querySelectorAll(".text-field")).forEach(setupTextField);
    refreshPositions();
  }

  function setActiveView(view) {
    closeInlineEditor(true);
    currentView = view;
    const isFront = view === "front";
    if (cardFront) cardFront.classList.toggle("active", isFront);
    if (cardBack) cardBack.classList.toggle("active", !isFront);

    viewToggleButtons.forEach(btn => {
      const target = btn === frontBtn ? "front" : btn === backBtn ? "back" : btn.dataset.view;
      const active = target === view;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });

    refreshFieldState();
  }

  function updateZoom() {
    if (!canvas) return;
    closeInlineEditor(true);
    canvas.style.transform = `scale(${zoom})`;
    canvas.style.transformOrigin = "center center";
    if (zoomLevel) zoomLevel.textContent = `${Math.round(zoom * 100)}%`;
    if (zoomInBtn) zoomInBtn.disabled = zoom >= ZOOM_MAX;
    if (zoomOutBtn) zoomOutBtn.disabled = zoom <= ZOOM_MIN;
  }

  // Event bindings
  if (frontBtn) frontBtn.addEventListener("click", () => setActiveView("front"));
  if (backBtn) backBtn.addEventListener("click", () => setActiveView("back"));

  if (zoomInBtn) {
    zoomInBtn.addEventListener("click", () => {
      if (zoom < ZOOM_MAX) {
        zoom = Math.min(ZOOM_MAX, +(zoom + ZOOM_STEP).toFixed(2));
        updateZoom();
      }
    });
  }

  if (zoomOutBtn) {
    zoomOutBtn.addEventListener("click", () => {
      if (zoom > ZOOM_MIN) {
        zoom = Math.max(ZOOM_MIN, +(zoom - ZOOM_STEP).toFixed(2));
        updateZoom();
      }
    });
  }

  updateZoom();

  cacheSvgNodes(cardFront, "front");
  cacheSvgNodes(cardBack, "back");
  applyImage("front", imageState.front.currentSrc, { skipPreview: false });
  applyImage("back", imageState.back.currentSrc, { skipPreview: false });
  initializeTextFields();

  if (addTextBtn && textFieldsContainer) {
    addTextBtn.addEventListener("click", () => {
      const wrapper = document.createElement("div");
      wrapper.classList.add("text-field");
      wrapper.dataset.cardSide = currentView;

      const input = document.createElement("input");
      input.type = "text";
      input.value = "New Text";
      input.dataset.cardSide = currentView;
      input.dataset.textNode = `custom-${Date.now()}`;
      input.dataset.topPercent = TEXT_BASE_TOP;
      input.dataset.leftPercent = 50;
      input.dataset.align = "center";
    input.dataset.defaultValue = "New Text";
    input.placeholder = "New Text";

      const delBtn = document.createElement("button");
      delBtn.classList.add("delete-text");
      delBtn.type = "button";
      delBtn.setAttribute("aria-label", "Delete text field");
      delBtn.textContent = "🗑";

      wrapper.appendChild(input);
      wrapper.appendChild(delBtn);
      textFieldsContainer.appendChild(wrapper);

      setupTextField(wrapper);
      refreshPositions();

      try {
        input.focus({ preventScroll: true });
      } catch (err) {
        input.focus();
      }
    });
  }

  sideButtons.forEach(btn => {
    const panelName = btn.dataset.panel || "text";
    btn.addEventListener("click", () => {
      sideButtons.forEach(sideBtn => sideBtn.classList.remove("active"));
      btn.classList.add("active");
      showPanel(panelName);
    });
  });

  imageInputs.forEach(input => {
    const side = input.dataset.imageInput;
    input.addEventListener("change", () => {
      const file = input.files && input.files[0];
      if (!file) return;

      if (!file.type.startsWith("image/")) {
        input.value = "";
        alert("Please choose an image file.");
        return;
      }

      if (file.size > MAX_IMAGE_SIZE) {
        input.value = "";
        alert("Image is too large. Please upload a file smaller than 5MB.");
        return;
      }

      const reader = new FileReader();
      reader.onload = event => {
        const result = event.target?.result;
        if (typeof result === "string") {
          applyImage(side, result);
        }
      };
      reader.onerror = () => {
        console.error("Unable to read selected image file.");
        alert("Unable to read the selected image file. Please try another image.");
      };
      reader.readAsDataURL(file);
    });
  });

  resetImageButtons.forEach(btn => {
    const side = btn.dataset.resetImage;
    btn.addEventListener("click", () => resetImage(side));
  });

  setActiveView("front");
  showPanel(sideButtons[0]?.dataset?.panel || "text");
});
