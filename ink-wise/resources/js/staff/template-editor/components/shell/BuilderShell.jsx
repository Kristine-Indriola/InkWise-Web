import React, { useCallback, useEffect, useRef, useState } from 'react';
import html2canvas from 'html2canvas';
import { toSvg } from 'html-to-image';
import { deflate, gzip } from 'pako';

import { useBuilderStore } from '../../state/BuilderStore';
import { ToolSidebar } from '../panels/ToolSidebar';
import { InspectorPanel } from '../panels/InspectorPanel';
import { CanvasToolbar } from '../canvas/CanvasToolbar';
import { CanvasViewport } from '../canvas/CanvasViewport';
import { BuilderTopBar } from './BuilderTopBar';
import { BuilderStatusBar } from './BuilderStatusBar';
import { BuilderHotkeys } from './BuilderHotkeys';
import { PreviewModal } from '../modals/PreviewModal';
import { serializeDesign } from '../../utils/serializeDesign';
import { derivePageLabel, normalizePageTypeValue } from '../../utils/pageFactory';
import { BuilderErrorBoundary } from './BuilderErrorBoundary';

const MAX_DEVICE_PIXEL_RATIO = 2.5; // allow higher density captures for crisper exports
const PREVIEW_MAX_EDGE = 1800; // slightly smaller edge to keep payloads leaner
const PREVIEW_JPEG_QUALITY = 0.9; // balance fidelity with payload size
const PREVIEW_MIN_JPEG_QUALITY = 0.82;
const PREVIEW_MAX_BYTES = 3_200_000; // individual preview budget
const PREVIEW_TOTAL_BUDGET = 5_000_000; // combined preview payload budget
const MANUAL_SAVE_PAYLOAD_BUDGET = 7_500_000; // safety budget for full save payloads
const AUTOSAVE_PAYLOAD_BUDGET = 5_000_000;
const PAYLOAD_COMPRESSION_ENCODING = 'gzip-base64';
const PAYLOAD_VERSION = 1;
const POST_FAILSAFE_LIMIT = 1_600_000; // conservative ceiling to dodge strict post_max_size limits

const waitForNextFrame = () => new Promise((resolve) => setTimeout(() => requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)))), 100));

