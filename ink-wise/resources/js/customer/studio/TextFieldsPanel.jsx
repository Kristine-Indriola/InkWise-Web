import React, { useState, useEffect, useRef } from 'react';
import './TextFieldsPanel.css';

function TextFieldsPanel({ bootstrap = {} }) {
  const [frontFields, setFrontFields] = useState([]);
  const [backFields, setBackFields] = useState([]);
  const [frontCollapsed, setFrontCollapsed] = useState(false);
  const [backCollapsed, setBackCollapsed] = useState(false);
  const [activeFieldKey, setActiveFieldKey] = useState(null);
  const [fontModalOpen, setFontModalOpen] = useState(null); // Will store {side, index} of the field whose font modal is open
  const [fieldStyles, setFieldStyles] = useState({}); // Store current styles for each field
  const fontModalRef = useRef(null);

  // Font options
  const fontOptions = [
    'Arial, sans-serif',
    'Helvetica, sans-serif',
    'Times New Roman, serif',
    'Georgia, serif',
    'Verdana, sans-serif',
    'Courier New, monospace',
    'Impact, sans-serif',
    'Comic Sans MS, cursive',
    'Poppins, sans-serif',
    'Montserrat, sans-serif',
    'Playfair Display, serif',
    'Lora, serif',
    'Raleway, sans-serif',
    'Great Vibes, cursive',
    'Pacifico, cursive',
    'Cormorant Garamond, serif',
    'Open Sans, sans-serif',
    'Bebas Neue, cursive',
    'Allura, cursive',
    'Alex Brush, cursive',
    'Dancing Script, cursive',
    'Cinzel, serif',
    'Abril Fatface, cursive',
    'Cormorant SC, serif',
    'Libre Baskerville, serif',
    'Crimson Text, serif',
    'Josefin Sans, sans-serif',
    'Tangerine, cursive'
  ];

  // Size options
  const sizeOptions = [8, 10, 12, 14, 16, 18, 20, 24, 28, 32, 36, 48, 60, 72];

  useEffect(() => {
    // Listen for active element changes to highlight fields
    const handleActiveElement = (event) => {
      const detail = event?.detail || {};
      if (detail.type === 'text' && detail.key) {
        setActiveFieldKey(detail.key);
      } else {
        setActiveFieldKey(null);
      }
    };

    window.addEventListener('inkwise:active-element', handleActiveElement);

    // Initial population
    populateFields();

    // Set up observers for SVG containers to detect when content is loaded
    const frontContainer = document.getElementById('template-editor-container-front');
    const backContainer = document.getElementById('template-editor-container-back');

    const observerConfig = { childList: true, subtree: true };

    const handleMutation = (mutations) => {
      let shouldUpdate = false;
      mutations.forEach((mutation) => {
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeName === 'svg' || (node.querySelector && node.querySelector('svg'))) {
              shouldUpdate = true;
            }
          });
        }
      });
      if (shouldUpdate) {
        setTimeout(populateFields, 100);
      }
    };

    const frontObserver = new MutationObserver(handleMutation);
    const backObserver = new MutationObserver(handleMutation);

    if (frontContainer) {
      frontObserver.observe(frontContainer, observerConfig);
    }
    if (backContainer) {
      backObserver.observe(backContainer, observerConfig);
    }

    // Also periodically check for SVG content (fallback)
    const intervalId = setInterval(() => {
      const currentFrontSvg = document.querySelector('#template-editor-container-front svg');
      const currentBackSvg = document.querySelector('#template-editor-container-back svg');
      
      const currentFrontCount = currentFrontSvg ? currentFrontSvg.querySelectorAll('text').length : 0;
      const currentBackCount = currentBackSvg ? currentBackSvg.querySelectorAll('text').length : 0;
      
      if (currentFrontCount !== frontFields.length || currentBackCount !== backFields.length) {
        populateFields();
      }
    }, 1000);

    // Listen for text changes to update the panel
    const handleTextChange = () => {
      setTimeout(populateFields, 50);
    };

    window.addEventListener('inkwise:text-changed', handleTextChange);

    // Listen for modal show events
    const handleModalShow = () => {
      setTimeout(populateFields, 100);
    };

    document.addEventListener('show.bs.modal', handleModalShow);

    // Handle click outside to close font modal
    const handleClickOutside = (event) => {
      if (fontModalRef.current && !fontModalRef.current.contains(event.target)) {
        setFontModalOpen(null);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);

    return () => {
      window.removeEventListener('inkwise:active-element', handleActiveElement);
      window.removeEventListener('inkwise:text-changed', handleTextChange);
      document.removeEventListener('show.bs.modal', handleModalShow);
      document.removeEventListener('mousedown', handleClickOutside);
      frontObserver.disconnect();
      backObserver.disconnect();
      clearInterval(intervalId);
    };
  }, [frontFields.length, backFields.length]);

  const populateFields = () => {
    const frontSvg = document.querySelector('#template-editor-container-front svg');
    const backSvg = document.querySelector('#template-editor-container-back svg');

    const frontTexts = frontSvg ? Array.from(frontSvg.querySelectorAll('text')) : [];
    const backTexts = backSvg ? Array.from(backSvg.querySelectorAll('text')) : [];

    const frontFields = frontTexts.map((text, index) => ({
      key: text.getAttribute('data-preview-node') || text.id || `front-text-${index}`,
      label: text.getAttribute('data-preview-label') || `Front Text ${index + 1}`,
      value: text.textContent || '',
      element: text
    }));

    const backFields = backTexts.map((text, index) => ({
      key: text.getAttribute('data-preview-node') || text.id || `back-text-${index}`,
      label: text.getAttribute('data-preview-label') || `Back Text ${index + 1}`,
      value: text.textContent || '',
      element: text
    }));

    // Initialize field styles
    const initialStyles = {};
    frontFields.forEach((field, index) => {
      const fieldKey = `front-${index}`;
      initialStyles[fieldKey] = {
        'font-weight': field.element.getAttribute('font-weight') || 'normal',
        'font-style': field.element.getAttribute('font-style') || 'normal'
      };
    });
    backFields.forEach((field, index) => {
      const fieldKey = `back-${index}`;
      initialStyles[fieldKey] = {
        'font-weight': field.element.getAttribute('font-weight') || 'normal',
        'font-style': field.element.getAttribute('font-style') || 'normal'
      };
    });
    setFieldStyles(initialStyles);

    setFrontFields(frontFields);
    setBackFields(backFields);
  };

  const handleFieldChange = (side, index, value) => {
    const fields = side === 'front' ? [...frontFields] : [...backFields];
    fields[index].value = value;
    fields[index].element.textContent = value;

    if (side === 'front') {
      setFrontFields(fields);
    } else {
      setBackFields(fields);
    }

    // Dispatch text change event
    window.dispatchEvent(new CustomEvent('inkwise:text-changed', {
      detail: { key: fields[index].key, value }
    }));
  };

  const handleStyleChange = (side, index, attribute, value) => {
    const fields = side === 'front' ? [...frontFields] : [...backFields];
    const svgText = fields[index].element;

    if (svgText) {
      if (attribute === 'font-family') {
        svgText.setAttribute('font-family', value);
        svgText.style.fontFamily = value;
      } else if (attribute === 'font-size') {
        svgText.setAttribute('font-size', value);
        svgText.style.fontSize = `${value}px`;
      } else if (attribute === 'fill') {
        svgText.setAttribute('fill', value);
        svgText.style.fill = value;
      } else if (attribute === 'font-weight') {
        svgText.setAttribute('font-weight', value);
        svgText.style.fontWeight = value;
      } else if (attribute === 'font-style') {
        svgText.setAttribute('font-style', value);
        svgText.style.fontStyle = value;
      } else if (attribute === 'text-anchor') {
        svgText.setAttribute('text-anchor', value);
      }
    }

    // Dispatch style change event
    window.dispatchEvent(new CustomEvent('inkwise:text-style-changed', {
      detail: { attribute, value, key: fields[index].key }
    }));

    // Update field styles state
    const fieldKey = `${side}-${index}`;
    setFieldStyles(prev => ({
      ...prev,
      [fieldKey]: {
        ...prev[fieldKey],
        [attribute]: value
      }
    }));
  };

  const deleteField = (side, index) => {
    const fields = side === 'front' ? [...frontFields] : [...backFields];
    const fieldToDelete = fields[index];

    if (fieldToDelete && fieldToDelete.element) {
      // Remove from SVG
      fieldToDelete.element.remove();

      // Dispatch text changed event to update other components
      window.dispatchEvent(new CustomEvent('inkwise:text-changed', {
        detail: { action: 'delete', key: fieldToDelete.key }
      }));

      // Refresh fields
      setTimeout(populateFields, 10);
    }
  };

  const addNewField = (side) => {
    const containerId = side === 'front' ? 'template-editor-container-front' : 'template-editor-container-back';
    const svgRoot = document.querySelector(`#${containerId} svg`);

    if (!svgRoot) {
      console.warn(`[TextFieldsPanel] No SVG root found for ${side} side`);
      return;
    }

    // Generate unique key
    const timestamp = Date.now();
    const random = Math.random().toString(36).slice(2, 5);
    const previewKey = `custom_${timestamp}_${random}`;

    // Create SVG text element
    const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    textElement.setAttribute('id', previewKey);
    textElement.setAttribute('data-preview-node', previewKey);
    textElement.setAttribute('data-default-text', 'NEW TEXT');
    textElement.setAttribute('x', '50%');
    textElement.setAttribute('y', '50%');
    textElement.setAttribute('text-anchor', 'middle');
    textElement.setAttribute('dominant-baseline', 'middle');
    textElement.setAttribute('font-family', 'Arial, sans-serif');
    textElement.setAttribute('font-size', '24');
    textElement.setAttribute('fill', '#000000');
    textElement.textContent = 'NEW TEXT';

    // Add event listeners
    textElement.addEventListener('input', () => {
      textElement.dataset.currentText = textElement.textContent || '';
      // Trigger overlay sync if available
      if (window.inkwiseToolbar && typeof window.inkwiseToolbar.scheduleOverlaySync === 'function') {
        window.inkwiseToolbar.scheduleOverlaySync();
      }
    });

    textElement.addEventListener('click', () => {
      // Set as active element
      window.dispatchEvent(new CustomEvent('inkwise:active-element', {
        detail: { type: 'text', key: previewKey }
      }));
    });

    // Add to SVG
    svgRoot.appendChild(textElement);

    // Update React state
    const newField = {
      key: previewKey,
      label: 'Custom',
      value: 'NEW TEXT',
      element: textElement
    };

    if (side === 'front') {
      setFrontFields(prev => [...prev, newField]);
    } else {
      setBackFields(prev => [...prev, newField]);
    }

    // Dispatch text changed event
    window.dispatchEvent(new CustomEvent('inkwise:text-changed', {
      detail: { key: previewKey, value: 'NEW TEXT' }
    }));

    // Refresh fields from SVG
    setTimeout(populateFields, 50);

    // Trigger overlay sync
    if (window.inkwiseToolbar && typeof window.inkwiseToolbar.scheduleOverlaySync === 'function') {
      window.inkwiseToolbar.scheduleOverlaySync();
    }
  };

  const FieldControls = ({ side, field, index }) => {
    const svgText = field.element;
    const fieldKey = `${side}-${index}`;
    const currentStyles = fieldStyles[fieldKey] || {};
    
    const currentFont = svgText?.getAttribute('font-family') || 'Arial, sans-serif';
    const currentSize = parseInt(svgText?.getAttribute('font-size') || '24');
    const currentColor = svgText?.getAttribute('fill') || '#000000';
    const currentWeight = currentStyles['font-weight'] || svgText?.getAttribute('font-weight') || 'normal';
    const currentStyle = currentStyles['font-style'] || svgText?.getAttribute('font-style') || 'normal';
    const currentAlign = svgText?.getAttribute('text-anchor') || 'middle';

    const isFontModalOpen = fontModalOpen && fontModalOpen.side === side && fontModalOpen.index === index;

    const handleFontSelect = (fontFamily) => {
      handleStyleChange(side, index, 'font-family', fontFamily);
      setFontModalOpen(null);
    };

    return (
      <div className="controls-row">
        <select
          value={currentSize}
          onChange={(e) => handleStyleChange(side, index, 'font-size', e.target.value)}
          className="size-select"
        >
          {sizeOptions.map(size => (
            <option key={size} value={size}>{size}</option>
          ))}
        </select>

        <input
          type="color"
          value={currentColor}
          onChange={(e) => handleStyleChange(side, index, 'fill', e.target.value)}
          className="color-picker"
        />

        <button
          onClick={() => handleStyleChange(side, index, 'font-weight', currentWeight === 'bold' ? 'normal' : 'bold')}
          className={`style-btn bold-toggle-btn ${currentWeight === 'bold' ? 'active' : ''}`}
          title={currentWeight === 'bold' ? 'Remove bold formatting' : 'Make text bold'}
        >
          B
        </button>

        <button
          onClick={() => handleStyleChange(side, index, 'font-style', currentStyle === 'italic' ? 'normal' : 'italic')}
          className={`style-btn italic-btn ${currentStyle === 'italic' ? 'active' : ''}`}
          title="Toggle italic"
        >
          I
        </button>

        <div className="align-group">
          {[
            { value: 'start', label: 'L' },
            { value: 'middle', label: 'C' },
            { value: 'end', label: 'R' }
          ].map(align => (
            <button
              key={align.value}
              onClick={() => handleStyleChange(side, index, 'text-anchor', align.value)}
              className={`align-btn ${currentAlign === align.value ? 'active' : ''}`}
            >
              {align.label}
            </button>
          ))}
        </div>

        <div className="font-selector-container">
          <button
            type="button"
            className="font-select-button"
            onClick={() => setFontModalOpen(isFontModalOpen ? null : { side, index })}
            aria-label="Choose font family"
            aria-haspopup="listbox"
            aria-expanded={isFontModalOpen}
          >
            <span style={{ fontFamily: currentFont }}>{currentFont.split(',')[0]}</span>
            <i className="fa-solid fa-chevron-down" aria-hidden="true" />
          </button>
          {isFontModalOpen && (
            <div className="font-modal" ref={fontModalRef}>
              <div className="font-modal-content">
                {fontOptions.map((font) => (
                  <button
                    key={font}
                    type="button"
                    className="font-option"
                    onClick={() => handleFontSelect(font)}
                    style={{ fontFamily: font }}
                  >
                    {font.split(',')[0]}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="text-fields-panel-react">
      {/* Front Text Fields Section */}
      <div className="text-section" style={{ marginBottom: 20 }}>
        <button
          onClick={() => setFrontCollapsed(!frontCollapsed)}
          className="section-toggle-btn"
        >
          Front Text Fields
          <span>{frontCollapsed ? '▶' : '▼'}</span>
        </button>
        {!frontCollapsed && (
          <div className="section-content">
            {frontFields.map((field, index) => (
              <div
                key={field.key}
                className={`field-card ${activeFieldKey === field.key ? 'active' : ''}`}
              >
                <div className="field-header">
                  <div className="field-label">{field.label}</div>
                  <button
                    onClick={() => deleteField('front', index)}
                    className="delete-field-btn"
                    title="Delete this text field"
                  >
                    ×
                  </button>
                </div>
                <input
                  type="text"
                  value={field.value}
                  onChange={(e) => handleFieldChange('front', index, e.target.value)}
                  onFocus={() => setActiveFieldKey(field.key)}
                  onBlur={() => setActiveFieldKey(null)}
                  className="text-input"
                />
                <FieldControls side="front" field={field} index={index} />
              </div>
            ))}
            <button
              onClick={() => addNewField('front')}
              className="add-section-field-btn"
            >
              <span>+</span>
              New Front Text Field
            </button>
          </div>
        )}
      </div>

      {/* Back Text Fields Section */}
      <div className="text-section" style={{ marginBottom: 20 }}>
        <button
          onClick={() => setBackCollapsed(!backCollapsed)}
          className="section-toggle-btn"
        >
          Back Text Fields
          <span>{backCollapsed ? '▶' : '▼'}</span>
        </button>
        {!backCollapsed && (
          <div className="section-content">
            {backFields.map((field, index) => (
              <div
                key={field.key}
                className={`field-card ${activeFieldKey === field.key ? 'active' : ''}`}
              >
                <div className="field-header">
                  <div className="field-label">{field.label}</div>
                  <button
                    onClick={() => deleteField('back', index)}
                    className="delete-field-btn"
                    title="Delete this text field"
                  >
                    ×
                  </button>
                </div>
                <input
                  type="text"
                  value={field.value}
                  onChange={(e) => handleFieldChange('back', index, e.target.value)}
                  onFocus={() => setActiveFieldKey(field.key)}
                  onBlur={() => setActiveFieldKey(null)}
                  className="text-input"
                />
                <FieldControls side="back" field={field} index={index} />
              </div>
            ))}
            <button
              onClick={() => addNewField('back')}
              className="add-section-field-btn"
            >
              <span>+</span>
              New Back Text Field
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

export default TextFieldsPanel;