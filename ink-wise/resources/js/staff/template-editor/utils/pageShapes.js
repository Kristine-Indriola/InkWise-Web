const POLYGON_CLIP_PATHS = {
	triangle: 'polygon(50% 0%, 0% 100%, 100% 100%)',
	'triangle-equilateral': 'polygon(50% 0%, 0% 100%, 100% 100%)',
	'triangle-right': 'polygon(0% 0%, 100% 100%, 0% 100%)',
	'triangle-isosceles': 'polygon(50% 0%, 10% 100%, 90% 100%)',
	'triangle-scalene': 'polygon(15% 10%, 90% 80%, 5% 100%)',
	diamond: 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
	hexagon: 'polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%)',
	octagon: 'polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%)',
	star: 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
	shield: 'polygon(50% 0%, 100% 20%, 100% 80%, 50% 100%, 0% 80%, 0% 20%)',
	pentagon: 'polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%)',
	parallelogram: 'polygon(20% 0%, 100% 0%, 80% 100%, 0% 100%)',
	trapezoid: 'polygon(20% 0%, 80% 0%, 100% 100%, 0% 100%)',
	rhombus: 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
	badge: 'path("M18 6 L46 6 L54 10 L60 20 L60 44 L54 54 L46 58 L18 58 L10 54 L4 44 L4 20 L10 10 Z")',
	heptagon: 'polygon(50% 0%, 90% 25%, 90% 75%, 50% 100%, 10% 75%, 10% 25%)',
	hexagram: 'polygon(50% 0%, 93.3% 25%, 93.3% 75%, 50% 100%, 6.7% 75%, 6.7% 25%, 50% 50%)',
	octagram: 'polygon(50% 0%, 85.4% 14.6%, 100% 50%, 85.4% 85.4%, 50% 100%, 14.6% 85.4%, 0% 50%, 14.6% 14.6%)',
	'arrow-right': 'polygon(0% 20%, 80% 20%, 80% 0%, 100% 50%, 80% 100%, 80% 80%, 0% 80%)',
	'arrow-left': 'polygon(100% 20%, 20% 20%, 20% 0%, 0% 50%, 20% 100%, 20% 80%, 100% 80%)',
	'arrow-up': 'polygon(20% 100%, 20% 20%, 0% 20%, 50% 0%, 100% 20%, 80% 20%, 80% 100%)',
	'arrow-down': 'polygon(20% 0%, 20% 80%, 0% 80%, 50% 100%, 100% 80%, 80% 80%, 80% 0%)',
	'cross-plus': 'polygon(40% 0%, 60% 0%, 60% 40%, 100% 40%, 100% 60%, 60% 60%, 60% 100%, 40% 100%, 40% 60%, 0% 60%, 0% 40%, 40% 40%)',
	'cross-x': 'polygon(30% 10%, 40% 20%, 10% 30%, 20% 40%, 30% 50%, 40% 40%, 50% 50%, 60% 40%, 70% 30%, 50% 20%, 60% 10%, 50% 20%)',
	chevron: 'polygon(0% 20%, 30% 0%, 70% 20%, 100% 0%, 100% 30%, 70% 50%, 30% 30%, 0% 50%, 0% 20%)',
	kite: 'polygon(50% 0%, 75% 50%, 50% 100%, 25% 50%)',
	lightning: 'polygon(20% 0%, 30% 0%, 25% 25%, 40% 25%, 20% 60%, 30% 45%, 15% 45%, 25% 25%, 10% 25%)',
	'polygon-custom': 'polygon(50% 0%, 93.3% 25%, 93.3% 75%, 50% 100%, 6.7% 75%, 6.7% 25%)',
};

const ORGANIC_CLIP_PATHS = {
	heart: 'path("M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z")',
	'cloud-shape': 'ellipse(60% 40% at 50% 50%)',
	'arch-shape': 'path("M0% 100% Q50% 0% 100% 100% Z")',
	flower: 'polygon(50% 0%, 65% 35%, 100% 50%, 65% 65%, 50% 100%, 35% 65%, 0% 50%, 35% 35%)',
	butterfly: 'polygon(50% 0%, 70% 20%, 90% 40%, 70% 60%, 50% 80%, 30% 60%, 10% 40%, 30% 20%)',
	leaf: 'polygon(50% 0%, 100% 30%, 90% 70%, 50% 100%, 10% 70%, 0% 30%)',
	balloon: 'ellipse(50% 60% at 50% 50%)',
	crown: 'polygon(50% 0%, 60% 25%, 85% 25%, 75% 50%, 95% 50%, 80% 75%, 100% 75%, 85% 100%, 15% 100%, 0% 75%, 20% 75%, 5% 50%, 25% 50%, 15% 25%, 40% 25%)',
	'puzzle-piece': 'polygon(0% 0%, 70% 0%, 70% 30%, 100% 30%, 100% 70%, 70% 70%, 70% 100%, 30% 100%, 30% 70%, 0% 70%)',
	'ribbon-banner': 'polygon(0% 0%, 10% 50%, 0% 100%, 100% 100%, 90% 50%, 100% 0%)',
	blob: 'path("M18 8 C30 0 54 6 58 26 C62 46 46 58 30 58 C14 58 6 40 8 26 C10 14 6 16 18 8 Z")',
	wave: 'path("M0 26 Q16 14 32 26 T64 26 L64 64 L0 64 Z")',
	sun: 'circle(50% at 50% 50%)',
	moon: 'path("M44 8 A24 24 0 1 1 20 56 A16 16 0 1 0 44 8 Z")',
	crescent: 'path("M44 8 A24 24 0 1 1 20 56 A16 16 0 1 0 44 8 Z")',
	'speech-bubble-rounded': 'path("M8 16 L8 40 Q8 48 16 48 L40 48 Q48 48 48 40 L48 16 Q48 8 40 8 L32 8 L28 0 L24 8 L16 8 Q8 8 8 16 Z")',
	'speech-bubble-oval': 'path("M16 8 Q8 8 8 16 L8 32 Q8 40 16 40 L32 40 Q40 40 40 32 L40 16 Q40 8 32 8 L24 8 L28 0 L20 0 L24 8 Z")',
	spiral: 'path("M32 32 Q40 32 40 24 Q40 16 48 16 Q56 16 56 24 Q56 32 48 32 Q40 32 40 40 Q40 48 32 48 Q24 48 24 40 Q24 32 32 32 Z")',
	teardrop: 'path("M32 8 Q48 8 48 24 Q48 40 32 56 Q16 40 16 24 Q16 8 32 8 Z")',
	gear: 'path("M32 4 L36 12 L44 8 L40 16 L48 20 L40 24 L44 32 L36 28 L32 36 L28 28 L20 32 L24 24 L16 20 L24 16 L20 8 L28 12 Z")',
};

