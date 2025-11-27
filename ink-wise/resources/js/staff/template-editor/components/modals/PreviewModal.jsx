import React, { useEffect, useMemo, useState } from 'react';
import PropTypes from 'prop-types';
import { createPortal } from 'react-dom';

import { useBuilderStore } from '../../state/BuilderStore';

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

	const activePage = pages.find((page) => page.id === state.activePageId) ?? pages[0];
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

	const safeZoneStyle = showSafeZone && hasSafeZone ? {
		position: 'absolute',
		top: safeZone.top,
		right: safeZone.right,
		bottom: safeZone.bottom,
		left: safeZone.left,
		border: '1px dashed rgba(37, 99, 235, 0.55)',
		borderRadius: 'inherit',
		pointerEvents: 'none',
	} : null;

	const bleedStyle = showBleed && hasBleed ? {
		position: 'absolute',
		top: -bleed.top,
		right: -bleed.right,
		bottom: -bleed.bottom,
		left: -bleed.left,
		border: '1px solid rgba(220, 38, 38, 0.4)',
		borderRadius: 'inherit',
		pointerEvents: 'none',
	} : null;

	const handlePageSelect = (pageId) => {
		if (!pageId || pageId === activePage.id) {
			return;
		}
		dispatch({ type: 'SELECT_PAGE', pageId });
	};

	const getShapeStyling = (shape) => {
		if (!shape) return {};

		const { variant, id, borderRadius } = shape;

		switch (variant) {
			case 'circle':
				return { borderRadius: '50%', overflow: 'hidden' };
			case 'polygon': {
				const polygonClips = {
					triangle: 'polygon(50% 0%, 0% 100%, 100% 100%)',
					diamond: 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
					hexagon: 'polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%)',
					octagon: 'polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%)',
					star: 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
					shield: 'polygon(50% 0%, 100% 20%, 100% 80%, 50% 100%, 0% 80%, 0% 20%)',
				};
				return { clipPath: polygonClips[id] || 'none', overflow: 'hidden' };
			}
			case 'organic': {
				const organicClips = {
					heart: 'path("M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z")',
					'cloud-shape': 'ellipse(60% 40% at 50% 50%)',
					flower: 'polygon(50% 0%, 65% 35%, 100% 50%, 65% 65%, 50% 100%, 35% 65%, 0% 50%, 35% 35%)',
					butterfly: 'polygon(50% 0%, 70% 20%, 90% 40%, 70% 60%, 50% 80%, 30% 60%, 10% 40%, 30% 20%)',
					leaf: 'polygon(50% 0%, 100% 30%, 90% 70%, 50% 100%, 10% 70%, 0% 30%)',
					balloon: 'ellipse(50% 60% at 50% 50%)',
					crown: 'polygon(50% 0%, 60% 25%, 85% 25%, 75% 50%, 95% 50%, 80% 75%, 100% 75%, 85% 100%, 15% 100%, 0% 75%, 20% 75%, 5% 50%, 25% 50%, 15% 25%, 40% 25%)',
					'puzzle-piece': 'polygon(0% 0%, 70% 0%, 70% 30%, 100% 30%, 100% 70%, 70% 70%, 70% 100%, 30% 100%, 30% 70%, 0% 70%)',
					'ribbon-banner': 'polygon(0% 0%, 10% 50%, 0% 100%, 100% 100%, 90% 50%, 100% 0%)',
				};
				return { clipPath: organicClips[id] || 'none', overflow: 'hidden' };
			}
			case 'frame':
			case 'layout':
			case 'rectangle':
				return {
					borderRadius: typeof borderRadius === 'number' ? `${borderRadius}px` : borderRadius || 0,
					overflow: 'hidden',
				};
			case 'tag': {
				const tagClips = {
					'tag-shape': 'polygon(0% 0%, 85% 0%, 100% 50%, 85% 100%, 0% 100%)',
					'ticket-shape': 'polygon(0% 0%, 85% 0%, 100% 50%, 85% 100%, 0% 100%)',
				};
				return { clipPath: tagClips[id] || 'none', overflow: 'hidden' };
			}
			default:
				return {};
		}
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
											...getShapeStyling(pageShape),
											borderRadius: '12px',
											boxShadow: '0 18px 40px rgba(15, 23, 42, 0.25)',
											transform: `scale(${previewScale})`,
											transformOrigin: 'top left',
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

													{isImage && layer.content && typeof layer.content === 'string' && (layer.content.startsWith('data:') || layer.content.startsWith('blob:')) && (
														<img
															src={layer.content}
															alt={layer.name || 'Template image'}
															style={{
																width: '100%',
																height: '100%',
																objectFit: objectFitMode,
																borderRadius: layer.borderRadius ?? 0,
																display: 'block',
																transform: `translate(${imageOffsetX}px, ${imageOffsetY}px) scale(${scaleX}, ${scaleY})`,
																transformOrigin: 'center',
															}}
														/>
													)}

													{layer.type === 'shape' && !isText && !isImage && (
														<div
															style={{
																width: '100%',
																height: '100%',
																backgroundColor: layer.fill || 'rgba(37, 99, 235, 0.12)',
																borderRadius: layer.borderRadius ?? 0,
																...getShapeStyling(layer.shape),
															}}
														/>
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
