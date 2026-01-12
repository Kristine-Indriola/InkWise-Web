// SVG Template Editor (Vite module version)
// Provides interactive editing capabilities for SVG templates

const SVG_NS = 'http://www.w3.org/2000/svg';
const XLINK_NS = 'http://www.w3.org/1999/xlink';

class SvgTemplateEditor {
    constructor(svgElement, options = {}) {
        console.log('SvgTemplateEditor: Constructor called for SVG element:', svgElement);
        this.svg = svgElement;
        this.options = {
            onImageChange: options.onImageChange || null,
            onTextChange: options.onTextChange || null,
            ...options,
        };
        const bodyDataset = document && document.body && document.body.dataset ? document.body.dataset : {};
        const modeRaw = (bodyDataset.imageReplacementMode || '').toLowerCase();
        let mode = 'full';
        if (modeRaw === 'panel-only' || modeRaw === 'disabled') {
            mode = modeRaw;
        } else if (bodyDataset.allowImageReplacement === 'false') {
            mode = 'disabled';
        }
        this.imageReplacementMode = mode;
        this.allowCanvasImageTools = mode !== 'disabled';

        this.changeableImages = [];
        this.textElements = [];
        this.imageClassTokens = new Set([
            'canvas-layer__image',
            'changeable-image',
            'svg-changeable-image',
            'inkwise-image',
        ]);
        this.init();
    }

    init() {
        console.log('SvgTemplateEditor: Initializing...');
        this.parseSvgData();
        this.setupImageHandlers();
        this.setupTextHandlers();
        this.addCssStyles();
        console.log('SvgTemplateEditor: Initialization complete');
    }

    parseSvgData() {
        const svgData = this.svg.dataset.svgData;
        if (svgData) {
            try {
                const data = JSON.parse(svgData);
                this.changeableImages = data.changeable_images || [];
                this.textElements = data.text_elements || [];
            } catch (error) {
                console.warn('SvgTemplateEditor: Failed to parse data-svg-data payload', error);
            }
        }
    }

    readDatasetTokens(node) {
        if (!node || !node.dataset) {
            return [];
        }
        const keys = ['changeable', 'editableType', 'elementType', 'layerType', 'previewType', 'fieldType'];
        return keys
            .map((key) => node.dataset[key])
            .filter((value) => typeof value === 'string' && value.trim() !== '')
            .map((value) => value.trim().toLowerCase());
    }

    findImageContentNode(node) {
        if (!node || typeof node.querySelector !== 'function') {
            return null;
        }
        return node.querySelector('[data-changeable="image"], [data-editable-image], .canvas-layer__image, img, image');
    }

    nodeRepresentsImage(node) {
        if (!node) {
            return false;
        }
        const tagName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
        if (tagName === 'image' || tagName === 'img') {
            return true;
        }
        const datasetHints = this.readDatasetTokens(node);
        if (datasetHints.some((hint) => hint === 'image' || hint === 'photo' || hint === 'graphic')) {
            return true;
        }
        if (typeof node.hasAttribute === 'function') {
            if (node.hasAttribute('data-editable-image') || node.hasAttribute('data-changeable-image')) {
                return true;
            }
        }
        if (node.classList) {
            for (const token of this.imageClassTokens) {
                if (node.classList.contains(token)) {
                    return true;
                }
            }
        }
        if (tagName === 'foreignobject') {
            return !!this.findImageContentNode(node);
        }
        return false;
    }

    resolveImageNode(node) {
        if (!node) {
            return null;
        }
        if (this.nodeRepresentsImage(node)) {
            return node;
        }
        if (typeof node.closest === 'function') {
            const ancestor = node.closest('[data-preview-node]');
            if (ancestor && ancestor !== node && this.nodeRepresentsImage(ancestor)) {
                return ancestor;
            }
        }
        return this.findImageContentNode(node);
    }

    resolveImageHandleNodes(node) {
        const contentNode = this.resolveImageNode(node);
        if (!contentNode) {
            return null;
        }
        const interactionNode = (typeof contentNode.closest === 'function' && contentNode.closest('foreignObject'))
            || contentNode;
        return { interactionNode, contentNode };
    }

