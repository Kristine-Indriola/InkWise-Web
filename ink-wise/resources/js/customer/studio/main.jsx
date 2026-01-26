import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';


import { initializeCustomerStudioLegacy } from './legacy';
// Text mini toolbar removed: kept code paths but do not import or mount
import TemplateEditor from './TemplateEditor.jsx';
import ColorsPanel from './ColorsPanel.jsx';
import TextFieldsPanel from './TextFieldsPanel.jsx';
import GraphicsPanel from './GraphicsPanel.jsx';
import './status.css';
import './text-toolbar.css';

function readBootstrapPayload() {
  const script = document.getElementById('inkwise-customer-studio-bootstrap');
  if (script) {
    try {
      const payload = JSON.parse(script.textContent || '{}');
      if (payload && typeof payload === 'object') {
        if (typeof window !== 'undefined') {
          window.inkwiseStudioBootstrap = payload;
        }
        return payload;
      }
    } catch (error) {
      console.error('[InkWise Studio] Failed to parse bootstrap payload.', error);
    }
  }

  if (typeof window !== 'undefined' && window.inkwiseStudioBootstrap) {
    return window.inkwiseStudioBootstrap;
  }

  return {};
}

function TemplateSummary({ template }) {
  if (!template) {
    return (
      <span className="studio-status__message">Template data unavailable</span>
    );
  }

  const updatedAt = template.updated_at ? new Date(template.updated_at) : null;
  const updatedLabel = updatedAt && !Number.isNaN(updatedAt.getTime())
    ? updatedAt.toLocaleString()
    : 'Not yet saved';

  return (
    <div className="studio-status__summary">
      <span className="studio-status__template-name" title={template.name || 'Untitled Template'}>
        {template.name || 'Untitled Template'}
      </span>
      {template.svg_path && (
        <span className="studio-status__badge studio-status__badge--svg">SVG ready</span>
      )}
      {template.preview && (
        <span className="studio-status__badge studio-status__badge--preview">Preview loaded</span>
      )}
      <span className="studio-status__meta">Updated: {updatedLabel}</span>
    </div>
  );
}

function StudioStatusBadge({ bootstrap, status }) {
  const template = bootstrap?.template ?? null;
  const product = bootstrap?.product ?? null;

  const statusLabel = status === 'ready'
    ? 'Design canvas ready'
    : status === 'initializing'
      ? 'Preparing editor…'
      : status;

  return (
    <div className="studio-status" role="status" aria-live="polite">
      <div className="studio-status__header">
        <span className="studio-status__dot" data-state={status} />
        <span className="studio-status__label">{statusLabel}</span>
      </div>
      <TemplateSummary template={template} />
      {product && (
        <div className="studio-status__product">Editing: {product.name || `Product #${product.id}`}</div>
      )}
    </div>
  );
}