async function captureCanvasRaster(canvas, pixelRatio, backgroundColor = '#ffffff') {
  const diagnostics = {
    stageFound: false,
    targetChildren: 0,
    captureWidth: 0,
    captureHeight: 0,
    attempts: [],
  };

  console.error('[InkWise Builder] CAPTURE FUNCTION CALLED - Starting canvas capture for template save');
  alert('Canvas capture starting... check console for details');
  console.log('[InkWise Builder] Starting canvas capture for template save');
  console.log('[InkWise Builder] Canvas element:', canvas);
  console.log('[InkWise Builder] Canvas exists:', !!canvas);

  // Add a timeout to prevent hanging
  const timeoutPromise = new Promise((_, reject) => {
    setTimeout(() => reject(new Error('Canvas capture timeout after 30 seconds')), 30000);
  });

  async function performCapture() {
    if (!canvas) {
      console.error('[InkWise Builder] Canvas element is null - cannot capture preview');
      diagnostics.error = 'canvas-null';
      return { dataUrl: null, diagnostics };
    }

    const stage = canvas.closest('.canvas-viewport__stage');
  const target = stage ? stage : canvas; // Prefer stage element over fold-container
  diagnostics.stageFound = !!stage;
  const surface = target.parentElement;

  const rect = target.getBoundingClientRect();
  const captureWidth = Math.max(1, Math.round(target.scrollWidth || rect.width));
  const captureHeight = Math.max(1, Math.round(target.scrollHeight || rect.height));
  diagnostics.captureWidth = captureWidth;
  diagnostics.captureHeight = captureHeight;

  console.log('[InkWise Builder] Capture target:', target.className, 'size', captureWidth, 'x', captureHeight, 'children:', target.children.length);

  // Check for transforms that might cause issues
  const computedStyle = window.getComputedStyle(target);
  console.log('[InkWise Builder] Target transform:', computedStyle.transform);
  console.log('[InkWise Builder] Target transform-origin:', computedStyle.transformOrigin);
  console.log('[InkWise Builder] Target position:', computedStyle.position);
  console.log('[InkWise Builder] Target display:', computedStyle.display);
  console.log('[InkWise Builder] Target visibility:', computedStyle.visibility);

  if (target.children.length === 0) {
    console.warn('[InkWise Builder] WARNING: Canvas has no child elements - preview will be blank!');
  } else {
    const elementTypes = Array.from(target.querySelectorAll('*'))
      .map((el) => el.className + ' (' + el.tagName + ')')
      .filter(Boolean)
      .slice(0, 10);
    console.log('[InkWise Builder] Canvas has', target.children.length, 'child elements');
    console.log('[InkWise Builder] Element classes found:', elementTypes);

    // Check for actual content
    const textElements = target.querySelectorAll('[data-preview-node]');
    console.log('[InkWise Builder] Found', textElements.length, 'elements with data-preview-node');

    const images = target.querySelectorAll('img');
    console.log('[InkWise Builder] Found', images.length, 'img elements');

    const textContent = Array.from(textElements).map(el => el.textContent?.trim()).filter(Boolean);
    console.log('[InkWise Builder] Text content found:', textContent);

    // Check visibility of elements
    const allElements = target.querySelectorAll('*');
    const visibleElements = Array.from(allElements).filter(el => {
      const style = window.getComputedStyle(el);
      return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
    });
    console.log('[InkWise Builder] Visible elements:', visibleElements.length, 'out of', allElements.length);

    // Check for fonts that need to load
    const fontFamilies = new Set();
    allElements.forEach(el => {
      const style = window.getComputedStyle(el);
      const fontFamily = style.fontFamily;
      if (fontFamily && fontFamily !== 'inherit') {
        // Extract the first font family name
        const primaryFont = fontFamily.split(',')[0].replace(/['"]/g, '').trim();
        if (primaryFont && !primaryFont.includes('system-ui') && !primaryFont.includes('sans-serif') && !primaryFont.includes('serif')) {
          fontFamilies.add(primaryFont);
        }
      }
    });
    console.log('[InkWise Builder] Fonts to check:', Array.from(fontFamilies));

    // For canvas capture, skip font loading and rely on CSS fallbacks
    // This ensures text renders even when Google Fonts fail
    console.log('[InkWise Builder] Skipping font loading for canvas capture - using CSS fallbacks');

    // Small delay to ensure CSS fallbacks are applied
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  diagnostics.targetChildren = target.children.length;

  await new Promise((resolve) => setTimeout(resolve, 80));

  const originalStageTransform = stage?.style.transform ?? null;
  const originalStageOrigin = stage?.style.transformOrigin ?? null;
  const originalStageBackground = stage?.style.background ?? null;
  const originalStageWillChange = stage?.style.willChange ?? null;
  const originalCanvasTransform = canvas.style.transform;
  const originalCanvasOrigin = canvas.style.transformOrigin;
  const originalCanvasBackground = canvas.style.background;

  const originalSurfaceOverflow = surface?.style?.overflow;

  try {
    if (stage) {
      stage.style.transform = 'none';
      stage.style.transformOrigin = 'top left';
      stage.style.willChange = 'auto';
      stage.style.background = backgroundColor;
      stage.style.width = `${captureWidth}px`;
      stage.style.height = `${captureHeight}px`;
    }
    canvas.style.transform = 'none';
    canvas.style.transformOrigin = 'top left';
    canvas.style.background = backgroundColor;
    canvas.style.width = `${captureWidth}px`;
    canvas.style.height = `${captureHeight}px`;

    if (surface) {
      surface.style.overflow = 'visible';
    }

    // Force reflow
    target.offsetHeight;

    console.log('[InkWise Builder] Target dimensions after transform removal:', target.offsetWidth, 'x', target.offsetHeight);
    console.log('[InkWise Builder] Target computed style:', window.getComputedStyle(target));

    // Wait for all images to load
    const images = target.querySelectorAll('img');
    if (images.length > 0) {
      console.log('[InkWise Builder] Waiting for', images.length, 'images to load');
      await Promise.all(Array.from(images).map(img => {
        if (img.complete) return Promise.resolve();
        return new Promise((resolve, reject) => {
          img.onload = resolve;
          img.onerror = reject;
          setTimeout(() => resolve(), 5000); // Timeout after 5 seconds
        });
      }));
      console.log('[InkWise Builder] All images loaded or timed out');
    }

    try {
      console.log('[InkWise Builder] Attempting html2canvas capture on stage (only method - html-to-image disabled due to CORS)');

      const html2canvasPromise = html2canvas(target, {
        scale: pixelRatio,
        useCORS: true,
        allowTaint: true,
        backgroundColor,
        width: captureWidth,
        height: captureHeight,
        scrollX: 0,
        scrollY: 0,
        logging: false, // Reduce noise
        removeContainer: true,
        foreignObjectRendering: false, // Use canvas rendering to avoid CORS issues
        imageTimeout: 10000,
        ignoreElements: (element) => {
          // Skip elements that might cause CORS issues
          return element.tagName === 'LINK' && element.rel === 'stylesheet' && (
            element.href && (
              element.href.includes('fonts.googleapis.com') ||
              element.href.includes('fonts.gstatic.com') ||
              element.href.includes('cdn.jsdelivr.net') ||
              element.href.includes('stackpath.bootstrapcdn.com')
            )
          );
        },
      });

      const html2canvasTimeout = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('html2canvas timeout')), 15000)
      );

      const canvasResult = await Promise.race([html2canvasPromise, html2canvasTimeout]);

      const dataUrl = canvasResult.toDataURL('image/png');
      if (dataUrl && dataUrl.length >= 100) {
        console.log('[InkWise Builder] html2canvas stage capture succeeded, PNG length:', dataUrl.length);
        diagnostics.attempts.push({ method: 'html2canvas-stage', success: true, length: dataUrl.length });
        return { dataUrl, diagnostics };
      }
      console.warn('[InkWise Builder] html2canvas stage capture produced empty output');
      diagnostics.attempts.push({ method: 'html2canvas-stage', success: false, reason: 'empty-output', length: dataUrl?.length || 0 });
    } catch (stageCaptureError) {
      console.error('[InkWise Builder] Stage capture failed:', stageCaptureError.message);
      diagnostics.attempts.push({ method: 'stage-capture', success: false, error: stageCaptureError.message });
    }
  } finally {
    if (stage) {
      stage.style.transform = originalStageTransform ?? '';
      stage.style.transformOrigin = originalStageOrigin ?? '';
      stage.style.background = originalStageBackground ?? '';
      stage.style.willChange = originalStageWillChange ?? '';
      stage.style.width = '';
      stage.style.height = '';
    }
    canvas.style.transform = originalCanvasTransform;
    canvas.style.transformOrigin = originalCanvasOrigin;
    canvas.style.background = originalCanvasBackground;
    canvas.style.width = '';
    canvas.style.height = '';

    if (surface) {
      surface.style.overflow = originalSurfaceOverflow ?? '';
    }
  }

  const clone = target.cloneNode(true);
  clone.style.position = 'absolute';
  clone.style.top = '-100000px';
  clone.style.left = '-100000px';
  clone.style.transform = 'none';
  clone.style.transformOrigin = 'top left';
  clone.style.width = `${captureWidth}px`;
  clone.style.height = `${captureHeight}px`;
  clone.style.background = backgroundColor;
  clone.style.willChange = 'auto';
  clone.style.display = 'block';
  clone.style.visibility = 'hidden';
  clone.style.opacity = '0';

  document.body.appendChild(clone);

  const originalSurfaceBackground = surface?.style?.background;
  const originalTargetBackground = target.style.background;
  const originalTargetBgColor = target.backgroundColor;

  if (surface) {
    surface.style.background = backgroundColor;
  }
  target.style.background = backgroundColor;

  if (target.backgroundColor !== undefined) {
    target.backgroundColor = backgroundColor;
    target.renderAll && target.renderAll();
  }

  try {
    console.log('[InkWise Builder] Attempting html2canvas capture (cloned node) at', captureWidth, 'x', captureHeight);

    const captureWithHtml2Canvas = async (sourceNode) => {
      const promise = html2canvas(sourceNode, {
        scale: pixelRatio,
        useCORS: true,
        allowTaint: true,
        backgroundColor,
        width: captureWidth,
        height: captureHeight,
        scrollX: 0,
        scrollY: 0,
        logging: false,
        removeContainer: true,
        foreignObjectRendering: false,
        imageTimeout: 8000,
        ignoreElements: (element) => {
          return element.tagName === 'LINK' && element.rel === 'stylesheet' && (
            element.href && (
              element.href.includes('fonts.googleapis.com') ||
              element.href.includes('fonts.gstatic.com') ||
              element.href.includes('cdn.jsdelivr.net') ||
              element.href.includes('stackpath.bootstrapcdn.com')
            )
          );
        },
      });
      const timeout = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('html2canvas clone timeout')), 15000)
      );
      return Promise.race([promise, timeout]);
    };

    const capturedCanvas = await captureWithHtml2Canvas(clone);
    diagnostics.attempts.push({ method: 'html2canvas-clone', success: !!capturedCanvas, width: capturedCanvas?.width ?? 0, height: capturedCanvas?.height ?? 0 });

    const normalizeCanvasResult = (canvasElement) => {
      if (!canvasElement) {
        return null;
      }
      if (canvasElement.width < 2 || canvasElement.height < 2) {
        console.error('[InkWise Builder] html2canvas produced empty canvas:', canvasElement.width, 'x', canvasElement.height);
        diagnostics.attempts.push({ method: 'html2canvas-normalize', success: false, reason: 'tiny-canvas', width: canvasElement.width, height: canvasElement.height });
        return null;
      }

      const dataUrl = canvasElement.toDataURL('image/png');
      console.log('[InkWise Builder] PNG data URL length:', dataUrl?.length, 'bytes');
      if (!dataUrl || dataUrl.length < 100) {
        console.error('[InkWise Builder] Generated PNG is too small (likely empty):', dataUrl?.length, 'bytes');
        diagnostics.attempts.push({ method: 'html2canvas-normalize', success: false, reason: 'tiny-data-url', length: dataUrl?.length || 0 });
        return null;
      }
      diagnostics.attempts.push({ method: 'html2canvas-normalize', success: true, length: dataUrl.length });
      return dataUrl;
    };

    let result = normalizeCanvasResult(capturedCanvas);

    if (!result) {
      console.warn('[InkWise Builder] html2canvas clone capture failed or produced empty output, retrying with original target');
      const fallbackCanvas = await captureWithHtml2Canvas(target);
      diagnostics.attempts.push({ method: 'html2canvas-target', success: !!fallbackCanvas, width: fallbackCanvas?.width ?? 0, height: fallbackCanvas?.height ?? 0 });
      result = normalizeCanvasResult(fallbackCanvas);
    }

    if (!result) {
      console.warn('[InkWise Builder] All html2canvas attempts failed - canvas capture will be empty');
      diagnostics.attempts.push({ method: 'final-failure', success: false, reason: 'all-methods-failed' });
    }

    if (result) {
      console.error('[InkWise Builder] CAPTURE SUCCESSFUL - Returning data URL, length:', result.length);
      alert('Canvas capture successful! Length: ' + result.length);
      return { dataUrl: result, diagnostics };
    }

    console.error('[InkWise Builder] All capture attempts failed');
    diagnostics.error = 'all-attempts-failed';
    return { dataUrl: null, diagnostics };
  } catch (error) {
    console.error('[InkWise Builder] html2canvas capture exception:', error.message, error.stack);
    diagnostics.error = `exception:${error.message}`;
    try {
      console.log('[InkWise Builder] Attempting final fallback capture with minimal settings');
      const basicCanvas = await html2canvas(target, {
        scale: 1,
        backgroundColor,
        logging: true,
      });
      if (basicCanvas && basicCanvas.width >= 2 && basicCanvas.height >= 2) {
        const result = basicCanvas.toDataURL('image/png');
        console.log('[InkWise Builder] Basic fallback succeeded, PNG length:', result?.length);
        diagnostics.attempts.push({ method: 'html2canvas-basic', success: true, length: result?.length || 0 });
        return { dataUrl: result, diagnostics };
      }
    } catch (fallbackError) {
      console.error('[InkWise Builder] Final html2canvas fallback also failed:', fallbackError);
      diagnostics.attempts.push({ method: 'html2canvas-basic', success: false, error: fallbackError.message });
    }

    diagnostics.error = diagnostics.error || 'all-html2canvas-failed';
    console.error('[InkWise Builder] CAPTURE COMPLETED - All html2canvas methods failed, returning null');
    alert('Canvas capture completed - all methods failed due to CORS issues');
    return { dataUrl: null, diagnostics };
  } finally {
    if (surface) {
      surface.style.background = originalSurfaceBackground;
    }
    target.style.background = originalTargetBackground;
    if (target.backgroundColor !== undefined) {
      target.backgroundColor = originalTargetBgColor;
      target.renderAll && target.renderAll();
    }
    if (clone && clone.parentNode) {
      clone.parentNode.removeChild(clone);
    }
  }
}

