<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SvgAutoParser
{
    /**
     * Process Figma imported SVG content specifically
     * This method is more aggressive in converting vector shapes to changeable images
     *
     * @param string $svgContent Raw SVG content from Figma import
     * @return array Processed SVG data with converted changeable elements
     */
    public function processFigmaImportedSvg(string $svgContent): array
    {
        try {
            $processedSvg = $this->processSvgContent($svgContent, true); // Pass flag for Figma processing

            return [
                'content' => $processedSvg['content'],
                'text_elements' => $processedSvg['text_elements'],
                'image_elements' => $processedSvg['image_elements'],
                'changeable_images' => $processedSvg['changeable_images'],
                'metadata' => array_merge($processedSvg['metadata'], [
                    'processing_type' => 'figma_import',
                    'vector_shapes_converted' => count($processedSvg['changeable_images'])
                ])
            ];

        } catch (\Exception $e) {
            Log::error('Figma SVG processing failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'content' => $svgContent,
                'text_elements' => [],
                'image_elements' => [],
                'changeable_images' => [],
                'metadata' => ['error' => $e->getMessage(), 'processing_type' => 'figma_import_failed']
            ];
        }
    }

    /**
     * Parse and process an uploaded SVG file
     *
     * @param string $filePath The path to the uploaded SVG file
     * @return array Processed SVG data
     */
    public function parseSvg(string $filePath): array
    {
        try {
            // Read the SVG content
            $svgContent = Storage::disk('public')->get($filePath);

            if (!$svgContent) {
                throw new \Exception("Could not read SVG file: {$filePath}");
            }

            // Parse the SVG
            $processedSvg = $this->processSvgContent($svgContent);

            // Save the processed SVG back to storage
            $processedPath = $this->saveProcessedSvg($filePath, $processedSvg['content']);

            return [
                'original_path' => $filePath,
                'processed_path' => $processedPath,
                'content' => $processedSvg['content'],
                'text_elements' => $processedSvg['text_elements'],
                'image_elements' => $processedSvg['image_elements'],
                'changeable_images' => $processedSvg['changeable_images'],
                'metadata' => $processedSvg['metadata']
            ];

        } catch (\Exception $e) {
            Log::error('SVG parsing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            // Return original file data if parsing fails
            return [
                'original_path' => $filePath,
                'processed_path' => $filePath,
                'content' => Storage::disk('public')->get($filePath),
                'text_elements' => [],
                'image_elements' => [],
                'changeable_images' => [],
                'metadata' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Process SVG content to add data attributes and clean up
     */
    public function processSvgContent(string $content, bool $isFigmaImport = false): array
    {
        $textElements = [];
    $imageElements = [];
        $changeableImages = [];

        // Load SVG as DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);
        
        // Register XLink namespace for XPath queries
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        // Find all text elements
        $textNodes = $dom->getElementsByTagName('text');
        foreach ($textNodes as $index => $textNode) {
            // Add data-editable attribute
            $textNode->setAttribute('data-editable', 'true');
            $textNode->setAttribute('data-text-id', 'text_' . ($index + 1));

            $textElements[] = [
                'id' => 'text_' . ($index + 1),
                'content' => $textNode->textContent,
                'x' => $textNode->getAttribute('x') ?: '0',
                'y' => $textNode->getAttribute('y') ?: '0',
                'font_family' => $textNode->getAttribute('font-family') ?: '',
                'font_size' => $textNode->getAttribute('font-size') ?: '',
                'fill' => $textNode->getAttribute('fill') ?: ''
            ];
        }

        // Find all image elements
        $imageNodeList = $dom->getElementsByTagName('image');
        foreach ($imageNodeList as $index => $imageNode) {
            $imageNode->setAttribute('data-image-id', 'image_' . ($index + 1));

            $imageElements[] = [
                'id' => 'image_' . ($index + 1),
                'href' => $imageNode->getAttribute('href') ?: $imageNode->getAttribute('xlink:href'),
                'x' => $imageNode->getAttribute('x') ?: '0',
                'y' => $imageNode->getAttribute('y') ?: '0',
                'width' => $imageNode->getAttribute('width') ?: '',
                'height' => $imageNode->getAttribute('height') ?: ''
            ];
        }

        // Enhanced Figma SVG processing - convert vector shapes to changeable images
        // (Use the existing $xpath variable created above)
        
        // Process existing images with changeable patterns
        $changeableImageNodes = $xpath->query('//*[local-name()="image"]');
        if ($changeableImageNodes === false) {
            $changeableImageNodes = [];
        }

        foreach ($changeableImageNodes as $element) {
            /** @var \DOMElement $element */
            $id = strtolower($element->getAttribute('id') ?: '');
            $href = strtolower($element->getAttribute('href') ?: $element->getAttribute('xlink:href') ?: '');
            
            // Check if this image should be changeable
            $isChangeable = false;
            $changeablePatterns = ['photo', 'replaceable', 'editable', 'changeable', 'placeholder'];
            
            foreach ($changeablePatterns as $pattern) {
                if (strpos($id, $pattern) !== false || strpos($href, $pattern) !== false) {
                    $isChangeable = true;
                    break;
                }
            }
            
            if ($isChangeable) {
                $element->setAttribute('data-changeable', 'image');
                $newId = 'changeable_' . count($changeableImages);
                $changeableImages[] = [
                    'id' => $newId,
                    'element_type' => $element->tagName,
                    'original_id' => $element->getAttribute('id'),
                    'x' => $element->getAttribute('x') ?: '0',
                    'y' => $element->getAttribute('y') ?: '0',
                    'width' => $element->getAttribute('width') ?: '',
                    'height' => $element->getAttribute('height') ?: ''
                ];
                $element->setAttribute('data-changeable-id', $newId);
            }
        }
        
        // Enhanced conversion of vector shapes to changeable image placeholders (especially for Figma imports)
            if ($isFigmaImport) {
                $this->convertFigmaVectorShapesToChangeableImages($dom, $xpath, $changeableImages);
            }

        // Clean up base64 data and local paths
        $this->cleanSvgReferences($dom);

        // Add SVG data attribute for JavaScript to read
        $svgElement = $dom->getElementsByTagName('svg')->item(0);
        if ($svgElement) {
            $svgElement->setAttribute('data-svg-editor', 'true');
            $svgElement->setAttribute('data-svg-data', json_encode([
                'text_elements' => $textElements,
                'image_elements' => $imageElements,
                'changeable_images' => $changeableImages
            ]));
        }

        // Save the processed SVG
        $processedContent = $dom->saveXML($dom->documentElement);

        return [
            'content' => $processedContent,
            'text_elements' => $textElements,
            'image_elements' => $imageElements,
            'changeable_images' => $changeableImages,
            'metadata' => [
                'text_count' => count($textElements),
                'image_count' => count($imageElements),
                'changeable_count' => count($changeableImages),
                'processed_at' => now()->toISOString()
            ]
        ];
    }

    /**
     * Convert Figma vector shapes to changeable image elements
     * This method identifies rectangular shapes that could be placeholders for images
     * and converts them to proper <image> elements with changeable attributes
     */
    private function convertFigmaVectorShapesToChangeableImages(\DOMDocument $dom, \DOMXPath $xpath, array &$changeableImages): void
    {
        // Strategy 1: Convert large rectangular shapes to changeable images
        // Use local-name() to handle default namespaces in SVG
        $rectangularShapes = $xpath->query('//*[local-name()="rect"]');
        if ($rectangularShapes === false) {
            $rectangularShapes = []; // Fallback to empty array
        }
        
        foreach ($rectangularShapes as $element) {
            /** @var \DOMElement $element */
            
            // Skip if already processed
            if ($element->hasAttribute('data-changeable')) {
                continue;
            }
            
            $shouldConvert = $this->shouldConvertShapeToChangeableImage($element);
            
            if ($shouldConvert) {
                // Convert shape to changeable image placeholder
                $this->convertShapeToChangeableImage($element, $changeableImages);
            }
        }
        
        // Strategy 2: Look for groups or elements with specific patterns that suggest images
        $potentialImageContainers = $xpath->query("//g[contains(@id, 'image') or contains(@id, 'photo') or contains(@id, 'picture')] | //*[contains(@class, 'image') or contains(@class, 'photo')]");
        
        if ($potentialImageContainers !== false) {
            foreach ($potentialImageContainers as $container) {
                /** @var \DOMElement $container */
                
                // Check if this container has children that could be converted to images
                $childShapes = $xpath->query('.//*[local-name()="rect" or local-name()="path" or local-name()="circle" or local-name()="ellipse"]', $container);
                
                if ($childShapes !== false && $childShapes->length > 0) {
                    // Convert the largest child shape to a changeable image
                    $largestShape = $this->findLargestShape($childShapes);
                    if ($largestShape && !$largestShape->hasAttribute('data-changeable')) {
                        $this->convertShapeToChangeableImage($largestShape, $changeableImages);
                    }
                }
            }
        }
        
        // Strategy 3: Aggressive conversion for Figma imports - convert the largest rectangles
            // Strategy 3 removed: avoid aggressively converting large rectangles to prevent stripping real artwork
    }
    
    /**
     * Determine if a shape should be converted to a changeable image
     */
    private function shouldConvertShapeToChangeableImage(\DOMElement $element): bool
    {
        $indicators = [
            strtolower($element->getAttribute('id') ?: ''),
            strtolower($element->getAttribute('class') ?: ''),
            strtolower($element->getAttribute('name') ?: ''),
            strtolower($element->getAttribute('data-name') ?: ''),
            strtolower($element->getAttribute('data-placeholder') ?: ''),
        ];

        $imageIndicators = [
            'photo', 'image', 'picture', 'img', 'placeholder', 'changeable',
            'editable', 'replaceable', 'avatar', 'profile', 'logo', 'banner'
        ];

        foreach ($indicators as $value) {
            if (empty($value)) {
                continue;
            }

            foreach ($imageIndicators as $indicator) {
                if (strpos($value, $indicator) !== false) {
                    return true;
                }
            }
        }

        // Walk up the DOM tree to see if any parent group hints at being an image container
        $parent = $element->parentNode;
        while ($parent instanceof \DOMElement) {
            /** @var \DOMElement $parent */
            $parentTokens = [
                strtolower($parent->getAttribute('id') ?: ''),
                strtolower($parent->getAttribute('class') ?: ''),
                strtolower($parent->getAttribute('name') ?: ''),
            ];

            foreach ($parentTokens as $token) {
                if (empty($token)) {
                    continue;
                }

                foreach ($imageIndicators as $indicator) {
                    if (strpos($token, $indicator) !== false) {
                        return true;
                    }
                }
            }

            $parent = $parent->parentNode;
        }

        return false;
    }
    
    /**
     * Convert a shape element to a changeable image element
     */
    private function convertShapeToChangeableImage(\DOMElement $element, array &$changeableImages): void
    {
        // Extract position and size information
        $bounds = $this->getElementBounds($element);
        
        if (!$bounds) {
            return; // Skip if we can't determine bounds
        }
        
        // Create a new image element
        $imageElement = $element->ownerDocument->createElementNS('http://www.w3.org/2000/svg', 'image');
        
        // Set image attributes
        $imageElement->setAttribute('x', $bounds['x']);
        $imageElement->setAttribute('y', $bounds['y']);
        $imageElement->setAttribute('width', $bounds['width']);
        $imageElement->setAttribute('height', $bounds['height']);
        $imageElement->setAttribute('href', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y0ZjRmNCIvPjx0ZXh0IHg9IjUwIiB5PSI1NSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzk5OTk5OSI+SW1hZ2U8L3RleHQ+PC9zdmc+'); // Placeholder image
        $imageElement->setAttribute('preserveAspectRatio', 'xMidYMid slice');
        
        // Add changeable attributes
        $changeableId = 'changeable_' . count($changeableImages);
        $imageElement->setAttribute('data-changeable', 'image');
        $imageElement->setAttribute('data-changeable-id', $changeableId);
        $imageElement->setAttribute('data-original-element', $element->tagName);
        
        // Copy over original id if it exists
        if ($element->hasAttribute('id')) {
            $imageElement->setAttribute('data-original-id', $element->getAttribute('id'));
        }
        
        // Replace the original element with the new image element
        $element->parentNode->replaceChild($imageElement, $element);
        
        // Add to changeable images array
        $changeableImages[] = [
            'id' => $changeableId,
            'element_type' => 'image',
            'original_element' => $element->tagName,
            'original_id' => $element->getAttribute('id') ?: '',
            'x' => $bounds['x'],
            'y' => $bounds['y'],
            'width' => $bounds['width'],
            'height' => $bounds['height']
        ];
    }
    
    /**
     * Get bounds for an SVG element
     */
    private function getElementBounds(\DOMElement $element): ?array
    {
        switch ($element->tagName) {
            case 'rect':
                return [
                    'x' => $element->getAttribute('x') ?: '0',
                    'y' => $element->getAttribute('y') ?: '0',
                    'width' => $element->getAttribute('width') ?: '100',
                    'height' => $element->getAttribute('height') ?: '100'
                ];
                
            case 'circle':
                $cx = floatval($element->getAttribute('cx') ?: 0);
                $cy = floatval($element->getAttribute('cy') ?: 0);
                $r = floatval($element->getAttribute('r') ?: 50);
                return [
                    'x' => strval($cx - $r),
                    'y' => strval($cy - $r),
                    'width' => strval($r * 2),
                    'height' => strval($r * 2)
                ];
                
            case 'ellipse':
                $cx = floatval($element->getAttribute('cx') ?: 0);
                $cy = floatval($element->getAttribute('cy') ?: 0);
                $rx = floatval($element->getAttribute('rx') ?: 50);
                $ry = floatval($element->getAttribute('ry') ?: 50);
                return [
                    'x' => strval($cx - $rx),
                    'y' => strval($cy - $ry),
                    'width' => strval($rx * 2),
                    'height' => strval($ry * 2)
                ];
                
            case 'path':
                // For paths, try to extract bounds from the d attribute
                // This is a simplified approach - for production, you'd want more robust path parsing
                $d = $element->getAttribute('d');
                if ($d) {
                    // Try to extract coordinate numbers from the path
                    preg_match_all('/[\d.]+/', $d, $matches);
                    if (isset($matches[0]) && count($matches[0]) >= 4) {
                        $coords = array_map('floatval', $matches[0]);
                        $minX = min($coords);
                        $minY = min($coords);
                        $maxX = max($coords);
                        $maxY = max($coords);
                        
                        return [
                            'x' => strval($minX),
                            'y' => strval($minY),
                            'width' => strval($maxX - $minX),
                            'height' => strval($maxY - $minY)
                        ];
                    }
                }
                // Fallback for paths
                return [
                    'x' => '0',
                    'y' => '0',
                    'width' => '100',
                    'height' => '100'
                ];
                
            default:
                return null;
        }
    }
    
    /**
     * Find the largest shape among a collection of elements
     */
    private function findLargestShape(\DOMNodeList $shapes): ?\DOMElement
    {
        $largestShape = null;
        $largestArea = 0;
        
        foreach ($shapes as $shape) {
            /** @var \DOMElement $shape */
            $bounds = $this->getElementBounds($shape);
            if ($bounds) {
                $area = floatval($bounds['width']) * floatval($bounds['height']);
                if ($area > $largestArea) {
                    $largestArea = $area;
                    $largestShape = $shape;
                }
            }
        }
        
        return $largestShape;
    }

    /**
     * Clean up SVG references (base64 data, local paths)
     */
    private function cleanSvgReferences(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        // Handle image elements and preserve inline data URLs so previews render correctly
        $images = $xpath->query('//*[local-name()="image"]');
        foreach ($images as $image) {
            /** @var \DOMElement $image */
            $href = $image->getAttribute('href') ?: $image->getAttribute('xlink:href');

            if (!$href) {
                continue;
            }

            if (strpos($href, 'data:image') === 0) {
                // Keep data URLs intact; ensure both href variants are aligned
                $image->setAttribute('href', $href);
                if ($image->hasAttribute('xlink:href')) {
                    $image->setAttribute('xlink:href', $href);
                }
                continue;
            }

            if (!filter_var($href, FILTER_VALIDATE_URL)) {
                // Strip unresolved local file paths to avoid broken references
                if ($image->hasAttribute('href')) {
                    $image->removeAttribute('href');
                }
                if ($image->hasAttribute('xlink:href')) {
                    $image->removeAttribute('xlink:href');
                }
            }
        }

        // Handle other elements that might have local references
        $elementsWithRefs = $xpath->query('//*[@href] | //*[@src]');
        if ($elementsWithRefs === false) {
            $elementsWithRefs = [];
        }
        foreach ($elementsWithRefs as $element) {
            /** @var \DOMElement $element */
            $href = $element->getAttribute('href') ?: $element->getAttribute('xlink:href') ?: $element->getAttribute('src');

            if (!$href) {
                continue;
            }

            if (strpos($href, 'data:') === 0 || filter_var($href, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Remove unresolved local references while leaving valid URLs/data intact
            if ($element->hasAttribute('href')) {
                $element->removeAttribute('href');
            }
            if ($element->hasAttribute('xlink:href')) {
                $element->removeAttribute('xlink:href');
            }
            if ($element->hasAttribute('src')) {
                $element->removeAttribute('src');
            }
        }
    }

    /**
     * Save the processed SVG content
     */
    private function saveProcessedSvg(string $originalPath, string $content): string
    {
        $pathInfo = pathinfo($originalPath);
        $processedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_processed.' . $pathInfo['extension'];

        Storage::disk('public')->put($processedPath, $content);

        return $processedPath;
    }

    /**
     * Generate JavaScript for SVG editor functionality
     */
    public function generateEditorJs(array $svgData): string
    {
        $changeableImages = $svgData['changeable_images'] ?? [];
        $textElements = $svgData['text_elements'] ?? [];

        $js = <<<JS
(function() {
    'use strict';

    // SVG Editor functionality
    class SvgEditor {
        constructor(svgElement) {
            this.svg = svgElement;
            this.changeableImages = {$this->arrayToJs($changeableImages)};
            this.textElements = {$this->arrayToJs($textElements)};
            this.init();
        }

        init() {
            this.setupImageUpload();
            this.setupTextEditing();
        }

        setupImageUpload() {
            const self = this;

            // Find all changeable image elements
            this.changeableImages.forEach(function(imageData, index) {
                const element = self.svg.querySelector('[data-changeable-id="' + imageData.id + '"]');
                if (element) {
                    element.style.cursor = 'pointer';
                    element.addEventListener('click', function() {
                        self.showImageUploadDialog(imageData, element);
                    });
                }
            });
        }

        setupTextEditing() {
            const self = this;

            // Find all editable text elements
            this.textElements.forEach(function(textData, index) {
                const element = self.svg.querySelector('[data-text-id="' + textData.id + '"]');
                if (element) {
                    element.style.cursor = 'pointer';
                    element.addEventListener('click', function() {
                        self.showTextEditDialog(textData, element);
                    });
                }
            });
        }

        showImageUploadDialog(imageData, element) {
            const self = this;

            // Create file input
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.style.display = 'none';

            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    self.handleImageUpload(file, element);
                }
                document.body.removeChild(input);
            });

            document.body.appendChild(input);
            input.click();
        }

        handleImageUpload(file, element) {
            const self = this;
            const reader = new FileReader();

            reader.onload = function(e) {
                const imageUrl = e.target.result;

                // Update the SVG element
                if (element.tagName.toLowerCase() === 'image') {
                    element.setAttribute('href', imageUrl);
                } else if (element.tagName.toLowerCase() === 'rect') {
                    // For rect elements, we might need to create a pattern or clip
                    // For now, we'll create an image element to replace the rect
                    const image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
                    image.setAttribute('x', element.getAttribute('x') || '0');
                    image.setAttribute('y', element.getAttribute('y') || '0');
                    image.setAttribute('width', element.getAttribute('width') || '100');
                    image.setAttribute('height', element.getAttribute('height') || '100');
                    image.setAttribute('href', imageUrl);
                    image.setAttribute('preserveAspectRatio', 'xMidYMid slice');

                    element.parentNode.replaceChild(image, element);
                }

                // Trigger change event for form handling
                const event = new CustomEvent('svgImageChanged', {
                    detail: { element: element, imageUrl: imageUrl }
                });
                self.svg.dispatchEvent(event);
            };

            reader.readAsDataURL(file);
        }

        showTextEditDialog(textData, element) {
            const self = this;
            const currentText = element.textContent || '';

            const newText = prompt('Edit text:', currentText);
            if (newText !== null && newText !== currentText) {
                // Clear existing text nodes
                while (element.firstChild) {
                    element.removeChild(element.firstChild);
                }

                // Add new text
                element.appendChild(document.createTextNode(newText));

                // Trigger change event
                const event = new CustomEvent('svgTextChanged', {
                    detail: { element: element, oldText: currentText, newText: newText }
                });
                self.svg.dispatchEvent(event);
            }
        }
    }

    // Initialize SVG editors when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const svgElements = document.querySelectorAll('svg[data-svg-editor]');
        svgElements.forEach(function(svg) {
            new SvgEditor(svg);
        });
    });

    // Also initialize on dynamic content
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.matches && node.matches('svg[data-svg-editor]')) {
                    new SvgEditor(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

})();
JS;

        return $js;
    }

    /**
     * Convert PHP array to JavaScript array/object
     */
    private function arrayToJs(array $array): string
    {
        return json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