function CustomerStudioApp() {
  const bootstrap = useMemo(() => readBootstrapPayload(), []);
  const [status, setStatus] = useState('initializing');
  const [activeElementType, setActiveElementType] = useState(null);

  // Keep body class in sync so the toolbar is only visible when a text/image is selected
  useEffect(() => {
    if (typeof document === 'undefined') return;
    const show = activeElementType === 'text' || activeElementType === 'image';
    document.body.classList.toggle('text-toolbar-visible', !!show);
    return () => {
      document.body.classList.remove('text-toolbar-visible');
    };
  }, [activeElementType]);

  useEffect(() => {
    initializeCustomerStudioLegacy();
    setStatus('ready');
  }, []);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined;
    }

    const handleActiveElement = (event) => {
      const detail = event?.detail || {};
      setActiveElementType(detail.type || null);
    };

    window.addEventListener('inkwise:active-element', handleActiveElement);

    const bridge = window.inkwiseToolbar;
    if (bridge && typeof bridge.getSelection === 'function') {
      try {
        const selection = bridge.getSelection();
        if (selection && selection.type) {
          setActiveElementType(selection.type);
        }
      } catch (error) {
        console.debug('[InkWise Studio] Unable to read initial toolbar selection.', error);
      }
    }

    return () => {
      window.removeEventListener('inkwise:active-element', handleActiveElement);
    };
  }, []);

  // Handle graphics selection from GraphicsPanel
  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined;
    }

    const handleGraphicSelected = (event) => {
      const { category, item, side } = event.detail || {};
      if (!item || !side) return;

      // Add the graphic to the specified canvas
      addGraphicToCanvas(item, side, category);
    };

    window.addEventListener('inkwise:graphic-selected', handleGraphicSelected);

    return () => {
      window.removeEventListener('inkwise:graphic-selected', handleGraphicSelected);
    };
  }, []);

  // Function to add graphics to canvas
  const addGraphicToCanvas = (item, side, category) => {
    try {
      // Find the target SVG canvas
      const canvasContainer = document.querySelector(`#template-editor-container-${side}`);
      if (!canvasContainer) {
        console.warn(`Canvas container for ${side} not found`);
        return;
      }

      const svgElement = canvasContainer.querySelector('svg');
      if (!svgElement) {
        console.warn(`SVG element for ${side} not found`);
        return;
      }

      // Create the graphic element based on category
      let graphicElement;

      if (category === 'shapes' && item.svg) {
        // For shapes, insert the SVG content
        const parser = new DOMParser();
        const svgDoc = parser.parseFromString(item.svg, 'image/svg+xml');
        const svgContent = svgDoc.documentElement;

        // Create a group element to contain the shape
        graphicElement = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        graphicElement.setAttribute('class', 'inkwise-graphic inkwise-shape');
        graphicElement.setAttribute('data-graphic-type', 'shape');
        graphicElement.setAttribute('data-graphic-source', item.source);

        // Copy all child elements from the shape SVG
        while (svgContent.firstChild) {
          graphicElement.appendChild(svgContent.firstChild);
        }

        // Set default size and position
        const bbox = graphicElement.getBBox();
        const scale = Math.min(100 / Math.max(bbox.width, bbox.height), 1);
        const transform = `translate(50, 50) scale(${scale})`;
        graphicElement.setAttribute('transform', transform);

      } else if (item.thumbnail || item.full) {
        // For images, illustrations, and patterns, create a group with image and bounding box
        const groupElement = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        groupElement.setAttribute('class', `inkwise-graphic inkwise-${category.slice(0, -1)} inkwise-graphic-group`);
        groupElement.setAttribute('data-graphic-type', category.slice(0, -1));
        groupElement.setAttribute('data-graphic-source', item.source);

        // Create the image element
        const imageElement = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        imageElement.setAttribute('class', 'inkwise-graphic-image');

        // Use full URL if available, otherwise thumbnail
        const imageUrl = item.full || item.thumbnail;
        imageElement.setAttributeNS('http://www.w3.org/1999/xlink', 'href', imageUrl);

        // Set default size and position (center of canvas)
        const canvasWidth = svgElement.viewBox?.baseVal?.width || svgElement.clientWidth || 400;
        const canvasHeight = svgElement.viewBox?.baseVal?.height || svgElement.clientHeight || 300;

        const size = Math.min(canvasWidth, canvasHeight) * 0.3; // 30% of smaller dimension
        const x = (canvasWidth - size) / 2;
        const y = (canvasHeight - size) / 2;

        imageElement.setAttribute('x', 0);
        imageElement.setAttribute('y', 0);
        imageElement.setAttribute('width', size);
        imageElement.setAttribute('height', size);
        imageElement.setAttribute('preserveAspectRatio', 'xMidYMid meet');

        // Create bounding box rectangle
        const boundingBox = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        boundingBox.setAttribute('class', 'inkwise-bounding-box');
        boundingBox.setAttribute('x', 0);
        boundingBox.setAttribute('y', 0);
        boundingBox.setAttribute('width', size);
        boundingBox.setAttribute('height', size);
        boundingBox.setAttribute('fill', 'none');
        boundingBox.setAttribute('stroke', '#3b82f6');
        boundingBox.setAttribute('stroke-width', '2');
        boundingBox.setAttribute('stroke-dasharray', '5,5');
        boundingBox.setAttribute('opacity', '0');
        boundingBox.setAttribute('pointer-events', 'none');

        // Create resize handles
        const handles = [];
        const handlePositions = [
          { x: 0, y: 0, cursor: 'nw-resize' },           // top-left
          { x: size, y: 0, cursor: 'ne-resize' },        // top-right
          { x: size, y: size, cursor: 'se-resize' },     // bottom-right
          { x: 0, y: size, cursor: 'sw-resize' }         // bottom-left
        ];

        handlePositions.forEach((pos, index) => {
          const handle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
          handle.setAttribute('class', 'inkwise-resize-handle');
          handle.setAttribute('cx', pos.x);
          handle.setAttribute('cy', pos.y);
          handle.setAttribute('r', '6');
          handle.setAttribute('fill', '#3b82f6');
          handle.setAttribute('stroke', 'white');
          handle.setAttribute('stroke-width', '2');
          handle.setAttribute('style', `cursor: ${pos.cursor}; opacity: 0; pointer-events: none;`);
          handle.setAttribute('data-handle-index', index);
          handles.push(handle);
        });

        // Add click handler for selection
        groupElement.addEventListener('click', (e) => {
          e.stopPropagation();
          
          // Remove selected class from all graphics
          document.querySelectorAll('.inkwise-graphic-group').forEach(el => {
            el.classList.remove('selected');
          });
          
          // Add selected class to this graphic
          groupElement.classList.add('selected');
        });

        // Add drag functionality
        let isDragging = false;
        let startX, startY, startTransform;

        groupElement.addEventListener('mousedown', (e) => {
          if (e.target.classList.contains('inkwise-resize-handle')) return; // Don't drag when resizing
          
          isDragging = true;
          startX = e.clientX;
          startY = e.clientY;
          
          const transform = groupElement.getAttribute('transform') || 'translate(0,0)';
          const match = transform.match(/translate\(([^,]+),\s*([^)]+)\)/);
          if (match) {
            startTransform = { x: parseFloat(match[1]), y: parseFloat(match[2]) };
          } else {
            startTransform = { x: 0, y: 0 };
          }
          
          e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
          if (!isDragging) return;
          
          const dx = e.clientX - startX;
          const dy = e.clientY - startY;
          
          const newX = startTransform.x + dx;
          const newY = startTransform.y + dy;
          
          groupElement.setAttribute('transform', `translate(${newX}, ${newY})`);
        });

        document.addEventListener('mouseup', () => {
          isDragging = false;
        });

        // Add resize functionality
        handles.forEach((handle, index) => {
          let isResizing = false;
          let resizeStartX, resizeStartY, originalSize, originalTransform;

          handle.addEventListener('mousedown', (e) => {
            isResizing = true;
            resizeStartX = e.clientX;
            resizeStartY = e.clientY;
            
            const transform = groupElement.getAttribute('transform') || 'translate(0,0)';
            const match = transform.match(/translate\(([^,]+),\s*([^)]+)\)/);
            originalTransform = match ? { x: parseFloat(match[1]), y: parseFloat(match[2]) } : { x: 0, y: 0 };
            
            originalSize = parseFloat(boundingBox.getAttribute('width'));
            
            e.preventDefault();
            e.stopPropagation();
          });

          document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;
            
            const dx = e.clientX - resizeStartX;
            const dy = e.clientY - resizeStartY;
            
            let newSize = originalSize;
            
            // Different resize behaviors based on handle
            switch (index) {
              case 0: // top-left
                newSize = Math.max(20, originalSize - Math.max(dx, dy));
                break;
              case 1: // top-right
                newSize = Math.max(20, originalSize + dx);
                break;
              case 2: // bottom-right
                newSize = Math.max(20, originalSize + Math.max(dx, dy));
                break;
              case 3: // bottom-left
                newSize = Math.max(20, originalSize + dy);
                break;
            }
            
            // Update bounding box and image size
            boundingBox.setAttribute('width', newSize);
            boundingBox.setAttribute('height', newSize);
            imageElement.setAttribute('width', newSize);
            imageElement.setAttribute('height', newSize);
            
            // Update handle positions
            const handlePositions = [
              { x: 0, y: 0 },           // top-left
              { x: newSize, y: 0 },     // top-right
              { x: newSize, y: newSize }, // bottom-right
              { x: 0, y: newSize }      // bottom-left
            ];
            
            handles.forEach((h, i) => {
              h.setAttribute('cx', handlePositions[i].x);
              h.setAttribute('cy', handlePositions[i].y);
            });
          });

          document.addEventListener('mouseup', () => {
            isResizing = false;
          });
        });

        graphicElement = groupElement;
      }

      if (graphicElement) {
        // Add the graphic to the SVG
        svgElement.appendChild(graphicElement);

        // Make it selectable by adding to the editor's interactive elements
        if (window.inkwiseEditors && window.inkwiseEditors[side]) {
          const editor = window.inkwiseEditors[side];
          if (editor && typeof editor.refreshInteractiveElements === 'function') {
            editor.refreshInteractiveElements();
          }
        }

        // Dispatch event to notify other components
        window.dispatchEvent(new CustomEvent('inkwise:canvas-changed', {
          detail: { side, action: 'graphic-added', element: graphicElement }
        }));

        console.log(`Added ${category} graphic to ${side} canvas:`, item.name);
      }
    } catch (error) {
      console.error('Error adding graphic to canvas:', error);
    }
  };

  if (!bootstrap || Object.keys(bootstrap).length === 0) {
    return null;
  }

  const handleFontChange = (family) => {
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (!bridge || typeof bridge.setFontFamily !== 'function') {
      console.debug('[InkWise Studio] Font change ignored (bridge unavailable).', family);
      return;
    }
    bridge.setFontFamily(family);
    // Also dispatch event for inline editor
    if (activeElementType === 'text') {
      const selection = bridge.getSelection();
      if (selection && selection.key) {
        window.dispatchEvent(new CustomEvent('inkwise:text-style-changed', {
          detail: { attribute: 'font-family', value: family, key: selection.key }
        }));
      }
    }
  };

  const handleSizeChange = (size) => {
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (!bridge || typeof bridge.setFontSize !== 'function') {
      console.debug('[InkWise Studio] Size change ignored (bridge unavailable).', size);
      return;
    }
    bridge.setFontSize(size);
    // Also dispatch event for inline editor
    if (activeElementType === 'text') {
      const selection = bridge.getSelection();
      if (selection && selection.key) {
        window.dispatchEvent(new CustomEvent('inkwise:text-style-changed', {
          detail: { attribute: 'font-size', value: size, key: selection.key }
        }));
      }
    }
  };

  const handleColorChange = (color) => {
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (!bridge || typeof bridge.setColor !== 'function') {
      console.debug('[InkWise Studio] Color change ignored (bridge unavailable).', color);
      return;
    }
    bridge.setColor(color);
    // Also dispatch event for inline editor
    if (activeElementType === 'text') {
      const selection = bridge.getSelection();
      if (selection && selection.key) {
        window.dispatchEvent(new CustomEvent('inkwise:text-style-changed', {
          detail: { attribute: 'fill', value: color, key: selection.key }
        }));
      }
    }
  };

  const handleToolbarAction = (action) => {
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (!bridge || typeof bridge.dispatch !== 'function') {
      console.debug('[InkWise Studio] Toolbar action ignored (bridge unavailable).', action);
      return () => {};
    }

    const actionsRequiringValue = new Set([
      'line-spacing',
      'letter-spacing',
      'effect-style',
      'effect-shape',
      'opacity',
      'rotation',
      'layer',
      'more',
    ]);

    if (actionsRequiringValue.has(action)) {
      return (value) => {
        bridge.dispatch(action, value);
      };
    }

    bridge.dispatch(action);
    return () => {};
  };

  return (
    <div className="studio-status-wrapper">
      <StudioStatusBadge bootstrap={bootstrap} status={status} />
    </div>
  );
}