try {
  const capturePromise = performCapture();
  return await Promise.race([capturePromise, timeoutPromise]);
} catch (error) {
  console.error('[InkWise Builder] Capture failed with timeout or error:', error);
  alert('Canvas capture failed: ' + error.message);
  return { dataUrl: null, diagnostics: { ...diagnostics, error: error.message } };
}
}

function derivePreviewKey(page, index) {
  const candidates = [
    page?.pageType,
    page?.metadata?.pageType,
    page?.metadata?.side,
    page?.metadata?.sideLabel,
    page?.name,
  ];

  for (const candidate of candidates) {
    const normalized = normalizePageTypeValue(candidate);
    if (normalized) {
      return normalized;
    }
  }

  if (index === 0) {
    return 'front';
  }
  if (index === 1) {
    return 'back';
  }
  return `page-${index + 1}`;
}

function derivePreviewLabel(page, index, totalPages) {
  if (typeof page?.metadata?.sideLabel === 'string' && page.metadata.sideLabel.trim() !== '') {
    return page.metadata.sideLabel.trim();
  }

  const normalized = normalizePageTypeValue(
    page?.pageType ?? page?.metadata?.pageType ?? page?.metadata?.side ?? page?.name ?? null,
  );

  if (normalized) {
    return derivePageLabel(normalized, index, totalPages);
  }

  if (typeof page?.name === 'string' && page.name.trim() !== '') {
    return page.name.trim();
  }

  return `Page ${index + 1}`;
}

function estimateBase64Bytes(dataUrl) {
  if (typeof dataUrl !== 'string') {
    return 0;
  }
  const commaIndex = dataUrl.indexOf(',');
  const base64 = commaIndex !== -1 ? dataUrl.slice(commaIndex + 1) : dataUrl;
  return Math.ceil((base64.length * 3) / 4);
}

function estimateJsonBytes(value) {
  try {
    const encoder = new TextEncoder();
    return encoder.encode(JSON.stringify(value)).length;
  } catch (err) {
    console.warn('[InkWise Builder] Failed to estimate JSON payload size.', err);
    try {
      return JSON.stringify(value).length;
    } catch (stringifyError) {
      console.warn('[InkWise Builder] Stringify fallback also failed.', stringifyError);
      return 0;
    }
  }
}

function measureStringBytes(value) {
  if (typeof value !== 'string') {
    return 0;
  }

  try {
    return new TextEncoder().encode(value).length;
  } catch (err) {
    console.warn('[InkWise Builder] Failed to measure string length with TextEncoder.', err);
  }

  return value.length;
}

