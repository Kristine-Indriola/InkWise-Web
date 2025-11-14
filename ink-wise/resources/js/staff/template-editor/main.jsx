import React from 'react';
import { createRoot } from 'react-dom/client';
import { BuilderApp } from './components/BuilderApp';

import './styles.css';

function readBootstrapPayload() {
  const script = document.getElementById('inkwise-builder-bootstrap');
  if (!script) {
    console.warn('[InkWise Builder] Bootstrap JSON not found. Falling back to legacy window payload.');
    return window.inkwiseTemplateBootstrap || {};
  }

  try {
    return JSON.parse(script.textContent || '{}');
  } catch (error) {
    console.error('[InkWise Builder] Failed to parse bootstrap JSON.', error);
    return {};
  }
}

const bootstrap = readBootstrapPayload();
const rootEl = document.getElementById('template-builder-app');

if (!rootEl) {
  console.error('[InkWise Builder] Root element not found.');
} else {
  rootEl.classList.add('inkwise-builder--hydrated');

  const root = createRoot(rootEl);
  root.render(
    <React.StrictMode>
      <BuilderApp bootstrap={bootstrap} />
    </React.StrictMode>,
  );
}