// A small host that mounts the `TextMiniToolbar` inside the preview area
function ToolbarHost() {
  // Mini toolbar removed — no-op to preserve structure and avoid mounting.
  return null;
}

function mountReactApp() {
  const mount = () => {
    const rootElement = document.getElementById('inkwise-customer-studio-root');
    if (!rootElement) {
      return;
    }

    if (!rootElement.__inkwiseRoot) {
      rootElement.__inkwiseRoot = createRoot(rootElement);
    }
    rootElement.__inkwiseRoot.render(
      <React.StrictMode>
        <CustomerStudioApp />
      </React.StrictMode>,
    );

    // Mini toolbar mounting removed — toolbar has been disabled intentionally.

    // Mount the new JSX-based TemplateEditor if a mount point exists
    try {
      const editorRootEl = document.getElementById('template-editor-root');
      if (editorRootEl) {
        const bootstrap = readBootstrapPayload();
        const editorRoot = createRoot(editorRootEl);
        editorRoot.render(
          <React.StrictMode>
            <TemplateEditor bootstrap={bootstrap} />
          </React.StrictMode>,
        );
      }
    } catch (e) {
      console.debug('[InkWise Studio] Failed to mount TemplateEditor', e);
    }

    // Mount colors panel into legacy modal container if present
    try {
      const colorsModalEl = document.getElementById('colors-modal');
      if (colorsModalEl) {
        const colorsRoot = createRoot(colorsModalEl.querySelector('.modal-content') || colorsModalEl);
        colorsRoot.render(
          <React.StrictMode>
            <ColorsPanel bootstrap={readBootstrapPayload()} />
          </React.StrictMode>
        );
      }
    } catch (e) {
      console.debug('[InkWise Studio] Failed to mount ColorsPanel', e);
    }

    // Mount text fields panel into legacy modal container if present
    try {
      const textModalEl = document.getElementById('text-modal');
      if (textModalEl) {
        const textRoot = createRoot(textModalEl.querySelector('.modal-content') || textModalEl);
        textRoot.render(
          <React.StrictMode>
            <TextFieldsPanel bootstrap={readBootstrapPayload()} />
          </React.StrictMode>
        );
      }
    } catch (e) {
      console.debug('[InkWise Studio] Failed to mount TextFieldsPanel', e);
    }

    // Mount graphics panel into legacy modal container if present
    try {
      const graphicsModalEl = document.getElementById('graphics-modal');
      if (graphicsModalEl) {
        const graphicsRoot = createRoot(graphicsModalEl.querySelector('.modal-content') || graphicsModalEl);
        graphicsRoot.render(
          <React.StrictMode>
            <GraphicsPanel bootstrap={readBootstrapPayload()} />
          </React.StrictMode>
        );
      }
    } catch (e) {
      console.debug('[InkWise Studio] Failed to mount GraphicsPanel', e);
    }
  };

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    mount();
    return;
  }

  document.addEventListener('DOMContentLoaded', mount, { once: true });
}

mountReactApp();
