import React, { useMemo, useState, useRef } from 'react';

import { useBuilderStore } from '../../state/BuilderStore';
import { createLayer } from '../../utils/pageFactory';

// Helper functions to constrain layers within safe zone
function resolveInsets(zone) {
  if (!zone) {
    return { top: 0, right: 0, bottom: 0, left: 0 };
  }

  const toNumber = (value) => {
    if (typeof value === 'number') return value;
    if (typeof value === 'string') {
      const parsed = parseFloat(value);
      return Number.isNaN(parsed) ? 0 : parsed;
    }
    return 0;
  };

  const fallback = toNumber(zone.margin ?? zone.all ?? 0);

  return {
    top: toNumber(zone.top ?? fallback),
    right: toNumber(zone.right ?? fallback),
    bottom: toNumber(zone.bottom ?? fallback),
    left: toNumber(zone.left ?? fallback),
  };
}

function constrainFrameToSafeZone(frame, page, safeInsets) {
  const minX = safeInsets.left;
  const maxX = page.width - safeInsets.right - frame.width;
  const minY = safeInsets.top;
  const maxY = page.height - safeInsets.bottom - frame.height;

  return {
    ...frame,
    x: Math.max(minX, Math.min(maxX, frame.x)),
    y: Math.max(minY, Math.min(maxY, frame.y)),
  };
}

const TOOL_SECTIONS = [
  { id: 'text', label: 'Text', description: 'Add headings, body copy, and typography styles.', icon: 'fa-solid fa-t' },
  { id: 'images', label: 'Upload', description: 'Upload customer photos or choose from brand assets.', icon: 'fa-solid fa-cloud-arrow-up' },
  { id: 'shapes', label: 'Shapes', description: 'Insert vector shapes, lines, and frames.', icon: 'fa-solid fa-shapes' },
  { id: 'photos', label: 'Photos', description: 'Add photos and images.', icon: 'fa-solid fa-image' },
  { id: 'icons', label: 'Icons', description: 'Insert icons and symbols.', icon: 'fa-solid fa-icons' },
  { id: 'draw', label: 'Draw', description: 'Draw shapes and lines.', icon: 'fa-solid fa-pencil' },
  { id: 'videos', label: 'Videos', description: 'Add videos.', icon: 'fa-solid fa-file-video' },
  { id: 'background', label: 'Background', description: 'Set background.', icon: 'fa-solid fa-palette' },
  { id: 'layers', label: 'Layers', description: 'Manage layers.', icon: 'fa-solid fa-layer-group' },
  { id: 'quotes', label: 'Quotes', description: 'Add quotes.', icon: 'fa-solid fa-quote-left' },
];

