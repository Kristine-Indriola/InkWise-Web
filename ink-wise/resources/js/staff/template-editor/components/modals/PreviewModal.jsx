import React, { useEffect, useMemo, useState } from 'react';
import PropTypes from 'prop-types';
import { createPortal } from 'react-dom';

import { useBuilderStore } from '../../state/BuilderStore';
import { getShapeMaskStyles, getShapeVisualStyles } from '../../utils/pageShapes';

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

function formatInsetSummary(insets, fallback = 'None') {
	if (!insets) {
		return fallback;
	}

	const values = [insets.top, insets.right, insets.bottom, insets.left];
	const hasValue = values.some((value) => value > 0);
	if (!hasValue) {
		return fallback;
	}

	return `${values[0]}px / ${values[1]}px / ${values[2]}px / ${values[3]}px`;
}

export function PreviewModal({ isOpen, onClose }) {
	const { state, dispatch } = useBuilderStore();

	const pages = state?.pages ?? [];
	if (!isOpen || pages.length === 0) {
		return null;
	}

	const activePage = pages?.find((page) => page.id === state.activePageId) ?? pages?.[0];
	const safeZone = resolveInsets(activePage.safeZone);
	const bleed = resolveInsets(activePage.bleed);
	const hasSafeZone = Object.values(safeZone).some((value) => value > 0);
	const hasBleed = Object.values(bleed).some((value) => value > 0);

	const [showSafeZone, setShowSafeZone] = useState(() => hasSafeZone);
	const [showBleed, setShowBleed] = useState(false);
	const [backgroundTone, setBackgroundTone] = useState('light');

	useEffect(() => {
		setShowSafeZone(hasSafeZone);
		setShowBleed(false);
	}, [hasSafeZone, activePage.id]);

	const pageWidth = Math.max(Number(activePage.width) || 0, 1);
	const pageHeight = Math.max(Number(activePage.height) || 0, 1);
	const previewMaxWidth = 760;
	const previewMaxHeight = 520;
	const previewScale = Math.min(previewMaxWidth / pageWidth, previewMaxHeight / pageHeight, 1);
	const scaledWidth = Math.round(pageWidth * previewScale);
	const scaledHeight = Math.round(pageHeight * previewScale);

	const rawLayers = Array.isArray(activePage.nodes) ? activePage.nodes : [];
	const visibleLayers = useMemo(
		() => rawLayers.filter((layer) => layer.visible !== false),
		[rawLayers],
	);

	const layerStats = useMemo(() => {
		return visibleLayers.reduce(
			(acc, layer) => {
				const type = layer.type || 'other';
				acc.total += 1;
				acc[type] = (acc[type] || 0) + 1;
				return acc;
			},
			{ total: 0 },
		);
	}, [visibleLayers]);

	const sampleTexts = new Set([
		'Aurora Atelier',
		'Visual storytelling & editorial craft',
		'Playfair Display headline with Source Sans Pro narrative subcopy.',
		'Lumina Forge',
		'Product strategy & venture labs',
		'DM Serif Display hero matched with crisp Inter labels.',
		'Saffron Harbor',
		'Artisanal markets & seaside residencies',
		'Cormorant Garamond headline with welcoming Nunito Sans body.',
		'Meridian Labs',
		'Insight decks • Research memos',
		'Libre Baskerville deck titles with Manrope research notes.',
		'Celestial Grid',
		'Futurist observatory narratives',
		'Cinzel monograms paired with Space Grotesk captions.',
		'Radiant Market',
		'Seasonal florals & gifting studio',
		'Abril Fatface display balanced by Work Sans merchandising notes.',
		'Atelier North',
		'Boutique identity consultancy',
		'Lora type paired with crisp Poppins service line.',
		'Solstice Audio',
		'Soundscapes for modern retreats',
		'Volkhov warmth with Mulish modernity.',
		'Harvest Lane',
		'Slow food residencies & journals',
		'Cardo editorial serif with Questrial market notes.',
		'Paragon Museum',
		'Annual symposium schedule',
		'Tickets • Archives • Tours',
		'Marcellus title with IBM Plex Sans itinerary details.',
		'Velvet Horizon',
		'Sunset editorials & travel essays',
		'Rosarivo curves with airy Raleway notes.',
		'Meteora Studio',
		'Speculative interiors & lighting labs',
		'Prata elegance with grounded Figtree descriptors.',
		'Ember Studio',
		'Culinary films & sonic diaries',
		'Spectral prose headlines with Karla annotations.',
		'Glasshouse',
		'Greenhouse culture & creative labs',
		'Gloock statement paired with Montserrat details.',
		'Nova Lounge',
		'Midnight tastings & listening rooms',
		'Noto Serif Display paired with Urbanist lounge notes.',
		'Terrace Agency',
		'Landscape narratives & brand sites',
		'Quattrocento headlines with DM Sans service info.',
		'Lucid Canvas',
		'Immersive art fair programming',
		'Ysabeau contrasts with approachable Cabin body.',
		'Citrine Summit',
		'Sustainability briefings & labs',
		'Crimson Pro titles with Open Sans agenda text.',
		'Frame Factory',
		'Gallery systems & product drops',
		'Fraunces curves with minimalist Outfit copy.',
		'Indigo Manor',
		'Cultural residences & salons',
		'EB Garamond editorial voice with Hind Madurai support.',
		'Marble Coast',
		'Coastal architecture dossiers',
		'Tenor Sans structure with Gentium Plus narrative.',
		'Orchid Bureau',
		'Trend reports • Field interviews',
		'Newsreader gravitas with Heebo clarity.',
		'Lux Atelier',
		'Made-to-measure wardrobe plans',
		'Baskervville elegance with utilitarian Lato captions.',
		'Cobalt Grove',
		'Botanical research & residency notes',
		'Zilla Slab hero text with Rubik supporting copy.',
	]);

	const textContents = useMemo(() => {
		const texts = visibleLayers
			.filter((layer) => layer.type === 'text' && layer.content && layer.content.trim())
			.map((layer) => layer.content.trim())
			.filter((content) => !sampleTexts.has(content));
		return [...new Set(texts)]; // Remove duplicates
	}, [visibleLayers, sampleTexts]);

	const sortedLayers = useMemo(() => {
		const order = { back: 0, middle: 1, front: 2 };
		return [...rawLayers].sort((a, b) => {
			const sideA = a.side || 'middle';
			const sideB = b.side || 'middle';
			return (order[sideA] ?? 1) - (order[sideB] ?? 1);
		});
	}, [rawLayers]);

	const pageShape = activePage.shape ?? null;
	const pageShapeStyles = useMemo(() => getShapeVisualStyles(pageShape), [pageShape]);
	const pageShapeMaskStyles = useMemo(() => getShapeMaskStyles(pageShape), [pageShape]);

	const safeZoneStyle = showSafeZone && hasSafeZone ? {
		position: 'absolute',
		top: safeZone.top,
		right: safeZone.right,
		bottom: safeZone.bottom,
		left: safeZone.left,
		border: '1px dashed rgba(37, 99, 235, 0.55)',
		pointerEvents: 'none',
		...pageShapeMaskStyles,
	} : null;

	const bleedStyle = showBleed && hasBleed ? {
		position: 'absolute',
		top: -bleed.top,
		right: -bleed.right,
		bottom: -bleed.bottom,
		left: -bleed.left,
		border: '1px solid rgba(220, 38, 38, 0.4)',
		pointerEvents: 'none',
		...pageShapeMaskStyles,
	} : null;

	const handlePageSelect = (pageId) => {
		if (!pageId || pageId === activePage.id) {
			return;
		}
		dispatch({ type: 'SELECT_PAGE', pageId });
	};


	const modalContent = (
		<div className="preview-modal-overlay" onClick={onClose}>
			<div className="preview-modal" onClick={(event) => event.stopPropagation()}>
				<div className="preview-modal__header">
					<div className="preview-modal__title-group">
						<h2>Template Preview</h2>
						<p>
							{state.template?.name || 'Untitled template'} - {pages.length} page{pages.length === 1 ? '' : 's'}
						</p>
					</div>
					<div className="preview-modal__actions" role="toolbar" aria-label="Preview options">
						{hasSafeZone && (
							<button
								type="button"
								className={`preview-toggle ${showSafeZone ? 'is-active' : ''}`}
								onClick={() => setShowSafeZone((value) => !value)}
							>
								Safe zone
							</button>
						)}
						{hasBleed && (
							<button
								type="button"
								className={`preview-toggle ${showBleed ? 'is-active' : ''}`}
								onClick={() => setShowBleed((value) => !value)}
							>
								Bleed
							</button>
						)}
						<div className="preview-toggle-group" role="group" aria-label="Preview background">
							<button
								type="button"
								className={`preview-toggle ${backgroundTone === 'light' ? 'is-active' : ''}`}
								onClick={() => setBackgroundTone('light')}
							>
								Light
							</button>
							<button
								type="button"
								className={`preview-toggle ${backgroundTone === 'dark' ? 'is-active' : ''}`}
								onClick={() => setBackgroundTone('dark')}
							>
								Dark
							</button>
						</div>
						<button
							type="button"
							className="preview-modal__close"
							onClick={onClose}
							aria-label="Close preview"
						>
							×
						</button>
					</div>
				</div>

				<div className="preview-modal__content">
					<aside className="preview-modal__sidebar" aria-label="Template details">
						<div className="preview-sidebar__section">
							<h3>Page info</h3>
							<dl className="preview-info-list">
								<div>
									<dt>Title</dt>
									<dd>{activePage.name || 'Untitled page'}</dd>
								</div>
								<div>
									<dt>Dimensions</dt>
									<dd>{pageWidth} × {pageHeight}px</dd>
								</div>
								<div>
									<dt>Scale</dt>
									<dd>{Math.round(previewScale * 100)}%</dd>
								</div>
								<div>
									<dt>Safe zone</dt>
									<dd>{formatInsetSummary(hasSafeZone ? safeZone : null)}</dd>
								</div>
								<div>
									<dt>Bleed</dt>
									<dd>{formatInsetSummary(hasBleed ? bleed : null)}</dd>
								</div>
							</dl>
						</div>

						{pages.length > 1 && (
							<div className="preview-sidebar__section">
								<h3>Pages</h3>
								<ul className="preview-page-list">
									{pages.map((page) => (
										<li key={page.id}>
											<button
												type="button"
												className={page.id === activePage.id ? 'is-active' : ''}
												onClick={() => handlePageSelect(page.id)}
											>
												<span className="preview-page-list__name">{page.name || 'Untitled page'}</span>
												<span className="preview-page-list__meta">{page.width} × {page.height}px</span>
											</button>
										</li>
									))}
								</ul>
							</div>
						)}

						<div className="preview-sidebar__section">
							<h3>Layer summary</h3>
							<ul className="preview-layer-stats">
								<li><span>Total layers</span><span>{layerStats.total}</span></li>
								<li><span>Text</span><span>{layerStats.text || 0}</span></li>
								<li><span>Images</span><span>{layerStats.image || 0}</span></li>
								<li><span>Shapes</span><span>{layerStats.shape || 0}</span></li>
							</ul>
						</div>

						{textContents.length > 0 && (
							<div className="preview-sidebar__section">
								<h3>Text content</h3>
								<ul className="preview-text-list">
									{textContents.map((text, index) => (
										<li key={index} title={text}>
											{text.length > 30 ? `${text.substring(0, 30)}...` : text}
										</li>
									))}
								</ul>
							</div>
						)}
					</aside>

					<section className={`preview-modal__canvas preview-modal__canvas--${backgroundTone}`} aria-label="Canvas preview">
						<div className="preview-canvas">
							<header className="preview-canvas__toolbar">
								<span>{activePage.name || 'Untitled page'}</span>
								<span>{scaledWidth} × {scaledHeight}px · {Math.round(previewScale * 100)}%</span>
							</header>
							<div className="preview-canvas__viewport">
								<div className="preview-canvas__stage" style={{ width: scaledWidth, height: scaledHeight }}>
									<div
										className="preview-page"
										style={{
											position: 'relative',
											width: pageWidth,
											height: pageHeight,
											background: activePage.background || '#ffffff',
											borderRadius: '12px',
											boxShadow: '0 18px 40px rgba(15, 23, 42, 0.25)',
											transform: `scale(${previewScale})`,
											transformOrigin: 'top left',
											...pageShapeStyles,
										}}
									>
										{bleedStyle && <div className="preview-bleed-guide" style={bleedStyle} />}
										{safeZoneStyle && <div className="preview-safezone" style={safeZoneStyle} />}

										{sortedLayers.map((layer) => {
											if (layer.visible === false) return null;

											const frame = layer.frame;
											if (!frame) return null;

											const isText = layer.type === 'text';
											const isImage = layer.type === 'image';
											const metadata = layer?.metadata ?? {};
											const objectFitMode = typeof metadata.objectFit === 'string' ? metadata.objectFit : 'cover';
											const rawScale = Number(metadata.imageScale);
											const imageScale = Number.isFinite(rawScale) ? Math.max(0.25, Math.min(4, rawScale)) : 1;
											const rawOffsetX = Number(metadata.imageOffsetX);
											const rawOffsetY = Number(metadata.imageOffsetY);
											const imageOffsetX = Number.isFinite(rawOffsetX) ? Math.max(-500, Math.min(500, rawOffsetX)) : 0;
											const imageOffsetY = Number.isFinite(rawOffsetY) ? Math.max(-500, Math.min(500, rawOffsetY)) : 0;
											const flipHorizontal = Boolean(metadata.flipHorizontal);
											const flipVertical = Boolean(metadata.flipVertical);
											const scaleX = flipHorizontal ? -imageScale : imageScale;
											const scaleY = flipVertical ? -imageScale : imageScale;

											const isShapeImageFrame = layer.type === 'shape' && Boolean(metadata.isImageFrame);
											const shapeMaskKey = metadata.maskVariant ?? layer.shape?.id ?? layer.variant;
											const rawImageContent = isImage
												? layer.content
												: isShapeImageFrame
													? (layer.content || metadata.backgroundImage || '')
													: '';
											const trimmedImageContent = typeof rawImageContent === 'string' ? rawImageContent.trim() : '';
											const hasImageSource = Boolean(
												trimmedImageContent &&
												(trimmedImageContent.startsWith('data:') ||
													trimmedImageContent.startsWith('blob:') ||
													/^https?:/i.test(trimmedImageContent)),
											);
											const imageSource = hasImageSource ? trimmedImageContent : null;
											const shapeDescriptor = layer.type === 'shape'
												? layer.shape ?? {
													id: shapeMaskKey,
													variant: layer.variant,
													borderRadius: layer.borderRadius,
												}
												: null;
											const shapeVisualStyles = layer.type === 'shape' ? getShapeVisualStyles(shapeDescriptor) : null;

											const layerStyle = {
												position: 'absolute',
												left: frame.x,
												top: frame.y,
												width: frame.width,
												height: frame.height,
												opacity: layer.opacity ?? 1,
												borderRadius: layer.borderRadius ?? 0,
												transform: frame.rotation ? `rotate(${frame.rotation}deg)` : undefined,
												transformOrigin: 'center',
												overflow: 'hidden',
											};

											return (
												<div key={layer.id} style={layerStyle}>
													{isText && (
														<div
															style={{
																width: '100%',
																height: '100%',
																display: 'flex',
																alignItems: 'center',
																justifyContent: layer.textAlign === 'left'
																	? 'flex-start'
																	: layer.textAlign === 'right'
																		? 'flex-end'
																		: 'center',
																color: layer.fill || '#0f172a',
																fontSize: layer.fontSize ? `${layer.fontSize}px` : '16px',
																fontFamily: layer.fontFamily || 'Arial, sans-serif',
																fontWeight: layer.fontWeight ?? 'normal',
																textAlign: layer.textAlign ?? 'center',
																padding: '8px',
																boxSizing: 'border-box',
																wordWrap: 'break-word',
															}}
														>
															{layer.content || 'Add your text'}
														</div>
													)}

													{(isImage || isShapeImageFrame) && hasImageSource && (
														<img
															src={imageSource}
															alt={layer.name || 'Template image'}
															style={{
																width: '100%',
																height: '100%',
																objectFit: objectFitMode,
																borderRadius: isShapeImageFrame ? 0 : layer.borderRadius ?? 0,
																display: 'block',
																transform: `translate(${imageOffsetX}px, ${imageOffsetY}px) scale(${scaleX}, ${scaleY})`,
																transformOrigin: 'center',
															}}
														/>
													)}

													{layer.type === 'shape' && (
														<div
															className="canvas-shape-frame"
															style={{
																width: '100%',
																height: '100%',
																overflow: 'hidden',
																backgroundColor: hasImageSource ? 'transparent' : layer.fill || 'rgba(37, 99, 235, 0.12)',
																...(shapeVisualStyles ?? {}),
															}}
														>
															{isShapeImageFrame && !hasImageSource && (
																<div className="canvas-shape-frame__placeholder">
																	<i className="fa-solid fa-image" aria-hidden="true"></i>
																	<span>Add image</span>
																</div>
															)}
														</div>
													)}
												</div>
											);
										})}
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>

				<footer className="preview-modal__footer">
					<div className="preview-modal__footer-tip">
						<strong>Tip:</strong> Toggle guides to validate bleed and safety before exporting previews.
					</div>
					<button type="button" className="builder-btn" onClick={onClose}>
						Close preview
					</button>
				</footer>
			</div>
		</div>
	);

	if (typeof document === 'undefined') {
		return modalContent;
	}

	return createPortal(modalContent, document.body);
}

PreviewModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
};