    applyImageSourceToNode(node, dataUrl) {
        if (!node || !dataUrl) {
            return null;
        }
        const tagName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
        if (tagName === 'image') {
            node.setAttributeNS(XLINK_NS, 'href', dataUrl);
            node.setAttribute('href', dataUrl);
            if (node.dataset) {
                node.dataset.src = dataUrl;
            }
            return node;
        }
        if (tagName === 'img') {
            node.setAttribute('src', dataUrl);
            if (node.dataset) {
                node.dataset.src = dataUrl;
            }
            return node;
        }
        if (tagName === 'foreignobject') {
            const innerNode = this.findImageContentNode(node);
            if (innerNode && innerNode !== node) {
                return this.applyImageSourceToNode(innerNode, dataUrl);
            }
            return null;
        }
        if (tagName === 'rect') {
            return this.convertRectToImage(node, dataUrl);
        }
        if (node.style && typeof node.style.setProperty === 'function') {
            node.style.backgroundImage = `url('${dataUrl}')`;
        }
        if (node.dataset) {
            node.dataset.src = dataUrl;
            node.dataset.replacedImage = 'true';
        }
        return node;
    }

    convertRectToImage(rectNode, dataUrl) {
        if (!rectNode || !rectNode.parentNode) {
            return null;
        }
        const image = document.createElementNS(SVG_NS, 'image');
        const transferableAttributes = ['x', 'y', 'width', 'height', 'transform', 'preserveAspectRatio'];
        transferableAttributes.forEach((attr) => {
            if (rectNode.hasAttribute && rectNode.hasAttribute(attr)) {
                image.setAttribute(attr, rectNode.getAttribute(attr));
            }
        });
        Array.from(rectNode.attributes || []).forEach((attr) => {
            if (attr.name === 'id' || attr.name.startsWith('data-')) {
                image.setAttribute(attr.name, attr.value);
            }
        });
        if (rectNode.classList && rectNode.classList.length) {
            rectNode.classList.forEach((className) => image.classList.add(className));
        }
        image.setAttribute('href', dataUrl);
        image.setAttributeNS(XLINK_NS, 'href', dataUrl);
        if (!image.hasAttribute('preserveAspectRatio')) {
            image.setAttribute('preserveAspectRatio', 'xMidYMid slice');
        }
        rectNode.parentNode.replaceChild(image, rectNode);
        return image;
    }

