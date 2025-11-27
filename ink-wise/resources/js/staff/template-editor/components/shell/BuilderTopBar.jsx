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

function formatManualSaveStatus({ isSavingTemplate, saveError, lastManualSaveAt }) {
  if (isSavingTemplate) {
    return 'Saving template…';
  }
  if (saveError) {
    return saveError;
  }
  if (lastManualSaveAt) {
    const date = new Date(lastManualSaveAt);
    if (!Number.isNaN(date.getTime())) {
      return `Template saved at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }
  }
  return null;
}

export function BuilderTopBar({
  autosaveStatus,
  lastSavedAt,
  onSaveTemplate,
  isSavingTemplate,
  saveError,
  lastManualSaveAt,
}) {
  const { state, dispatch, routes } = useBuilderStore();
  const templateName = state.template?.name ?? 'Untitled template';

  const handleUndo = () => dispatch({ type: 'UNDO' });
  const handleRedo = () => dispatch({ type: 'REDO' });
  const handlePreview = () => dispatch({ type: 'SHOW_PREVIEW_MODAL' });
  const handleSave = () => {
    if (typeof onSaveTemplate === 'function' && !isSavingTemplate) {
      onSaveTemplate();
    }
  };
  const statusLabel = formatSavedLabel(autosaveStatus, lastSavedAt);
  const isSaving = autosaveStatus === 'saving';
  const hasError = autosaveStatus === 'error';
  const manualSaveStatusLabel = formatManualSaveStatus({ isSavingTemplate, saveError, lastManualSaveAt });
  const manualStatusClass = saveError
    ? 'builder-topbar__status--error'
    : isSavingTemplate
      ? 'builder-topbar__status--saving'
      : 'builder-topbar__status--success';
  const saveButtonLabel = isSavingTemplate ? 'Saving template…' : 'Save template';
  const saveDisabled = typeof onSaveTemplate !== 'function' || isSavingTemplate;

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
        <div className="builder-topbar__status-column" role="status" aria-live="polite">
          <span
            className={`builder-topbar__status${isSaving ? ' builder-topbar__status--saving' : ''}${hasError ? ' builder-topbar__status--error' : ''}`}
          >
            {statusLabel}
          </span>
          {manualSaveStatusLabel && (
            <span className={`builder-topbar__status builder-topbar__status--sub ${manualStatusClass}`}>
              {manualSaveStatusLabel}
            </span>
          )}
        </div>
        <button type="button" className="builder-btn" aria-label="Preview" onClick={handlePreview}>
          Preview
        </button>
        <button
          type="button"
          className="builder-btn builder-btn--primary"
          aria-label="Save template"
          onClick={handleSave}
          disabled={saveDisabled}
        >
          {saveButtonLabel}
        </button>
      </div>
    </header>
  );
}

BuilderTopBar.propTypes = {
  autosaveStatus: PropTypes.string,
  lastSavedAt: PropTypes.string,
  onSaveTemplate: PropTypes.func,
  isSavingTemplate: PropTypes.bool,
  saveError: PropTypes.string,
  lastManualSaveAt: PropTypes.string,
};

BuilderTopBar.defaultProps = {
  autosaveStatus: 'idle',
  lastSavedAt: null,
  onSaveTemplate: null,
  isSavingTemplate: false,
  saveError: null,
  lastManualSaveAt: null,
};