function base64EncodeBinaryString(binaryString) {
  if (typeof window !== 'undefined' && typeof window.btoa === 'function') {
    return window.btoa(binaryString);
  }

  if (typeof Buffer !== 'undefined') {
    return Buffer.from(binaryString, 'binary').toString('base64');
  }

  throw new Error('No base64 encoder available for payload compression.');
}

function prepareCompressedJsonPayload(payload, thresholdBytes = MANUAL_SAVE_PAYLOAD_BUDGET, forceCompression = false) {
  const json = JSON.stringify(payload);

  let byteLength = json.length;
  try {
    byteLength = new TextEncoder().encode(json).length;
  } catch (err) {
    console.warn('[InkWise Builder] Failed to measure JSON payload length with TextEncoder.', err);
  }

  if (!forceCompression && (!Number.isFinite(thresholdBytes) || byteLength <= thresholdBytes)) {
    return {
      body: json,
      headers: {},
      compressed: false,
      originalBytes: byteLength,
      bodyBytes: byteLength,
    };
  }

  try {
    const compressed = compressor(json, { level: 9 });
    const compressedBinary = String.fromCharCode(...compressed);
    const compressedBytes = compressedBinary.length;
    const base64Payload = base64EncodeBinaryString(compressedBinary);
    const requestPayload = {
      payload_version: PAYLOAD_VERSION,
      payload_encoding: PAYLOAD_COMPRESSION_ENCODING,
      compressed_payload: base64Payload,
    };

    const body = JSON.stringify(requestPayload);
    const bodyBytes = measureStringBytes(body);

    return {
      body,
      headers: {
        'X-Payload-Compressed': PAYLOAD_COMPRESSION_ENCODING,
        'X-Original-Payload-Bytes': String(byteLength),
        'X-Compressed-Payload-Bytes': String(compressedBytes),
      },
      compressed: true,
      originalBytes: byteLength,
      compressedBytes,
      bodyBytes,
    };
  } catch (err) {
    console.warn('[InkWise Builder] Payload compression failed, sending raw JSON.', err);
    return {
      body: json,
      headers: {},
      compressed: false,
      originalBytes: byteLength,
      bodyBytes: byteLength,
    };
  }
}

function buildManualSaveVariant(basePayload, { stripPreviewImages = false, stripPrimaryPreview = false, stripSvgMarkup = false } = {}) {
  const next = { ...basePayload };

  if (stripPreviewImages) {
    delete next.preview_images;
    delete next.preview_images_meta;
    next.preview_images_truncated = true;
  }

  if (stripPrimaryPreview) {
    delete next.preview_image;
  }

  if (stripSvgMarkup) {
    delete next.svg_markup;
  }

  return next;
}

function prepareManualSaveRequest(basePayload) {
  const attempts = [
    { id: 'full', options: {} },
    { id: 'no-preview-images', options: { stripPreviewImages: true } },
    { id: 'no-previews', options: { stripPreviewImages: true, stripPrimaryPreview: true } },
    { id: 'minimal', options: { stripPreviewImages: true, stripPrimaryPreview: true, stripSvgMarkup: true } },
  ];

  let bestAttempt = null;

  for (const attempt of attempts) {
    const payloadVariant = buildManualSaveVariant(basePayload, attempt.options);
    const prepared = prepareCompressedJsonPayload(payloadVariant, MANUAL_SAVE_PAYLOAD_BUDGET, true);

    if (!bestAttempt || prepared.bodyBytes < bestAttempt.prepared.bodyBytes) {
      bestAttempt = { prepared, payload: payloadVariant, id: attempt.id, trimmed: attempt.id !== 'full' };
    }

    if (prepared.bodyBytes <= POST_FAILSAFE_LIMIT) {
      if (attempt.id !== 'full') {
        console.warn('[InkWise Builder] Trimmed manual save payload to stay within POST budget.', {
          variant: attempt.id,
          bodyBytes: prepared.bodyBytes,
          limit: POST_FAILSAFE_LIMIT,
        });
      }
      return { prepared, payload: payloadVariant, id: attempt.id, trimmed: attempt.id !== 'full' };
    }
  }

  if (bestAttempt) {
    console.error('[InkWise Builder] Manual save payload exceeds failsafe limit even after trimming.', {
      smallestBytes: bestAttempt.prepared.bodyBytes,
      limit: POST_FAILSAFE_LIMIT,
    });
    return bestAttempt;
  }

  throw new Error('Failed to prepare manual save payload.');
}

function getPreviewPriority(entry) {
  if (!entry) {
    return 0;
  }

  const key = entry.key ?? '';
  if (key === 'front') {
    return 400;
  }
  if (key === 'back') {
    return 360;
  }

  const order = typeof entry.meta?.order === 'number' ? entry.meta.order : 0;
  // Prefer earlier pages and smaller assets to keep payload compact.
  return 300 - order * 2 - Math.round((entry.bytes || 0) / 200_000);
}

function loadImageElement(dataUrl) {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = (err) => reject(err);
    image.src = dataUrl;
  });
}

function renderCompressedImage(image, scale, quality) {
  const targetWidth = Math.max(1, Math.round(image.width * scale));
  const targetHeight = Math.max(1, Math.round(image.height * scale));

  const canvas = document.createElement('canvas');
  canvas.width = targetWidth;
  canvas.height = targetHeight;

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return null;
  }

  ctx.drawImage(image, 0, 0, targetWidth, targetHeight);
  return canvas.toDataURL('image/jpeg', quality);
}

async function compressPreviewImage(dataUrl, options = {}) {
  if (!dataUrl || typeof document === 'undefined') {
    return dataUrl;
  }

  const {
    maxEdge = PREVIEW_MAX_EDGE,
    quality = PREVIEW_JPEG_QUALITY,
    maxBytes = PREVIEW_MAX_BYTES,
  } = options;

  try {
    const image = await loadImageElement(dataUrl);
    const longestEdge = Math.max(image.width, image.height);
    let scale = longestEdge > maxEdge && maxEdge > 0 ? maxEdge / longestEdge : 1;
    let currentQuality = quality;
    const minScale = 0.4;
    const isPng = dataUrl.startsWith('data:image/png');

    let output;

    if (isPng) {
      const pngCanvas = document.createElement('canvas');
      pngCanvas.width = Math.max(1, Math.round(image.width * scale));
      pngCanvas.height = Math.max(1, Math.round(image.height * scale));
      const ctx = pngCanvas.getContext('2d');
      if (ctx) {
        ctx.drawImage(image, 0, 0, pngCanvas.width, pngCanvas.height);
        output = pngCanvas.toDataURL('image/png');
      } else {
        output = dataUrl;
      }

      if (estimateBase64Bytes(output) <= maxBytes) {
        return output;
      }

      output = renderCompressedImage(image, scale, currentQuality) || output;
    } else {
      output = renderCompressedImage(image, scale, currentQuality) || dataUrl;
    }

    while (estimateBase64Bytes(output) > maxBytes && (scale > minScale || currentQuality > PREVIEW_MIN_JPEG_QUALITY)) {
      if (scale > minScale) {
        scale = Math.max(minScale, scale * 0.9);
      }
      if (currentQuality > PREVIEW_MIN_JPEG_QUALITY) {
        currentQuality = Math.max(PREVIEW_MIN_JPEG_QUALITY, currentQuality - 0.04);
      }

      const attempt = renderCompressedImage(image, scale, currentQuality);
      if (!attempt) {
        break;
      }
      output = attempt;
    }

    return output;
  } catch (err) {
    console.warn('[InkWise Builder] Failed to compress preview image.', err);
    return dataUrl;
  }
}

