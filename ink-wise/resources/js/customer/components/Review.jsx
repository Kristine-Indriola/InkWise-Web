import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';

const Review = () => {
  const [data, setData] = useState(window.reviewData || {});

  useEffect(() => {
    // Load data if not available
    if (!data.product) {
      // Fetch or use passed data
    }
    // Debug logging
    console.log('Review component data:', data);
    console.log('Front image:', data.finalArtworkFront);
    console.log('Back image:', data.finalArtworkBack);
  }, []);

  const {
    product,
    finalArtworkFront,
    finalArtworkBack,
    orderSummary,
    customerReview,
    lastEditedAt
  } = data;

  const [currentView, setCurrentView] = useState('front');

  const frontImage = finalArtworkFront;
  const backImage = finalArtworkBack;

  // Helper function to render image or SVG
  const renderArtwork = (imageSrc) => {
    console.log('Rendering artwork:', imageSrc ? imageSrc.substring(0, 100) + '...' : 'null');
    if (!imageSrc) {
      return <div className="preview-placeholder">No preview available</div>;
    }

    // Check if it's a data URL for SVG
    if (imageSrc.startsWith('data:image/svg+xml;base64,')) {
      try {
        const svgContent = atob(imageSrc.split(',')[1]);
        console.log('Decoded SVG length:', svgContent.length);
        return (
          <div
            className="preview-svg-container"
            style={{
              width: '100%',
              height: '100%',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              overflow: 'hidden'
            }}
            dangerouslySetInnerHTML={{ __html: svgContent }}
          />
        );
      } catch (e) {
        console.error('Failed to decode SVG data URL:', e);
        return <img src={imageSrc} alt="Design preview" className="preview-image" />;
      }
    }

    // Regular image
    return <img src={imageSrc} alt="Design preview" className="preview-image" />;
  };

  return (
    <div className="review-shell">
      <section className="preview-card">
        <div className="preview-layout">
          <div className="review-panel">
            <div className="sidebar-heading">
              <h1>Review your design</h1>
              <p>It will be printed like this preview. Make sure you are happy before continuing.</p>
            </div>

            <ul className="review-checklist">
              <li>Are the text and images clear and easy to read?</li>
              <li>Do the design elements fit in the safety area?</li>
              <li>Does the background fill out to the edges?</li>
              <li>Is everything spelled correctly?</li>
            </ul>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', marginTop: '20px' }}>
              <button 
                style={{
                  padding: '12px 24px',
                  border: 'none',
                  borderRadius: '8px',
                  background: '#6b7280',
                  color: 'white',
                  fontSize: '15px',
                  fontWeight: '600',
                  cursor: 'pointer'
                }}
                onClick={() => window.location.href = data.editHref}
              >
                Edit Design
              </button>
              <button 
                style={{
                  padding: '12px 24px',
                  border: 'none',
                  borderRadius: '8px',
                  background: '#a6b7ff',
                  color: 'white',
                  fontSize: '15px',
                  fontWeight: '600',
                  cursor: 'pointer'
                }}
                onClick={() => window.location.href = data.continueHref}
              >
                Continue
              </button>
            </div>
          </div>

          <div className="preview-display">
            <div className="preview-header">
              <h2>Final artwork preview</h2>
              <span className="last-updated">
                Last updated: {lastEditedAt ? new Date(lastEditedAt).toLocaleString() : 'Just now'}
              </span>
            </div>

            <div className="preview-toggle" role="group" aria-label="Preview sides">
              <button
                type="button"
                className={`view-btn ${currentView === 'front' ? 'active' : ''}`}
                onClick={() => setCurrentView('front')}
                aria-pressed={currentView === 'front'}
              >
                Front
              </button>
              <button
                type="button"
                className={`view-btn ${currentView === 'back' ? 'active' : ''}`}
                onClick={() => setCurrentView('back')}
                aria-pressed={currentView === 'back'}
              >
                Back
              </button>
            </div>

            <div className={`card-flip ${currentView === 'back' ? 'flipped' : ''}`}>
              <div className="inner">
                <div className="card-face front">
                  {renderArtwork(frontImage)}
                </div>
                <div className="card-face back">
                  {renderArtwork(backImage)}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

// Initialize React
const container = document.getElementById('review-react-root');
if (container) {
  const root = createRoot(container);
  root.render(<Review />);
}

export default Review;