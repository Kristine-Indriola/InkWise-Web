import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

import { initializeCustomerStudioLegacy } from './legacy';
import TextMiniToolbar from './TextMiniToolbar.jsx';
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
  const [activeElementType, setActiveElementType] = useState('text');

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
  };

  const handleSizeChange = (size) => {
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (!bridge || typeof bridge.setFontSize !== 'function') {
      console.debug('[InkWise Studio] Size change ignored (bridge unavailable).', size);
      return;
    }
    bridge.setFontSize(size);
  };

  const handleColorChange = (color) => {
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (!bridge || typeof bridge.setColor !== 'function') {
      console.debug('[InkWise Studio] Color change ignored (bridge unavailable).', color);
      return;
    }
    bridge.setColor(color);
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
    <div className="studio-react-widgets">
      <TextMiniToolbar
        onAction={handleToolbarAction}
        onFontChange={handleFontChange}
        onSizeChange={handleSizeChange}
        onColorChange={handleColorChange}
        activeElementType={activeElementType}
      />
    </div>
  );
}

function mountReactApp() {
  const mount = () => {
    const rootElement = document.getElementById('inkwise-customer-studio-root');
    if (!rootElement) {
      return;
    }

    const root = createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <CustomerStudioApp />
      </React.StrictMode>,
    );
  };

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    mount();
    return;
  }

  document.addEventListener('DOMContentLoaded', mount, { once: true });
}

mountReactApp();