export function ToolSidebar({ isSidebarHidden, onToggleSidebar }) {
  const [activeTool, setActiveTool] = useState('text');
  const { state, dispatch } = useBuilderStore();
  const fileInputRef = useRef(null);
  const activePage = useMemo(
    () => state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0],
    [state.pages, state.activePageId],
  );

  const safeInsets = useMemo(() => resolveInsets(activePage?.safeZone), [activePage?.safeZone]);

  const handleAddText = (preset) => {
    if (!activePage) {
      return;
    }

    const layer = createLayer('text', activePage, {
      name: preset.name,
      content: preset.content,
      fontSize: preset.fontSize,
      textAlign: preset.align ?? 'center',
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const handleAddShape = (variant) => {
    if (!activePage) {
      return;
    }

    const layer = createLayer('shape', activePage, {
      name: variant === 'circle' ? 'Circle' : 'Rectangle',
      variant,
      borderRadius: variant === 'circle' ? 9999 : 16,
    });

    if (variant === 'circle') {
      layer.frame = {
        ...layer.frame,
        width: Math.min(activePage.width * 0.3, layer.frame?.width ?? 280),
        height: Math.min(activePage.width * 0.3, layer.frame?.height ?? 280),
      };
    }

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const handleAddImagePlaceholder = () => {
    if (!activePage) {
      return;
    }

    // Trigger file input for image upload
    fileInputRef.current?.click();
  };

  const handleFileSelect = async (event) => {
    const file = event.target.files[0];
    if (!file || !activePage) {
      console.log('No file selected or no active page');
      return;
    }

    console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);

    // Check if file is an image
    if (!file.type.startsWith('image/')) {
      alert('Please select an image file.');
      return;
    }

    // Check file size (limit to 10MB to prevent memory issues)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
      alert('Image file is too large. Please select an image smaller than 10MB.');
      return;
    }


    // Create a FileReader to read the image and create a data URL for the canvas
    const reader = new FileReader();
    reader.onload = async (e) => {
      try {
        const imageUrl = e.target.result;
        console.log('Image loaded, data URL length:', imageUrl?.length ?? 0);

        // Validate that we got a valid data URL
        if (!imageUrl || !imageUrl.startsWith('data:image/')) {
          console.error('Invalid data URL:', imageUrl);
          alert('Failed to read the image file. Please try again.');
          return;
        }

        // Measure image natural size so we can create a larger frame on the canvas
        const img = new Image();
        img.onload = async () => {
          try {
            const naturalW = img.naturalWidth || img.width;
            const naturalH = img.naturalHeight || img.height;
            const maxW = Math.round(activePage.width * 0.85);
            const maxH = Math.round(activePage.height * 0.85);
            const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
            const width = Math.max(1, Math.round(naturalW * scale));
            const height = Math.max(1, Math.round(naturalH * scale));
            const x = Math.round((activePage.width - width) / 2);
            const y = Math.round((activePage.height - height) / 2);

            // Create image layer with the uploaded image (use data URL for canvas)
            // Use `contain` objectFit to show whole image inside the frame (no automatic crop)
            const layer = createLayer('image', activePage, {
              name: file.name || 'Uploaded image',
              content: imageUrl,
              metadata: { objectFit: 'contain', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
            });

            // Override frame to make the image larger on canvas
            layer.frame = { x, y, width, height, rotation: 0 };

            if (layer.frame) {
              layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
            }

            try {
              dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
              console.log('Layer dispatched to store');
            } catch (dispatchError) {
              console.error('Error dispatching layer:', dispatchError);
              alert('Failed to add image to canvas. Please try again.');
            }

            // Persist the file to IndexedDB and add to recent images for the sidebar
            try {
              const dbModule = await import('../../utils/recentImagesDB');
              const id = `recent-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
              await dbModule.saveImage(id, file.name, file);
              dbModule.pruneOld(10).catch(() => {});
              const objectUrl = URL.createObjectURL(file);
              dispatch({ type: 'ADD_RECENTLY_UPLOADED_IMAGE', dataUrl: objectUrl, fileName: file.name, id });
            } catch (err) {
              console.error('Failed to persist or dispatch recent image from sidebar:', err);
              // Fallback: do nothing — layer was already added
            }
          } catch (err) {
            console.error('Error creating layer from uploaded image:', err);
            alert('An error occurred while processing the image. Please try again.');
          }
        };
        img.onerror = () => {
          console.error('Failed to load image for sizing');
          alert('Uploaded image could not be loaded. Try a different file.');
        };
        img.src = imageUrl;
      } catch (error) {
        console.error('Error processing uploaded image:', error);
        alert('An error occurred while processing the image. Please try again.');
      }
    };

    reader.onerror = () => {
      console.error('Error reading file');
      alert('Failed to read the image file. Please try again.');
    };

    console.log('Starting to read file as data URL');
    reader.readAsDataURL(file);

    // Reset the input so the same file can be selected again
    event.target.value = '';
  };

  const handleUseRecentFromSidebar = (image) => {
    if (!activePage) return;
      try {
      // Measure the image to size it larger in the canvas
      const img = new Image();
      img.onload = () => {
        const naturalW = img.naturalWidth || img.width;
        const naturalH = img.naturalHeight || img.height;
        const maxW = Math.round(activePage.width * 0.85);
        const maxH = Math.round(activePage.height * 0.85);
        const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
        const width = Math.max(1, Math.round(naturalW * scale));
        const height = Math.max(1, Math.round(naturalH * scale));
        const x = Math.round((activePage.width - width) / 2);
        const y = Math.round((activePage.height - height) / 2);

        const layer = createLayer('image', activePage, {
          name: image.fileName || 'Uploaded image',
          content: image.dataUrl,
          metadata: { objectFit: 'contain', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
        });
        layer.frame = { x, y, width, height, rotation: 0 };

        if (layer.frame) {
          layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
        }

        dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
      };
      img.onerror = () => {
        // fallback: create a default-sized layer
        const layer = createLayer('image', activePage, {
          name: image.fileName || 'Uploaded image',
          content: image.dataUrl,
          metadata: { objectFit: 'contain', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
        });
        dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
      };
      img.src = image.dataUrl;
    } catch (err) {
      console.error('Failed to add recent image to canvas:', err);
      alert('Could not add image to canvas. Check console for details.');
    }
  };

  const renderToolContent = () => {
    if (!activePage) {
      return (
        <div className="builder-sidebar__empty-state">Create a page to start designing.</div>
      );
    }

    switch (activeTool) {
      case 'text':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Text</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Select a preset to add editable text blocks to your design.</p>
            <div className="builder-sidebar__tool-actions">
              <button
                type="button"
                className="tool-action-btn"
                onClick={() => handleAddText({ name: 'Heading', content: 'Add a headline', fontSize: 48 })}
              >
                Add heading
              </button>
              <button
                type="button"
                className="tool-action-btn"
                onClick={() => handleAddText({ name: 'Subheading', content: 'Add supporting copy', fontSize: 32 })}
              >
                Add subheading
              </button>
              <button
                type="button"
                className="tool-action-btn"
                onClick={() => handleAddText({ name: 'Body text', content: 'Start typing your message here.', fontSize: 24, align: 'left' })}
              >
                Add paragraph
              </button>
            </div>
          </div>
        );
      case 'shapes':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Shapes</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Use basic shapes to build graphic elements and structure.</p>
            <div className="builder-sidebar__tool-actions">
              <button type="button" className="tool-action-btn" onClick={() => handleAddShape('rectangle')}>
                Rectangle
              </button>
              <button type="button" className="tool-action-btn" onClick={() => handleAddShape('circle')}>
                Circle
              </button>
            </div>
          </div>
        );
      case 'images':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Upload</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Upload and add images to enhance your design.</p>
            <div className="builder-sidebar__tool-actions">
              <button type="button" className="tool-action-btn" onClick={handleAddImagePlaceholder}>
                Upload image
              </button>
            </div>
            <div className="builder-sidebar__recently-uploaded">
              <h3>Recently Upload</h3>
              <div className="builder-sidebar__recent-items">
                {state.recentlyUploadedImages && state.recentlyUploadedImages.length > 0 ? (
                  <div className="inspector-recent-images">
                    {state.recentlyUploadedImages.map((image) => (
                      <div key={image.id} className="inspector-recent-image__tile">
                        <button
                          type="button"
                          className="inspector-recent-image"
                          onClick={() => handleUseRecentFromSidebar(image)}
                          title={image.fileName}
                          aria-label={`Use recently uploaded image: ${image.fileName}`}
                        >
                          <img
                            src={image.dataUrl}
                            alt={image.fileName}
                            className="inspector-recent-image__thumb"
                            onError={(e) => { e.target.style.display = 'none'; }}
                          />
                        </button>
                        <button
                          type="button"
                          className="inspector-recent-image__delete"
                          title="Delete recent upload"
                          aria-label={`Delete recent upload ${image.fileName}`}
                          onClick={async (evt) => {
                            evt.stopPropagation();
                            try {
                              const dbModule = await import('../../utils/recentImagesDB');
                              if (image.dataUrl && image.dataUrl.startsWith('blob:')) {
                                try { URL.revokeObjectURL(image.dataUrl); } catch (e) { /* ignore */ }
                              }
                              await dbModule.deleteImage(image.id);
                              dispatch({ type: 'DELETE_RECENTLY_UPLOADED_IMAGE', id: image.id });
                            } catch (err) {
                              console.error('Failed to delete recent image', err);
                              alert('Failed to delete recent image. See console for details.');
                            }
                          }}
                        >
                          {/* Minimal outline trash icon (stroke-only) */}
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" style={{ width: 14, height: 14 }}>
                            <path d="M3 6h18" />
                            <path d="M8 6v14a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                          </svg>
                        </button>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="builder-sidebar__empty-state">Recently uploaded images will appear here.</div>
                )}
              </div>
            </div>
            <div className="builder-sidebar__hint">Image uploads will connect to the asset library in a future release.</div>
          </div>
        );
      case 'photos':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Photos</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Upload and add photos to enhance your design.</p>
            <div className="builder-sidebar__tool-actions">
              <button type="button" className="tool-action-btn" onClick={handleAddImagePlaceholder}>
                Upload photo
              </button>
            </div>
            <div className="builder-sidebar__recently-uploaded">
              <h3>Recently Upload</h3>
              <div className="builder-sidebar__recent-items">
                {state.recentlyUploadedImages && state.recentlyUploadedImages.length > 0 ? (
                  <div className="inspector-recent-images">
                    {state.recentlyUploadedImages.map((image) => (
                      <div key={image.id} className="inspector-recent-image__tile">
                        <button
                          type="button"
                          className="inspector-recent-image"
                          onClick={() => handleUseRecentFromSidebar(image)}
                          title={image.fileName}
                          aria-label={`Use recently uploaded photo: ${image.fileName}`}
                        >
                          <img
                            src={image.dataUrl}
                            alt={image.fileName}
                            className="inspector-recent-image__thumb"
                            onError={(e) => { e.target.style.display = 'none'; }}
                          />
                        </button>
                        <button
                          type="button"
                          className="inspector-recent-image__delete"
                          title="Delete recent upload"
                          aria-label={`Delete recent upload ${image.fileName}`}
                          onClick={async (evt) => {
                            evt.stopPropagation();
                            try {
                              const dbModule = await import('../../utils/recentImagesDB');
                              if (image.dataUrl && image.dataUrl.startsWith('blob:')) {
                                try { URL.revokeObjectURL(image.dataUrl); } catch (e) { /* ignore */ }
                              }
                              await dbModule.deleteImage(image.id);
                              dispatch({ type: 'DELETE_RECENTLY_UPLOADED_IMAGE', id: image.id });
                            } catch (err) {
                              console.error('Failed to delete recent image', err);
                              alert('Failed to delete recent image. See console for details.');
                            }
                          }}
                        >
                          {/* Minimal outline trash icon (stroke-only) */}
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" style={{ width: 14, height: 14 }}>
                            <path d="M3 6h18" />
                            <path d="M8 6v14a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                          </svg>
                        </button>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="builder-sidebar__empty-state">Recently uploaded photos will appear here.</div>
                )}
              </div>
            </div>
            <div className="builder-sidebar__hint">Photo uploads will connect to the asset library in a future release.</div>
          </div>
        );
      case 'background':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Background</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Quickly apply background colors to the current page.</p>
            <div className="builder-sidebar__swatches" role="list">
              {['#ffffff', '#fef3c7', '#dbeafe', '#fef2f2', '#ecfccb'].map((swatch) => (
                <button
                  key={swatch}
                  type="button"
                  className="tool-swatch"
                  style={{ backgroundColor: swatch }}
                  aria-label={`Set page background to ${swatch}`}
                  onClick={() => dispatch({ type: 'UPDATE_PAGE_PROPS', pageId: activePage.id, props: { background: swatch } })}
                />
              ))}
            </div>
          </div>
        );
      case 'quotes':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Quotes</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Select a quote preset to add inspirational text blocks.</p>
            <div className="builder-sidebar__tool-actions">
              <button
                type="button"
                className="tool-action-btn"
                onClick={() => handleAddText({ name: 'Quote', content: '"The best way to predict the future is to create it."', fontSize: 32 })}
              >
                Add quote
              </button>
              <button
                type="button"
                className="tool-action-btn"
                onClick={() => handleAddText({ name: 'Inspiration', content: '"Believe you can and you\'re halfway there."', fontSize: 28 })}
              >
                Add inspiration
              </button>
            </div>
          </div>
        );
      default:
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>{TOOL_SECTIONS.find((tool) => tool.id === activeTool)?.label ?? 'Tools'}</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Additional creative resources will appear here soon.</p>
            <div className="builder-sidebar__empty-state">
              Tool-specific controls will render here as the builder matures.
            </div>
          </div>
        );
    }
  };

  return (
    <>
      <nav className={`builder-sidebar ${isSidebarHidden ? 'is-collapsed' : ''}`} aria-label="Primary design tools">
        <div className="builder-sidebar__tabs" role="tablist" aria-orientation="vertical">
          {TOOL_SECTIONS.map((tool) => (
            <button
              key={tool.id}
              type="button"
              role="tab"
              aria-selected={activeTool === tool.id}
              className={`builder-sidebar__tab ${activeTool === tool.id ? 'is-active' : ''}`}
              onClick={() => setActiveTool(tool.id)}
            >
              <i className={tool.icon} aria-hidden="true"></i>
              <span>{tool.label}</span>
            </button>
          ))}
          {isSidebarHidden && (
            <button
              type="button"
              className="builder-sidebar__expand-toggle"
              onClick={onToggleSidebar}
              aria-label="Expand sidebar"
              title="Expand sidebar"
            >
              <i className="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
          )}
        </div>
        {!isSidebarHidden && (
          <div className="builder-sidebar__panel" role="tabpanel" aria-live="polite">
            {renderToolContent()}
          </div>
        )}
      </nav>
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleFileSelect}
        accept="image/*"
        style={{ display: 'none' }}
      />
    </>
  );
}