    escapeSelector(value) {
        if (typeof value !== 'string' || !value.length) {
            return '';
        }
        if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
            return CSS.escape(value);
        }
        return value.replace(/([^a-zA-Z0-9_-])/g, '\\$1');
    }

    resolveNodeFromMetadata(entry) {
        if (!entry || typeof entry !== 'object') {
            return null;
        }
        const preferredId = entry.id || entry.element_id || entry.node_id;
        if (preferredId) {
            const selector = `#${this.escapeSelector(preferredId)}`;
            const match = this.svg.querySelector(selector);
            if (match) {
                return match;
            }
        }
        const selector = entry.selector || entry.css_selector;
        if (selector) {
            try {
                const match = this.svg.querySelector(selector);
                if (match) {
                    return match;
                }
            } catch (error) {
                console.warn('SvgTemplateEditor: Invalid selector in changeable image metadata', selector, error);
            }
        }
        return null;
    }

    collectImageCandidates() {
        const candidates = new Set();
        this.changeableImages.forEach((entry) => {
            const node = this.resolveNodeFromMetadata(entry);
            if (node) {
                candidates.add(node);
            }
        });
        const fallbackSelectors = '[data-changeable="image"], [data-editable-image], .changeable-image, .canvas-layer__image, image, img, foreignObject';
        this.svg.querySelectorAll(fallbackSelectors).forEach((node) => {
            candidates.add(node);
        });
        return Array.from(candidates);
    }

    getBBoxSafe(element) {
        if (!element || typeof element.getBBox !== 'function') {
            return null;
        }
        try {
            return element.getBBox();
        } catch (error) {
            console.warn('SvgTemplateEditor: Unable to compute bounding box for element', error, element);
            return null;
        }
    }

    toSvgPoint(clientX, clientY) {
        if (!this.svg || typeof this.svg.createSVGPoint !== 'function') {
            return null;
        }

        const point = this.svg.createSVGPoint();
        point.x = clientX;
        point.y = clientY;

        const matrix = this.svg.getScreenCTM();
        if (!matrix) {
            return null;
        }

        return point.matrixTransform(matrix.inverse());
    }

    setupImageHandlers() {
        const changeableElements = this.collectImageCandidates();

        console.log('SvgTemplateEditor: Found', changeableElements.length, 'changeable image elements');

        if (!this.allowCanvasImageTools) {
            changeableElements.forEach((element) => {
                const resolved = this.resolveImageHandleNodes(element);
                if (!resolved) {
                    return;
                }
                const { interactionNode } = resolved;
                interactionNode.style.cursor = 'default';
                interactionNode.classList.remove('svg-changeable-image');
                interactionNode.classList.remove('changeable-image-hover');
                if (interactionNode._boundingBox) {
                    try {
                        interactionNode._boundingBox.remove();
                    } catch (err) {}
                    interactionNode._boundingBox = null;
                }
            });
            return;
        }

        setTimeout(() => {
            changeableElements.forEach((element, index) => {
                const resolved = this.resolveImageHandleNodes(element);
                if (!resolved) {
                    return;
                }
                const { interactionNode, contentNode } = resolved;
                if (!interactionNode || interactionNode.__inkwiseSvgImageBound) {
                    return;
                }
                console.log('SvgTemplateEditor: Setting up element', index, interactionNode);
                interactionNode.__inkwiseSvgImageBound = true;
                interactionNode.__inkwiseImageContent = contentNode || interactionNode;
                interactionNode.style.cursor = 'pointer';
                interactionNode.classList.add('svg-changeable-image');
                interactionNode.classList.add('changeable-image-hover');

                interactionNode.addEventListener('mouseenter', () => {
                    interactionNode.classList.add('changeable-image-active');
                });

                interactionNode.addEventListener('mouseleave', () => {
                    interactionNode.classList.remove('changeable-image-active');
                });

                interactionNode.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.showImageUploadDialog(interactionNode);
                });

                const boundingBox = this.createBoundingBox(interactionNode);
                if (boundingBox) {
                    interactionNode._boundingBox = boundingBox;
                }

                this.makeDraggable(interactionNode);
            });
        }, 200);
    }

    createBoundingBox(element) {
        const bbox = this.getBBoxSafe(element);
        if (!bbox) {
            return null;
        }
        const group = document.createElementNS(SVG_NS, 'g');
        group.classList.add('svg-bounding-box');

        const rect = document.createElementNS(SVG_NS, 'rect');
        rect.classList.add('svg-bounding-box__rect');
        rect.setAttribute('fill', 'none');
        rect.setAttribute('stroke', '#007cba');
        rect.setAttribute('stroke-width', '2');
        rect.setAttribute('stroke-dasharray', '5,5');
        rect.setAttribute('rx', '3');
        rect.style.pointerEvents = 'none';
        group.appendChild(rect);

        const handles = this.createResizeHandles();
        handles.forEach((handle) => group.appendChild(handle));

        this.svg.appendChild(group);
        this.positionBoundingElements(group, bbox);

        return group;
    }

    createResizeHandles() {
        const handles = [];
        const positions = [
            { name: 'nw', x: 0, y: 0 },
            { name: 'ne', x: 1, y: 0 },
            { name: 'sw', x: 0, y: 1 },
            { name: 'se', x: 1, y: 1 },
        ];

        positions.forEach((pos) => {
            const handle = document.createElementNS(SVG_NS, 'circle');
            handle.setAttribute('r', '4');
            handle.setAttribute('fill', '#007cba');
            handle.setAttribute('stroke', '#ffffff');
            handle.setAttribute('stroke-width', '1');
            handle.classList.add('resize-handle', `handle-${pos.name}`);
            handle.style.pointerEvents = 'all';
            handle.style.cursor = `${pos.name}-resize`;
            handles.push(handle);
        });

        return handles;
    }

    positionBoundingElements(group, rawBBox) {
        if (!group || !rawBBox) {
            return;
        }
        const rect = group.querySelector('.svg-bounding-box__rect');
        if (!rect) {
            return;
        }

        const offset = 5;
        const x = rawBBox.x - offset;
        const y = rawBBox.y - offset;
        const width = rawBBox.width + offset * 2;
        const height = rawBBox.height + offset * 2;

        rect.setAttribute('x', x);
        rect.setAttribute('y', y);
        rect.setAttribute('width', Math.max(width, 0));
        rect.setAttribute('height', Math.max(height, 0));

        const handles = group.querySelectorAll('.resize-handle');
        handles.forEach((handle) => {
            if (handle.classList.contains('handle-nw')) {
                handle.setAttribute('cx', x);
                handle.setAttribute('cy', y);
            } else if (handle.classList.contains('handle-ne')) {
                handle.setAttribute('cx', x + width);
                handle.setAttribute('cy', y);
            } else if (handle.classList.contains('handle-sw')) {
                handle.setAttribute('cx', x);
                handle.setAttribute('cy', y + height);
            } else if (handle.classList.contains('handle-se')) {
                handle.setAttribute('cx', x + width);
                handle.setAttribute('cy', y + height);
            } else if (handle.classList.contains('handle-n')) {
                handle.setAttribute('cx', x + width / 2);
                handle.setAttribute('cy', y);
            } else if (handle.classList.contains('handle-s')) {
                handle.setAttribute('cx', x + width / 2);
                handle.setAttribute('cy', y + height);
            } else if (handle.classList.contains('handle-e')) {
                handle.setAttribute('cx', x + width);
                handle.setAttribute('cy', y + height / 2);
            } else if (handle.classList.contains('handle-w')) {
                handle.setAttribute('cx', x);
                handle.setAttribute('cy', y + height / 2);
            }
        });
    }

    updateBoundingBox(element) {
        if (!element || !element._boundingBox) {
            return;
        }
        const bbox = this.getBBoxSafe(element);
        this.positionBoundingElements(element._boundingBox, bbox);
    }

    focusElementById(elementId) {
        if (!elementId || !this.svg) {
            return false;
        }
        const selector = `#${this.escapeSelector(elementId)}`;
        let target = null;
        try {
            target = this.svg.querySelector(selector);
        } catch (error) {
            console.warn('SvgTemplateEditor: Invalid selector generated for element id', elementId, error);
            return false;
        }
        if (!target) {
            target = this.svg.querySelector(`[data-element-id="${elementId}"]`);
        }
        if (!target) {
            return false;
        }
        const resolved = this.resolveImageHandleNodes(target) || { interactionNode: target };
        const focusNode = resolved.interactionNode || target;
        const bbox = this.getBBoxSafe(focusNode);
        if (!bbox) {
            return false;
        }

        this.updateBoundingBox(focusNode);
        if (focusNode._boundingBox) {
            focusNode._boundingBox.classList.add('svg-bounding-box--pulse');
            setTimeout(() => {
                if (focusNode._boundingBox) {
                    focusNode._boundingBox.classList.remove('svg-bounding-box--pulse');
                }
            }, 1000);
        }

        if (typeof focusNode.scrollIntoView === 'function') {
            focusNode.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }
        focusNode.classList.add('changeable-image-active');
        setTimeout(() => focusNode.classList.remove('changeable-image-active'), 1200);
        return true;
    }

    makeDraggable(element) {
        let isDragging = false;
        let originalX = 0;
        let originalY = 0;
        let grabOffsetX = 0;
        let grabOffsetY = 0;
        let currentX = 0;
        let currentY = 0;

        const startDrag = (e) => {
            if (e.target.closest('.resize-handle')) {
                return;
            }

            const startPoint = this.toSvgPoint(e.clientX, e.clientY);
            if (!startPoint) {
                return;
            }

            const bbox = this.getBBoxSafe(element);
            if (!bbox) {
                return;
            }

            const canReadAttribute = typeof element.getAttribute === 'function';
            const canMutateAttribute = typeof element.setAttribute === 'function';
            originalX = canReadAttribute ? parseFloat(element.getAttribute('x')) : bbox.x;
            originalY = canReadAttribute ? parseFloat(element.getAttribute('y')) : bbox.y;

            if (Number.isNaN(originalX)) {
                originalX = bbox.x;
                if (canMutateAttribute) {
                    element.setAttribute('x', originalX);
                }
            }

            if (Number.isNaN(originalY)) {
                originalY = bbox.y;
                if (canMutateAttribute) {
                    element.setAttribute('y', originalY);
                }
            }

            grabOffsetX = startPoint.x - originalX;
            grabOffsetY = startPoint.y - originalY;
            currentX = originalX;
            currentY = originalY;
            isDragging = true;

            element.style.cursor = 'grabbing';
            this.svg.style.cursor = 'grabbing';
            document.body.style.userSelect = 'none';

            e.preventDefault();
        };

        const drag = (e) => {
            if (!isDragging) {
                return;
            }

            const point = this.toSvgPoint(e.clientX, e.clientY);
            if (!point) {
                return;
            }

            currentX = point.x - grabOffsetX;
            currentY = point.y - grabOffsetY;

            if (typeof element.removeAttribute === 'function') {
                element.removeAttribute('transform');
            }
            if (typeof element.setAttribute === 'function') {
                element.setAttribute('x', currentX);
                element.setAttribute('y', currentY);
            }

            this.updateBoundingBox(element);

            e.preventDefault();
        };

        const endDrag = (e) => {
            if (!isDragging) {
                return;
            }

            isDragging = false;
            element.style.cursor = 'pointer';
            this.svg.style.cursor = 'default';
            document.body.style.userSelect = '';

            this.updateBoundingBox(element);

            const event = new CustomEvent('svgImageMoved', {
                detail: {
                    element,
                    x: currentX,
                    y: currentY,
                },
            });
            this.svg.dispatchEvent(event);

            e.preventDefault();
        };

        element.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);

        if (element._boundingBox) {
            const handles = element._boundingBox.querySelectorAll('.resize-handle');
            handles.forEach((handle) => {
                this.makeResizable(element, handle);
            });
        }
    }

    makeResizable(element, handle) {
        let isResizing = false;
        let startPoint = null;
        let originalWidth = 0;
        let originalHeight = 0;
        let originalX = 0;
        let originalY = 0;
        let handleType = '';
        let originalFontSize = 0;

        const startResize = (e) => {
            const point = this.toSvgPoint(e.clientX, e.clientY);
            if (!point) {
                return;
            }

            const bbox = this.getBBoxSafe(element);
            if (!bbox) {
                return;
            }
            originalWidth = bbox.width;
            originalHeight = bbox.height;
            const canReadAttribute = typeof element.getAttribute === 'function';
            const canMutateAttribute = typeof element.setAttribute === 'function';
            originalX = canReadAttribute ? parseFloat(element.getAttribute('x')) : bbox.x;
            originalY = canReadAttribute ? parseFloat(element.getAttribute('y')) : bbox.y;

            if (Number.isNaN(originalX)) {
                originalX = bbox.x;
                if (canMutateAttribute) {
                    element.setAttribute('x', originalX);
                }
            }

            if (Number.isNaN(originalY)) {
                originalY = bbox.y;
                if (canMutateAttribute) {
                    element.setAttribute('y', originalY);
                }
            }

            originalFontSize = parseFloat(element.getAttribute('font-size'))
                || parseFloat(window.getComputedStyle(element).fontSize)
                || 16;

            startPoint = point;
            handleType = Array.from(handle.classList).find((cls) => cls.startsWith('handle-')) || '';
            isResizing = true;

            element.style.cursor = handle.style.cursor;
            this.svg.style.cursor = handle.style.cursor;
            document.body.style.userSelect = 'none';

            e.preventDefault();
            e.stopPropagation();
        };

        const resize = (e) => {
            if (!isResizing) {
                return;
            }

            const point = this.toSvgPoint(e.clientX, e.clientY);
            if (!point) {
                return;
            }

            const deltaX = point.x - startPoint.x;
            const deltaY = point.y - startPoint.y;

            let newWidth = originalWidth;
            let newHeight = originalHeight;
            let newX = originalX;
            let newY = originalY;

            switch (handleType) {
                case 'handle-se':
                    newWidth = originalWidth + deltaX;
                    newHeight = originalHeight + deltaY;
                    break;
                case 'handle-sw':
                    newWidth = originalWidth - deltaX;
                    newHeight = originalHeight + deltaY;
                    newX = originalX + deltaX;
                    break;
                case 'handle-ne':
                    newWidth = originalWidth + deltaX;
                    newHeight = originalHeight - deltaY;
                    newY = originalY + deltaY;
                    break;
                case 'handle-nw':
                    newWidth = originalWidth - deltaX;
                    newHeight = originalHeight - deltaY;
                    newX = originalX + deltaX;
                    newY = originalY + deltaY;
                    break;
                default:
                    break;
            }

            newWidth = Math.max(20, newWidth);
            newHeight = Math.max(20, newHeight);

            const tag = element.tagName.toLowerCase();
            if ((tag === 'image' || tag === 'rect') && typeof element.setAttribute === 'function') {
                element.setAttribute('width', newWidth);
                element.setAttribute('height', newHeight);
                element.setAttribute('x', newX);
                element.setAttribute('y', newY);
            } else {
                const initialWidth = Math.max(1, originalWidth);
                const initialHeight = Math.max(1, originalHeight);
                const scaleX = newWidth / initialWidth;
                const scaleY = newHeight / initialHeight;
                const scale = Math.max(scaleX, scaleY);
                const newFontSize = Math.max(6, originalFontSize * scale);
                if (typeof element.setAttribute === 'function') {
                    element.setAttribute('font-size', newFontSize);
                }
            }

            this.updateBoundingBox(element);

            e.preventDefault();
            e.stopPropagation();
        };

        const endResize = (e) => {
            if (!isResizing) {
                return;
            }

            isResizing = false;
            element.style.cursor = 'pointer';
            this.svg.style.cursor = 'default';
            document.body.style.userSelect = '';

            const detail = {
                element,
                width: parseFloat(element.getAttribute ? element.getAttribute('width') : '') || originalWidth,
                height: parseFloat(element.getAttribute ? element.getAttribute('height') : '') || originalHeight,
                x: parseFloat(element.getAttribute ? element.getAttribute('x') : '') || originalX,
                y: parseFloat(element.getAttribute ? element.getAttribute('y') : '') || originalY,
            };

            const event = new CustomEvent('svgImageResized', { detail });
            this.svg.dispatchEvent(event);

            e.preventDefault();
            e.stopPropagation();
        };

        handle.addEventListener('mousedown', startResize);
        document.addEventListener('mousemove', resize);
        document.addEventListener('mouseup', endResize);
    }

    setupTextHandlers() {
        const self = this;
        const editableTexts = this.svg.querySelectorAll('[data-editable="true"]');

        editableTexts.forEach((element) => {
            element.style.cursor = 'pointer';
            element.classList.add('svg-editable-text');

            element.addEventListener('click', function(e) {
                e.preventDefault();
                self.showTextEditDialog(element);
            });

            element.addEventListener('mouseenter', function() {
                element.classList.add('editable-text-hover');
            });

            element.addEventListener('mouseleave', function() {
                element.classList.remove('editable-text-hover');
            });
        });
    }

    showImageUploadDialog(element) {
        const self = this;
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.style.display = 'none';

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                self.handleImageUpload(file, element);
            }
            if (input.parentNode) {
                input.parentNode.removeChild(input);
            }
        });

        document.body.appendChild(input);
        input.click();
    }

    async handleImageUpload(file, triggerElement) {
        const self = this;
        const resolvedTarget = triggerElement && triggerElement.__inkwiseImageContent
            ? triggerElement.__inkwiseImageContent
            : triggerElement;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
        const formData = new FormData();
        formData.append('image', file);
        if (csrfToken) {
            formData.append('_token', csrfToken);
        }

        let uploadedUrl = null;

        try {
            const response = await fetch('/design/upload-image', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const text = await response.text().catch(() => '');
                throw new Error(`Upload failed (${response.status}): ${text}`);
            }

            const data = await response.json();
            uploadedUrl = data.url || data.path || null;
        } catch (error) {
            console.error('SvgTemplateEditor: Failed to upload image', error);
            alert('We could not upload your image. Please try again.');
            const uploadErrorEvent = new CustomEvent('svgImageUploadFailed', {
                detail: { error, file },
            });
            self.svg.dispatchEvent(uploadErrorEvent);
            return;
        }

        if (!uploadedUrl) {
            alert('Upload did not return a usable image URL.');
            return;
        }

        const updatedElement = self.applyImageSourceToNode(resolvedTarget, uploadedUrl) || resolvedTarget;
        if (triggerElement && updatedElement && triggerElement.__inkwiseImageContent !== updatedElement) {
            triggerElement.__inkwiseImageContent = updatedElement;
        }

        const event = new CustomEvent('svgImageChanged', {
            detail: {
                element: updatedElement,
                imageUrl: uploadedUrl,
                file,
            },
        });
        self.svg.dispatchEvent(event);

        if (self.options.onImageChange) {
            self.options.onImageChange(updatedElement, uploadedUrl, file);
        }

        self.updateBoundingBox(triggerElement || updatedElement);
    }

    showTextEditDialog(element) {
        const self = this;
        const currentText = element.textContent || '';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.className = 'svg-text-editor';

        const rect = element.getBoundingClientRect();
        const svgRect = self.svg.getBoundingClientRect();

        input.style.position = 'absolute';
        input.style.left = (rect.left - svgRect.left) + 'px';
        input.style.top = (rect.top - svgRect.top) + 'px';
        input.style.width = Math.max(rect.width, 100) + 'px';
        input.style.fontSize = window.getComputedStyle(element).fontSize;
        input.style.fontFamily = window.getComputedStyle(element).fontFamily;
        input.style.border = '1px solid #007cba';
        input.style.padding = '2px';
        input.style.background = 'white';
        input.style.zIndex = '1000';

        element.style.visibility = 'hidden';

        self.svg.parentNode.appendChild(input);
        input.focus();
        input.select();

        const finishEdit = function() {
            const newText = input.value;

            while (element.firstChild) {
                element.removeChild(element.firstChild);
            }

            element.appendChild(document.createTextNode(newText));
            element.style.visibility = 'visible';

            if (input.parentNode) {
                input.parentNode.removeChild(input);
            }

            const event = new CustomEvent('svgTextChanged', {
                detail: {
                    element,
                    oldText: currentText,
                    newText,
                },
            });
            self.svg.dispatchEvent(event);

            if (self.options.onTextChange) {
                self.options.onTextChange(element, currentText, newText);
            }
        };

        input.addEventListener('blur', finishEdit);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                finishEdit();
            } else if (e.key === 'Escape') {
                element.style.visibility = 'visible';
                if (input.parentNode) {
                    input.parentNode.removeChild(input);
                }
            }
        });
    }

    addCssStyles() {
        if (document.getElementById('svg-editor-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'svg-editor-styles';
        style.textContent = `
            .svg-changeable-image {
                transition: all 0.2s ease;
            }

            .changeable-image-hover:hover {
                filter: brightness(0.8);
                stroke: #007cba !important;
                stroke-width: 2px !important;
            }

            .changeable-image-active {
                filter: brightness(0.7);
                stroke: #007cba !important;
                stroke-width: 3px !important;
            }

            .svg-bounding-box {
                transition: all 0.2s ease;
            }

            .svg-bounding-box__rect {
                pointer-events: none;
            }

            .resize-handle {
                transition: all 0.2s ease;
                pointer-events: all !important;
            }

            .resize-handle:hover {
                fill: #005a87;
                r: 6px;
            }

            .svg-editable-text {
                transition: all 0.2s ease;
            }

            .editable-text-hover:hover {
                fill: #007cba !important;
                text-decoration: underline;
            }

            .uploaded-image {
                transition: all 0.2s ease;
            }

            .svg-text-editor {
                box-sizing: border-box;
                outline: none;
            }

            .svg-text-editor:focus {
                box-shadow: 0 0 5px rgba(0, 124, 186, 0.5);
            }
                .svg-bounding-box--pulse .svg-bounding-box__rect {
                    animation: svgBoundingBoxPulse 1s ease-out;
                }

                @keyframes svgBoundingBoxPulse {
                    0% {
                        stroke-width: 2;
                        opacity: 1;
                    }
                    50% {
                        stroke-width: 4;
                        opacity: 0.4;
                    }
                    100% {
                        stroke-width: 2;
                        opacity: 1;
                    }
                }
        `;

        document.head.appendChild(style);
    }

    getSvgString() {
        // Clone the element so we don't interfere with the live editor state during serialization
        const clone = this.svg.cloneNode(true);

        // Remove editor-only UI elements from the export
        clone.querySelectorAll('.svg-bounding-box, .inkwise-editor-marker, [data-editor-only="true"]').forEach(el => {
            el.parentNode?.removeChild(el);
        });

        // Ensure root SVG attributes are set for high-fidelity standalone viewing
        clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        clone.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        
        // Remove UI scaling styles to allow the SVG to render at its natural viewBox size
        clone.style.width = '';
        clone.style.height = '';
        clone.style.maxWidth = '';
        clone.style.maxHeight = '';
        clone.removeAttribute('preserveAspectRatio');

        // Inject required font definitions and CSS resets
        const styleEl = document.createElementNS(SVG_NS, 'style');
        styleEl.id = 'inkwise-export-styles';
        styleEl.textContent = `
            @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
            text { white-space: pre; }
        `;
        clone.insertBefore(styleEl, clone.firstChild);

        const svgString = new XMLSerializer().serializeToString(clone);
        return svgString;
    }

    getSvgDataUrl() {
        const svgString = this.getSvgString();
        return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgString)));
    }

    async saveSvg(templateId, side = 'front') {
        const svgData = this.getSvgString();

        try {
            const response = await fetch(`/staff/templates/${templateId}/save-svg`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    svg_content: svgData,
                    side,
                }),
            });

            if (!response.ok) {
                const errorText = await response.text().catch(() => '');
                throw new Error(errorText || `Request failed with status ${response.status}`);
            }

            let result;
            try {
                result = await response.json();
            } catch (parseError) {
                console.warn('SvgTemplateEditor: Failed to parse save response', parseError);
                result = { success: false, error: 'Invalid response from server' };
            }
            return result;
        } catch (error) {
            console.error('Failed to save SVG:', error);
            return { success: false, error: error.message };
        }
    }
}

