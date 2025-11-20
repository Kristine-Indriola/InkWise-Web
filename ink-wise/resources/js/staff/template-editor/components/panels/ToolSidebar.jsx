import React, { useMemo, useState, useRef, useEffect, useCallback } from 'react';

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
  { id: 'background', label: 'Background', description: 'Set background.', icon: 'fa-solid fa-palette' },
  { id: 'colors', label: 'Colors', description: 'Generate color palettes.', icon: 'fa-solid fa-palette' },
  { id: 'layers', label: 'Layers', description: 'Manage layers.', icon: 'fa-solid fa-layer-group' },
  { id: 'quotes', label: 'Quotes', description: 'Add quotes.', icon: 'fa-solid fa-quote-left' },
];




const PHOTO_FILTERS = [
  { id: 'all', label: 'All images', value: null },
  { id: 'photos', label: 'Photos', value: 'photos' },
  { id: 'illustrations', label: 'Illustrations', value: 'illustrations' },
  { id: 'vectors', label: 'Vectors', value: 'vectors' },
  { id: '3d', label: '3D Models', value: '3d' },
  { id: 'gifs', label: 'GIFs', value: 'gifs' },
];

const PHOTO_PROVIDERS = [
  { id: 'unsplash', label: 'Unsplash' },
  { id: 'pixabay', label: 'Pixabay' },
];

const PROVIDER_LABELS = {
  unsplash: 'Unsplash',
  pixabay: 'Pixabay',
};

const PIXABAY_API_KEY = '53250708-d3f88461e75cb0c2c5366a181';

const FLATICON_API_KEY = 'FPSX2c99579cb6ea5314189561ca375a1648';

const ITEMS_PER_PAGE = {
  unsplash: 20,
  pixabay: 30,
  flaticon: 50,
};

const PIXABAY_FILTER_RULES = {
  all: { imageType: 'all' },
  photos: { imageType: 'photo' },
  illustrations: { imageType: 'illustration' },
  vectors: { imageType: 'vector' },
  '3d': { querySuffix: '3d model render', category: 'computer' },
  gifs: { querySuffix: 'animated gif loop', order: 'popular' },
};

const normalizeUnsplashResults = (results = []) => (
  results
    .filter(Boolean)
    .map((photo) => ({
      id: `unsplash-${photo.id}`,
      thumbUrl: photo?.urls?.thumb ?? photo?.urls?.small ?? photo?.urls?.regular,
      previewUrl: photo?.urls?.small ?? photo?.urls?.regular ?? photo?.urls?.full,
      downloadUrl: photo?.urls?.regular ?? photo?.urls?.full ?? photo?.urls?.small,
      description: photo?.alt_description ?? photo?.description ?? 'Unsplash photo',
      provider: 'unsplash',
      providerLabel: PROVIDER_LABELS.unsplash,
      credit: photo?.user?.name ? `Photo by ${photo.user.name} on Unsplash` : 'Unsplash',
      raw: photo,
    }))
);

const normalizePixabayResults = (hits = []) => (
  hits
    .filter(Boolean)
    .map((hit) => ({
      id: `pixabay-${hit.id}`,
      thumbUrl: hit?.previewURL ?? hit?.webformatURL,
      previewUrl: hit?.webformatURL ?? hit?.largeImageURL ?? hit?.previewURL,
      downloadUrl: hit?.largeImageURL ?? hit?.webformatURL ?? hit?.previewURL,
      description: hit?.tags ?? 'Pixabay image',
      provider: 'pixabay',
      providerLabel: PROVIDER_LABELS.pixabay,
      credit: hit?.user ? `${hit.user} on Pixabay` : 'Pixabay',
      raw: hit,
    }))
);

