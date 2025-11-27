import React, { createContext, useContext, useMemo, useReducer } from 'react';
import PropTypes from 'prop-types';

import { createLayer, createPage } from '../utils/pageFactory';

const BuilderStoreContext = createContext(null);

function normalizePages(template) {
  const rawPages = template?.design?.pages;

  if (Array.isArray(rawPages) && rawPages.length > 0) {
    return rawPages.map((page, index) => createPage(page, index));
  }

  return [createPage(null, 0)];
}

const initialState = ({ template }) => {
  const pages = normalizePages(template);
  const fallbackPageId = pages[0]?.id ?? 'page-0';
  const configuredActiveId = template?.design?.activePageId;
  const activePageExists = pages.some((page) => page.id === configuredActiveId);
  const activePageId = activePageExists ? configuredActiveId : fallbackPageId;

  return {
    template,
    pages,
    activePageId,
    selectedLayerId: getFirstLayerId(pages, activePageId),
    zoom: 1,
    panX: 0,
    panY: 0,
    layersPanelOpen: true,
    inspectorOpen: true,
    recentlyUploadedImages: [],
    showPreviewModal: false,
    history: {
      undoStack: [],
      redoStack: [],
    },
  };
};

function reducer(state, action) {
  switch (action.type) {
    case 'SELECT_PAGE':
      return {
        ...state,
        activePageId: action.pageId,
        selectedLayerId: getFirstLayerId(state.pages, action.pageId),
      };
    case 'ADD_PAGE': {
      const newPage = createPage(action.page, state.pages.length);
      const nextPages = [...state.pages, newPage];
      return commitPages(state, nextPages, {
        activePageId: newPage.id,
        selectedLayerId: newPage.nodes[0]?.id ?? null,
      });
    }
    case 'DELETE_PAGE': {
      if (state.pages.length <= 1) {
        // Don't allow deleting the last page
        return state;
      }

      const pageIndex = state.pages.findIndex((page) => page.id === action.pageId);
      if (pageIndex === -1) {
        return state;
      }

      const nextPages = state.pages.filter((page) => page.id !== action.pageId);
      let nextActivePageId = state.activePageId;

      // If we're deleting the active page, switch to the previous page or first page
      if (action.pageId === state.activePageId) {
        const newActiveIndex = pageIndex > 0 ? pageIndex - 1 : 0;
        nextActivePageId = nextPages[newActiveIndex]?.id ?? nextPages[0]?.id ?? null;
      }

      const nextSelectedLayerId = getFirstLayerId(nextPages, nextActivePageId);

      return commitPages(state, nextPages, {
        activePageId: nextActivePageId,
        selectedLayerId: nextSelectedLayerId,
      });
    }
    case 'UPDATE_PAGE_NAME': {
      const pages = state.pages.map((page) => (
        page.id === action.pageId ? { ...page, name: action.name } : page
      ));
      return commitPages(state, pages);
    }
    case 'UPDATE_PAGE_PROPS': {
      const pages = state.pages.map((page) => (
        page.id === action.pageId ? { ...page, ...action.props } : page
      ));
      return commitPages(state, pages);
    }
    case 'SELECT_LAYER':
      return {
        ...state,
        selectedLayerId: action.layerId,
      };
    case 'UPDATE_LAYER_PROPS': {
      const pages = state.pages.map((page) => {
        if (page.id !== action.pageId) {
          return page;
        }

        const nodes = page.nodes.map((node) => (
          node.id === action.layerId ? { ...node, ...action.props } : node
        ));

        return { ...page, nodes };
      });

      return commitPages(state, pages, {
        selectedLayerId: ensureSelectedLayer(state, pages, action.layerId),
      });
    }
    case 'ADD_LAYER': {
      const pages = state.pages.map((page) => {
        if (page.id !== action.pageId) {
          return page;
        }
        const nodes = [...page.nodes, action.layer];
        return { ...page, nodes };
      });

      return commitPages(state, pages, {
        selectedLayerId: action.layer.id,
      });
    }
    case 'REMOVE_LAYER': {
      const pages = state.pages.map((page) => {
        if (page.id !== action.pageId) {
          return page;
        }
        const nodes = page.nodes.filter((node) => node.id !== action.layerId);
        return { ...page, nodes };
      });

      const nextSelected = ensureSelectedLayer(state, pages, null);

      return commitPages(state, pages, {
        selectedLayerId: nextSelected,
      });
    }
    case 'DUPLICATE_LAYER': {
      const pages = state.pages.map((page) => {
        if (page.id !== action.pageId) {
          return page;
        }
        const target = page.nodes.find((node) => node.id === action.layerId);
        if (!target) {
          return page;
        }
        const clone = {
          ...createLayer(target.type, page, {
            ...target,
            id: undefined,
            name: `${target.name} copy`,
            frame: target.frame ? {
              ...target.frame,
              x: target.frame.x + 24,
              y: target.frame.y + 24,
            } : target.frame,
          }),
        };
        return { ...page, nodes: [...page.nodes, clone] };
      });

      const addedLayer = pages
        .find((page) => page.id === action.pageId)
        ?.nodes.slice(-1)[0];

      return commitPages(state, pages, {
        selectedLayerId: addedLayer?.id ?? state.selectedLayerId,
      });
    }
    case 'REORDER_LAYER': {
      const pages = state.pages.map((page) => {
        if (page.id !== action.pageId) {
          return page;
        }
        const index = page.nodes.findIndex((node) => node.id === action.layerId);
        if (index === -1) {
          return page;
        }

        const nodes = [...page.nodes];
        const [layer] = nodes.splice(index, 1);
        if (action.direction === 'FORWARD') {
          nodes.splice(Math.min(nodes.length, index + 1), 0, layer);
        } else if (action.direction === 'BACKWARD') {
          nodes.splice(Math.max(0, index - 1), 0, layer);
        } else if (action.direction === 'FRONT') {
          nodes.push(layer);
        } else if (action.direction === 'BACK') {
          nodes.unshift(layer);
        } else if (typeof action.position === 'number') {
          const clamped = Math.max(0, Math.min(nodes.length, action.position));
          nodes.splice(clamped, 0, layer);
        } else {
          nodes.push(layer);
        }

        return { ...page, nodes };
      });

      return commitPages(state, pages);
    }
    case 'BEGIN_LAYER_TRANSFORM':
      return {
        ...state,
        history: pushHistory(state),
      };
    case 'UPDATE_LAYER_FRAME': {
      const pages = state.pages.map((page) => {
        if (page.id !== action.pageId) {
          return page;
        }

        const nodes = page.nodes.map((node) => {
          if (node.id !== action.layerId || node.locked) {
            return node;
          }

          const nextFrame = buildNextFrame(node.frame, action.frame, page);
          return {
            ...node,
            frame: nextFrame,
          };
        });

        return { ...page, nodes };
      });

      return commitPages(
        state,
        pages,
        { selectedLayerId: action.layerId },
        action.trackHistory !== false,
      );
    }
    case 'NUDGE_LAYER': {
      const page = state.pages.find((item) => item.id === action.pageId);
      if (!page) {
        return state;
      }
      const target = page.nodes.find((node) => node.id === action.layerId);
      if (!target || target.locked) {
        return state;
      }

      const deltaX = action.dx ?? 0;
      const deltaY = action.dy ?? 0;

      const pages = state.pages.map((item) => {
        if (item.id !== action.pageId) {
          return item;
        }
        const nodes = item.nodes.map((node) => {
          if (node.id !== action.layerId) {
            return node;
          }
          const baseFrame = node.frame ?? createFallbackFrame(item);
          return {
            ...node,
            frame: {
              ...baseFrame,
              x: Math.round(baseFrame.x + deltaX),
              y: Math.round(baseFrame.y + deltaY),
            },
          };
        });
        return { ...item, nodes };
      });

      return commitPages(
        state,
        pages,
        { selectedLayerId: action.layerId },
        action.trackHistory !== false,
      );
    }
    case 'UPDATE_ZOOM': {
      const nextZoom = Math.min(Math.max(action.value, 0.25), 3);
      return {
        ...state,
        zoom: Number(nextZoom.toFixed(2)),
      };
    }
    case 'UPDATE_PAN':
      return {
        ...state,
        panX: action.panX,
        panY: action.panY,
      };
    case 'SET_LAYERS_PANEL_OPEN':
      return {
        ...state,
        layersPanelOpen: action.open,
      };
    case 'ADD_RECENTLY_UPLOADED_IMAGE': {
      const maxRecentImages = 10; // Keep only the last 10 uploaded images
      const newImage = {
        id: `recent-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        dataUrl: action.dataUrl,
        fileName: action.fileName,
        uploadedAt: new Date().toISOString(),
      };

      const updatedImages = [newImage, ...state.recentlyUploadedImages.filter(img => img.dataUrl !== action.dataUrl)].slice(0, maxRecentImages);

      return {
        ...state,
        recentlyUploadedImages: updatedImages,
      };
    }
    case 'DELETE_RECENTLY_UPLOADED_IMAGE': {
      const idToRemove = action.id;
      const updated = (state.recentlyUploadedImages || []).filter((img) => img.id !== idToRemove);
      return {
        ...state,
        recentlyUploadedImages: updated,
      };
    }
    case 'SET_RECENTLY_UPLOADED_IMAGES': {
      // action.images expected to be an array of { id, dataUrl, fileName, uploadedAt }
      return {
        ...state,
        recentlyUploadedImages: Array.isArray(action.images) ? action.images : [],
      };
    }
    case 'PUSH_HISTORY':
      return {
        ...state,
        history: {
          undoStack: [...state.history.undoStack, action.snapshot],
          redoStack: [],
        },
      };
    case 'UNDO': {
      if (state.history.undoStack.length === 0) {
        return state;
      }
      const undoStack = [...state.history.undoStack];
      const last = undoStack.pop();
      const restoredPages = clonePages(last.pages);
      const nextActiveId = restoredPages.some((page) => page.id === state.activePageId)
        ? state.activePageId
        : restoredPages[0]?.id ?? null;
      const nextSelected = ensureSelectedLayer(
        state,
        restoredPages,
        state.selectedLayerId,
        nextActiveId,
      );
      return {
        ...state,
        pages: restoredPages,
        activePageId: nextActiveId,
        selectedLayerId: nextSelected,
        history: {
          undoStack,
          redoStack: [
            {
              pages: clonePages(state.pages),
            },
            ...state.history.redoStack,
          ],
        },
      };
    }
    case 'REDO': {
      if (state.history.redoStack.length === 0) {
        return state;
      }
      const [next, ...rest] = state.history.redoStack;
      const restoredPages = clonePages(next.pages);
      const nextActiveId = restoredPages.some((page) => page.id === state.activePageId)
        ? state.activePageId
        : restoredPages[0]?.id ?? null;
      const nextSelected = ensureSelectedLayer(
        state,
        restoredPages,
        state.selectedLayerId,
        nextActiveId,
      );
      return {
        ...state,
        pages: restoredPages,
        activePageId: nextActiveId,
        selectedLayerId: nextSelected,
        history: {
          undoStack: [...state.history.undoStack, { pages: clonePages(state.pages) }],
          redoStack: rest,
        },
      };
    }
    case 'SHOW_PREVIEW_MODAL':
      return {
        ...state,
        showPreviewModal: true,
      };
    case 'HIDE_PREVIEW_MODAL':
      return {
        ...state,
        showPreviewModal: false,
      };
    default:
      return state;
  }
}

export function BuilderStoreProvider({ template, routes, flags, user, csrfToken, children }) {
  const [state, dispatch] = useReducer(reducer, { template }, initialState);

  // Load persisted recent images from IndexedDB when provider mounts.
  React.useEffect(() => {
    let mounted = true;
    async function loadRecent() {
      try {
        const dbModule = await import('../utils/recentImagesDB');
        const images = await dbModule.getAllImages();
        if (!mounted) return;
        // Convert stored blobs to object URLs for immediate use in UI
        const mapped = images
          .slice()
          .sort((a, b) => new Date(b.uploadedAt) - new Date(a.uploadedAt))
          .slice(0, 10)
          .map((item) => ({
            id: item.id,
            dataUrl: item.blob ? URL.createObjectURL(item.blob) : '',
            fileName: item.fileName,
            uploadedAt: item.uploadedAt,
          }));

        dispatch({ type: 'SET_RECENTLY_UPLOADED_IMAGES', images: mapped });
      } catch (err) {
        // ignore for now
        // console.error('Failed to load persisted recent images', err);
      }
    }

    loadRecent();
    return () => { mounted = false; };
  }, []);

  const value = useMemo(() => ({
    state,
    dispatch,
    routes,
    flags,
    user,
    csrfToken,
  }), [state, routes, flags, user, csrfToken]);

  return (
    <BuilderStoreContext.Provider value={value}>
      {children}
    </BuilderStoreContext.Provider>
  );
}

BuilderStoreProvider.propTypes = {
  template: PropTypes.object,
  routes: PropTypes.object,
  flags: PropTypes.object,
  user: PropTypes.object,
  csrfToken: PropTypes.string,
  children: PropTypes.node.isRequired,
};

BuilderStoreProvider.defaultProps = {
  template: {},
  routes: {},
  flags: {},
  user: null,
  csrfToken: '',
};

export function useBuilderStore() {
  const context = useContext(BuilderStoreContext);
  if (!context) {
    throw new Error('useBuilderStore must be used within BuilderStoreProvider');
  }
  return context;
}

function getFirstLayerId(pages, pageId) {
  const page = pages.find((item) => item.id === pageId) ?? pages[0];
  if (!page || !Array.isArray(page.nodes) || page.nodes.length === 0) {
    return null;
  }
  const firstVisible = page.nodes.find((node) => node.visible !== false);
  return (firstVisible ?? page.nodes[0]).id;
}

function commitPages(state, pages, overrides = {}, trackHistory = true) {
  const history = trackHistory ? pushHistory(state) : state.history;
  return {
    ...state,
    ...overrides,
    pages,
    history,
  };
}

function pushHistory(state) {
  return {
    undoStack: [...state.history.undoStack, { pages: clonePages(state.pages) }],
    redoStack: [],
  };
}

function clonePages(pages) {
  return pages.map((page) => ({
    ...page,
    metadata: { ...(page.metadata ?? {}) },
    nodes: (Array.isArray(page.nodes) ? page.nodes : []).map((node) => ({
      ...node,
      frame: node.frame ? { ...node.frame } : null,
      metadata: { ...(node.metadata ?? {}) },
    })),
  }));
}

function ensureSelectedLayer(state, pages, preferredLayerId, activePageIdOverride) {
  const activePageId = activePageIdOverride ?? state.activePageId;
  const page = pages.find((item) => item.id === activePageId) ?? pages[0];
  if (!page) {
    return null;
  }

  if (preferredLayerId) {
    const exists = page.nodes.some((node) => node.id === preferredLayerId);
    if (exists) {
      return preferredLayerId;
    }
  }

  return getFirstLayerId(pages, activePageId);
}

function buildNextFrame(currentFrame, proposedFrame, page) {
  const baseFrame = currentFrame ?? createFallbackFrame(page);
  if (!proposedFrame) {
    return baseFrame;
  }
  return {
    x: Math.round(proposedFrame.x ?? baseFrame.x),
    y: Math.round(proposedFrame.y ?? baseFrame.y),
    width: Math.max(1, Math.round(proposedFrame.width ?? baseFrame.width)),
    height: Math.max(1, Math.round(proposedFrame.height ?? baseFrame.height)),
    rotation: Math.round(proposedFrame.rotation ?? baseFrame.rotation ?? 0),
  };
}

function createFallbackFrame(page) {
  return {
    x: Math.round(page.width * 0.1),
    y: Math.round(page.height * 0.1),
    width: Math.round(page.width * 0.6),
    height: Math.round(page.height * 0.25),
    rotation: 0,
  };
}
