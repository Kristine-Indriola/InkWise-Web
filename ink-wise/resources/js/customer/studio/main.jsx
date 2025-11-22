import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

import { initializeCustomerStudioLegacy } from './legacy';
import './status.css';

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

  useEffect(() => {
    initializeCustomerStudioLegacy();
    setStatus('ready');
  }, []);

  if (!bootstrap || Object.keys(bootstrap).length === 0) {
    return null;
  }

  return (
    null
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
