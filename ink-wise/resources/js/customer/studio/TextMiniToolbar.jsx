import React, { useMemo, useState, useEffect, useRef } from 'react';

const FONT_OPTIONS = [
  { name: 'Lobster', family: 'Lobster', weight: '400', style: 'normal' },
  { name: 'Fredoka One', family: 'Fredoka One', weight: '400', style: 'normal' },
  { name: 'Pacifico', family: 'Pacifico', weight: '400', style: 'normal' },
  { name: 'Baloo 2', family: 'Baloo 2', weight: '400', style: 'normal' },
  { name: 'Poppins Bold', family: 'Poppins', weight: '700', style: 'normal' },
  { name: 'Anton', family: 'Anton', weight: '400', style: 'normal' },
  { name: 'Gochi Hand', family: 'Gochi Hand', weight: '400', style: 'normal' },
  { name: 'Bangers', family: 'Bangers', weight: '400', style: 'normal' },
  { name: 'Chewy', family: 'Chewy', weight: '400', style: 'normal' },
  { name: 'Rubik ExtraBold', family: 'Rubik', weight: '800', style: 'normal' },
  { name: 'Comic Neue Bold', family: 'Comic Neue', weight: '700', style: 'normal' },
  { name: 'Amatic SC Bold', family: 'Amatic SC', weight: '700', style: 'normal' },
  { name: 'Luckiest Guy', family: 'Luckiest Guy', weight: '400', style: 'normal' },
  { name: 'Kalam Bold', family: 'Kalam', weight: '700', style: 'normal' },
  { name: 'Bubblegum Sans', family: 'Bubblegum Sans', weight: '400', style: 'normal' },
  { name: 'Shrikhand', family: 'Shrikhand', weight: '400', style: 'normal' },
  { name: 'Jua', family: 'Jua', weight: '400', style: 'normal' },
  { name: 'DynaPuff', family: 'DynaPuff', weight: '400', style: 'normal' },
  { name: 'Cabin Sketch', family: 'Cabin Sketch', weight: '400', style: 'normal' },
  { name: 'Titan One', family: 'Titan One', weight: '400', style: 'normal' },
  { name: 'Montserrat SemiBold', family: 'Montserrat', weight: '600', style: 'normal' },
  { name: 'Helvetica Neue', family: 'Helvetica Neue', weight: '400', style: 'normal' },
  { name: 'Lato Bold', family: 'Lato', weight: '700', style: 'normal' },
  { name: 'Source Sans Pro', family: 'Source Sans Pro', weight: '400', style: 'normal' },
  { name: 'Poppins Medium', family: 'Poppins', weight: '500', style: 'normal' },
  { name: 'Roboto Condensed', family: 'Roboto Condensed', weight: '400', style: 'normal' },
  { name: 'Open Sans', family: 'Open Sans', weight: '400', style: 'normal' },
  { name: 'Oswald', family: 'Oswald', weight: '400', style: 'normal' },
  { name: 'Nunito Sans', family: 'Nunito Sans', weight: '400', style: 'normal' },
  { name: 'Inter SemiBold', family: 'Inter', weight: '600', style: 'normal' },
  { name: 'Futura PT', family: 'Futura PT', weight: '400', style: 'normal' },
  { name: 'Avenir Next', family: 'Avenir Next', weight: '400', style: 'normal' },
  { name: 'Work Sans Medium', family: 'Work Sans', weight: '500', style: 'normal' },
  { name: 'Proxima Nova', family: 'Proxima Nova', weight: '400', style: 'normal' },
  { name: 'IBM Plex Sans', family: 'IBM Plex Sans', weight: '400', style: 'normal' },
  { name: 'Barlow SemiCondensed', family: 'Barlow Semi Condensed', weight: '400', style: 'normal' },
  { name: 'Sora', family: 'Sora', weight: '400', style: 'normal' },
  { name: 'Metropolis', family: 'Metropolis', weight: '400', style: 'normal' },
  { name: 'Urbanist', family: 'Urbanist', weight: '400', style: 'normal' },
  { name: 'Manrope', family: 'Manrope', weight: '400', style: 'normal' },
  { name: 'Quicksand Light', family: 'Quicksand', weight: '300', style: 'normal' },
  { name: 'Great Vibes', family: 'Great Vibes', weight: '400', style: 'normal' },
  { name: 'Parisienne', family: 'Parisienne', weight: '400', style: 'normal' },
  { name: 'Nunito Light', family: 'Nunito', weight: '300', style: 'normal' },
  { name: 'Cormorant Garamond', family: 'Cormorant Garamond', weight: '400', style: 'normal' },
  { name: 'Playfair Display', family: 'Playfair Display', weight: '400', style: 'normal' },
  { name: 'Allura', family: 'Allura', weight: '400', style: 'normal' },
  { name: 'DM Sans', family: 'DM Sans', weight: '400', style: 'normal' },
  { name: 'Satisfy', family: 'Satisfy', weight: '400', style: 'normal' },
  { name: 'Karla', family: 'Karla', weight: '400', style: 'normal' },
  { name: 'Josefin Light', family: 'Josefin Sans', weight: '300', style: 'normal' },
  { name: 'Merriweather Light', family: 'Merriweather', weight: '300', style: 'normal' },
  { name: 'Sacramento', family: 'Sacramento', weight: '400', style: 'normal' },
  { name: 'Dancing Script', family: 'Dancing Script', weight: '400', style: 'normal' },
  { name: 'Marcellus', family: 'Marcellus', weight: '400', style: 'normal' },
  { name: 'Crimson Pro', family: 'Crimson Pro', weight: '400', style: 'normal' },
  { name: 'ABeeZee', family: 'ABeeZee', weight: '400', style: 'normal' },
  { name: 'GFS Didot', family: 'GFS Didot', weight: '400', style: 'normal' },
  { name: 'Overlock Light', family: 'Overlock', weight: '300', style: 'normal' },
  { name: 'Hind Soft', family: 'Hind', weight: '400', style: 'normal' },
  { name: 'Cinzel', family: 'Cinzel', weight: '400', style: 'normal' },
  { name: 'Cormorant Infant', family: 'Cormorant Infant', weight: '400', style: 'normal' },
  { name: 'Playfair Display Italic', family: 'Playfair Display', weight: '400', style: 'italic' },
  { name: 'Alex Brush', family: 'Alex Brush', weight: '400', style: 'normal' },
  { name: 'Montserrat Light', family: 'Montserrat', weight: '300', style: 'normal' },
  { name: 'Bodoni Moda', family: 'Bodoni Moda', weight: '400', style: 'normal' },
  { name: 'Cormorant Upright', family: 'Cormorant Upright', weight: '400', style: 'normal' },
  { name: 'Libre Baskerville Italic', family: 'Libre Baskerville', weight: '400', style: 'italic' },
  { name: 'Tangerine', family: 'Tangerine', weight: '400', style: 'normal' },
  { name: 'Cinzel Decorative', family: 'Cinzel Decorative', weight: '400', style: 'normal' },
  { name: 'Forum', family: 'Forum', weight: '400', style: 'normal' },
  { name: 'Cardo Italic', family: 'Cardo', weight: '400', style: 'italic' },
  { name: 'Unna Italic', family: 'Unna', weight: '400', style: 'italic' },
  { name: 'Prata', family: 'Prata', weight: '400', style: 'normal' },
  { name: 'Gilda Display', family: 'Gilda Display', weight: '400', style: 'normal' },
  { name: 'Cormorant SC', family: 'Cormorant SC', weight: '400', style: 'normal' },
  { name: 'Quattrocento', family: 'Quattrocento', weight: '400', style: 'normal' },
  { name: 'Marcellus SC', family: 'Marcellus SC', weight: '400', style: 'normal' },
];

