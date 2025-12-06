import React, { useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';

import { useBuilderStore } from '../../state/BuilderStore';
import { derivePageLabel } from '../../utils/pageFactory';

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
  const [isSaveMenuOpen, setIsSaveMenuOpen] = useState(false);
  const saveMenuRef = useRef(null);
  const saveButtonRef = useRef(null);

  const handleUndo = () => dispatch({ type: 'UNDO' });
  const handleRedo = () => dispatch({ type: 'REDO' });
  const handlePreview = () => dispatch({ type: 'SHOW_PREVIEW_MODAL' });
  const closeSaveMenu = () => setIsSaveMenuOpen(false);
  const handleSave = () => {
    if (saveDisabled) {
      return;
    }
    setIsSaveMenuOpen((open) => !open);
  };
  const handleSaveChoice = (pageId) => {
    closeSaveMenu();
    if (typeof onSaveTemplate === 'function' && !isSavingTemplate) {
      onSaveTemplate({ pageId: pageId ?? null });
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
  const pages = state.pages ?? [];

  useEffect(() => {
    if (!isSaveMenuOpen) {
      return undefined;
    }

    const handleOutsideClick = (event) => {
      const menuNode = saveMenuRef.current;
      const buttonNode = saveButtonRef.current;
      if (menuNode && menuNode.contains(event.target)) {
        return;
      }
      if (buttonNode && buttonNode.contains(event.target)) {
        return;
      }
      closeSaveMenu();
    };

    const handleEsc = (event) => {
      if (event.key === 'Escape') {
        closeSaveMenu();
      }
    };

    document.addEventListener('mousedown', handleOutsideClick, true);
    document.addEventListener('keydown', handleEsc, true);

    return () => {
      document.removeEventListener('mousedown', handleOutsideClick, true);
      document.removeEventListener('keydown', handleEsc, true);
    };
  }, [isSaveMenuOpen]);

  useEffect(() => {
    if (saveDisabled && isSaveMenuOpen) {
      setIsSaveMenuOpen(false);
    }
  }, [saveDisabled, isSaveMenuOpen]);

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
        <div className="builder-topbar__save">
          <button
            type="button"
            ref={saveButtonRef}
            className="builder-btn builder-btn--primary"
            aria-haspopup="menu"
            aria-expanded={isSaveMenuOpen}
            aria-label="Save template"
            onClick={handleSave}
            disabled={saveDisabled}
          >
            {saveButtonLabel}
          </button>
          {isSaveMenuOpen && !saveDisabled && (
            <div className="builder-save-menu" ref={saveMenuRef} role="menu" aria-label="Select pages to save">
              <div className="builder-save-menu__header">Select which pages to save</div>
              <ul className="builder-save-menu__list">
                {pages.map((page, index) => {
                  const label = derivePageLabel(page?.pageType, index, pages.length);
                  const secondary = typeof page?.name === 'string' && page.name.trim() !== '' && page.name.trim() !== label
                    ? page.name.trim()
                    : null;
                  return (
                    <li key={page.id} className="builder-save-menu__item">
                      <button
                        type="button"
                        className="builder-save-menu__action"
                        onClick={() => handleSaveChoice(page.id)}
                        role="menuitem"
                      >
                        <span className="builder-save-menu__label">{label}</span>
                        {secondary && <span className="builder-save-menu__hint">{secondary}</span>}
                      </button>
                    </li>
                  );
                })}
              </ul>
              <button
                type="button"
                className="builder-save-menu__action builder-save-menu__action--full"
                onClick={() => handleSaveChoice(null)}
                role="menuitem"
              >
                Save all pages
              </button>
            </div>
          )}
        </div>
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