const FRAME_CLIP_PATHS = {
	'polaroid-frame': 'path("M12 8 H52 V48 H40 V58 H24 V48 H12 Z")',
	'film-strip-frame': 'path("M10 16 L18 8 H46 L54 16 V48 L46 56 H18 L10 48 Z")',
	'torn-paper-frame': 'path("M8 16 L16 8 L26 18 L36 10 L46 20 L56 12 L58 18 V50 L46 46 L36 54 L26 46 L16 52 L8 46 Z")',
	'curved-corner-frame': 'path("M18 12 Q12 12 12 18 V52 Q12 58 18 58 H36 Q42 58 42 62 H52 Q58 62 58 56 V22 Q58 16 52 16 H42 Q36 16 36 12 Z")',
	'scalloped-frame': 'path("M16 12 C18 8 22 8 24 12 C26 16 30 16 32 12 C34 8 38 8 40 12 C42 16 46 16 48 12 C50 8 54 8 56 12 V52 C54 56 50 56 48 52 C46 48 42 48 40 52 C38 56 34 56 32 52 C30 48 26 48 24 52 C22 56 18 56 16 52 Z")',
	'collage-frame': 'path("M12 12 H52 V52 H12 Z")',
	'camera-frame': 'path("M12 24 H20 L24 16 H40 L44 24 H52 V52 H12 Z")',
	'ribbon-frame': 'path("M10 22 L22 12 H42 L54 22 V48 H42 V56 H22 V48 H10 Z")',
};

const TAG_CLIP_PATHS = {
	'tag-shape': 'polygon(0% 0%, 85% 0%, 100% 50%, 85% 100%, 0% 100%)',
	'ticket-shape': 'polygon(0% 0%, 85% 0%, 100% 50%, 85% 100%, 0% 100%)',
};

function normalizeBorderRadius(borderRadius) {
	if (typeof borderRadius === 'number' && Number.isFinite(borderRadius)) {
		return `${borderRadius}px`;
	}

	if (typeof borderRadius === 'string') {
		return borderRadius;
	}

	return 0;
}

function applyClipPath(clipPath) {
	const value = clipPath || 'none';

	return {
		clipPath: value,
		WebkitClipPath: value,
	};
}

export function getShapeVisualStyles(shape) {
	if (!shape || typeof shape !== 'object') {
		return {};
	}

	const { variant, id, borderRadius } = shape;

	switch (variant) {
		case 'circle':
			return {
				borderRadius: '50%',
				overflow: 'hidden',
			};

		case 'polygon':
			return {
				overflow: 'hidden',
				...applyClipPath(POLYGON_CLIP_PATHS[id]),
			};

		case 'organic':
			return {
				overflow: 'hidden',
				...applyClipPath(ORGANIC_CLIP_PATHS[id]),
			};

		case 'tag':
			return {
				overflow: 'hidden',
				...applyClipPath(TAG_CLIP_PATHS[id]),
			};

		case 'frame':
		case 'layout':
		case 'rectangle':
		default: {
			const frameClipPath = FRAME_CLIP_PATHS[id];
			if (frameClipPath) {
				return {
					overflow: 'hidden',
					...applyClipPath(frameClipPath),
				};
			}
			return {
				borderRadius: normalizeBorderRadius(borderRadius),
				overflow: 'hidden',
			};
		}
	}
}

export function getShapeMaskStyles(shape) {
	const visualStyles = getShapeVisualStyles(shape);
	const maskStyles = {};

	if (visualStyles.borderRadius !== undefined) {
		maskStyles.borderRadius = visualStyles.borderRadius;
	}

	if (visualStyles.clipPath) {
		maskStyles.clipPath = visualStyles.clipPath;
	}

	if (visualStyles.WebkitClipPath) {
		maskStyles.WebkitClipPath = visualStyles.WebkitClipPath;
	}

	return maskStyles;
}
