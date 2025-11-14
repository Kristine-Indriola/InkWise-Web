// SVG Template Editor
// This script provides interactive editing capabilities for SVG templates

class SvgTemplateEditor {
    constructor(svgElement, options = {}) {
        console.log('SvgTemplateEditor: Constructor called for SVG element:', svgElement);
        this.svg = svgElement;
        this.options = {
            onImageChange: options.onImageChange || null,
            onTextChange: options.onTextChange || null,
            ...options
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
        // Get SVG data from the data attributes or from the template data
        const svgData = this.svg.dataset.svgData;
        if (svgData) {
            const data = JSON.parse(svgData);
            this.changeableImages = data.changeable_images || [];
            this.textElements = data.text_elements || [];
        }
    }

    setupImageHandlers() {
        const self = this;

        // Find all elements with data-changeable="image" or data-editable-image attributes
        const changeableElements = this.svg.querySelectorAll('[data-changeable="image"], [data-editable-image]');

        console.log('SvgTemplateEditor: Found', changeableElements.length, 'changeable image elements');

        if (!this.allowCanvasImageTools) {
            changeableElements.forEach((element) => {
                element.style.cursor = 'default';
                element.classList.remove('svg-changeable-image');
                element.classList.remove('changeable-image-hover');
                if (element._boundingBox) {
                    try { element._boundingBox.remove(); } catch (err) {}
                    element._boundingBox = null;
                }
            });
            return;
        }

        changeableElements.forEach((element, index) => {
            console.log('SvgTemplateEditor: Setting up element', index, element);
            element.style.cursor = 'pointer';
            element.classList.add('svg-changeable-image');

            // Add visual indicator
            element.classList.add('changeable-image-hover');

            // Create bounding box for drag functionality
            const boundingBox = this.createBoundingBox(element);
            element._boundingBox = boundingBox;

            element.addEventListener('mouseenter', function() {
                element.classList.add('changeable-image-active');
                self.showBoundingBox(element);
            });

            element.addEventListener('mouseleave', function() {
                element.classList.remove('changeable-image-active');
                self.hideBoundingBox(element);
            });

            // Add drag functionality
            this.makeDraggable(element);
        });
    }

    createBoundingBox(element) {
        const bbox = element.getBBox();
        const boundingBox = document.createElementNS('http://www.w3.org/2000/svg', 'rect');

        boundingBox.setAttribute('x', bbox.x - 5);
        boundingBox.setAttribute('y', bbox.y - 5);
        boundingBox.setAttribute('width', bbox.width + 10);
        boundingBox.setAttribute('height', bbox.height + 10);
        boundingBox.setAttribute('fill', 'none');
        boundingBox.setAttribute('stroke', '#007cba');
        boundingBox.setAttribute('stroke-width', '2');
        boundingBox.setAttribute('stroke-dasharray', '5,5');
        boundingBox.setAttribute('rx', '3');
        boundingBox.classList.add('svg-bounding-box');
        boundingBox.style.display = 'none';
        boundingBox.style.pointerEvents = 'none';

        // Add resize handles
        const handles = this.createResizeHandles(boundingBox);
        handles.forEach(handle => boundingBox.appendChild(handle));

        this.svg.appendChild(boundingBox);
        return boundingBox;
    }

    createResizeHandles(boundingBox) {
        const handles = [];
        const positions = [
            { name: 'nw', x: 0, y: 0 },
            { name: 'ne', x: 1, y: 0 },
            { name: 'sw', x: 0, y: 1 },
            { name: 'se', x: 1, y: 1 }
        ];

        positions.forEach(pos => {
            const handle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
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

    showBoundingBox(element) {
        if (element._boundingBox) {
            this.updateBoundingBox(element);
            element._boundingBox.style.display = 'block';
        }
    }

    hideBoundingBox(element) {
        if (element._boundingBox) {
            element._boundingBox.style.display = 'none';
        }
    }

    updateBoundingBox(element) {
        if (!element._boundingBox) return;

        const bbox = element.getBBox();
        element._boundingBox.setAttribute('x', bbox.x - 5);
        element._boundingBox.setAttribute('y', bbox.y - 5);
        element._boundingBox.setAttribute('width', bbox.width + 10);
        element._boundingBox.setAttribute('height', bbox.height + 10);

        // Update resize handle positions
        const handles = element._boundingBox.querySelectorAll('.resize-handle');
        const x = bbox.x - 5;
        const y = bbox.y - 5;
        const width = bbox.width + 10;
        const height = bbox.height + 10;

        handles.forEach((handle, index) => {
            const positions = [
                { x: x, y: y }, // nw
                { x: x + width, y: y }, // ne
                { x: x, y: y + height }, // sw
                { x: x + width, y: y + height } // se
            ];
            handle.setAttribute('cx', positions[index].x);
            handle.setAttribute('cy', positions[index].y);
        });
    }

    makeDraggable(element) {
        let isDragging = false;
        let startX, startY;
        let originalX, originalY;
        let currentX, currentY;

        const startDrag = (e) => {
            if (e.target.closest('.resize-handle')) return; // Don't drag if clicking on resize handle

            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;

            // Get current transform or position
            const transform = element.getAttribute('transform') || '';
            const translateMatch = transform.match(/translate\(([^,]+),\s*([^)]+)\)/);

            if (translateMatch) {
                originalX = parseFloat(translateMatch[1]);
                originalY = parseFloat(translateMatch[2]);
            } else {
                originalX = parseFloat(element.getAttribute('x') || 0);
                originalY = parseFloat(element.getAttribute('y') || 0);
            }

            currentX = originalX;
            currentY = originalY;

            element.style.cursor = 'grabbing';
            this.svg.style.cursor = 'grabbing';

            // Add drag overlay to prevent text selection
            document.body.style.userSelect = 'none';

            e.preventDefault();
        };

        const drag = (e) => {
            if (!isDragging) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;

            currentX = originalX + deltaX;
            currentY = originalY + deltaY;

            // Apply transform
            element.setAttribute('transform', `translate(${currentX}, ${currentY})`);

            // Update bounding box
            this.updateBoundingBox(element);

            e.preventDefault();
        };

        const endDrag = (e) => {
            if (!isDragging) return;

            isDragging = false;
            element.style.cursor = 'pointer';
            this.svg.style.cursor = 'default';
            document.body.style.userSelect = '';

            // Trigger change event
            const event = new CustomEvent('svgImageMoved', {
                detail: {
                    element: element,
                    x: currentX,
                    y: currentY
                }
            });
            this.svg.dispatchEvent(event);

            e.preventDefault();
        };

        element.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);

        // Add resize functionality to handles
        const handles = element._boundingBox.querySelectorAll('.resize-handle');
        handles.forEach(handle => {
            this.makeResizable(element, handle);
        });
    }

    makeResizable(element, handle) {
        let isResizing = false;
        let startX, startY;
        let originalWidth, originalHeight;
        let originalX, originalY;
        let handleType;

        const startResize = (e) => {
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            handleType = Array.from(handle.classList).find(cls => cls.startsWith('handle-'));

            // Get current dimensions
            const bbox = element.getBBox();
            originalWidth = bbox.width;
            originalHeight = bbox.height;
            originalX = bbox.x;
            originalY = bbox.y;

            element.style.cursor = handle.style.cursor;
            this.svg.style.cursor = handle.style.cursor;
            document.body.style.userSelect = 'none';

            e.preventDefault();
            e.stopPropagation();
        };

        const resize = (e) => {
            if (!isResizing) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            let newWidth = originalWidth;
            let newHeight = originalHeight;
            let newX = originalX;
            let newY = originalY;

            switch (handleType) {
                case 'handle-se': // southeast
                    newWidth = originalWidth + deltaX;
                    newHeight = originalHeight + deltaY;
                    break;
                case 'handle-sw': // southwest
                    newWidth = originalWidth - deltaX;
                    newHeight = originalHeight + deltaY;
                    newX = originalX + deltaX;
                    break;
                case 'handle-ne': // northeast
                    newWidth = originalWidth + deltaX;
                    newHeight = originalHeight - deltaY;
                    newY = originalY + deltaY;
                    break;
                case 'handle-nw': // northwest
                    newWidth = originalWidth - deltaX;
                    newHeight = originalHeight - deltaY;
                    newX = originalX + deltaX;
                    newY = originalY + deltaY;
                    break;
            }

            // Apply minimum size constraints
            newWidth = Math.max(20, newWidth);
            newHeight = Math.max(20, newHeight);

            // Update element size and position
            if (element.tagName.toLowerCase() === 'image') {
                element.setAttribute('width', newWidth);
                element.setAttribute('height', newHeight);
                element.setAttribute('x', newX);
                element.setAttribute('y', newY);
            } else if (element.tagName.toLowerCase() === 'rect') {
                element.setAttribute('width', newWidth);
                element.setAttribute('height', newHeight);
                element.setAttribute('x', newX);
                element.setAttribute('y', newY);
            }

            // Update bounding box
            this.updateBoundingBox(element);

            e.preventDefault();
            e.stopPropagation();
        };

        const endResize = (e) => {
            if (!isResizing) return;

            isResizing = false;
            element.style.cursor = 'pointer';
            this.svg.style.cursor = 'default';
            document.body.style.userSelect = '';

            // Trigger change event
            const event = new CustomEvent('svgImageResized', {
                detail: {
                    element: element,
                    width: parseFloat(element.getAttribute('width')),
                    height: parseFloat(element.getAttribute('height')),
                    x: parseFloat(element.getAttribute('x')),
                    y: parseFloat(element.getAttribute('y'))
                }
            });
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

        // Find all elements with data-editable="true"
        const editableTexts = this.svg.querySelectorAll('[data-editable="true"]');

        editableTexts.forEach((element, index) => {
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

        // Create file input
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.style.display = 'none';

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                self.handleImageUpload(file, element);
            }
            document.body.removeChild(input);
        });

        document.body.appendChild(input);
        input.click();
    }

    handleImageUpload(file, element) {
        const self = this;
        const reader = new FileReader();

        reader.onload = function(e) {
            const imageUrl = e.target.result;

            // Update the SVG element
            if (element.tagName.toLowerCase() === 'image') {
                element.setAttribute('href', imageUrl);
                // Remove xlink:href if it exists (for older SVG compatibility)
                if (element.hasAttribute('xlink:href')) {
                    element.removeAttribute('xlink:href');
                }
            } else if (element.tagName.toLowerCase() === 'rect') {
                // For rect elements, create an image pattern or replace with image
                const image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
                image.setAttribute('x', element.getAttribute('x') || '0');
                image.setAttribute('y', element.getAttribute('y') || '0');
                image.setAttribute('width', element.getAttribute('width') || '100');
                image.setAttribute('height', element.getAttribute('height') || '100');
                image.setAttribute('href', imageUrl);
                image.setAttribute('preserveAspectRatio', 'xMidYMid slice');
                image.classList.add('uploaded-image');

                element.parentNode.replaceChild(image, element);
                element = image; // Update reference
            }

            // Trigger change event
            const event = new CustomEvent('svgImageChanged', {
                detail: {
                    element: element,
                    imageUrl: imageUrl,
                    file: file
                }
            });
            self.svg.dispatchEvent(event);

            // Call optional callback
            if (self.options.onImageChange) {
                self.options.onImageChange(element, imageUrl, file);
            }
        };

        reader.readAsDataURL(file);
    }

    showTextEditDialog(element) {
        const self = this;
        const currentText = element.textContent || '';

        // Create a simple inline edit
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.className = 'svg-text-editor';

        // Position the input over the text element
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

        // Hide the original text temporarily
        element.style.visibility = 'hidden';

        self.svg.parentNode.appendChild(input);
        input.focus();
        input.select();

        const finishEdit = function() {
            const newText = input.value;

            // Clear existing text nodes
            while (element.firstChild) {
                element.removeChild(element.firstChild);
            }

            // Add new text
            element.appendChild(document.createTextNode(newText));

            // Show original element
            element.style.visibility = 'visible';

            // Remove input
            if (input.parentNode) {
                input.parentNode.removeChild(input);
            }

            // Trigger change event
            const event = new CustomEvent('svgTextChanged', {
                detail: {
                    element: element,
                    oldText: currentText,
                    newText: newText
                }
            });
            self.svg.dispatchEvent(event);

            // Call optional callback
            if (self.options.onTextChange) {
                self.options.onTextChange(element, currentText, newText);
            }
        };

        input.addEventListener('blur', finishEdit);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                finishEdit();
            } else if (e.key === 'Escape') {
                // Cancel edit
                element.style.visibility = 'visible';
                if (input.parentNode) {
                    input.parentNode.removeChild(input);
                }
            }
        });
    }

    addCssStyles() {
        if (document.getElementById('svg-editor-styles')) {
            return; // Already added
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
        `;

        document.head.appendChild(style);
    }

    // Get the current SVG as a string
    getSvgString() {
        return new XMLSerializer().serializeToString(this.svg);
    }

    // Export the SVG as a data URL
    getSvgDataUrl() {
        const svgString = this.getSvgString();
        return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgString)));
    }

    // Save the current SVG to server
    async saveSvg(templateId, side = 'front') {
        const svgData = this.getSvgString();

        try {
            const response = await fetch(`/staff/templates/${templateId}/save-svg`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    svg_content: svgData,
                    side: side
                })
            });

            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Failed to save SVG:', error);
            return { success: false, error: error.message };
        }
    }
}

// Auto-initialize editors for SVGs with data-svg-editor attribute
document.addEventListener('DOMContentLoaded', function() {
    const svgEditors = document.querySelectorAll('svg[data-svg-editor]');
    console.log('SvgTemplateEditor: Found', svgEditors.length, 'SVGs with data-svg-editor attribute');
    svgEditors.forEach((svg, index) => {
        console.log('SvgTemplateEditor: Initializing editor', index, 'for SVG:', svg);
        new SvgTemplateEditor(svg);
    });
});

// Also initialize on dynamic content
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === 1 && node.matches && node.matches('svg[data-svg-editor]')) {
                new SvgTemplateEditor(node);
            }
        });
    });
});

observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Export for global use
window.SvgTemplateEditor = SvgTemplateEditor;