export function ToolSidebar({ isSidebarHidden, onToggleSidebar }) {
  const [activeTool, setActiveTool] = useState('text');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [selectedFilter, setSelectedFilter] = useState('all');
  const [photoProvider, setPhotoProvider] = useState('unsplash');
  const currentPageRef = useRef(1);
  const activeSearchQueryRef = useRef('');
  const hasTriggeredSearchRef = useRef(false);

  // Color palette state
  const [currentPalette, setCurrentPalette] = useState([]);
  const [isGeneratingPalette, setIsGeneratingPalette] = useState(false);

  // Icon search state
  const [iconSearchQuery, setIconSearchQuery] = useState('');
  const [iconSearchResults, setIconSearchResults] = useState([
    {
      id: 'default-heart',
      thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
      previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
      downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
      description: 'Heart',
      provider: 'default',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
    },
    {
      id: 'default-star',
      thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
      previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
      downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
      description: 'Star',
      provider: 'default',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
    },
    {
      id: 'default-home',
      thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
      previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
      downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
      description: 'Home',
      provider: 'default',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
    },
    {
      id: 'default-user',
      thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
      previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
      downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
      description: 'User',
      provider: 'default',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
    },
  ]);
  const [isSearchingIcons, setIsSearchingIcons] = useState(false);
  const [iconCurrentPage, setIconCurrentPage] = useState(1);
  const [isLoadingMoreIcons, setIsLoadingMoreIcons] = useState(false);
  const [hasMoreIcons, setHasMoreIcons] = useState(true);
  const iconCurrentPageRef = useRef(1);
  const activeIconSearchQueryRef = useRef('');
  const hasTriggeredIconSearchRef = useRef(false);
  const [fontSearchQuery, setFontSearchQuery] = useState('');
  const [fontSearchResults, setFontSearchResults] = useState([]);
  const [isSearchingFonts, setIsSearchingFonts] = useState(false);
  const [fontCurrentPage, setFontCurrentPage] = useState(1);
  const [isLoadingMoreFonts, setIsLoadingMoreFonts] = useState(false);
  const [hasMoreFonts, setHasMoreFonts] = useState(true);
  const fontCurrentPageRef = useRef(1);
  const activeFontSearchQueryRef = useRef('');
  const hasTriggeredFontSearchRef = useRef(false);

  // Styled text presets (loaded from Google Fonts list + sample text)
  const [styledPresets, setStyledPresets] = useState([]);
  const [styledPage, setStyledPage] = useState(1);
  const [isLoadingStyledPresets, setIsLoadingStyledPresets] = useState(false);
  const [hasMoreStyledPresets, setHasMoreStyledPresets] = useState(true);
  const styledPerPage = 12;
  const styledFontsListRef = useRef([]); // cache of fonts fetched from API
  const styledContainerRef = useRef(null);
  const styledObserverRef = useRef(null);

  // Use the provided Google API key (from your message)
  const GOOGLE_FONTS_API_KEY = 'AIzaSyBRCDdZjTcR4brOsHV_OBsDO11We11BVi0';

  const sampleStyledTexts = [
    'Life is an ADVENTURE',
    "Congratulations! You're a Big Brother",
    'MARKETING PROPOSAL',
    'SALE',
    'MINIMALISM',
    'Operations Manager',
    'CREATIVE DESIGN',
    'MODERN ARTISTRY',
    'PROFESSIONAL SERVICES',
    'DIGITAL INNOVATION',
    'BRAND IDENTITY',
    'VISUAL STORYTELLING',
    'GRAPHIC DESIGN',
    'TYPOGRAPHY MATTERS',
    'DESIGN EXCELLENCE',
    'CREATIVE SOLUTIONS',
    'ART DIRECTION',
    'VISUAL IMPACT',
    'DESIGN THINKING',
    'CREATIVE PROCESS',
    'BRAND STORY',
    'VISUAL LANGUAGE',
    'DESIGN SYSTEM',
    'CREATIVE AGENCY',
    'VISUAL DESIGN',
    'TYPEFACE DESIGN',
    'GRAPHIC ARTS',
    'DESIGN STUDIO',
    'CREATIVE BRIEF',
    'VISUAL CONCEPT',
  ];

  const loadStyledPresets = useCallback(async (page = 1) => {
    if (isLoadingStyledPresets || !hasMoreStyledPresets) return;
    setIsLoadingStyledPresets(true);
    try {
      // Fetch full fonts list once and cache it
      if (!styledFontsListRef.current || styledFontsListRef.current.length === 0) {
        try {
          const res = await fetch(`https://www.googleapis.com/webfonts/v1/webfonts?key=${GOOGLE_FONTS_API_KEY}&sort=popularity`);
          if (res.ok) {
            const data = await res.json();
            styledFontsListRef.current = data.items || [];
          } else {
            console.warn('Google Fonts API returned', res.status);
            styledFontsListRef.current = [];
          }
        } catch (err) {
          console.error('Failed to fetch Google Fonts list', err);
          styledFontsListRef.current = [];
        }
      }

      const fonts = styledFontsListRef.current.length > 0 ? styledFontsListRef.current : [
        { family: 'Inter' }, { family: 'Roboto' }, { family: 'Montserrat' }, { family: 'Poppins' }, { family: 'Playfair Display' }, { family: 'Lato' }, { family: 'Merriweather' }, { family: 'Crimson Text' }
      ];

      const start = (page - 1) * styledPerPage;
      const slice = fonts.slice(start, start + styledPerPage);
      const newPresets = slice.map((font, idx) => {
        const sample = sampleStyledTexts[(start + idx) % sampleStyledTexts.length];
        const fontSize = (sample.length > 12) ? 20 : 36;
        const variations = [
          { fontWeight: '400', align: 'center', transform: 'none' },
          { fontWeight: '700', align: 'center', transform: 'none' },
          { fontWeight: '400', align: 'left', transform: 'none' },
          { fontWeight: '700', align: 'left', transform: 'uppercase' },
          { fontWeight: '400', align: 'center', transform: 'uppercase' },
        ];
        const variation = variations[(start + idx) % variations.length];

        return {
          id: `styled-${font.family.replace(/\s+/g, '-').toLowerCase()}-${start + idx}`,
          family: font.family,
          content: sample,
          fontSize,
          fontWeight: variation.fontWeight,
          align: variation.align,
          transform: variation.transform,
        };
      });

      setStyledPresets((prev) => [...prev, ...newPresets]);
      setStyledPage(page + 1);
      setHasMoreStyledPresets(start + styledPerPage < fonts.length);
    } finally {
      setIsLoadingStyledPresets(false);
    }
  }, [isLoadingStyledPresets, hasMoreStyledPresets]);

  // Initialize styled presets when text tool becomes active
  useEffect(() => {
    if (activeTool === 'text' && styledPresets.length === 0) {
      loadStyledPresets(1);
    }
  }, [activeTool, styledPresets.length, loadStyledPresets]);

  // IntersectionObserver for styled presets infinite scroll
  useEffect(() => {
    const container = styledContainerRef.current || document.querySelector('.text-panel__styled-presets');
    const loadingIndicator = container?.querySelector('.styled-loading-indicator');
    if (!container || !loadingIndicator || !hasMoreStyledPresets) return;

    const observer = new IntersectionObserver((entries) => {
      const [entry] = entries;
      if (entry.isIntersecting && !isLoadingStyledPresets && hasMoreStyledPresets) {
        loadStyledPresets(styledPage);
      }
    }, { root: container, rootMargin: '50px', threshold: 0.1 });

    observer.observe(loadingIndicator);
    styledObserverRef.current = observer;

    return () => {
      try { styledObserverRef.current?.disconnect(); } catch (e) { /* ignore */ }
    };
  }, [styledPage, isLoadingStyledPresets, hasMoreStyledPresets, loadStyledPresets]);

  // Shape search state
  const [shapeSearchQuery, setShapeSearchQuery] = useState('');
  const [shapeSearchResults, setShapeSearchResults] = useState([
    {
      id: 'default-square',
      thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
      previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
      downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
      description: 'Square',
      provider: 'default',
      providerLabel: 'Basic Shapes',
      credit: 'Basic Shapes',
      variant: 'rectangle'
    },
    {
      id: 'default-circle',
      thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
      previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
      downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
      description: 'Circle',
      provider: 'default',
      providerLabel: 'Basic Shapes',
      credit: 'Basic Shapes',
      variant: 'circle'
    },
    {
      id: 'default-triangle',
      thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
      previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
      downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
      description: 'Triangle',
      provider: 'default',
      providerLabel: 'Basic Shapes',
      credit: 'Basic Shapes',
      variant: 'triangle'
    },
    {
      id: 'default-star-shape',
      thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiwxOSA0OSwxOSA1OCwzNiAzMiwzNiIgcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
      previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiwxOSA0OSwxOSA1OCwzNiAzMiwzNiIgcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
      downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiwxOSA0OSwxOSA1OCwzNiAzMiwzNiIgcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
      description: 'Star',
      provider: 'default',
      providerLabel: 'Basic Shapes',
      credit: 'Basic Shapes',
      variant: 'star'
    },
  ]);
  const [isSearchingShapes, setIsSearchingShapes] = useState(false);
  const [shapeCurrentPage, setShapeCurrentPage] = useState(1);
  const [isLoadingMoreShapes, setIsLoadingMoreShapes] = useState(false);
  const [hasMoreShapes, setHasMoreShapes] = useState(true);
  const shapeCurrentPageRef = useRef(1);
  const activeShapeSearchQueryRef = useRef('');
  const hasTriggeredShapeSearchRef = useRef(false);

  const loadedFontsRef = useRef(new Set());
  const { state, dispatch } = useBuilderStore();
  const fileInputRef = useRef(null);
  const searchInputRef = useRef(null);
  const textPanelCanvasRef = useRef(null);

  const activePage = useMemo(
    () => state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0],
    [state.pages, state.activePageId],
  );

  const safeInsets = useMemo(() => resolveInsets(activePage?.safeZone), [activePage?.safeZone]);
  const activeProviderLabel = PROVIDER_LABELS[photoProvider] ?? PROVIDER_LABELS.unsplash;





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

  const handleAddShape = (variant, imageDataUrl = null) => {
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

    // If image data is provided, set it as the shape's fill/background
    if (imageDataUrl) {
      layer.metadata = {
        ...layer.metadata,
        backgroundImage: imageDataUrl,
        objectFit: 'cover',
        imageScale: 1,
        imageOffsetX: 0,
        imageOffsetY: 0,
      };
      // Set fill to transparent so the background image shows through
      layer.fill = 'transparent';
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
            // Default to a "Fill" treatment like Canva so the entire frame is covered
            const layer = createLayer('image', activePage, {
              name: file.name || 'Uploaded image',
              content: imageUrl,
              metadata: { objectFit: 'cover', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
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
          metadata: { objectFit: 'cover', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
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
          metadata: { objectFit: 'cover', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
        });
        dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
      };
      img.src = image.dataUrl;
    } catch (err) {
      console.error('Failed to add recent image to canvas:', err);
      alert('Could not add image to canvas. Check console for details.');
    }
  };

  const fetchPhotos = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    if (photoProvider === 'unsplash') {
      const perPage = ITEMS_PER_PAGE.unsplash;
      const filterParam = selectedFilter !== 'all' ? `&content_filter=${selectedFilter}` : '';
      const response = await fetch(`https://api.unsplash.com/search/photos?query=${encodeURIComponent(trimmedQuery)}&client_id=iFpUZ_6aTnLGz0Voz0MYlprq9i_RBl83ux9DzV6EMOs&per_page=${perPage}&page=${pageNumber}${filterParam}`);
      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }
      const data = await response.json();
      const formattedResults = normalizeUnsplashResults(data.results || []);
      const totalPages = data.total_pages ?? (formattedResults.length === perPage ? pageNumber + 1 : pageNumber);
      return {
        items: formattedResults,
        hasMore: pageNumber < totalPages && formattedResults.length > 0,
      };
    }

    const perPage = ITEMS_PER_PAGE.pixabay;
    const pixabayRule = PIXABAY_FILTER_RULES[selectedFilter] ?? PIXABAY_FILTER_RULES.all;
    const queryPieces = [trimmedQuery, pixabayRule?.querySuffix].filter(Boolean);
    const combinedQuery = queryPieces.join(' ');
    const params = new URLSearchParams({
      key: PIXABAY_API_KEY,
      q: combinedQuery || trimmedQuery,
      safesearch: 'true',
      page: String(pageNumber),
      per_page: String(perPage),
    });

    if (pixabayRule?.imageType && pixabayRule.imageType !== 'all') {
      params.set('image_type', pixabayRule.imageType);
    }
    if (pixabayRule?.category) {
      params.set('category', pixabayRule.category);
    }
    if (pixabayRule?.order) {
      params.set('order', pixabayRule.order);
    }

    const response = await fetch(`https://pixabay.com/api/?${params.toString()}`);
    if (!response.ok) {
      throw new Error(`API error: ${response.status}`);
    }
    const data = await response.json();
    const formattedResults = normalizePixabayResults(data.hits || []);
    const cappedTotal = Math.min(data.totalHits ?? 0, 500);
    const fetchedCount = (pageNumber - 1) * perPage + formattedResults.length;

    return {
      items: formattedResults,
      hasMore: cappedTotal > 0 && fetchedCount < cappedTotal,
    };
  }, [photoProvider, selectedFilter]);

  const handleSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();
    if (!trimmedQuery) return;
    hasTriggeredSearchRef.current = true;
    activeSearchQueryRef.current = trimmedQuery;
    setIsSearching(true);
    setCurrentPage(1);
    currentPageRef.current = 1;
    setHasMore(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchPhotos(trimmedQuery, 1);
      setSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setCurrentPage(nextPage);
      currentPageRef.current = nextPage;
      setHasMore(nextHasMore);
    } catch (error) {
      console.error('Search failed:', error);
      alert('Search failed. Please try again.');
      setSearchResults([]);
      setHasMore(false);
    } finally {
      setIsSearching(false);
    }
  }, [fetchPhotos]);

  const loadMoreImages = useCallback(async () => {
    if (isLoadingMore || !hasMore) {
      return;
    }
    const activeQuery = activeSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMore(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchPhotos(activeQuery, currentPageRef.current);
      if (items.length > 0) {
        setSearchResults((prev) => [...prev, ...items]);
        const nextPage = currentPageRef.current + 1;
        setCurrentPage(nextPage);
        currentPageRef.current = nextPage;
      }
      setHasMore(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more failed:', error);
      alert('Failed to load more images. Please try again.');
      setHasMore(false);
    } finally {
      setIsLoadingMore(false);
    }
  }, [fetchPhotos, hasMore, isLoadingMore]);

  const loadMoreImagesRef = useRef(loadMoreImages);

  useEffect(() => {
    loadMoreImagesRef.current = loadMoreImages;
  }, [loadMoreImages]);

  const fetchIcons = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    const perPage = ITEMS_PER_PAGE.flaticon;
    console.log('Fetching icons:', { query: trimmedQuery, pageNumber, perPage });

    // Try reliable icon APIs
    const apis = [
      {
        name: 'Iconify',
        searchUrl: `https://api.iconify.design/search?query=${encodeURIComponent(trimmedQuery)}&limit=${perPage}&start=${(pageNumber - 1) * perPage}`,
        noAuth: true,
        type: 'iconify'
      },
      {
        name: 'Simple Icons Search',
        searchUrl: `https://api.github.com/search/code?q=${encodeURIComponent(trimmedQuery)}+repo:simple-icons/simple-icons&per_page=${Math.min(perPage, 30)}&page=${pageNumber}`,
        noAuth: true,
        type: 'github'
      }
    ];

    for (const api of apis) {
      try {
        console.log(`Trying ${api.name} API...`);

        const searchResponse = await fetch(api.searchUrl);

        if (!searchResponse.ok) {
          const errorText = await searchResponse.text();
          console.log(`${api.name} search failed:`, searchResponse.status, errorText);
          continue; // Try next API
        }

        const searchData = await searchResponse.json();
        console.log(`${api.name} search successful:`, searchData);

        // Format results based on API
        let icons = [];
        let totalItems = 0;

        if (api.name === 'Iconify') {
          icons = searchData?.icons || [];
          totalItems = searchData?.total || 0;

          // Iconify returns a lot of results, limit to reasonable amount for UX
          const maxResults = 1000; // Prevent loading too many icons
          totalItems = Math.min(totalItems, maxResults);
        } else if (api.name === 'Simple Icons Search') {
          // GitHub search results
          icons = searchData.items || [];
          totalItems = searchData.total_count || 0;
        }

        const formattedResults = icons.map((icon, index) => {
          let thumbUrl = '';
          let previewUrl = '';
          let downloadUrl = '';
          let description = '';
          let credit = '';

          if (api.name === 'Iconify') {
            // Iconify returns icon names like "mdi:home" or "fa:heart"
            const [prefix, name] = icon.split(':');
            thumbUrl = `https://api.iconify.design/${icon}.svg?width=64&height=64`;
            previewUrl = `https://api.iconify.design/${icon}.svg?width=128&height=128`;
            downloadUrl = `https://api.iconify.design/${icon}.svg`;
            description = name.replace(/([A-Z])/g, ' $1').trim() || icon;
            credit = `Iconify (${prefix})`;
          } else if (api.name === 'Simple Icons Search') {
            // GitHub code search returns items with path or name
            let iconName = '';
            if (icon.path) {
              iconName = icon.path.split('/').pop().replace('.svg', '');
            }
            iconName = iconName || icon.name || icon.path?.split('/').pop() || `icon-${index}`;
            // simple-icons package uses lowercased names with no spaces
            const normalized = String(iconName).toLowerCase().replace(/[^a-z0-9]/g, '');
            thumbUrl = `https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/${encodeURIComponent(normalized)}.svg`;
            previewUrl = thumbUrl;
            downloadUrl = thumbUrl;
            description = iconName.replace(/([A-Z])/g, ' $1').trim() || 'Simple Icon';
            credit = 'Simple Icons';
          }

          return {
            id: `${api.name.toLowerCase()}-${icon}-${index}`,
            thumbUrl,
            previewUrl,
            downloadUrl,
            description,
            provider: api.name.toLowerCase(),
            providerLabel: api.name,
            credit,
            raw: icon,
          };
        }).filter(icon => icon.thumbUrl); // Only include icons with valid URLs

        if (formattedResults.length > 0) {
          console.log(`Returning ${formattedResults.length} results from ${api.name}`);
          const totalPages = Math.ceil(totalItems / perPage);
          const hasMoreResults = pageNumber < totalPages && formattedResults.length === perPage && totalItems > (pageNumber * perPage);

          console.log(`Pagination info: page ${pageNumber}/${totalPages}, hasMore: ${hasMoreResults}, totalItems: ${totalItems}`);

          return {
            items: formattedResults,
            hasMore: hasMoreResults,
          };
        } else {
          console.log(`${api.name} returned no valid results`);
        }

      } catch (error) {
        console.log(`${api.name} API error:`, error.message);
        continue; // Try next API
      }
    }

    // If all APIs fail, provide fallback icons that always work
    console.log('All APIs failed, providing fallback icons');
    const commonIcons = [
      { name: 'heart', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg', tags: ['love', 'favorite', 'like'] },
      { name: 'star', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg', tags: ['favorite', 'rating', 'bookmark'] },
      { name: 'home', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg', tags: ['house', 'building', 'property'] },
      { name: 'user', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg', tags: ['person', 'profile', 'account'] },
      { name: 'search', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg', tags: ['find', 'magnify', 'lookup'] },
      { name: 'settings', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg', tags: ['gear', 'config', 'preferences'] },
      { name: 'mail', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/mail.svg', tags: ['email', 'message', 'envelope'] },
      { name: 'phone', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/phone.svg', tags: ['call', 'contact', 'telephone'] },
      { name: 'camera', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/camera.svg', tags: ['photo', 'picture', 'image'] },
      { name: 'music', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/music.svg', tags: ['audio', 'sound', 'note'] },
      { name: 'play', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/play.svg', tags: ['start', 'begin', 'media'] },
      { name: 'pause', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/pause.svg', tags: ['stop', 'wait', 'media'] },
      { name: 'arrowright', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/arrowright.svg', tags: ['next', 'forward', 'direction'] },
      { name: 'arrowleft', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/arrowleft.svg', tags: ['back', 'previous', 'direction'] },
      { name: 'check', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/check.svg', tags: ['yes', 'confirm', 'tick'] },
      { name: 'close', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/close.svg', tags: ['x', 'cancel', 'exit'] },
      { name: 'plus', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/plus.svg', tags: ['add', 'new', 'create'] },
      { name: 'minus', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/minus.svg', tags: ['remove', 'subtract', 'delete'] },
      { name: 'menu', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/menu.svg', tags: ['hamburger', 'navigation', 'list'] },
      { name: 'calendar', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/calendar.svg', tags: ['date', 'schedule', 'event'] },
    ];

    // Filter fallback icons based on search query
    const filteredFallbacks = commonIcons.filter(icon =>
      icon.name.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
      icon.tags.some(tag => tag.toLowerCase().includes(trimmedQuery.toLowerCase()))
    );

    const formattedFallbacks = filteredFallbacks.slice(0, Math.min(perPage, 20)).map((icon, index) => ({
      id: `fallback-${index}`,
      thumbUrl: icon.url,
      previewUrl: icon.url,
      downloadUrl: icon.url,
      description: icon.name.replace(/([A-Z])/g, ' $1').trim(),
      provider: 'fallback',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
      raw: icon,
    }));

    console.log(`Returning ${formattedFallbacks.length} filtered fallback icons for query "${trimmedQuery}"`);
    return {
      items: formattedFallbacks,
      hasMore: false,
    };
  }, []);

  const fetchShapes = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    const perPage = ITEMS_PER_PAGE.flaticon;
    console.log('Fetching shapes:', { query: trimmedQuery, pageNumber, perPage });

    // Try reliable icon/shape APIs
    const apis = [
      {
        name: 'Iconify',
        searchUrl: `https://api.iconify.design/search?query=${encodeURIComponent(trimmedQuery + ' shape')}&limit=${perPage}&start=${(pageNumber - 1) * perPage}`,
        noAuth: true,
        type: 'iconify'
      },
      {
        name: 'Simple Icons Search',
        searchUrl: `https://api.github.com/search/code?q=${encodeURIComponent(trimmedQuery + ' shape')}+repo:simple-icons/simple-icons&per_page=${Math.min(perPage, 30)}&page=${pageNumber}`,
        noAuth: true,
        type: 'github'
      }
    ];

    for (const api of apis) {
      try {
        console.log(`Trying ${api.name} API for shapes...`);

        const searchResponse = await fetch(api.searchUrl);

        if (!searchResponse.ok) {
          const errorText = await searchResponse.text();
          console.log(`${api.name} search failed:`, searchResponse.status, errorText);
          continue; // Try next API
        }

        const searchData = await searchResponse.json();
        console.log(`${api.name} search successful:`, searchData);

        // Format results based on API
        let shapes = [];
        let totalItems = 0;

        if (api.name === 'Iconify') {
          shapes = searchData?.icons || [];
          totalItems = searchData?.total || 0;

          // Iconify returns a lot of results, limit to reasonable amount for UX
          const maxResults = 1000; // Prevent loading too many shapes
          totalItems = Math.min(totalItems, maxResults);
        } else if (api.name === 'Simple Icons Search') {
          // GitHub search results
          shapes = searchData.items || [];
          totalItems = searchData.total_count || 0;
        }

        const formattedResults = shapes.map((shape, index) => {
          let thumbUrl = '';
          let previewUrl = '';
          let downloadUrl = '';
          let description = '';
          let credit = '';
          let variant = 'rectangle'; // default

          if (api.name === 'Iconify') {
            // Iconify returns icon names like "mdi:home" or "fa:heart"
            const [prefix, name] = shape.split(':');
            thumbUrl = `https://api.iconify.design/${shape}.svg?width=64&height=64`;
            previewUrl = `https://api.iconify.design/${shape}.svg?width=128&height=128`;
            downloadUrl = `https://api.iconify.design/${shape}.svg`;
            description = name.replace(/([A-Z])/g, ' $1').trim() || shape;
            credit = `Iconify (${prefix})`;
            
            // Determine shape variant based on name
            if (description.toLowerCase().includes('circle') || description.toLowerCase().includes('round')) {
              variant = 'circle';
            } else if (description.toLowerCase().includes('triangle')) {
              variant = 'triangle';
            } else if (description.toLowerCase().includes('star')) {
              variant = 'star';
            }
          } else if (api.name === 'Simple Icons Search') {
            // GitHub code search returns items with path or name
            let shapeName = '';
            if (shape.path) {
              shapeName = shape.path.split('/').pop().replace('.svg', '');
            }
            shapeName = shapeName || shape.name || shape.path?.split('/').pop() || `shape-${index}`;
            // simple-icons package uses lowercased names with no spaces
            const normalized = String(shapeName).toLowerCase().replace(/[^a-z0-9]/g, '');
            thumbUrl = `https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/${encodeURIComponent(normalized)}.svg`;
            previewUrl = thumbUrl;
            downloadUrl = thumbUrl;
            description = shapeName.replace(/([A-Z])/g, ' $1').trim() || 'Shape';
            credit = 'Simple Icons';
            
            // Determine shape variant based on name
            if (description.toLowerCase().includes('circle') || description.toLowerCase().includes('round')) {
              variant = 'circle';
            } else if (description.toLowerCase().includes('triangle')) {
              variant = 'triangle';
            } else if (description.toLowerCase().includes('star')) {
              variant = 'star';
            }
          }

          return {
            id: `${api.name.toLowerCase()}-shape-${shape}-${index}`,
            thumbUrl,
            previewUrl,
            downloadUrl,
            description,
            provider: api.name.toLowerCase(),
            providerLabel: api.name,
            credit,
            variant,
            raw: shape,
          };
        }).filter(shape => shape.thumbUrl); // Only include shapes with valid URLs

        if (formattedResults.length > 0) {
          console.log(`Returning ${formattedResults.length} results from ${api.name}`);
          const totalPages = Math.ceil(totalItems / perPage);
          const hasMoreResults = pageNumber < totalPages && formattedResults.length === perPage && totalItems > (pageNumber * perPage);

          console.log(`Pagination info: page ${pageNumber}/${totalPages}, hasMore: ${hasMoreResults}, totalItems: ${totalItems}`);

          return {
            items: formattedResults,
            hasMore: hasMoreResults,
          };
        } else {
          console.log(`${api.name} returned no valid results`);
        }

      } catch (error) {
        console.log(`${api.name} API error:`, error.message);
        continue; // Try next API
      }
    }

    // If all APIs fail, provide fallback shapes that always work
    console.log('All APIs failed, providing fallback shapes');
    const commonShapes = [
      { name: 'square', variant: 'rectangle', url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=', tags: ['rectangle', 'square', 'box'] },
      { name: 'circle', variant: 'circle', url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==', tags: ['circle', 'round', 'oval'] },
      { name: 'triangle', variant: 'triangle', url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==', tags: ['triangle', 'polygon', 'geometric'] },
      { name: 'star', variant: 'star', url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+', tags: ['star', 'rating', 'favorite'] },
      { name: 'hexagon', variant: 'hexagon', url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIxNiwyMCA4LDM2IDI0LDUyIDQwLDUyIDQ4LDM2IDQwLDIwIiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=', tags: ['hexagon', 'polygon', 'geometric'] },
      { name: 'diamond', variant: 'diamond', url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiwyMCAyMCw4IDQ0LDg1MiA0NCIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+', tags: ['diamond', 'rhombus', 'geometric'] },
    ];

    // Filter fallback shapes based on search query
    const filteredFallbacks = commonShapes.filter(shape =>
      shape.name.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
      shape.tags.some(tag => tag.toLowerCase().includes(trimmedQuery.toLowerCase()))
    );

    const formattedFallbacks = filteredFallbacks.slice(0, Math.min(perPage, 20)).map((shape, index) => ({
      id: `fallback-shape-${index}`,
      thumbUrl: shape.url,
      previewUrl: shape.url,
      downloadUrl: shape.url,
      description: shape.name.replace(/([A-Z])/g, ' $1').trim(),
      provider: 'fallback',
      providerLabel: 'Basic Shapes',
      credit: 'Basic Shapes',
      variant: shape.variant,
      raw: shape,
    }));

    console.log(`Returning ${formattedFallbacks.length} filtered fallback shapes for query "${trimmedQuery}"`);
    return {
      items: formattedFallbacks,
      hasMore: false,
    };
  }, []);

  const handleShapeSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    // If empty query, reset to default shapes
    if (!trimmedQuery) {
      setShapeSearchResults([
        {
          id: 'default-square',
          thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
          previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
          downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
          description: 'Square',
          provider: 'default',
          providerLabel: 'Basic Shapes',
          credit: 'Basic Shapes',
          variant: 'rectangle'
        },
        {
          id: 'default-circle',
          thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
          previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
          downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
          description: 'Circle',
          provider: 'default',
          providerLabel: 'Basic Shapes',
          credit: 'Basic Shapes',
          variant: 'circle'
        },
        {
          id: 'default-triangle',
          thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
          previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
          downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
          description: 'Triangle',
          provider: 'default',
          providerLabel: 'Basic Shapes',
          credit: 'Basic Shapes',
          variant: 'triangle'
        },
        {
          id: 'default-star-shape',
          thumbUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
          previewUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
          downloadUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
          description: 'Star',
          provider: 'default',
          providerLabel: 'Basic Shapes',
          credit: 'Basic Shapes',
          variant: 'star'
        },
      ]);
      hasTriggeredShapeSearchRef.current = false;
      setShapeCurrentPage(1);
      shapeCurrentPageRef.current = 1;
      setHasMoreShapes(false);
      return;
    }

    hasTriggeredShapeSearchRef.current = true;
    activeShapeSearchQueryRef.current = trimmedQuery;
    setIsSearchingShapes(true);
    setShapeCurrentPage(1);
    shapeCurrentPageRef.current = 1;
    setHasMoreShapes(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchShapes(trimmedQuery, 1);
      setShapeSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setShapeCurrentPage(nextPage);
      shapeCurrentPageRef.current = nextPage;
      setHasMoreShapes(nextHasMore);
    } catch (error) {
      console.error('Shape search failed:', error);
      // Even on error, we should have fallback shapes, but let's show a warning
      alert('Some shape providers are unavailable. Showing fallback shapes instead.');
      // Don't clear results - fetchShapes should always return fallback shapes
      setHasMoreShapes(false);
    } finally {
      setIsSearchingShapes(false);
    }
  }, [fetchShapes]);

  const loadMoreShapes = useCallback(async () => {
    if (isLoadingMoreShapes || !hasMoreShapes) {
      return;
    }
    const activeQuery = activeShapeSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMoreShapes(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchShapes(activeQuery, shapeCurrentPageRef.current);
      if (items.length > 0) {
        setShapeSearchResults((prev) => [...prev, ...items]);
        const nextPage = shapeCurrentPageRef.current + 1;
        setShapeCurrentPage(nextPage);
        shapeCurrentPageRef.current = nextPage;
      }
      setHasMoreShapes(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more shapes failed:', error);
      alert('Unable to load more shapes. Using available shapes.');
      setHasMoreShapes(false);
    } finally {
      setIsLoadingMoreShapes(false);
    }
  }, [fetchShapes, hasMoreShapes, isLoadingMoreShapes]);

  const loadMoreShapesRef = useRef(loadMoreShapes);

  useEffect(() => {
    loadMoreShapesRef.current = loadMoreShapes;
  }, [loadMoreShapes]);

  // IntersectionObserver for shape infinite scroll
  useEffect(() => {
    if (shapeSearchResults.length === 0 || !hasMoreShapes) {
      return;
    }

    const loadingIndicator = document.querySelector('.shape-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__shape-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Shape loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for shape loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Shape IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreShapes,
          hasMoreShapes,
          shapeSearchResultsLength: shapeSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreShapes && hasMoreShapes) {
          console.log('Loading more shapes via IntersectionObserver...');
          loadMoreShapesRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up shape IntersectionObserver');
      observer.disconnect();
    };
  }, [shapeSearchResults.length, hasMoreShapes, isLoadingMoreShapes]);

  // Prevent scroll propagation from shape results to parent containers
  useEffect(() => {
    const shapeResultsContainer = document.querySelector('.builder-sidebar__shape-results');
    if (!shapeResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    shapeResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    shapeResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      shapeResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      shapeResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreShapes = useCallback(() => {
    if (!isLoadingMoreShapes && hasMoreShapes) {
      loadMoreShapesRef.current();
    }
  }, [isLoadingMoreShapes, hasMoreShapes]);

  const handleIconSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    // If empty query, reset to default icons
    if (!trimmedQuery) {
      setIconSearchResults([
        {
          id: 'default-heart',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
          description: 'Heart',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-star',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
          description: 'Star',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-home',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
          description: 'Home',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-user',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
          description: 'User',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-search',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg',
          description: 'Search',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-settings',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg',
          description: 'Settings',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
      ]);
      hasTriggeredIconSearchRef.current = false;
      setIconCurrentPage(1);
      iconCurrentPageRef.current = 1;
      setHasMoreIcons(false);
      return;
    }

    hasTriggeredIconSearchRef.current = true;
    activeIconSearchQueryRef.current = trimmedQuery;
    setIsSearchingIcons(true);
    setIconCurrentPage(1);
    iconCurrentPageRef.current = 1;
    setHasMoreIcons(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchIcons(trimmedQuery, 1);
      setIconSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setIconCurrentPage(nextPage);
      iconCurrentPageRef.current = nextPage;
      setHasMoreIcons(nextHasMore);
    } catch (error) {
      console.error('Icon search failed:', error);
      // Even on error, we should have fallback icons, but let's show a warning
      alert('Some icon providers are unavailable. Showing fallback icons instead.');
      // Don't clear results - fetchIcons should always return fallback icons
      setHasMoreIcons(false);
    } finally {
      setIsSearchingIcons(false);
    }
  }, [fetchIcons]);

  const loadMoreIcons = useCallback(async () => {
    if (isLoadingMoreIcons || !hasMoreIcons) {
      return;
    }
    const activeQuery = activeIconSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMoreIcons(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchIcons(activeQuery, iconCurrentPageRef.current);
      if (items.length > 0) {
        setIconSearchResults((prev) => [...prev, ...items]);
        const nextPage = iconCurrentPageRef.current + 1;
        setIconCurrentPage(nextPage);
        iconCurrentPageRef.current = nextPage;
      }
      setHasMoreIcons(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more icons failed:', error);
      alert('Unable to load more icons. Using available icons.');
      setHasMoreIcons(false);
    } finally {
      setIsLoadingMoreIcons(false);
    }
  }, [fetchIcons, hasMoreIcons, isLoadingMoreIcons]);

  const loadMoreIconsRef = useRef(loadMoreIcons);

  useEffect(() => {
    loadMoreIconsRef.current = loadMoreIcons;
  }, [loadMoreIcons]);

  // IntersectionObserver for icon infinite scroll
  useEffect(() => {
    if (iconSearchResults.length === 0 || !hasMoreIcons) {
      return;
    }

    const loadingIndicator = document.querySelector('.icon-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__icon-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Icon loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for icon loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Icon IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreIcons,
          hasMoreIcons,
          iconSearchResultsLength: iconSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreIcons && hasMoreIcons) {
          console.log('Loading more icons via IntersectionObserver...');
          loadMoreIconsRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up icon IntersectionObserver');
      observer.disconnect();
    };
  }, [iconSearchResults.length, hasMoreIcons, isLoadingMoreIcons]);

  // Prevent scroll propagation from icon results to parent containers
  useEffect(() => {
    const iconResultsContainer = document.querySelector('.builder-sidebar__icon-results');
    if (!iconResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    iconResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    iconResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      iconResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      iconResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreIcons = useCallback(() => {
    if (!isLoadingMoreIcons && hasMoreIcons) {
      loadMoreIconsRef.current();
    }
  }, [isLoadingMoreIcons, hasMoreIcons]);

  const handleUseShape = async (shape) => {
    if (!activePage) return;

    // For shapes, we can either use the shape variant directly or load the icon
    if (shape.variant) {
      // Use the built-in shape variant - now supports image content
      handleAddShape(shape.variant);
    } else {
      // Handle shape as an icon/image
      const sourceUrl = shape?.downloadUrl || shape?.previewUrl || shape?.thumbUrl;
      if (!sourceUrl) {
        alert('Unable to load the selected shape. Please choose another one.');
        return;
      }

      try {
        const response = await fetch(sourceUrl);
        if (!response.ok) {
          throw new Error(`Network error: ${response.status}`);
        }
        const blob = await response.blob();
        const reader = new FileReader();
        reader.onload = () => {
          const dataUrl = reader.result;
          const img = new Image();
          img.onload = () => {
            const naturalW = img.naturalWidth || img.width;
            const naturalH = img.naturalHeight || img.height;
            // Shapes should be smaller than photos, max 200px
            const maxW = Math.round(activePage.width * 0.2);
            const maxH = Math.round(activePage.height * 0.2);
            const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
            const width = Math.max(1, Math.round(naturalW * scale));
            const height = Math.max(1, Math.round(naturalH * scale));
            const x = Math.round((activePage.width - width) / 2);
            const y = Math.round((activePage.height - height) / 2);
            const layer = createLayer('image', activePage, {
              name: shape?.description || 'Shape',
              content: dataUrl,
              metadata: {
                objectFit: 'contain',
                imageScale: 1,
                imageOffsetX: 0,
                imageOffsetY: 0,
                attribution: shape?.credit,
                isShape: true,
              },
            });
            layer.frame = { x, y, width, height, rotation: 0 };
            if (layer.frame) {
              layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
            }
            dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
          };
          img.src = dataUrl;
        };
        reader.readAsDataURL(blob);
      } catch (error) {
        console.error('Failed to load shape:', error);
        alert('Failed to load shape. Please try again.');
      }
    }
  };

  const handleShapeWithImage = async (variant) => {
    if (!activePage) return;

    // Trigger file input for image selection
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async (event) => {
      const file = event.target.files[0];
      if (!file) return;

      // Check file size (limit to 10MB)
      const maxSize = 10 * 1024 * 1024;
      if (file.size > maxSize) {
        alert('Image file is too large. Please select an image smaller than 10MB.');
        return;
      }

      // Read the image file
      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const imageUrl = e.target.result;

          // Validate the data URL
          if (!imageUrl || !imageUrl.startsWith('data:image/')) {
            alert('Failed to read the image file. Please try again.');
            return;
          }

          // Create shape with image content
          handleAddShape(variant, imageUrl);

          // Persist the file to IndexedDB for recent images
          try {
            const dbModule = await import('../../utils/recentImagesDB');
            const id = `shape-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            await dbModule.saveImage(id, file.name, file);
            dbModule.pruneOld(10).catch(() => {});
            const objectUrl = URL.createObjectURL(file);
            dispatch({ type: 'ADD_RECENTLY_UPLOADED_IMAGE', dataUrl: objectUrl, fileName: file.name, id });
          } catch (err) {
            console.error('Failed to persist shape image:', err);
          }
        } catch (error) {
          console.error('Error processing image for shape:', error);
          alert('An error occurred while processing the image. Please try again.');
        }
      };

      reader.onerror = () => {
        alert('Failed to read the image file. Please try again.');
      };

      reader.readAsDataURL(file);
    };

    input.click();
  };

  const generateColorPalette = async () => {
    setIsGeneratingPalette(true);
    try {
      const response = await fetch('http://colormind.io/api/', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          model: 'default'
        })
      });

      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }

      const data = await response.json();
      const colors = data.result.map(rgb => `rgb(${rgb[0]}, ${rgb[1]}, ${rgb[2]})`);
      setCurrentPalette(colors);
    } catch (error) {
      console.error('Failed to generate color palette:', error);
      alert('Failed to generate color palette. Please try again.');
    } finally {
      setIsGeneratingPalette(false);
    }
  };

  const applyColorToShape = (color) => {
    if (!activePage) return;

    const layer = createLayer('shape', activePage, {
      name: 'Colored Shape',
      variant: 'rectangle',
      fill: color,
      borderRadius: 16,
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const applyColorToText = (color) => {
    if (!activePage) return;

    const layer = createLayer('text', activePage, {
      name: 'Colored Text',
      content: 'Colored text',
      fontSize: 32,
      fill: color,
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const ensureFontLoaded = useCallback((fontDescriptor = {}) => {
    const { family, weights } = fontDescriptor;
    if (!family) {
      return;
    }

    const normalizedWeights = Array.isArray(weights) && weights.length > 0
      ? Array.from(new Set(weights.map((weight) => {
        if (typeof weight === 'number') {
          return String(weight);
        }
        if (typeof weight === 'string') {
          const numericMatch = weight.match(/\d+/);
          if (numericMatch) {
            return numericMatch[0];
          }
          if (weight.toLowerCase().includes('bold')) {
            return '700';
          }
          return '400';
        }
        return '400';
      })))
      : ['400'];

    const weightsParam = normalizedWeights.length > 0 ? normalizedWeights.join(';') : '400';
    const fontKey = `${family}-${weightsParam}`;

    if (loadedFontsRef.current.has(fontKey)) {
      return;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@${weightsParam}&display=swap`;
    document.head.appendChild(link);
    loadedFontsRef.current.add(fontKey);
  }, []);

  const handleUseIcon = async (icon) => {
    if (!activePage) return;

    const sourceUrl = icon?.downloadUrl || icon?.previewUrl || icon?.thumbUrl;
    if (!sourceUrl) {
      alert('Unable to load the selected icon. Please choose another one.');
      return;
    }

    try {
      const response = await fetch(sourceUrl);
      if (!response.ok) {
        throw new Error(`Network error: ${response.status}`);
      }
      const blob = await response.blob();
      const reader = new FileReader();
      reader.onload = () => {
        const dataUrl = reader.result;
        const img = new Image();
        img.onload = () => {
          const naturalW = img.naturalWidth || img.width;
          const naturalH = img.naturalHeight || img.height;
          // Icons should be smaller than photos, max 150px
          const maxW = Math.round(activePage.width * 0.15);
          const maxH = Math.round(activePage.height * 0.15);
          const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
          const width = Math.max(1, Math.round(naturalW * scale));
          const height = Math.max(1, Math.round(naturalH * scale));
          const x = Math.round((activePage.width - width) / 2);
          const y = Math.round((activePage.height - height) / 2);
          const layer = createLayer('image', activePage, {
            name: icon?.description || 'Icon',
            content: dataUrl,
            metadata: {
              objectFit: 'contain',
              imageScale: 1,
              imageOffsetX: 0,
              imageOffsetY: 0,
              attribution: icon?.credit,
              isIcon: true,
            },
          });
          layer.frame = { x, y, width, height, rotation: 0 };
          if (layer.frame) {
            layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
          }
          dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
        };
        img.src = dataUrl;
      };
      reader.readAsDataURL(blob);
    } catch (error) {
      console.error('Failed to load icon:', error);
      alert('Failed to load icon. Please try again.');
    }
  };

  const fetchFonts = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    const perPage = 20; // Google Fonts API returns up to 100, but we'll paginate
    const startIndex = (pageNumber - 1) * perPage;

    try {
      // Use Google Fonts API
      const apiKey = process.env.REACT_APP_GOOGLE_FONTS_API_KEY || 'AIzaSyB8AzfLkq8VwHq5t5n5n5n5n5n5n5n5n5'; // Placeholder - replace with actual key
      const response = await fetch(`https://www.googleapis.com/webfonts/v1/webfonts?key=${apiKey}&sort=popularity`);

      if (!response.ok) {
        throw new Error(`Google Fonts API error: ${response.status}`);
      }

      const data = await response.json();
      const allFonts = data.items || [];

      // Filter fonts based on search query
      const filteredFonts = allFonts.filter(font =>
        font.family.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        (font.category && font.category.toLowerCase().includes(trimmedQuery.toLowerCase()))
      );

      // Paginate the results
      const start = startIndex;
      const end = start + perPage;
      const paginatedFonts = filteredFonts.slice(start, end);

      const formattedResults = paginatedFonts.map((font) => ({
        id: `font-${font.family.replace(/\s+/g, '-').toLowerCase()}`,
        family: font.family,
        category: font.category,
        variants: font.variants,
        subsets: font.subsets,
        version: font.version,
        lastModified: font.lastModified,
        files: font.files,
        provider: 'google-fonts',
        providerLabel: 'Google Fonts',
        credit: 'Google Fonts',
      }));

      return {
        items: formattedResults,
        hasMore: end < filteredFonts.length,
      };
    } catch (error) {
      console.error('Google Fonts API failed:', error);
      // Fallback to some popular fonts
      const fallbackFonts = [
        { family: 'Inter', category: 'sans-serif', variants: ['400', '500', '600', '700'] },
        { family: 'Roboto', category: 'sans-serif', variants: ['300', '400', '500', '700'] },
        { family: 'Open Sans', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
        { family: 'Lato', category: 'sans-serif', variants: ['300', '400', '700'] },
        { family: 'Montserrat', category: 'sans-serif', variants: ['400', '500', '600', '700'] },
        { family: 'Poppins', category: 'sans-serif', variants: ['300', '400', '500', '600', '700'] },
        { family: 'Nunito', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
        { family: 'Playfair Display', category: 'serif', variants: ['400', '700'] },
        { family: 'Merriweather', category: 'serif', variants: ['300', '400', '700'] },
        { family: 'Crimson Text', category: 'serif', variants: ['400', '600'] },
      ];

      const filteredFallbacks = fallbackFonts.filter(font =>
        font.family.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        font.category.toLowerCase().includes(trimmedQuery.toLowerCase())
      );

      const start = startIndex;
      const end = start + perPage;
      const paginatedFallbacks = filteredFallbacks.slice(start, end);

      const formattedFallbacks = paginatedFallbacks.map((font) => ({
        id: `font-${font.family.replace(/\s+/g, '-').toLowerCase()}`,
        family: font.family,
        category: font.category,
        variants: font.variants,
        subsets: ['latin'],
        provider: 'fallback',
        providerLabel: 'System Fonts',
        credit: 'System Fonts',
      }));

      return {
        items: formattedFallbacks,
        hasMore: end < filteredFallbacks.length,
      };
    }
  }, []);

  const handleFontSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    // If empty query, reset to default fonts
    if (!trimmedQuery) {
      setFontSearchResults([]);
      hasTriggeredFontSearchRef.current = false;
      setFontCurrentPage(1);
      fontCurrentPageRef.current = 1;
      setHasMoreFonts(false);
      return;
    }

    hasTriggeredFontSearchRef.current = true;
    activeFontSearchQueryRef.current = trimmedQuery;
    setIsSearchingFonts(true);
    setFontCurrentPage(1);
    fontCurrentPageRef.current = 1;
    setHasMoreFonts(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchFonts(trimmedQuery, 1);
      setFontSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setFontCurrentPage(nextPage);
      fontCurrentPageRef.current = nextPage;
      setHasMoreFonts(nextHasMore);
    } catch (error) {
      console.error('Font search failed:', error);
      alert('Some font providers are unavailable. Showing fallback fonts instead.');
      setHasMoreFonts(false);
    } finally {
      setIsSearchingFonts(false);
    }
  }, [fetchFonts]);

  const loadMoreFonts = useCallback(async () => {
    if (isLoadingMoreFonts || !hasMoreFonts) {
      return;
    }
    const activeQuery = activeFontSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMoreFonts(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchFonts(activeQuery, fontCurrentPageRef.current);
      if (items.length > 0) {
        setFontSearchResults((prev) => [...prev, ...items]);
        const nextPage = fontCurrentPageRef.current + 1;
        setFontCurrentPage(nextPage);
        fontCurrentPageRef.current = nextPage;
      }
      setHasMoreFonts(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more fonts failed:', error);
      alert('Unable to load more fonts. Using available fonts.');
      setHasMoreFonts(false);
    } finally {
      setIsLoadingMoreFonts(false);
    }
  }, [fetchFonts, hasMoreFonts, isLoadingMoreFonts]);

  const loadMoreFontsRef = useRef(loadMoreFonts);

  useEffect(() => {
    loadMoreFontsRef.current = loadMoreFonts;
  }, [loadMoreFonts]);

  // IntersectionObserver for font infinite scroll
  useEffect(() => {
    if (fontSearchResults.length === 0 || !hasMoreFonts) {
      return;
    }

    const loadingIndicator = document.querySelector('.font-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__font-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Font loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for font loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Font IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreFonts,
          hasMoreFonts,
          fontSearchResultsLength: fontSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreFonts && hasMoreFonts) {
          console.log('Loading more fonts via IntersectionObserver...');
          loadMoreFontsRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up font IntersectionObserver');
      observer.disconnect();
    };
  }, [fontSearchResults.length, hasMoreFonts, isLoadingMoreFonts]);

  // Prevent scroll propagation from font results to parent containers
  useEffect(() => {
    const fontResultsContainer = document.querySelector('.builder-sidebar__font-results');
    if (!fontResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    fontResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    fontResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      fontResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      fontResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreFonts = useCallback(() => {
    if (!isLoadingMoreFonts && hasMoreFonts) {
      loadMoreFontsRef.current();
    }
  }, [isLoadingMoreFonts, hasMoreFonts]);

  const handleUseFont = (font) => {
    if (!activePage) return;

    ensureFontLoaded({ family: font.family, weights: font.variants });

    // Create text layer with the selected font
    const layer = createLayer('text', activePage, {
      name: `${font.family} Text`,
      content: 'Sample text with new font',
      fontSize: 32,
      fontFamily: `${font.family}, ${font.category}`,
      fontWeight: '400',
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };







  // Trigger new search when filter or provider changes and there's an existing search query
  useEffect(() => {
    if (!hasTriggeredSearchRef.current || !activeSearchQueryRef.current) {
      return;
    }
    handleSearch(activeSearchQueryRef.current);
  }, [handleSearch, photoProvider, selectedFilter]);

  // IntersectionObserver for infinite scroll
  useEffect(() => {
    if (searchResults.length === 0 || !hasMore) {
      return;
    }

    const loadingIndicator = document.querySelector('.loading-indicator');
    if (!loadingIndicator) {
      console.log('Loading indicator not found');
      return;
    }

    console.log('Setting up IntersectionObserver for loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('IntersectionObserver triggered:', { isIntersecting: entry.isIntersecting, isLoadingMore, hasMore });

        if (entry.isIntersecting && !isLoadingMore && hasMore && searchQuery.trim()) {
          console.log('Loading more images via IntersectionObserver...');
          loadMoreImagesRef.current();
        }
      },
      {
        root: document.querySelector('.builder-sidebar__search-results'),
        rootMargin: '100px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up IntersectionObserver');
      observer.disconnect();
    };
  }, [searchResults.length, hasMore, isLoadingMore, searchQuery]);

  // Prevent scroll propagation from search results to parent containers
  useEffect(() => {
    const searchResultsContainer = document.querySelector('.builder-sidebar__search-results');
    if (!searchResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    searchResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    searchResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      searchResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      searchResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  const handleUsePhotoResult = async (photo) => {
    if (!activePage) return;

    const sourceUrl = photo?.downloadUrl || photo?.previewUrl || photo?.thumbUrl;
    if (!sourceUrl) {
      alert('Unable to load the selected image. Please choose another one.');
      return;
    }

    try {
      const response = await fetch(sourceUrl);
      if (!response.ok) {
        throw new Error(`Network error: ${response.status}`);
      }
      const blob = await response.blob();
      const reader = new FileReader();
      reader.onload = () => {
        const dataUrl = reader.result;
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
            name: photo?.description || `${photo?.providerLabel ?? 'Stock'} image`,
            content: dataUrl,
            metadata: {
              objectFit: 'cover',
              imageScale: 1,
              imageOffsetX: 0,
              imageOffsetY: 0,
              attribution: photo?.credit,
            },
          });
          layer.frame = { x, y, width, height, rotation: 0 };
          if (layer.frame) {
            layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
          }
          dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
        };
        img.src = dataUrl;
      };
      reader.readAsDataURL(blob);
    } catch (error) {
      console.error('Failed to load photo:', error);
      alert('Failed to load photo. Please try again.');
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
          <div className="builder-sidebar__content builder-sidebar__content--text">
            <div className="builder-sidebar__header">
              <div>
                <h2>Text</h2>
                <p>Add quick headings, stylized blocks, or browse the full font library.</p>
              </div>
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
            <div className="text-panel">
              <div className="text-panel__canvas" ref={textPanelCanvasRef}>
                <div className="text-panel__quick" style={{ marginBottom: '1rem' }}>
                  <div className="text-panel__quick-actions" style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                    <button
                      type="button"
                      className="text-panel__quick-btn"
                      style={{
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(37, 99, 235, 0.25)',
                        background: 'rgba(37, 99, 235, 0.08)',
                        color: 'var(--builder-text)',
                        padding: '0.4rem 0.8rem',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                        width: '100%'
                      }}
                      onClick={() => handleAddText({ name: 'Heading', content: 'Add header', fontSize: 52 })}
                      onMouseEnter={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.15)'}
                      onMouseLeave={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.08)'}
                    >
                      add header
                    </button>
                    <button
                      type="button"
                      className="text-panel__quick-btn"
                      style={{
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(37, 99, 235, 0.25)',
                        background: 'rgba(37, 99, 235, 0.08)',
                        color: 'var(--builder-text)',
                        padding: '0.4rem 0.8rem',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                        width: '100%'
                      }}
                      onClick={() => handleAddText({ name: 'Subheading', content: 'Add sub header', fontSize: 34 })}
                      onMouseEnter={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.15)'}
                      onMouseLeave={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.08)'}
                    >
                      add sub header
                    </button>
                    <button
                      type="button"
                      className="text-panel__quick-btn"
                      style={{
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(37, 99, 235, 0.25)',
                        background: 'rgba(37, 99, 235, 0.08)',
                        color: 'var(--builder-text)',
                        padding: '0.4rem 0.8rem',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                        width: '100%'
                      }}
                      onClick={() => handleAddText({ name: 'Body text', content: 'Add body text', fontSize: 26, align: 'left' })}
                      onMouseEnter={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.15)'}
                      onMouseLeave={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.08)'}
                    >
                      add body text
                    </button>
                  </div>
                </div>
                <div className="builder-sidebar__search" style={{ marginBottom: '1rem' }}>
                  <input
                    type="text"
                    placeholder="Search for fonts..."
                    value={fontSearchQuery}
                    onChange={(e) => setFontSearchQuery(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleFontSearch(e.target.value)}
                  />
                  <button type="button" onClick={() => handleFontSearch(fontSearchQuery)} disabled={isSearchingFonts}>
                    {isSearchingFonts ? 'Searching...' : 'Search'}
                  </button>
                </div>

                {/* Ready-made styled text presets (visual previews) */}
                <div className="text-panel__styled-presets" ref={styledContainerRef}>
                  <div className="text-panel__styled-grid">
                    {styledPresets.map((preset) => (
                      <button
                        key={preset.id}
                        type="button"
                        className="styled-text-preset"
                        onClick={() => {
                          try {
                            ensureFontLoaded({ family: preset.family, weights: [preset.fontWeight] });
                          } catch (err) {
                            // ignore font load errors
                          }
                          handleAddText({ name: preset.family, content: preset.content, fontSize: preset.fontSize, fontWeight: preset.fontWeight, align: preset.align, transform: preset.transform });
                        }}
                        title={`Add styled text: ${preset.family}`}
                      >
                        <div
                          className="styled-text-preview"
                          style={{
                            fontFamily: preset.family,
                            fontSize: `${preset.fontSize}px`,
                            fontWeight: preset.fontWeight,
                            textAlign: preset.align,
                            textTransform: preset.transform,
                          }}
                        >
                          {preset.content}
                        </div>
                      </button>
                    ))}
                  </div>
                  <div className="styled-loading-indicator">
                    <div className={`spinner ${isLoadingStyledPresets ? 'loading' : ''}`}></div>
                    <span>
                      {isLoadingStyledPresets
                        ? 'Loading styled presets...'
                        : (hasMoreStyledPresets ? 'Scroll for more presets' : 'No more presets')}
                    </span>
                  </div>
                </div>
                {fontSearchResults.length > 0 ? (
                  <div className="builder-sidebar__font-results">
                    {fontSearchResults.map((font) => (
                      <button
                        key={font.id}
                        type="button"
                        className="search-result-thumb font-result-thumb"
                        onClick={() => handleUseFont(font)}
                        title={`${font.family} (${font.category}) - ${font.providerLabel}`}
                      >
                        <div
                          className="font-preview"
                          style={{
                            fontFamily: `${font.family}, ${font.category}`,
                            fontSize: '16px',
                            fontWeight: '400',
                            textAlign: 'center',
                            padding: '8px',
                            backgroundColor: '#fff',
                            border: '1px solid #e5e7eb',
                            borderRadius: '4px',
                            minHeight: '48px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis'
                          }}
                        >
                          {font.family}
                        </div>
                        <span className="search-result-provider-tag">{font.providerLabel}</span>
                      </button>
                    ))}
                    {hasMoreFonts && (
                      <>
                        <div className="font-loading-indicator">
                          <div className={`spinner ${isLoadingMoreFonts ? 'loading' : ''}`}></div>
                          <span>
                            {isLoadingMoreFonts
                              ? 'Loading more fonts...'
                              : 'Scroll for more fonts'}
                          </span>
                        </div>
                        <div className="load-more-container">
                          <button
                            type="button"
                            className="load-more-btn"
                            onClick={handleLoadMoreFonts}
                            disabled={isLoadingMoreFonts}
                          >
                            {isLoadingMoreFonts ? 'Loading...' : 'Load More Fonts'}
                          </button>
                        </div>
                      </>
                    )}
                  </div>
                ) : hasTriggeredFontSearchRef.current ? (
                  <div className="builder-sidebar__empty-state">
                    {isSearchingFonts ? 'Searching for fonts...' : 'No fonts found. Try a different search term.'}
                  </div>
                ) : null}
              </div>
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
            <p>Search for shapes from multiple providers.</p>
            <div className="builder-sidebar__provider-note">
              Shapes from Iconify and Simple Icons. Please display attribution when publishing your design.
            </div>
            <div className="builder-sidebar__search">
              <input
                ref={(input) => { searchInputRef.current = input; }}
                type="text"
                placeholder="Search for shapes..."
                value={shapeSearchQuery}
                onChange={(e) => setShapeSearchQuery(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleShapeSearch(e.target.value)}
              />
              <button type="button" onClick={() => handleShapeSearch(searchInputRef.current?.value || shapeSearchQuery)} disabled={isSearchingShapes}>
                {isSearchingShapes ? 'Searching...' : 'Search'}
              </button>
            </div>
            {!hasTriggeredShapeSearchRef.current && (
              <div className="builder-sidebar__hint" style={{ marginBottom: '10px', fontSize: '12px', color: '#666' }}>
                Popular shapes (search above for more options):
              </div>
            )}
            {!hasTriggeredShapeSearchRef.current && (
              <div className="builder-sidebar__shape-presets" style={{ marginBottom: '15px' }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px' }}>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleAddShape('rectangle')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    □ Rectangle
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleShapeWithImage('rectangle')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    □ + Image
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleAddShape('circle')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    ○ Circle
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleShapeWithImage('circle')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    ○ + Image
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleAddShape('triangle')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    △ Triangle
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleShapeWithImage('triangle')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    △ + Image
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleAddShape('star')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    ★ Star
                  </button>
                  <button
                    type="button"
                    className="tool-action-btn"
                    onClick={() => handleShapeWithImage('star')}
                    style={{ padding: '8px', fontSize: '12px' }}
                  >
                    ★ + Image
                  </button>
                </div>
              </div>
            )}
            {shapeSearchResults.length > 0 ? (
              <div className="builder-sidebar__shape-results">
                {shapeSearchResults.map((shape) => (
                  <button
                    key={shape.id}
                    type="button"
                    className="search-result-thumb"
                    onClick={() => handleUseShape(shape)}
                    title={`${shape.description} (${shape.providerLabel})`}
                  >
                    <img
                      src={shape.thumbUrl}
                      alt={shape.description}
                      style={{ width: 64, height: 64, objectFit: 'contain', backgroundColor: '#fff' }}
                      onError={(e) => {
                        // Replace broken/missing shape image with a tiny inline SVG placeholder
                        try {
                          e.target.onerror = null;
                          const label = (shape.description || 'sh').toUpperCase().slice(0, 2);
                          const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23f3f4f6'/><text x='50%' y='50%' font-size='14' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                          e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                        } catch (err) {
                          /* ignore fallback errors */
                        }
                      }}
                    />
                    {shape.providerLabel !== 'Iconify' && (
                      <span className="search-result-provider-tag">{shape.providerLabel}</span>
                    )}
                  </button>
                ))}
                {hasMoreShapes && (
                  <>
                    <div className="shape-loading-indicator">
                      <div className={`spinner ${isLoadingMoreShapes ? 'loading' : ''}`}></div>
                      <span>
                        {isLoadingMoreShapes
                          ? 'Loading more shapes...'
                          : 'Scroll for more shapes'}
                      </span>
                    </div>
                    <div className="load-more-container">
                      <button
                        type="button"
                        className="load-more-btn"
                        onClick={handleLoadMoreShapes}
                        disabled={isLoadingMoreShapes}
                      >
                        {isLoadingMoreShapes ? 'Loading...' : 'Load More Shapes'}
                      </button>
                    </div>
                  </>
                )}
              </div>
            ) : hasTriggeredShapeSearchRef.current ? (
              <div className="builder-sidebar__empty-state">
                {isSearchingShapes ? 'Searching for shapes...' : 'No shapes found. Try a different search term.'}
              </div>
            ) : null}
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
            <p>Search for royalty-free images from Unsplash and Pixabay.</p>
            <div className="builder-sidebar__provider-toggle" role="group" aria-label="Choose image provider">
              {PHOTO_PROVIDERS.map((provider) => (
                <button
                  key={provider.id}
                  type="button"
                  className={`filter-btn ${photoProvider === provider.id ? 'active' : ''}`}
                  onClick={() => setPhotoProvider(provider.id)}
                >
                  {provider.label}
                </button>
              ))}
            </div>
            <div className="builder-sidebar__provider-note">
              Showing results from {activeProviderLabel}. Please display attribution when publishing your design.
            </div>
            <div className="builder-sidebar__filters" role="group" aria-label="Filter results">
              {PHOTO_FILTERS.map((filter) => (
                <button
                  key={filter.id}
                  type="button"
                  className={`filter-btn ${selectedFilter === filter.id ? 'active' : ''}`}
                  onClick={() => setSelectedFilter(filter.id)}
                >
                  {filter.label}
                </button>
              ))}
            </div>
            <div className="builder-sidebar__search">
              <input
                type="text"
                placeholder="Search for photos..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSearch(e.target.value)}
              />
              <button type="button" onClick={() => handleSearch(searchQuery)} disabled={isSearching}>
                {isSearching ? 'Searching...' : 'Search'}
              </button>
            </div>
            {searchResults.length > 0 && (
              <div className="builder-sidebar__search-results">
                {searchResults.map((photo) => (
                  <button
                    key={photo.id}
                    type="button"
                    className="search-result-thumb"
                    onClick={() => handleUsePhotoResult(photo)}
                    title={`${photo.description} (${photo.providerLabel})`}
                  >
                    <img
                      src={photo.thumbUrl}
                      alt={photo.description}
                      style={{ width: 96, height: 96, objectFit: 'cover', backgroundColor: '#f3f4f6' }}
                      onError={(e) => {
                        // Replace broken/missing photo image with a placeholder
                        try {
                          e.target.onerror = null;
                          const label = (photo.description || 'img').toUpperCase().slice(0, 3);
                          const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='96' height='96'><rect width='100%' height='100%' fill='%23e5e7eb'/><text x='50%' y='50%' font-size='12' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                          e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                        } catch (err) {
                          /* ignore fallback errors */
                        }
                      }}
                    />
                  </button>
                ))}
                {hasMore && (
                  <div className="loading-indicator">
                    <div className={`spinner ${isLoadingMore ? 'loading' : ''}`}></div>
                    <span>
                      {isLoadingMore
                        ? `Loading more ${activeProviderLabel} images...`
                        : `Scroll for more ${activeProviderLabel} images`}
                    </span>
                  </div>
                )}
              </div>
            )}
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
      case 'colors':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Colors</h2>
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
            <p>Generate beautiful color palettes using Colormind's AI.</p>
            <div className="builder-sidebar__tool-actions">
              <button
                type="button"
                className="tool-action-btn"
                onClick={generateColorPalette}
                disabled={isGeneratingPalette}
              >
                {isGeneratingPalette ? 'Generating...' : 'Generate Palette'}
              </button>
            </div>
            {currentPalette.length > 0 && (
              <div className="builder-sidebar__palette">
                <h3>Current Palette</h3>
                <div className="builder-sidebar__palette-colors">
                  {currentPalette.map((color, index) => (
                    <div key={index} className="builder-sidebar__palette-item">
                      <div
                        className="builder-sidebar__palette-swatch"
                        style={{ backgroundColor: color }}
                        title={color}
                      />
                      <div className="builder-sidebar__palette-actions">
                        <button
                          type="button"
                          className="tool-action-btn-small"
                          onClick={() => applyColorToShape(color)}
                          title="Apply to shape"
                        >
                          Shape
                        </button>
                        <button
                          type="button"
                          className="tool-action-btn-small"
                          onClick={() => applyColorToText(color)}
                          title="Apply to text"
                        >
                          Text
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
            <div className="builder-sidebar__provider-note">
              Color palettes generated by Colormind.io API.
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
      case 'icons':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Icons</h2>
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
            <p>Search for icons from multiple providers.</p>
            <div className="builder-sidebar__provider-note">
              Icons from Simple Icons, IconFinder, Iconify, and Flaticon. Please display attribution when publishing your design.
            </div>
            <div className="builder-sidebar__search">
              <input
                ref={(input) => { searchInputRef.current = input; }}
                type="text"
                placeholder="Search for icons..."
                value={iconSearchQuery}
                onChange={(e) => setIconSearchQuery(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleIconSearch(e.target.value)}
              />
              <button type="button" onClick={() => handleIconSearch(searchInputRef.current?.value || iconSearchQuery)} disabled={isSearchingIcons}>
                {isSearchingIcons ? 'Searching...' : 'Search'}
              </button>
            </div>
            {!hasTriggeredIconSearchRef.current && (
              <div className="builder-sidebar__hint" style={{ marginBottom: '10px', fontSize: '12px', color: '#666' }}>
                Popular icons (search above for more options):
              </div>
            )}
            {iconSearchResults.length > 0 ? (
              <div className="builder-sidebar__icon-results">
                {iconSearchResults.map((icon) => (
                  <button
                    key={icon.id}
                    type="button"
                    className="search-result-thumb"
                    onClick={() => handleUseIcon(icon)}
                    title={`${icon.description} (${icon.providerLabel})`}
                  >
                    <img
                      src={icon.thumbUrl}
                      alt={icon.description}
                      style={{ width: 64, height: 64, objectFit: 'contain', backgroundColor: '#fff' }}
                      onError={(e) => {
                        // Replace broken/missing icon image with a tiny inline SVG placeholder
                        try {
                          e.target.onerror = null;
                          const label = (icon.description || 'ic').toUpperCase().slice(0, 2);
                          const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23f3f4f6'/><text x='50%' y='50%' font-size='14' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                          e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                        } catch (err) {
                          /* ignore fallback errors */
                        }
                      }}
                    />
                    {icon.providerLabel !== 'Iconify' && (
                      <span className="search-result-provider-tag">{icon.providerLabel}</span>
                    )}
                  </button>
                ))}
                {hasMoreIcons && (
                  <>
                    <div className="icon-loading-indicator">
                      <div className={`spinner ${isLoadingMoreIcons ? 'loading' : ''}`}></div>
                      <span>
                        {isLoadingMoreIcons
                          ? 'Loading more icons...'
                          : 'Scroll for more icons'}
                      </span>
                    </div>
                    <div className="load-more-container">
                      <button
                        type="button"
                        className="load-more-btn"
                        onClick={handleLoadMoreIcons}
                        disabled={isLoadingMoreIcons}
                      >
                        {isLoadingMoreIcons ? 'Loading...' : 'Load More Icons'}
                      </button>
                    </div>
                  </>
                )}
              </div>
            ) : hasTriggeredIconSearchRef.current ? (
              <div className="builder-sidebar__empty-state">
                {isSearchingIcons ? 'Searching for icons...' : 'No icons found. Try a different search term.'}
              </div>
            ) : null}
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
