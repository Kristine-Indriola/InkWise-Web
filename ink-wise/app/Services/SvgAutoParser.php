<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SvgAutoParser
{
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
    private function processSvgContent(string $content): array
    {
        $textElements = [];
        $imageElements = [];
        $changeableImages = [];

        // Load SVG as DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);

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
        $imageNodes = $dom->getElementsByTagName('image');
        foreach ($imageNodes as $index => $imageNode) {
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

        // Find images and rects that should be changeable
        $changeableSelectors = [
            'image[id*="photo" i]',
            'image[id*="replaceable" i]',
            'image[id*="editable" i]',
            'rect[id*="photo" i]',
            'rect[id*="replaceable" i]',
            'rect[id*="editable" i]'
        ];

        $xpath = new \DOMXPath($dom);
        foreach ($changeableSelectors as $selector) {
            // Simple attribute contains check since XPath 1.0 doesn't have contains() for case-insensitive
            $elements = $xpath->query('//' . $selector);
            foreach ($elements as $element) {
                /** @var \DOMElement $element */
                $element->setAttribute('data-changeable', 'image');
                $element->setAttribute('data-changeable-id', 'changeable_' . count($changeableImages));

                $changeableImages[] = [
                    'id' => 'changeable_' . count($changeableImages),
                    'element_type' => $element->tagName,
                    'original_id' => $element->getAttribute('id'),
                    'x' => $element->getAttribute('x') ?: '0',
                    'y' => $element->getAttribute('y') ?: '0',
                    'width' => $element->getAttribute('width') ?: '',
                    'height' => $element->getAttribute('height') ?: ''
                ];
            }
        }

        // Clean up base64 data and local paths
        $this->cleanSvgReferences($dom);

        // Save the processed SVG
        $processedContent = $dom->saveXML();

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
     * Clean up SVG references (base64 data, local paths)
     */
    private function cleanSvgReferences(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);

        // Handle image elements with base64 data
        $images = $xpath->query('//image');
        foreach ($images as $image) {
            /** @var \DOMElement $image */
            $href = $image->getAttribute('href') ?: $image->getAttribute('xlink:href');

            if ($href && strpos($href, 'data:image') === 0) {
                // Replace base64 data with placeholder
                $image->setAttribute('href', 'data:image/png;base64,PLACEHOLDER_IMAGE');
                if ($image->hasAttribute('xlink:href')) {
                    $image->removeAttribute('xlink:href');
                }
            } elseif ($href && !filter_var($href, FILTER_VALIDATE_URL)) {
                // Remove local file paths
                $image->setAttribute('href', 'data:image/png;base64,PLACEHOLDER_IMAGE');
                if ($image->hasAttribute('xlink:href')) {
                    $image->removeAttribute('xlink:href');
                }
            }
        }

        // Handle other elements that might have local references
        $elementsWithRefs = $xpath->query('//*[@href] | //*[@xlink:href] | //*[@src]');
        foreach ($elementsWithRefs as $element) {
            /** @var \DOMElement $element */
            $href = $element->getAttribute('href') ?: $element->getAttribute('xlink:href') ?: $element->getAttribute('src');

            if ($href && !filter_var($href, FILTER_VALIDATE_URL) && strpos($href, 'data:') !== 0) {
                // Remove local references
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