function extractSvgMarkup(dataUrl) {
  if (typeof dataUrl !== 'string') {
    return null;
  }

  if (!dataUrl.startsWith('data:image/svg+xml')) {
    return dataUrl;
  }

  const commaIndex = dataUrl.indexOf(',');
  if (commaIndex === -1) {
    return dataUrl;
  }

  const encodedPayload = dataUrl.slice(commaIndex + 1);

  try {
    return decodeURIComponent(encodedPayload);
  } catch (err) {
    console.warn('[InkWise Builder] Failed to decode SVG markup payload.', err);
    return encodedPayload;
  }
}

function sanitizeSvgMarkup(markup) {
  if (typeof markup !== 'string') {
    return markup;
  }

  return markup
    .replace(/<!--.*?-->/gs, '')
    .replace(/\s*\n+\s*/g, ' ')
    .replace(/\s{2,}/g, ' ')
    .trim();
}

function encodeSvgMarkup(markup) {
  if (!markup || typeof markup !== 'string') {
    return markup;
  }

  const sanitized = sanitizeSvgMarkup(markup);

  try {
    if (typeof window !== 'undefined' && typeof window.btoa === 'function') {
      const encoded = window.btoa(unescape(encodeURIComponent(sanitized)));
      return `data:image/svg+xml;base64,${encoded}`;
    }
  } catch (err) {
    console.warn('[InkWise Builder] Failed to encode SVG markup payload.', err);
  }

  return sanitized;
}

