import React, { useCallback, useEffect, useRef, useState } from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

export function PagesPanel() {
  const { state, dispatch } = useBuilderStore();
  const [editingPageId, setEditingPageId] = useState(null);
  const [draftName, setDraftName] = useState('');
  const inputRef = useRef(null);
  const skipBlurCommitRef = useRef(false);
  const cancelCommitRef = useRef(false);

  const stopEditing = useCallback(() => {
    setEditingPageId(null);
    setDraftName('');
  }, []);

  useEffect(() => {
    if (!editingPageId) {
      return;
    }

    const inputNode = inputRef.current;
    if (!inputNode) {
      return;
    }

    const frame = requestAnimationFrame(() => {
      if (inputRef.current === inputNode) {
        inputNode.focus();
        inputNode.select();
      }
    });

    return () => cancelAnimationFrame(frame);
  }, [editingPageId]);

  useEffect(() => {
    if (!editingPageId) {
      return;
    }

    const exists = state.pages.some((page) => page.id === editingPageId);
    if (!exists) {
      stopEditing();
    }
  }, [state.pages, editingPageId, stopEditing]);

  const handleAddPage = () => {
    dispatch({ type: 'ADD_PAGE', page: null });
  };

  const handleSelectPage = (pageId) => {
    dispatch({ type: 'SELECT_PAGE', pageId });
  };

  const handleDeletePage = (pageId, event) => {
    event.stopPropagation(); // Prevent triggering page selection
    dispatch({ type: 'DELETE_PAGE', pageId });
  };

  const beginEditingPage = (event, page) => {
    event.preventDefault();
    event.stopPropagation();
    cancelCommitRef.current = false;
    skipBlurCommitRef.current = false;
    handleSelectPage(page.id);
    setEditingPageId(page.id);
    setDraftName(page.name ?? '');
  };

  const commitRename = (pageId, name) => {
    const trimmed = name.trim();
    const target = state.pages.find((item) => item.id === pageId);

    if (!target) {
      stopEditing();
      return;
    }

    if (!trimmed) {
      stopEditing();
      return;
    }

    if (target.name !== trimmed) {
      dispatch({ type: 'UPDATE_PAGE_NAME', pageId, name: trimmed });
    }

    stopEditing();
  };

  const handleInputBlur = (pageId) => {
    if (cancelCommitRef.current) {
      cancelCommitRef.current = false;
      return;
    }

    if (skipBlurCommitRef.current) {
      skipBlurCommitRef.current = false;
      return;
    }

    commitRename(pageId, draftName);
  };

  const handleInputKeyDown = (event, pageId) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      skipBlurCommitRef.current = true;
      commitRename(pageId, draftName);
    } else if (event.key === 'Escape') {
      event.preventDefault();
      cancelCommitRef.current = true;
      stopEditing();
    }
  };

  const renderPageItem = (page) => {
    const isActive = page.id === state.activePageId;
    const isEditing = editingPageId === page.id;

    const handleItemKeyDown = (event) => {
      if (isEditing) {
        return;
      }
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handleSelectPage(page.id);
      }
    };

    return (
      <div key={page.id} className="pages-panel__item-wrapper">
        <div
          className={`pages-panel__item ${isActive ? 'is-active' : ''}${isEditing ? ' is-editing' : ''}`}
          role="button"
          tabIndex={0}
          onClick={(event) => {
            if (isEditing) {
              event.preventDefault();
              return;
            }
            handleSelectPage(page.id);
          }}
          onKeyDown={handleItemKeyDown}
          aria-pressed={isActive}
          aria-current={isActive ? 'page' : undefined}
        >
          <span className="pages-panel__thumb" aria-hidden="true">
            <span className="pages-panel__thumb-page" />
          </span>
          {isEditing ? (
            <input
              ref={(node) => { inputRef.current = node; }}
              className="pages-panel__input"
              type="text"
              value={draftName}
              onChange={(event) => setDraftName(event.target.value)}
              onBlur={() => handleInputBlur(page.id)}
              onKeyDown={(event) => handleInputKeyDown(event, page.id)}
              onClick={(event) => event.stopPropagation()}
              aria-label="Rename page"
            />
          ) : (
            <span
              className="pages-panel__label"
              onClick={(event) => {
                event.stopPropagation();
                beginEditingPage(event, page);
              }}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                  event.stopPropagation();
                  beginEditingPage(event, page);
                }
              }}
              role="button"
              tabIndex={0}
              title="Click to rename page"
            >
              {page.name}
            </span>
          )}
        </div>
        {state.pages.length > 1 && (
          <div className="pages-panel__item-controls">
            <button
              type="button"
              className="pages-panel__control pages-panel__control--delete"
              onClick={(event) => handleDeletePage(page.id, event)}
              aria-label={`Delete ${page.name}`}
              title={`Delete ${page.name}`}
            >
              <i className="fas fa-trash-alt" aria-hidden="true"></i>
            </button>
          </div>
        )}
      </div>
    );
  };

  return (
    <section className="pages-panel" aria-label="Pages">
      <header className="pages-panel__header">
        <h2>Pages</h2>
        <button type="button" className="builder-btn" onClick={handleAddPage}>
          Add page
        </button>
      </header>
      <div className="pages-panel__list" role="list">
        {state.pages.map(renderPageItem)}
      </div>
    </section>
  );
}