const SIZE_OPTIONS = [12, 14, 16, 18, 24, 32, 36, 39, 42, 48, 60];

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

export function TextMiniToolbar({
  onFontChange = () => {},
  onSizeChange = () => {},
  onAction = () => {},
  onColorChange = () => {},
  activeElementType = null,
}) {
  const defaultFont = useMemo(() => FONT_OPTIONS[0], []);
  const [selectedOption, setSelectedOption] = useState(defaultFont);
  const [fontSize, setFontSize] = useState(39);
  const [isFontModalOpen, setIsFontModalOpen] = useState(false);
  const [isColorModalOpen, setIsColorModalOpen] = useState(false);
  const modalRef = useRef(null);
  const colorModalRef = useRef(null);
  // Format modal state and ref
  const [isFormatModalOpen, setIsFormatModalOpen] = useState(false);
  const formatModalRef = useRef(null);
  // Case (uppercase/lowercase) modal state and ref
  const [isCaseModalOpen, setIsCaseModalOpen] = useState(false);
  const caseModalRef = useRef(null);
  // Effects modal state and ref
  const [isEffectsModalOpen, setIsEffectsModalOpen] = useState(false);
  const effectsModalRef = useRef(null);

  const [color, setColor] = useState('#FFFFFF');
  const [recentColors, setRecentColors] = useState([]);
  const PRESET_GROUPS = useMemo(() => [
    {
      label: 'Grayscale',
      colors: ['#FFFFFF', '#E5E7EB', '#9CA3AF', '#4B5563', '#111827']
    },
    {
      label: 'Blues',
      colors: ['#DCEEFB', '#A8D1FF', '#4DA1FF', '#2563EB', '#0B3D91']
    },
    {
      label: 'Greens',
      colors: ['#DEF7EC', '#BFEBD3', '#34D399', '#059669', '#064E3B']
    },
    {
      label: 'Yellows',
      colors: ['#FFFBEB', '#FEF3C7', '#FBBF24', '#F59E0B', '#B45309']
    },
    {
      label: 'Oranges',
      colors: ['#FFF1E6', '#FFD8B5', '#FB923C', '#F97316', '#7C2D12']
    },
    {
      label: 'Reds',
      colors: ['#FFEFF2', '#FFD6DD', '#EF4444', '#DC2626', '#58151C']
    }
  ], []);
  const [h, setH] = useState(0);
  const [s, setS] = useState(0);
  const [l, setL] = useState(100);
  const [activeTab, setActiveTab] = useState('swatches');
  const pickerRef = useRef(null);
  const isPickingRef = useRef(false);

  const isTextSelection = activeElementType === 'text';
  const isImageSelection = activeElementType === 'image';
  const hasSelection = isTextSelection || isImageSelection;
  
  // CMYK sliders state (percent 0-100)
  const [cVal, setCVal] = useState(0);
  const [mVal, setMVal] = useState(0);
  const [yVal, setYVal] = useState(0);
  const [kVal, setKVal] = useState(0);

  // Convert CMYK (0-100) to HEX color
  function cmykToHex(cPercent, mPercent, yPercent, kPercent) {
    const c = cPercent / 100;
    const m = mPercent / 100;
    const y = yPercent / 100;
    const k = kPercent / 100;
    const r = Math.round(255 * (1 - c) * (1 - k));
    const g = Math.round(255 * (1 - m) * (1 - k));
    const b = Math.round(255 * (1 - y) * (1 - k));
    const toHex = (n) => n.toString(16).padStart(2, '0').toUpperCase();
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
  }

  // When CMYK changes, update the color preview and notify parent
  useEffect(() => {
    // Try to read initial selection from the bridge so toolbar reflects current element
    try {
      const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
      if (bridge && typeof bridge.getSelection === 'function') {
        const sel = bridge.getSelection();
        if (sel) {
          if (sel.fontFamily) {
            const found = FONT_OPTIONS.find((f) => f.family === sel.fontFamily || f.name === sel.fontFamily);
            if (found) setSelectedOption(found);
          }
          if (Number.isFinite(Number(sel.fontSize))) {
            setFontSize(Number(sel.fontSize));
          }
          if (sel.color) {
            setColor(String(sel.color));
          }
        }
      }
    } catch (e) {
      // non-fatal - ignore
    }
    if (activeTab === 'cmyk') {
      try {
        const hex = cmykToHex(cVal, mVal, yVal, kVal);
        setColor(hex);
        onColorChange(hex);
      } catch (e) {
        // ignore
      }
    }
    // only care when CMYK values or activeTab change
  }, [cVal, mVal, yVal, kVal, activeTab]);

  // Convert hex to HSL and HSL to hex helpers
  function hexToRgb(hex) {
    const h = hex.replace('#', '');
    const bigint = parseInt(h, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return { r, g, b };
  }

  function rgbToHsl(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h = 0, s = 0, l = (max + min) / 2;
    if (max !== min) {
      const d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }
      h /= 6;
    }
    return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
  }

  function hslToHex(h, s, l) {
    s /= 100; l /= 100;
    const k = (n) => (n + h / 30) % 12;
    const a = s * Math.min(l, 1 - l);
    const f = (n) => {
      const color = l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
      return Math.round(255 * color).toString(16).padStart(2, '0');
    };
    return `#${f(0)}${f(8)}${f(4)}`.toUpperCase();
  }

  useEffect(() => {
    // initialize HSL from hex color whenever color changes programmatically
    try {
      const rgb = hexToRgb(color.replace('#', ''));
      const { h: hh, s: ss, l: ll } = rgbToHsl(rgb.r, rgb.g, rgb.b);
      setH(hh);
      setS(ss);
      setL(ll);
    } catch (e) {
      // ignore parse errors
    }
  }, [color]);

  // update color when H/S/L changes
  useEffect(() => {
    const hex = hslToHex(h, s, l);
    setColor(hex);
  }, [h, s, l]);

  // color area picking handlers
  const startPick = (e) => {
    isPickingRef.current = true;
    handlePick(e);
    window.addEventListener('mousemove', handlePick);
    window.addEventListener('mouseup', stopPick);
  };

  const handlePick = (e) => {
    if (!pickerRef.current) return;
    const rect = pickerRef.current.getBoundingClientRect();
    const x = Math.min(Math.max(0, (e.clientX - rect.left) / rect.width), 1);
    const y = Math.min(Math.max(0, (e.clientY - rect.top) / rect.height), 1);
    const nextS = Math.round(x * 100);
    const nextL = Math.round((1 - y) * 100);
    setS(nextS);
    setL(nextL);
  };

  const stopPick = () => {
    isPickingRef.current = false;
    window.removeEventListener('mousemove', handlePick);
    window.removeEventListener('mouseup', stopPick);
  };

  useEffect(() => {
    const handleClickOutside = (event) => {
      // Close font modal when clicking outside both modals
      if (
        (isFontModalOpen && modalRef.current && !modalRef.current.contains(event.target)) ||
        (isColorModalOpen && colorModalRef.current && !colorModalRef.current.contains(event.target)) ||
        (isFormatModalOpen && formatModalRef.current && !formatModalRef.current.contains(event.target)) ||
          (isCaseModalOpen && caseModalRef.current && !caseModalRef.current.contains(event.target)) ||
          (isAlignModalOpen && alignModalRef.current && !alignModalRef.current.contains(event.target)) ||
        (isListModalOpen && listModalRef.current && !listModalRef.current.contains(event.target)) ||
        (isNumberModalOpen && numberModalRef.current && !numberModalRef.current.contains(event.target))
        || (isOpacityModalOpen && opacityModalRef.current && !opacityModalRef.current.contains(event.target))
      ) {
        setIsFontModalOpen(false);
        setIsColorModalOpen(false);
        setIsFormatModalOpen(false);
          setIsCaseModalOpen(false);
        setIsAlignModalOpen(false);
        setIsListModalOpen(false);
        setIsNumberModalOpen(false);
        setIsOpacityModalOpen(false);
      }
    };

    const handleEsc = (event) => {
      if (event.key === 'Escape' || event.key === 'Esc') {
        setIsFontModalOpen(false);
        setIsColorModalOpen(false);
        setIsFormatModalOpen(false);
        setIsCaseModalOpen(false);
        setIsAlignModalOpen(false);
        setIsListModalOpen(false);
        setIsNumberModalOpen(false);
        setIsOpacityModalOpen(false);
        setIsRotationModalOpen(false);
        setIsLayersModalOpen(false);
        setIsMoreModalOpen(false);
        setIsEffectsModalOpen(false);
      }
    };

    if (isFontModalOpen || isColorModalOpen || isFormatModalOpen || isCaseModalOpen || isAlignModalOpen || isListModalOpen || isNumberModalOpen || isEffectsModalOpen || isOpacityModalOpen || isRotationModalOpen || isLayersModalOpen || isMoreModalOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('keydown', handleEsc);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleEsc);
    };
  }, [isFontModalOpen, isColorModalOpen]);

  const applyFont = (option) => {
    if (!isTextSelection) {
      return;
    }
    setSelectedOption(option);
    onFontChange(option.family);
    setIsFontModalOpen(false);
  };

  const applyColor = (value) => {
    const hex = (value || '#FFFFFF').toUpperCase();
    setColor(hex);
    onColorChange(hex);
    // update recent colors (unique, most recent first)
    setRecentColors((prev) => {
      const next = [hex, ...prev.filter((c) => c !== hex)];
      return next.slice(0, 8);
    });
    setIsColorModalOpen(false);
  };

  const applySize = (value) => {
    const clamped = clamp(value, 8, 96);
    setFontSize(clamped);
    onSizeChange(clamped);
  };

  const changeSizeBy = (delta) => {
    if (!hasSelection) {
      return;
    }
    applySize(fontSize + delta);
  };

  const handleSelectSize = (event) => {
    if (!hasSelection) {
      return;
    }
    const next = Number(event.target.value);
    if (Number.isFinite(next)) {
      applySize(next);
    }
  };

  // Align modal state and ref
  const [isAlignModalOpen, setIsAlignModalOpen] = useState(false);
  const alignModalRef = useRef(null);
  // List modal state and ref (for bullet/number choices)
  const [isListModalOpen, setIsListModalOpen] = useState(false);
  const listModalRef = useRef(null);
  // Numbered list / spacing modal state and ref
  const [isNumberModalOpen, setIsNumberModalOpen] = useState(false);
  const numberModalRef = useRef(null);
  // Line and letter spacing state
  const [lineSpacing, setLineSpacing] = useState(1.4);
  const [letterSpacing, setLetterSpacing] = useState(0.12);

  // Opacity modal state and ref
  const [isOpacityModalOpen, setIsOpacityModalOpen] = useState(false);
  const [opacityValue, setOpacityValue] = useState(100); // percent 0-100
  const opacityModalRef = useRef(null);

  // Rotation modal state and ref
  const [isRotationModalOpen, setIsRotationModalOpen] = useState(false);
  const [rotationValue, setRotationValue] = useState(0); // degrees -180..180
  const rotationModalRef = useRef(null);

  // Layers modal state and ref
  const [isLayersModalOpen, setIsLayersModalOpen] = useState(false);
  const layersModalRef = useRef(null);
  // More options modal state and ref
  const [isMoreModalOpen, setIsMoreModalOpen] = useState(false);
  const moreModalRef = useRef(null);

  useEffect(() => {
    if (isTextSelection) {
      return;
    }
    setIsFontModalOpen(false);
    setIsFormatModalOpen(false);
    setIsCaseModalOpen(false);
    setIsAlignModalOpen(false);
    setIsListModalOpen(false);
    setIsNumberModalOpen(false);
    setIsEffectsModalOpen(false);
  }, [isTextSelection]);

  useEffect(() => {
    if (hasSelection) {
      return;
    }
    setIsColorModalOpen(false);
    setIsOpacityModalOpen(false);
    setIsRotationModalOpen(false);
    setIsLayersModalOpen(false);
    setIsMoreModalOpen(false);
  }, [hasSelection]);

  const resetLineSpacing = () => {
    if (!isTextSelection) {
      return;
    }
    setLineSpacing(1.4);
    onAction('line-spacing')(1.4);
  };
  const resetLetterSpacing = () => {
    if (!isTextSelection) {
      return;
    }
    setLetterSpacing(0.12);
    onAction('letter-spacing')(0.12);
  };

  return (
    <div className={`text-mini-toolbar ${hasSelection ? 'floating' : ''}`} role="toolbar" aria-label={isImageSelection ? 'Image editing toolbar' : 'Text editing toolbar'}>
      <div className="toolbar-group toolbar-group--font">
        {isImageSelection ? (
          <>
            <button type="button" className="ghost-button" onClick={() => onAction('replace-image')}>Replace Image</button>
            <div className="toolbar-divider" aria-hidden="true" />
            <button type="button" className="ghost-button" onClick={() => onAction('crop')}><i className="fa-solid fa-crop-simple"></i> Crop</button>
          </>
        ) : (
          <button
            type="button"
            className="font-select-button"
            onClick={() => {
              if (!isTextSelection) {
                return;
              }
              setIsFontModalOpen((prev) => !prev);
            }}
            aria-label={isTextSelection ? 'Choose font family' : 'Font selection available for text elements'}
            aria-haspopup="listbox"
            aria-expanded={isFontModalOpen}
            disabled={!isTextSelection}
          >
            {isTextSelection ? selectedOption.name : 'Select element'} <i className="fa-solid fa-chevron-down" aria-hidden="true" />
          </button>
        )}
        {isTextSelection && isFontModalOpen && (
          <div className="font-modal" ref={modalRef}>
            <div className="font-modal-content">
              {FONT_OPTIONS.map((option) => (
                <button
                  key={option.name}
                  type="button"
                  className="font-option"
                  onClick={() => applyFont(option)}
                  style={{ fontFamily: option.family, fontWeight: option.weight, fontStyle: option.style }}
                >
                  {option.name}
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      <div className="toolbar-divider" aria-hidden="true" />

      <div className="toolbar-group toolbar-group--size" role="group" aria-label={isImageSelection ? 'Image scale' : 'Font size'}>
        <button type="button" className="ghost-button" aria-label={isImageSelection ? 'Decrease image scale' : 'Decrease font size'} onClick={() => changeSizeBy(-2)} disabled={!hasSelection}>
          <i className="fa-solid fa-minus" aria-hidden="true" />
        </button>
        <label className="sr-only" htmlFor="mini-toolbar-size">{isImageSelection ? 'Image scale' : 'Font size'}</label>
        <select id="mini-toolbar-size" className="size-select" value={fontSize} onChange={handleSelectSize} disabled={!hasSelection}>
          {SIZE_OPTIONS.map((size) => (
            <option key={size} value={size}>{size}</option>
          ))}
        </select>
        <button type="button" className="ghost-button" aria-label={isImageSelection ? 'Increase image scale' : 'Increase font size'} onClick={() => changeSizeBy(2)} disabled={!hasSelection}>
          <i className="fa-solid fa-plus" aria-hidden="true" />
        </button>
      </div>

      <div className="toolbar-divider" aria-hidden="true" />

      <div style={{ position: 'relative' }}>
        <button
          type="button"
          className="color-swatch"
          aria-label={isImageSelection ? 'Choose image tint' : 'Choose text color'}
          onClick={() => {
            if (!hasSelection) {
              return;
            }
            setIsFontModalOpen(false);
            setIsColorModalOpen((v) => !v);
          }}
          disabled={!hasSelection}
          aria-expanded={isColorModalOpen}
        >
          <span className="color-swatch__chip" style={{ background: color }} />
        </button>
        {isColorModalOpen && (
          <div className="color-modal" ref={colorModalRef}>
            <div className="color-modal-content">
              <div className="color-modal-header">
                <div className="color-modal-title">{isImageSelection ? 'Image tint' : 'Text color'}</div>
                <button type="button" className="ghost-button" onClick={() => setIsColorModalOpen(false)} aria-label="Close color picker">
                  <i className="fa-solid fa-x" />
                </button>
              </div>

              {/* Tabs moved below so they sit above the Recent colors */}

              <div className="color-picker-area">
                <div className="inline-color-picker">
                  <div className="spectrum-picker">
                    <div
                      className="spectrum-area"
                      ref={pickerRef}
                      onMouseDown={startPick}
                      style={{ background: `linear-gradient(to right, #fff, hsl(${h}, 100%, 50%)), linear-gradient(to top, rgba(0,0,0,0), rgba(0,0,0,1))` }}
                      role="application"
                      aria-label="Color spectrum"
                    >
                      <div
                        className="spectrum-handle"
                        style={{ left: `${s}%`, top: `${100 - l}%` }}
                        aria-hidden="true"
                      />
                    </div>

                    <div className="hue-row">
                      <input
                        className="hue-range"
                        type="range"
                        min="0"
                        max="360"
                        value={h}
                        onChange={(e) => setH(Number(e.target.value))}
                        style={{ background: `linear-gradient(to right, ${Array.from({ length: 13 }).map((_, i) => `hsl(${i*30},100%,50%)`).join(',')})` }}
                        aria-label="Hue"
                      />
                    </div>

                    <div className="hex-row">
                      <input
                        type="text"
                        className="hex-input"
                        value={hslToHex(h, s, l)}
                        onChange={(e) => setColor(e.target.value)}
                        aria-label="Hex color value"
                      />
                      <button type="button" className="eyedropper-button" title="Eyedropper" aria-label="Eyedropper">
                        <i className="fa-solid fa-eye-dropper" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div className="swatch-sections">
                {/* Tabs placed here so they appear above Recent colors */}
                <div className="tabs">
                  <div className={`tab ${activeTab === 'swatches' ? 'active' : ''}`} onClick={() => setActiveTab('swatches')}>Swatches</div>
                  <div className={`tab ${activeTab === 'cmyk' ? 'active' : ''}`} onClick={() => setActiveTab('cmyk')}>CMYK</div>
                </div>

                <div className="section-divider" aria-hidden="true" />

                {activeTab === 'swatches' && (
                  <>
                    {recentColors.length > 0 && (
                      <div className="swatch-section">
                        <div className="swatch-title">Recent colors</div>
                        <div className="swatches-grid">
                          {recentColors.map((c) => (
                            <button key={c} type="button" className="swatch" style={{ background: c }} onClick={() => applyColor(c)} />
                          ))}
                        </div>
                      </div>
                    )}

                    {recentColors.length > 0 && PRESET_GROUPS.length > 0 && (
                      <div className="section-divider" aria-hidden="true" />
                    )}

                    <div className="swatch-section">
                      <div className="preset-groups">
                        {PRESET_GROUPS.map((group, idx) => (
                          <div key={idx} className="swatch-group">
                            <div className="swatches-grid preset">
                              {group.colors.map((c) => (
                                <button key={c} type="button" className="swatch" style={{ background: c }} onClick={() => applyColor(c)} />
                              ))}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </>
                )}

                {activeTab === 'cmyk' && (
                  <div className="swatch-section cmyk-section">
                    <div className="swatch-title">CMYK (preview)</div>

                    <div className="cmyk-row">
                      <div className="cmyk-label">C</div>
                      <input
                        type="range"
                        min="0"
                        max="100"
                        value={cVal}
                        onChange={(e) => setCVal(Number(e.target.value))}
                        className="cmyk-range c-range"
                        aria-label="Cyan"
                      />
                      <div className="cmyk-value">{cVal}%</div>
                    </div>

                    <div className="cmyk-row">
                      <div className="cmyk-label">M</div>
                      <input
                        type="range"
                        min="0"
                        max="100"
                        value={mVal}
                        onChange={(e) => setMVal(Number(e.target.value))}
                        className="cmyk-range m-range"
                        aria-label="Magenta"
                      />
                      <div className="cmyk-value">{mVal}%</div>
                    </div>

                    <div className="cmyk-row">
                      <div className="cmyk-label">Y</div>
                      <input
                        type="range"
                        min="0"
                        max="100"
                        value={yVal}
                        onChange={(e) => setYVal(Number(e.target.value))}
                        className="cmyk-range y-range"
                        aria-label="Yellow"
                      />
                      <div className="cmyk-value">{yVal}%</div>
                    </div>

                    <div className="cmyk-row">
                      <div className="cmyk-label">K</div>
                      <input
                        type="range"
                        min="0"
                        max="100"
                        value={kVal}
                        onChange={(e) => setKVal(Number(e.target.value))}
                        className="cmyk-range k-range"
                        aria-label="Key Black"
                      />
                      <div className="cmyk-value">{kVal}%</div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </div>

      {isTextSelection && (
        <>
          <div className="toolbar-divider" aria-hidden="true" />

          <div className="toolbar-group toolbar-group--actions" role="group" aria-label="Text format actions" style={{ position: 'relative' }}>
        <button
          type="button"
          className="ghost-button"
          aria-label="Bold"
          onClick={() => {
            setIsFormatModalOpen((v) => !v);
            setIsColorModalOpen(false);
            setIsFontModalOpen(false);
          }}
        >
          <span className="toolbar-label">B</span>
        </button>

        {isFormatModalOpen && (
          <div className="format-modal" ref={formatModalRef} role="dialog" aria-label="Text formatting options">
            <div className="format-modal-content">
              <button type="button" className="format-option" title="Bold" onClick={() => { onAction('bold'); setIsFormatModalOpen(false); }}>
                <i className="fi fi-ss-bold" aria-hidden="true" />
              </button>
              <button type="button" className="format-option" title="Italic" onClick={() => { onAction('italic'); setIsFormatModalOpen(false); }}>
                <i className="fi fi-br-italic" aria-hidden="true" />
              </button>
              <button type="button" className="format-option" title="Underline" onClick={() => { onAction('underline'); setIsFormatModalOpen(false); }}>
                <i className="fi fi-br-underline" aria-hidden="true" />
              </button>
              <button type="button" className="format-option" title="Strikethrough" onClick={() => { onAction('strikethrough'); setIsFormatModalOpen(false); }}>
                <i className="fi fi-br-strikethrough" aria-hidden="true" />
              </button>
            </div>
          </div>
        )}

        <button
          type="button"
          className="ghost-button"
          aria-label="Align center"
          onClick={() => {
            setIsAlignModalOpen((v) => !v);
            setIsFormatModalOpen(false);
            setIsColorModalOpen(false);
            setIsFontModalOpen(false);
          }}
        >
          <i className="fa-solid fa-align-center" aria-hidden="true" />
        </button>

        {isAlignModalOpen && (
          <div className="align-modal" ref={alignModalRef} role="dialog" aria-label="Alignment options">
            <div className="align-modal-content">
              <button type="button" className="align-option" title="Align-justify" onClick={() => { onAction('align-justify'); setIsAlignModalOpen(false); }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="9" width="18" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="14" width="18" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="19" width="18" height="2" rx="1" fill="currentColor" />
                </svg>
              </button>
              <button type="button" className="align-option" title="Align-center" onClick={() => { onAction('align-center'); setIsAlignModalOpen(false); }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="5" y="4" width="14" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="9" width="18" height="2" rx="1" fill="currentColor" />
                  <rect x="5" y="14" width="14" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="19" width="18" height="2" rx="1" fill="currentColor" />
                </svg>
              </button>
              <button type="button" className="align-option" title="Align-left" onClick={() => { onAction('align-left'); setIsAlignModalOpen(false); }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="3" y="4" width="12" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="9" width="18" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="14" width="12" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="19" width="18" height="2" rx="1" fill="currentColor" />
                </svg>
              </button>
              <button type="button" className="align-option" title="Align-right" onClick={() => { onAction('align-right'); setIsAlignModalOpen(false); }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="9" y="4" width="12" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="9" width="18" height="2" rx="1" fill="currentColor" />
                  <rect x="9" y="14" width="12" height="2" rx="1" fill="currentColor" />
                  <rect x="3" y="19" width="18" height="2" rx="1" fill="currentColor" />
                </svg>
              </button>
            </div>
          </div>
        )}
        <button
          type="button"
          className="ghost-button"
          aria-label="Bulleted list"
          onClick={() => {
            setIsListModalOpen((v) => !v);
            setIsAlignModalOpen(false);
            setIsFormatModalOpen(false);
            setIsColorModalOpen(false);
            setIsFontModalOpen(false);
          }}
        >
          <i className="fa-solid fa-list-ul" aria-hidden="true" />
        </button>
        {isListModalOpen && (
          <div className="list-modal" ref={listModalRef} role="dialog" aria-label="List options">
            <div className="list-modal-content">
              <button type="button" className="list-option" title="Numbered list" onClick={() => { /* open numbered spacing modal */ setIsListModalOpen(false); setIsNumberModalOpen(true); }}>
                <i className="fa-solid fa-list-ol" aria-hidden="true" />
              </button>
              <button type="button" className="list-option" title="Bulleted list" onClick={() => { onAction('list-bullets'); setIsListModalOpen(false); }}>
                <i className="fa-solid fa-list-ul" aria-hidden="true" />
              </button>
            </div>
          </div>
        )}

        {/* Numbered list button toggles spacing modal (line / letter spacing) */}
        <button
          type="button"
          className="ghost-button"
          aria-label="Numbered list"
          onClick={() => {
            setIsNumberModalOpen((v) => !v);
            setIsListModalOpen(false);
            setIsAlignModalOpen(false);
            setIsFormatModalOpen(false);
            setIsColorModalOpen(false);
            setIsFontModalOpen(false);
          }}
        >
          <i className="fa-solid fa-text-height" aria-hidden="true" />
        </button>

        {isNumberModalOpen && (
          <div className="number-modal" ref={numberModalRef} role="dialog" aria-label="Text spacing options">
            <div className="number-modal-content">
              <div className="slider-row number-row">
                <label className="slider-label">Line spacing</label>
                <input
                  type="range"
                  min="0.8"
                  max="3"
                  step="0.01"
                  value={lineSpacing}
                  onChange={(e) => { const v = Number(e.target.value); setLineSpacing(v); onAction('line-spacing')(v); }}
                  className="number-range line-range"
                  aria-label="Line spacing"
                />
                <button type="button" className="reset-button" title="Reset" onClick={resetLineSpacing} aria-label="Reset line spacing">⟲</button>
                <input className="slider-value-input" type="number" step="0.01" value={lineSpacing} onChange={(e) => { const v = Number(e.target.value); setLineSpacing(v); onAction('line-spacing')(v); }} />
              </div>

              <div className="slider-row number-row">
                <label className="slider-label">Letter spacing</label>
                <input
                  type="range"
                  min="-0.5"
                  max="1"
                  step="0.01"
                  value={letterSpacing}
                  onChange={(e) => { const v = Number(e.target.value); setLetterSpacing(v); onAction('letter-spacing')(v); }}
                  className="number-range letter-range"
                  aria-label="Letter spacing"
                />
                <button type="button" className="reset-button" title="Reset" onClick={resetLetterSpacing} aria-label="Reset letter spacing">⟲</button>
                <input className="slider-value-input" type="number" step="0.01" value={letterSpacing} onChange={(e) => { const v = Number(e.target.value); setLetterSpacing(v); onAction('letter-spacing')(v); }} />
              </div>
            </div>
          </div>
        )}
          </div>

          <div className="toolbar-divider" aria-hidden="true" />

          <div className="toolbar-group toolbar-group--menu" role="group" aria-label="Text styles" style={{ position: 'relative' }}>
        <button
          type="button"
          className="text-button"
          onClick={() => {
            setIsCaseModalOpen((v) => !v);
            setIsListModalOpen(false);
            setIsAlignModalOpen(false);
            setIsColorModalOpen(false);
            setIsFontModalOpen(false);
            setIsNumberModalOpen(false);
            setIsFormatModalOpen(false);
            setIsEffectsModalOpen(false);
          }}
          aria-label="Format"
        >
          Format
        </button>

        {isCaseModalOpen && (
          <div className="case-modal format-modal" ref={caseModalRef} role="dialog" aria-label="Case options">
            <div className="format-modal-content">
              <button type="button" className={`format-option ${false ? 'selected' : ''}`} title="Lowercase" onClick={() => { onAction('lowercase'); setIsCaseModalOpen(false); }}>
                <span className="format-text-icon" aria-hidden="true">a</span>
              </button>
              <button type="button" className={`format-option ${false ? 'selected' : ''}`} title="Uppercase" onClick={() => { onAction('uppercase'); setIsCaseModalOpen(false); }}>
                <span className="format-text-icon" aria-hidden="true">A</span>
              </button>
            </div>
          </div>
        )}

        <button
          type="button"
          className="text-button"
          onClick={() => {
            setIsEffectsModalOpen((v) => !v);
            setIsCaseModalOpen(false);
            setIsListModalOpen(false);
            setIsAlignModalOpen(false);
            setIsFormatModalOpen(false);
            setIsColorModalOpen(false);
            setIsFontModalOpen(false);
            setIsNumberModalOpen(false);
          }}
        >
          T<span className="text-button__super">+</span> Effects
        </button>

        {isEffectsModalOpen && (
          <div className="effects-modal" ref={effectsModalRef} role="dialog" aria-label="Text effects">
            <button type="button" className="modal-close-btn" aria-label="Close effects" onClick={() => setIsEffectsModalOpen(false)}>
              <i className="fa-solid fa-x" aria-hidden="true" />
            </button>
            <div className="effects-modal-content">
              <div className="effects-section">
                <div className="effects-section-title">Style</div>
                <div className="effects-grid">
                  <button type="button" className={`effect-option`} title="None" onClick={() => { onAction('effect-style')('none'); }}>
                    <div className="effect-preview none">
                      <i className="fa-solid fa-ban" aria-hidden="true" />
                    </div>
                  </button>
                  <button type="button" className={`effect-option`} title="Shadow" onClick={() => { onAction('effect-style')('shadow'); }}>
                    <div className="effect-preview shadow">
                      <i className="fi fi-rr-text-shadow" aria-hidden="true" />
                    </div>
                  </button>
                  <button type="button" className={`effect-option`} title="Highlight" onClick={() => { onAction('effect-style')('highlight'); }}>
                    <div className="effect-preview highlight">
                      <i className="fa-solid fa-highlighter" aria-hidden="true" />
                    </div>
                  </button>
                  <button type="button" className={`effect-option`} title="Glitch" onClick={() => { onAction('effect-style')('glitch'); }}>
                    <div className="effect-preview glitch">
                      <span className="glitch-mark">/A</span>
                    </div>
                  </button>
                  <button type="button" className={`effect-option`} title="Echo" onClick={() => { onAction('effect-style')('echo'); }}>
                    <div className="effect-preview echo">
                      <span className="echo-mark">//A</span>
                    </div>
                  </button>
                </div>
              </div>

              <div className="effects-section">
                <div className="effects-section-title">Shape</div>
                <div className="effects-grid two">
                  <button type="button" className={`effect-option`} title="None" onClick={() => { onAction('effect-shape')('none'); }}>
                    <div className="effect-preview none">None</div>
                  </button>
                  <button type="button" className={`effect-option`} title="Curve" onClick={() => { onAction('effect-shape')('curve'); }}>
                    <div className="effect-preview curve">Curve</div>
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
          </div>
        </>
      )}

      <div className="toolbar-divider" aria-hidden="true" />

      <div className="toolbar-group toolbar-group--utility" role="group" aria-label="Utility actions" style={{ position: 'relative' }}>
        <div style={{ display: 'inline-block', position: 'relative' }}>
          <button
            type="button"
            className="ghost-button"
            aria-label="Spacing"
            onClick={() => {
              if (!hasSelection) {
                return;
              }
              // open opacity modal instead of default spacing action
              setIsOpacityModalOpen((v) => !v);
              // close other modals
              setIsEffectsModalOpen(false);
              setIsCaseModalOpen(false);
              setIsListModalOpen(false);
              setIsAlignModalOpen(false);
              setIsFormatModalOpen(false);
              setIsColorModalOpen(false);
              setIsFontModalOpen(false);
              setIsNumberModalOpen(false);
            }}
            disabled={!hasSelection}
          >
            <i className="fi fi-rr-chess-board" aria-hidden="true" />
          </button>

          {isOpacityModalOpen && (
            <div className="opacity-modal" ref={opacityModalRef} role="dialog" aria-label="Opacity" style={{ position: 'absolute', right: 0, top: '42px', zIndex: 1200 }}>
              <div className="opacity-modal-content">
                <div className="opacity-row">
                  <label className="opacity-label">Opacity</label>
                  <input
                    className="opacity-input"
                    type="number"
                    min="0"
                    max="100"
                    value={opacityValue}
                    onChange={(e) => {
                      const v = clamp(Number(e.target.value) || 0, 0, 100);
                      setOpacityValue(v);
                      onAction('opacity')(v);
                    }}
                    aria-label="Opacity percent"
                  />
                </div>

                <div className="opacity-slider-row">
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={opacityValue}
                    onChange={(e) => {
                      const v = Number(e.target.value);
                      setOpacityValue(v);
                      onAction('opacity')(v);
                    }}
                    className="opacity-slider"
                    aria-label="Opacity slider"
                    style={{ background: 'linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,1))' }}
                  />
                </div>
              </div>
            </div>
          )}
        </div>
        <div style={{ display: 'inline-block', position: 'relative' }}>
          <button
            type="button"
            className="ghost-button"
            aria-label="Layers"
            onClick={() => {
              if (!hasSelection) {
                return;
              }
              setIsLayersModalOpen((v) => !v);
              setIsRotationModalOpen(false);
              setIsOpacityModalOpen(false);
              setIsEffectsModalOpen(false);
              setIsCaseModalOpen(false);
              setIsListModalOpen(false);
              setIsAlignModalOpen(false);
              setIsFormatModalOpen(false);
              setIsColorModalOpen(false);
              setIsFontModalOpen(false);
              setIsNumberModalOpen(false);
            }}
            disabled={!hasSelection}
          >
            <i className="fa-solid fa-layer-group" aria-hidden="true" />
          </button>

          {isLayersModalOpen && (
            <div className="layers-modal" ref={layersModalRef} role="dialog" aria-label="Layer management" style={{ position: 'absolute', right: 0, top: '42px', zIndex: 1200 }}>
              <div className="layers-modal-content">
                <button type="button" className="layer-option" title="Bring to front" onClick={() => { onAction('layer')('bring-to-front'); setIsLayersModalOpen(false); }}>
                  <i className="fi fi-rr-chevron-double-up" aria-hidden="true" />
                  <span className="layer-label">Bring to front</span>
                </button>
                <button type="button" className="layer-option" title="Bring forward" onClick={() => { onAction('layer')('bring-forward'); setIsLayersModalOpen(false); }}>
                  <i className="fi fi-rr-angle-up" aria-hidden="true" />
                  <span className="layer-label">Bring forward</span>
                </button>
                <button type="button" className="layer-option" title="Send backward" onClick={() => { onAction('layer')('send-backward'); setIsLayersModalOpen(false); }}>
                  <i className="fi fi-rr-angle-down" aria-hidden="true" />
                  <span className="layer-label">Send backward</span>
                </button>
                <button type="button" className="layer-option" title="Send to back" onClick={() => { onAction('layer')('send-to-back'); setIsLayersModalOpen(false); }}>
                  <i className="fi fi-rr-chevron-double-down" aria-hidden="true" />
                  <span className="layer-label">Send to back</span>
                </button>
              </div>
            </div>
          )}
        </div>
        <div style={{ display: 'inline-block', position: 'relative' }}>
          <button
            type="button"
            className="ghost-button"
            aria-label="Share / Rotation"
            onClick={() => {
              if (!hasSelection) {
                return;
              }
              setIsRotationModalOpen((v) => !v);
              setIsOpacityModalOpen(false);
              setIsEffectsModalOpen(false);
              setIsCaseModalOpen(false);
              setIsListModalOpen(false);
              setIsAlignModalOpen(false);
              setIsFormatModalOpen(false);
              setIsColorModalOpen(false);
              setIsFontModalOpen(false);
              setIsNumberModalOpen(false);
            }}
            disabled={!hasSelection}
          >
            <i className="fi fi-rr-refresh" aria-hidden="true" />
          </button>

          {isRotationModalOpen && (
            <div className="rotation-modal" ref={rotationModalRef} role="dialog" aria-label="Rotation" style={{ position: 'absolute', right: 0, top: '42px', zIndex: 1200 }}>
              <div className="rotation-modal-content">
                <div className="rotation-row">
                  <label className="rotation-label">Rotation</label>
                  <input
                    className="rotation-input"
                    type="number"
                    min="-180"
                    max="180"
                    value={rotationValue}
                    onChange={(e) => {
                      const v = clamp(Number(e.target.value) || 0, -180, 180);
                      setRotationValue(v);
                      onAction('rotation')(v);
                    }}
                    aria-label="Rotation degrees"
                  />
                </div>

                <div className="rotation-slider-row">
                  <input
                    type="range"
                    min="-180"
                    max="180"
                    value={rotationValue}
                    onChange={(e) => {
                      const v = Number(e.target.value);
                      setRotationValue(v);
                      onAction('rotation')(v);
                    }}
                    className="rotation-slider"
                    aria-label="Rotation slider"
                  />
                  <i className="fa-solid fa-rotate-right rotation-icon" aria-hidden="true" />
                </div>
              </div>
            </div>
          )}
        </div>
        <div style={{ display: 'inline-block', position: 'relative' }}>
          <button
            type="button"
            className="ghost-button"
            aria-label="More options"
            onClick={() => {
              if (!hasSelection) {
                return;
              }
              setIsMoreModalOpen((v) => !v);
              setIsLayersModalOpen(false);
              setIsRotationModalOpen(false);
              setIsOpacityModalOpen(false);
              setIsEffectsModalOpen(false);
              setIsCaseModalOpen(false);
              setIsListModalOpen(false);
              setIsAlignModalOpen(false);
              setIsFormatModalOpen(false);
              setIsColorModalOpen(false);
              setIsFontModalOpen(false);
              setIsNumberModalOpen(false);
            }}
            disabled={!hasSelection}
          >
            <i className="fa-solid fa-ellipsis" aria-hidden="true" />
          </button>

          {isMoreModalOpen && (
            <div className="more-modal" ref={moreModalRef} role="dialog" aria-label="More options" style={{ position: 'absolute', right: 0, top: '42px', zIndex: 1200 }}>
              <div className="more-modal-content">
                <button type="button" className="more-option" title="Duplicate" onClick={() => { onAction('more')('duplicate'); setIsMoreModalOpen(false); }}>
                  <i className="fa-solid fa-clone" aria-hidden="true" />
                  <span className="more-label">Duplicate</span>
                </button>

                <button type="button" className="more-option" title="Delete" onClick={() => { onAction('more')('delete'); setIsMoreModalOpen(false); }}>
                  <i className="fi fi-rr-trash" aria-hidden="true" />
                  <span className="more-label">Delete</span>
                </button>

                <button type="button" className="more-option" title="Lock" onClick={() => { onAction('more')('lock'); setIsMoreModalOpen(false); }}>
                  <i className="fi fi-rr-unlock" aria-hidden="true" />
                  <span className="more-label">Lock</span>
                </button>

                <button type="button" className="more-option" title="Copy" onClick={() => { onAction('more')('copy'); setIsMoreModalOpen(false); }}>
                  <i className="fa-solid fa-copy" aria-hidden="true" />
                  <span className="more-label">Copy</span>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default TextMiniToolbar;
