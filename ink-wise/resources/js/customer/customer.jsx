import React from 'react';
import { createRoot } from 'react-dom/client';
import CustomerTopbar from './components/CustomerTopbar.jsx';

const bootTopbar = () => {
  const mountEl = document.getElementById('customer-topbar-root');
  if (!mountEl) {
    return;
  }

  try {
    const raw = mountEl.getAttribute('data-props') ?? '{}';
    const props = JSON.parse(raw);
    const root = createRoot(mountEl);
    root.render(<CustomerTopbar {...props} />);
  } catch (error) {
    console.error('[InkWise] Failed to mount customer topbar', error);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootTopbar, { once: true });
} else {
  bootTopbar();
}