export function BuilderShell() {
  const { state, routes, csrfToken, dispatch } = useBuilderStore();
  const activePage = state.pages?.find((page) => page.id === state.activePageId) ?? state.pages?.[0];
  const [isSidebarHidden, setIsSidebarHidden] = useState(false);
  const [autosaveStatus, setAutosaveStatus] = useState('idle');
  const [lastSavedAt, setLastSavedAt] = useState(state.template?.updated_at ?? null);
  const [isSavingTemplate, setIsSavingTemplate] = useState(false);
  const [saveTemplateError, setSaveTemplateError] = useState(null);
  const [lastTemplateSavedAt, setLastTemplateSavedAt] = useState(null);
  const pendingSaveRef = useRef(null);
  const lastSnapshotRef = useRef(null);
  const controllerRef = useRef(null);
  const initialRenderRef = useRef(true);
  const canvasRef = useRef(null);
  const saveInProgressRef = useRef(false);

  const handleBoundaryReset = useCallback(() => {
    if (typeof window !== 'undefined') {
      window.location.reload();
    }
  }, []);

  const toggleSidebar = () => {
    setIsSidebarHidden(!isSidebarHidden);
  };

  useEffect(() => {
    return () => {
      if (pendingSaveRef.current) {
        clearTimeout(pendingSaveRef.current);
        pendingSaveRef.current = null;
      }
      if (controllerRef.current) {
        controllerRef.current.abort();
      }
    };
  }, []);

  useEffect(() => {
    if (!routes?.autosave || !csrfToken) {
      return undefined;
    }

    const designSnapshot = serializeDesign(state);
    const activePage = state.pages?.find((p) => p.id === state.activePageId) || state.pages?.[0] || null;
    const serialized = JSON.stringify(designSnapshot);

    if (initialRenderRef.current) {
      initialRenderRef.current = false;
      lastSnapshotRef.current = serialized;
      return undefined;
    }

    if (serialized === lastSnapshotRef.current) {
      return undefined;
    }

    lastSnapshotRef.current = serialized;

    if (pendingSaveRef.current) {
      clearTimeout(pendingSaveRef.current);
    }

    setAutosaveStatus('dirty');

    pendingSaveRef.current = setTimeout(() => {
      if (!routes?.autosave || !csrfToken) {
        return;
      }

      if (controllerRef.current) {
        controllerRef.current.abort();
      }

      const controller = new AbortController();
      controllerRef.current = controller;

      setAutosaveStatus('saving');

      let autosavePayload = {
        design: designSnapshot,
        canvas: designSnapshot.canvas,
        template_name: state.template?.name ?? null,
        template: {
          width_inch: state.template?.width_inch ?? null,
          height_inch: state.template?.height_inch ?? null,
          fold_type: state.template?.fold_type ?? null,
          sizes: state.template?.sizes ?? null,
          page_width_px: activePage?.width ?? null,
          page_height_px: activePage?.height ?? null,
          page_background: activePage?.background ?? null,
        },
      };

      let preparedAutosave = prepareCompressedJsonPayload(autosavePayload, AUTOSAVE_PAYLOAD_BUDGET, true);

      if (preparedAutosave.bodyBytes > POST_FAILSAFE_LIMIT && autosavePayload.canvas) {
        const trimmedPayload = { ...autosavePayload };
        delete trimmedPayload.canvas;
        const retried = prepareCompressedJsonPayload(trimmedPayload, AUTOSAVE_PAYLOAD_BUDGET, true);

        if (retried.bodyBytes < preparedAutosave.bodyBytes) {
          console.warn('[InkWise Builder] Trimmed autosave payload (removed canvas state) to fit POST budget.', {
            originalBytes: preparedAutosave.bodyBytes,
            trimmedBytes: retried.bodyBytes,
            limit: POST_FAILSAFE_LIMIT,
          });
          preparedAutosave = retried;
          autosavePayload = trimmedPayload;
        } else {
          console.error('[InkWise Builder] Autosave payload exceeds failsafe limit even after trimming canvas.', {
            bodyBytes: preparedAutosave.bodyBytes,
            retriedBytes: retried.bodyBytes,
            limit: POST_FAILSAFE_LIMIT,
          });
        }
      }

      if (preparedAutosave.compressed) {
        console.debug('[InkWise Builder] Compressed autosave payload.', {
          originalBytes: preparedAutosave.originalBytes,
          compressedBytes: preparedAutosave.compressedBytes,
          requestBytes: preparedAutosave.bodyBytes,
          threshold: AUTOSAVE_PAYLOAD_BUDGET,
        });
      }

      fetch(routes.autosave, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          ...preparedAutosave.headers,
        },
        credentials: 'same-origin',
        body: preparedAutosave.body,
        signal: controller.signal,
      })
        .then(async (response) => {
          if (!response.ok) {
            const message = await response.text();
            throw new Error(message || 'Autosave request failed');
          }
          return response.json();
        })
        .then((data) => {
          setAutosaveStatus('saved');
          if (data?.saved_at) {
            setLastSavedAt(data.saved_at);
          }
          if (controllerRef.current === controller) {
            controllerRef.current = null;
          }
        })
        .catch((error) => {
          if (controller.signal.aborted) {
            return;
          }
          if (controllerRef.current === controller) {
            controllerRef.current = null;
          }
          console.error('[InkWise Builder] Autosave failed:', error);
          setAutosaveStatus('error');
          // Allow retry on the next state change
          lastSnapshotRef.current = null;
        });
    }, 1500);

    return () => {
      if (pendingSaveRef.current) {
        clearTimeout(pendingSaveRef.current);
        pendingSaveRef.current = null;
      }
    };
  }, [state.pages, state.activePageId, state.zoom, state.panX, state.panY, state.template?.name, routes?.autosave, csrfToken]);

  const saveTemplateRoute = routes?.saveTemplate ?? routes?.saveCanvas;

  const handleSaveTemplate = useCallback(async (options = {}) => {
    if (!saveTemplateRoute) {
      setSaveTemplateError('Save route is unavailable.');
      return;
    }
    if (!csrfToken) {
      setSaveTemplateError('Missing CSRF token.');
      return;
    }
    if (!canvasRef.current) {
      setSaveTemplateError('Canvas not ready yet.');
      console.error('[InkWise Builder] Canvas ref is null during save attempt');
      return;
    }

    console.log('[InkWise Builder] Starting save with canvas ref:', canvasRef.current);
    if (isSavingTemplate || saveInProgressRef.current) {
      console.log('[InkWise Builder] Save already in progress, skipping');
      return;
    }

    saveInProgressRef.current = true;
    setIsSavingTemplate(true);
    setSaveTemplateError(null);

    const bodyEl = typeof document !== 'undefined' ? document.body : null;
    const requestedPageId = options?.pageId ?? null;
    const pixelRatio = typeof window !== 'undefined'
      ? Math.min(Math.max(window.devicePixelRatio || 1, 1), MAX_DEVICE_PIXEL_RATIO)
      : MAX_DEVICE_PIXEL_RATIO;

    try {
      // dispatch({ type: 'SHOW_PREVIEW_MODAL' }); // Commented out for save to avoid modal interference
      bodyEl?.classList.add('builder-exporting');

      // Allow layout/styles to flush before snapshotting so export-only CSS applies.
      await waitForNextFrame();

      const designSnapshot = serializeDesign(state);
      const allPages = Array.isArray(state.pages) ? state.pages : [];
      const selectedPages = requestedPageId
        ? allPages.filter((page) => page.id === requestedPageId)
        : allPages;
      const fallbackActivePage = allPages.find((page) => page.id === state.activePageId) ?? null;

      let pagesToCapture = (selectedPages.length > 0 ? selectedPages : allPages).filter(Boolean);
      if (pagesToCapture.length === 0 && fallbackActivePage) {
        pagesToCapture = [fallbackActivePage];
      }

      const totalPages = allPages.length > 0 ? allPages.length : pagesToCapture.length;

      const pendingPreviewEntries = [];
      const seenPreviewKeys = new Set();
      let previewImages = {};
      let previewImagesMeta = {};
      let previewPayloadTrimmed = false;
      let primaryPreviewCandidate = null;
      let svgDataUrl = null;
      const captureDiagnostics = [];

      const originalActivePageId = state.activePageId;
      const originalSelectedLayerId = state.selectedLayerId;
      let currentActivePageId = originalActivePageId;

      // Temporarily deselect any layer to hide bounding boxes during capture
      dispatch({ type: 'SELECT_LAYER', layerId: null });
      await waitForNextFrame();

      const ensurePageActive = async (pageId) => {
        if (!pageId || pageId === currentActivePageId) {
          return;
        }
        dispatch({ type: 'SELECT_PAGE', pageId });
        currentActivePageId = pageId;
        // Ensure no layer is selected when switching pages during export
        dispatch({ type: 'SELECT_LAYER', layerId: null });
        await waitForNextFrame();
      };

      for (let index = 0; index < pagesToCapture.length; index += 1) {
        const page = pagesToCapture[index];
        if (!page) {
          continue;
        }

        await ensurePageActive(page.id);
        await waitForNextFrame();

        const backgroundColor = '#ffffff'; // Always use white background for previews
        console.log('[InkWise Builder] === Capturing preview for page', page.id, 'with background', backgroundColor, '===');
        console.log('[InkWise Builder] Page details:', { id: page.id, name: page.name, width: page.width, height: page.height, nodesCount: page.nodes?.length });
        
        const captureOutcome = await captureCanvasRaster(canvasRef.current, pixelRatio, backgroundColor);
        const rasterDataUrl = typeof captureOutcome === 'string'
          ? captureOutcome
          : captureOutcome?.dataUrl ?? null;
        const captureDetails = typeof captureOutcome === 'object' && captureOutcome !== null && 'diagnostics' in captureOutcome
          ? captureOutcome.diagnostics
          : null;
        if (captureDetails) {
          captureDiagnostics.push({ pageId: page.id, order: index, diagnostics: captureDetails });
        }
        console.log('[InkWise Builder] Capture result:', rasterDataUrl ? `SUCCESS (${rasterDataUrl.length} chars)` : 'FAILED - NO DATA');
        
        if (!rasterDataUrl) {
          console.error('[InkWise Builder] ❌ CRITICAL: No raster data URL returned for page', page.id);
          console.error('[InkWise Builder] This will result in a blank/white preview being saved!');
          console.error('[InkWise Builder] Possible causes:');
          console.error('[InkWise Builder]   1. Canvas has no rendered content (check page.nodes)');
          console.error('[InkWise Builder]   2. Canvas elements are not properly mounted in DOM');
          console.error('[InkWise Builder]   3. Image assets failed to load');
          console.error('[InkWise Builder]   4. CORS issues with external assets');
          pendingPreviewEntries.push({
            key: `__diag_fail__${page.id}-${index}`,
            data: null,
            meta: { diagnostics: captureDetails, pageId: page.id, order: index, failure: true },
            bytes: 0,
            diagnostics: captureDetails,
          });
          continue;
        }

        let compressedImage = await compressPreviewImage(rasterDataUrl);
        if (!compressedImage) {
          console.log('[InkWise Builder] Compression failed, using original raster data');
          compressedImage = rasterDataUrl;
        }
        if (!compressedImage) {
          continue;
        }

        const keyBase = derivePreviewKey(page, index);
        const safeKeyBase = typeof keyBase === 'string' && keyBase.trim() !== '' ? keyBase : null;
        const fallbackKey = `page-${index + 1}`;

        let uniqueKey = safeKeyBase ?? fallbackKey;
        let suffix = 2;
        while (seenPreviewKeys.has(uniqueKey)) {
          uniqueKey = safeKeyBase ? `${safeKeyBase}-${suffix}` : `${fallbackKey}-${suffix}`;
          suffix += 1;
        }
        seenPreviewKeys.add(uniqueKey);

        const entryMeta = {
          label: derivePreviewLabel(page, index, totalPages || pagesToCapture.length || 1),
          pageId: page.id,
          pageType: safeKeyBase ?? uniqueKey,
          order: index,
        };

        pendingPreviewEntries.push({
          key: uniqueKey,
          data: compressedImage,
          meta: entryMeta,
          bytes: estimateBase64Bytes(compressedImage),
          diagnostics: captureDetails,
        });

        if (!primaryPreviewCandidate) {
          primaryPreviewCandidate = compressedImage;
        }
        if (uniqueKey === 'front') {
          primaryPreviewCandidate = compressedImage;
        }

        if (!svgDataUrl) {
          try {
            console.log('[InkWise Builder] Attempting SVG capture for page', page.id);
            const svgTarget = canvasRef.current.querySelector('.canvas-viewport__stage') || canvasRef.current;
            svgDataUrl = await toSvg(svgTarget, {
              cacheBust: true,
              filter: (node) => !node.classList?.contains('canvas-layer__resize-handle'),
              backgroundColor: '#ffffff',
            });
            console.log('[InkWise Builder] SVG capture succeeded, length:', svgDataUrl?.length);
          } catch (captureError) {
            console.error('[InkWise Builder] SVG snapshot capture failed:', captureError.message, captureError.stack);
          }
        }
      }

      await ensurePageActive(originalActivePageId);

      const originalLayerStillExists = allPages.some((page) => (
        page.id === originalActivePageId
        && Array.isArray(page.nodes)
        && page.nodes.some((node) => node.id === originalSelectedLayerId)
      ));

      if (originalLayerStillExists && originalSelectedLayerId) {
        dispatch({ type: 'SELECT_LAYER', layerId: originalSelectedLayerId });
        await waitForNextFrame();
      }

      if (pendingPreviewEntries.length > 0) {
        const sortedEntries = [...pendingPreviewEntries].sort((a, b) => {
          const priorityDiff = getPreviewPriority(b) - getPreviewPriority(a);
          if (priorityDiff !== 0) {
            return priorityDiff;
          }
          return (a.bytes || 0) - (b.bytes || 0);
        });

        let remainingBudget = PREVIEW_TOTAL_BUDGET;
        const selectedEntries = [];

        for (const entry of sortedEntries) {
          const entryBytes = entry.bytes || 0;
          if (selectedEntries.length === 0 || entryBytes <= remainingBudget) {
            selectedEntries.push(entry);
            remainingBudget = Math.max(remainingBudget - entryBytes, 0);
          }
        }

        if (selectedEntries.length < pendingPreviewEntries.length) {
          previewPayloadTrimmed = true;
          console.warn('[InkWise Builder] Trimmed preview payload to avoid oversize POST.', {
            totalEntries: pendingPreviewEntries.length,
            selectedEntries: selectedEntries.length,
            remainingBudget,
          });
        }

        previewImages = {};
        previewImagesMeta = {};
        for (const entry of selectedEntries) {
          if (!entry.data) {
            continue;
          }
          previewImages[entry.key] = entry.data;
          previewImagesMeta[entry.key] = entry.meta;
        }
      } else {
        previewImages = {};
        previewImagesMeta = {};
      }

      const previewImage = previewImages.front ?? primaryPreviewCandidate ?? null;
      const svgMarkup = extractSvgMarkup(svgDataUrl);
      const svgPayload = encodeSvgMarkup(svgMarkup);

      console.log('[InkWise Builder] SVG capture:', {
        svgDataUrlLength: svgDataUrl ? svgDataUrl.length : 0,
        svgMarkupLength: svgMarkup ? svgMarkup.length : 0,
        svgPayloadLength: svgPayload ? svgPayload.length : 0,
      });

      console.log('[InkWise Builder] === Preview capture results ===');
      console.log('[InkWise Builder] Preview images count:', Object.keys(previewImages).length);
      console.log('[InkWise Builder] Has primary preview:', !!previewImage);
      console.log('[InkWise Builder] Has SVG:', !!svgPayload);
      console.log('[InkWise Builder] Primary preview length:', previewImage ? previewImage.length : 0, 'bytes');
      
      if (Object.keys(previewImages).length === 0) {
        console.error('[InkWise Builder] ⚠️  WARNING: No preview images were captured!');
        console.error('[InkWise Builder] The template will be saved with a blank white preview.');
      }

      // If no preview was captured, create a simple white placeholder
      let finalPreviewImage = previewImage;
      if (!finalPreviewImage) {
        console.warn('[InkWise Builder] ⚠️  No preview captured, creating fallback white preview');
        console.warn('[InkWise Builder] This indicates the canvas capture failed for all pages!');
        // Create a simple 400x400 white PNG as data URL
        const canvas = document.createElement('canvas');
        canvas.width = 400;
        canvas.height = 400;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, 400, 400);
        finalPreviewImage = canvas.toDataURL('image/png');
        console.warn('[InkWise Builder] Created dummy preview of length:', finalPreviewImage.length);
      } else {
        console.log('[InkWise Builder] ✓ Using captured preview of', finalPreviewImage.length, 'bytes');
      }

      const shouldIncludePreview = !!finalPreviewImage;

      const payload = {
        design: designSnapshot,
        template_name: state.template?.name ?? null,
        template: {
          width_inch: state.template?.width_inch ?? null,
          height_inch: state.template?.height_inch ?? null,
          fold_type: state.template?.fold_type ?? null,
          sizes: state.template?.sizes ?? null,
          selected_sizes: state.selectedSizes ?? [],
          page_width_px: activePage?.width ?? null,
          page_height_px: activePage?.height ?? null,
          page_background: activePage?.background ?? null,
        },
      };

      if (requestedPageId) {
        payload.save_context = {
          scope: 'page',
          page_id: requestedPageId,
        };
      }

      if (shouldIncludePreview) {
        payload.preview_image = finalPreviewImage;
      }

      if (Object.keys(previewImages).length > 0) {
        payload.preview_images = previewImages;
        payload.preview_images_meta = previewImagesMeta;
        if (previewPayloadTrimmed) {
          payload.preview_images_truncated = true;
        }
      } else if (previewPayloadTrimmed) {
        payload.preview_images_truncated = true;
      }

      if (svgPayload) {
        payload.svg_markup = svgPayload;
      }

      if (captureDiagnostics.length > 0) {
        payload.capture_diagnostics = captureDiagnostics;
      }

      // Fallback: if no preview captured, use dummy
      if (!payload.preview_image) {
        console.log('[InkWise Builder] No preview captured, using dummy');
        payload.preview_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
      }

      // Fallback: if no SVG, use dummy
      if (!payload.svg_markup) {
        console.log('[InkWise Builder] No SVG captured, using dummy');
        payload.svg_markup = 'data:image/svg+xml;base64,' + btoa('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="white"/></svg>');
      }

      console.log('[InkWise Builder] === Final payload validation ===');
      console.log('[InkWise Builder] Design pages:', designSnapshot.pages?.length || 0);
      console.log('[InkWise Builder] Design nodes in page 0:', designSnapshot.pages?.[0]?.nodes?.length || 0);
      console.log('[InkWise Builder] Preview image size:', payload.preview_image?.length || 0, 'bytes');
      console.log('[InkWise Builder] SVG markup size:', payload.svg_markup?.length || 0, 'bytes');
      console.log('[InkWise Builder] Additional preview images:', Object.keys(payload.preview_images || {}).length);
      
      if (!designSnapshot.pages || designSnapshot.pages.length === 0) {
        console.error('[InkWise Builder] ❌ CRITICAL: Design has no pages!');
      } else if (!designSnapshot.pages[0].nodes || designSnapshot.pages[0].nodes.length === 0) {
        console.error('[InkWise Builder] ❌ CRITICAL: First page has no nodes (design elements)!');
        console.error('[InkWise Builder] This will result in an empty template being saved.');
      }
      
      const { prepared: preparedPayload, id: payloadVariant } = prepareManualSaveRequest(payload);

      if (preparedPayload.compressed) {
        console.info('[InkWise Builder] ✓ Compressed manual-save payload.', {
          originalBytes: preparedPayload.originalBytes,
          compressedBytes: preparedPayload.compressedBytes,
          requestBytes: preparedPayload.bodyBytes,
          variant: payloadVariant,
          threshold: MANUAL_SAVE_PAYLOAD_BUDGET,
        });
      }

      if (preparedPayload.bodyBytes > POST_FAILSAFE_LIMIT) {
        console.error('[InkWise Builder] ❌ Manual save payload still exceeds failsafe limit after trimming.', {
          bodyBytes: preparedPayload.bodyBytes,
          limit: POST_FAILSAFE_LIMIT,
          variant: payloadVariant,
        });
      }
      
      console.log('[InkWise Builder] === Sending save request to server ===');

      const response = await fetch(saveTemplateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          ...preparedPayload.headers,
        },
        credentials: 'same-origin',
        body: preparedPayload.body,
      });

      if (!response.ok) {
        let errorMessage = 'Failed to save template';
        const contentType = response.headers.get('Content-Type') || '';

        if (contentType.includes('application/json')) {
          try {
            const errorPayload = await response.clone().json();
            if (typeof errorPayload?.message === 'string' && errorPayload.message.trim() !== '') {
              errorMessage = errorPayload.message.trim();
            } else if (errorPayload?.errors && typeof errorPayload.errors === 'object') {
              const firstField = Object.values(errorPayload.errors)[0];
              if (Array.isArray(firstField) && typeof firstField[0] === 'string') {
                errorMessage = firstField[0];
              }
            }
          } catch (jsonError) {
            console.warn('[InkWise Builder] Failed to parse error response JSON.', jsonError);
          }
        } else {
          const textMessage = await response.text();
          if (textMessage && textMessage.trim().length > 0) {
            errorMessage = textMessage.trim();
          }
        }

        throw new Error(errorMessage);
      }

      const data = await response.json().catch(() => ({}));
      const savedAt = new Date().toISOString();
      setLastTemplateSavedAt(savedAt);

      const redirectTarget = data?.redirect || routes?.index || '/staff/templates';
      window.location.href = redirectTarget;
    } catch (error) {
      console.error('[InkWise Builder] Template save failed:', error);
      setSaveTemplateError(error.message || 'Failed to save template');
      dispatch({ type: 'HIDE_PREVIEW_MODAL' });
    } finally {
      bodyEl?.classList.remove('builder-exporting');
      setIsSavingTemplate(false);
      saveInProgressRef.current = false;
    }
  }, [saveTemplateRoute, csrfToken, isSavingTemplate, state, routes, dispatch]);

  return (
    <BuilderErrorBoundary onReset={handleBoundaryReset} templateId={state.template?.id}>
      <div className="builder-shell" role="application" aria-label="InkWise template builder">
        <BuilderHotkeys />
        <BuilderTopBar
          autosaveStatus={autosaveStatus}
          lastSavedAt={lastSavedAt}
          onSaveTemplate={handleSaveTemplate}
          isSavingTemplate={isSavingTemplate}
          lastManualSaveAt={lastTemplateSavedAt}
          saveError={saveTemplateError}
        />
        <div className="builder-workspace" style={{ gridTemplateColumns: isSidebarHidden ? '60px minmax(0, 1fr) 340px' : '600px minmax(0, 1fr) 340px' }}>
          <ToolSidebar isSidebarHidden={isSidebarHidden} onToggleSidebar={toggleSidebar} />
          <main className="builder-canvas-column" aria-live="polite">
            <div className="builder-canvas-header">
              <CanvasToolbar />
            </div>
            <CanvasViewport page={activePage} canvasRef={canvasRef} />
            <BuilderStatusBar />
          </main>
          <aside className="builder-right-column" aria-label="Inspector panels">
            <InspectorPanel />
          </aside>
        </div>
        <PreviewModal
          isOpen={state.showPreviewModal}
          onClose={() => dispatch({ type: 'HIDE_PREVIEW_MODAL' })}
        />
      </div>
    </BuilderErrorBoundary>
  );
}
