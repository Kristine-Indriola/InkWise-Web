import React, { useState, useEffect } from 'react';
import './GraphicsPanel.css';

// Static images from the studio folder
const STUDIO_IMAGES = [
  '1.png', '2.png', '3.png', '4.png', '5.png', '6.png', '7.png', '8.png', '9.png', '10.png',
  'e.png', 'f.png', 'h.png', 'r.png'
];

function GraphicsPanel({ bootstrap = {} }) {
  const [selectedImage, setSelectedImage] = useState(null);
  const [selectedSide, setSelectedSide] = useState('front');

  const handleImageClick = (imageName) => {
    setSelectedImage(imageName);
    
    const imageUrl = `/customerVideo/studio/${imageName}`;
    
    // Dispatch graphic selection event
    window.dispatchEvent(new CustomEvent('inkwise:graphic-selected', {
      detail: {
        category: 'studio-images',
        item: {
          id: imageName,
          name: imageName,
          thumbnail: imageUrl,
          full: imageUrl,
          type: 'image',
          source: 'studio'
        },
        side: selectedSide
      }
    }));
    
    // Dispatch background application event (like colors do)
    window.dispatchEvent(new CustomEvent('inkwise:apply-background', {
      detail: { 
        side: selectedSide, 
        image: imageUrl,
        type: 'image'
      }
    }));
  };

  return (
    <div className="graphics-panel-react">
      <div className="graphics-header">
        <h3>Graphics Library</h3>
        <div className="graphics-side-toggle">
          <button
            className={`side-toggle-btn ${selectedSide === 'front' ? 'active' : ''}`}
            onClick={() => setSelectedSide('front')}
          >
            Front
          </button>
          <button
            className={`side-toggle-btn ${selectedSide === 'back' ? 'active' : ''}`}
            onClick={() => setSelectedSide('back')}
          >
            Back
          </button>
        </div>
      </div>

      <div className="graphics-content-scroll">
        <div className="graphics-items-grid">
          {STUDIO_IMAGES.map(imageName => (
            <div
              key={imageName}
              className={`graphics-item-card ${selectedImage === imageName ? 'selected' : ''}`}
              onClick={() => handleImageClick(imageName)}
            >
              <div className="item-preview">
                <img
                  src={`/customerVideo/studio/${imageName}`}
                  alt={imageName}
                  loading="lazy"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.nextSibling.style.display = 'flex';
                  }}
                />
                <div className="item-fallback-icon" style={{ display: 'none' }}>
                  <i className="fa-solid fa-image"></i>
                </div>
              </div>
              <div className="item-action">
                <i className="fa-solid fa-plus"></i>
              </div>
              <div className="item-name">{imageName}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default GraphicsPanel;