function autoInitializeEditors() {
    const initialize = () => {
        const svgEditors = document.querySelectorAll('svg[data-svg-editor]');
        console.log('SvgTemplateEditor: Found', svgEditors.length, 'SVGs with data-svg-editor attribute');
        svgEditors.forEach((svg, index) => {
            if (!svg.__inkwiseSvgTemplateEditor) {
                console.log('SvgTemplateEditor: Initializing editor', index, 'for SVG:', svg);
                svg.__inkwiseSvgTemplateEditor = new SvgTemplateEditor(svg);
            }
        });
    };

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initialize();
    } else {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    }

    if (!window.__inkwiseSvgTemplateEditorObserver) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.matches && node.matches('svg[data-svg-editor]')) {
                        if (!node.__inkwiseSvgTemplateEditor) {
                            node.__inkwiseSvgTemplateEditor = new SvgTemplateEditor(node);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });

        window.__inkwiseSvgTemplateEditorObserver = observer;
    }
}

if (typeof window !== 'undefined') {
    window.SvgTemplateEditor = SvgTemplateEditor;
    if (!window.__inkwiseSvgTemplateEditorAutoInit) {
        window.__inkwiseSvgTemplateEditorAutoInit = true;
        autoInitializeEditors();
    }
}

export default SvgTemplateEditor;
