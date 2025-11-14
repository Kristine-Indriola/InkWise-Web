import React from 'react';
import PropTypes from 'prop-types';

import { useBuilderStore } from '../../state/BuilderStore';

function formatSavedLabel(autosaveStatus, lastSavedAt) {
  switch (autosaveStatus) {
    case 'saving':
      return 'Autosaving...';
    case 'dirty':
      return 'Unsaved changes';
    case 'error':
      return 'Autosave failed';
    case 'saved': {
      if (!lastSavedAt) {
        return 'All changes saved';
      }
      const date = new Date(lastSavedAt);
      if (Number.isNaN(date.getTime())) {
        return 'All changes saved';
      }
      return `Saved at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }
    case 'idle':
    default:
      if (lastSavedAt) {
        const date = new Date(lastSavedAt);
        if (!Number.isNaN(date.getTime())) {
          return `Last saved at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
        }
      }
      return 'Ready';
  }
}

export function BuilderTopBar({ autosaveStatus, lastSavedAt }) {
  const { state, dispatch, routes } = useBuilderStore();
  const templateName = state.template?.name ?? 'Untitled template';

  const handleUndo = () => dispatch({ type: 'UNDO' });
  const handleRedo = () => dispatch({ type: 'REDO' });
  const statusLabel = formatSavedLabel(autosaveStatus, lastSavedAt);
  const isSaving = autosaveStatus === 'saving';
  const hasError = autosaveStatus === 'error';

  return (
    <header className="builder-topbar" role="banner">
      <div className="builder-topbar__left">
        <a href={routes.index} className="builder-topbar__back" aria-label="Back to templates">
          ← Templates
        </a>
        <div className="builder-topbar__title">
          <span className="builder-topbar__template-name" title={templateName}>{templateName}</span>
          {state.template?.category && (
            <span className="builder-topbar__meta">{state.template.category}</span>
          )}
        </div>
      </div>
      <div className="builder-topbar__center" role="toolbar" aria-label="Canvas actions">
        <button type="button" onClick={handleUndo} className="builder-btn" aria-label="Undo">
          Undo
        </button>
        <button type="button" onClick={handleRedo} className="builder-btn" aria-label="Redo">
          Redo
        </button>
        <button type="button" className="builder-btn" aria-label="Toggle rulers" disabled>
          Rulers
        </button>
        <button type="button" className="builder-btn" aria-label="Toggle guides" disabled>
          Guides
        </button>
      </div>
      <div className="builder-topbar__right">
        <span
          className={`builder-topbar__status${isSaving ? ' builder-topbar__status--saving' : ''}${hasError ? ' builder-topbar__status--error' : ''}`}
          role="status"
          aria-live="polite"
        >
          {statusLabel}
        </span>
        <button type="button" className="builder-btn" aria-label="Preview" disabled>
          Preview
        </button>
        <button type="button" className="builder-btn builder-btn--primary" aria-label="Save template" disabled>
          Save template
        </button>
      </div>
    </header>
  );
}

BuilderTopBar.propTypes = {
  autosaveStatus: PropTypes.string,
  lastSavedAt: PropTypes.string,
};

BuilderTopBar.defaultProps = {
  autosaveStatus: 'idle',
  lastSavedAt: null,
};
