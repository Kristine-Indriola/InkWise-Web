import { useEffect } from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

const ZOOM_STEP = 0.1;
const ZOOM_MIN = 0.25;
const ZOOM_MAX = 3;

function clampZoom(value) {
  return Math.min(Math.max(value, ZOOM_MIN), ZOOM_MAX);
}

export function BuilderHotkeys() {
  const { state, dispatch } = useBuilderStore();

  useEffect(() => {
    const handleKeyDown = (event) => {
      if (event.defaultPrevented) {
        return;
      }

      const target = event.target;
      if (target instanceof HTMLElement) {
        const tagName = target.tagName;
        if (target.isContentEditable || tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT') {
          return;
        }
      }

      const activePage = state.pages?.find((page) => page.id === state.activePageId) ?? state.pages?.[0];
      const selectedLayerId = state.selectedLayerId;
      const selectedLayer = activePage?.nodes?.find((node) => node.id === selectedLayerId);

      const isModifierPressed = event.ctrlKey || event.metaKey;

      if (isModifierPressed) {
        let handled = false;
        let nextZoom = state.zoom ?? 1;

        switch (event.key) {
          case '=':
          case '+':
            nextZoom = clampZoom(nextZoom + ZOOM_STEP);
            handled = true;
            break;
          case '-':
          case '_':
            nextZoom = clampZoom(nextZoom - ZOOM_STEP);
            handled = true;
            break;
          case '0':
            nextZoom = 1;
            handled = true;
            break;
          default:
            break;
        }

        if (handled) {
          event.preventDefault();
          dispatch({ type: 'UPDATE_ZOOM', value: nextZoom });
        }
        return;
      }

      if (!activePage || !selectedLayer || selectedLayer.locked) {
        return;
      }

      switch (event.key) {
        case 'ArrowUp':
        case 'ArrowDown':
        case 'ArrowLeft':
        case 'ArrowRight': {
          const step = event.shiftKey ? 10 : 1;
          let dx = 0;
          let dy = 0;
          if (event.key === 'ArrowLeft') dx = -step;
          if (event.key === 'ArrowRight') dx = step;
          if (event.key === 'ArrowUp') dy = -step;
          if (event.key === 'ArrowDown') dy = step;

          dispatch({ type: 'BEGIN_LAYER_TRANSFORM' });
          dispatch({
            type: 'NUDGE_LAYER',
            pageId: activePage.id,
            layerId: selectedLayerId,
            dx,
            dy,
            trackHistory: false,
          });
          event.preventDefault();
          break;
        }
        case 'Delete':
        case 'Backspace': {
          const confirmed = window.confirm(`Delete "${selectedLayer.name}"? You can undo this action if needed.`);
          if (confirmed) {
            dispatch({ type: 'REMOVE_LAYER', pageId: activePage.id, layerId: selectedLayerId });
            event.preventDefault();
          }
          break;
        }
        default:
          break;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => {
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [dispatch, state]);

  return null;
}
