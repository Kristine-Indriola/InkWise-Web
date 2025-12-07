@extends('layouts.staff')

@section('title', 'SVG Template Editor - ' . ($template->name ?? 'New Template'))

@push('styles')
<style>
    .svg-editor-container .svg-display-area {
        position: relative;
        min-height: 600px;
        background-color: #fff;
        overflow: auto;
        padding: 1.5rem;
    }

    .svg-editor-container .svg-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .svg-editor-container .svg-wrapper svg {
        display: block;
        width: 100%;
        max-width: 100%;
        height: auto;
        box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
        border-radius: 6px;
        background-color: #fff;
    }

    .svg-editor-container .svg-wrapper--normalized svg {
        max-width: clamp(320px, 100%, 960px);
    }

    .svg-editor-container svg text {
        fill: #000 !important;
        font-family: inherit;
        font-size: inherit;
    }

    .svg-editor-container svg {
        background-color: #fff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        SVG Template Editor
                        @if($template)
                            - {{ $template->name }}
                        @endif
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('staff.templates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Templates
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @php
                        $rawDesignData = ($template && $template->design)
                            ? (json_decode($template->design, true) ?: [])
                            : [];
                        $normalizeDesign = function ($value) {
                            return is_array($value) ? $value : null;
                        };
                        $frontDesignData = $normalizeDesign(
                            $rawDesignData['front']
                            ?? data_get($rawDesignData, 'front_design')
                            ?? data_get($rawDesignData, 'sides.front')
                        );
                        if (!$frontDesignData && !empty($rawDesignData)) {
                            $frontDesignData = $normalizeDesign($rawDesignData);
                        }
                        $backDesignData = $normalizeDesign(
                            $rawDesignData['back']
                            ?? data_get($rawDesignData, 'back_design')
                            ?? data_get($rawDesignData, 'sides.back')
                        );
                        $countElements = function ($data, $key) {
                            return is_array($data) ? count($data[$key] ?? []) : 0;
                        };
                        $frontTextCount = $countElements($frontDesignData, 'text_elements');
                        $frontImageCount = $countElements($frontDesignData, 'changeable_images');
                        $backTextCount = $countElements($backDesignData, 'text_elements');
                        $backImageCount = $countElements($backDesignData, 'changeable_images');
                    @endphp

                    @if($template && $template->design)

                        <div class="row">
                            <div class="col-md-8">
                                <div class="svg-editor-container">
                                    <div class="svg-editor-toolbar mb-3">
                                        @if($template->has_back_design)
                                            <div class="btn-group me-3" role="group" aria-label="Design side">
                                                <input type="radio" class="btn-check" name="design-side" id="front-side" autocomplete="off" checked>
                                                <label class="btn btn-outline-primary btn-sm" for="front-side">Front</label>
                                                <input type="radio" class="btn-check" name="design-side" id="back-side" autocomplete="off">
                                                <label class="btn btn-outline-primary btn-sm" for="back-side">Back</label>
                                            </div>
                                        @endif
                                        <button type="button" class="btn btn-primary btn-sm" id="save-svg-btn">
                                            <i class="fas fa-save me-1"></i>Save Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="reset-svg-btn">
                                            <i class="fas fa-undo me-1"></i>Reset
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" id="download-svg-btn">
                                            <i class="fas fa-download me-1"></i>Download SVG
                                        </button>
                                    </div>

                                    <div class="svg-display-area border rounded">
                                        <!-- Front Design -->
                                        <div class="svg-wrapper front-design" id="front-design">
                                            @if($template->svg_path)
                                                {!! Storage::disk('public')->get($template->svg_path) !!}
                                            @else
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-file-image fa-3x mb-3"></i>
                                                    <p>No front SVG content available</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Back Design -->
                                        @if($template->has_back_design)
                                        <div class="svg-wrapper back-design" id="back-design" style="display: none;">
                                            @if($template->back_svg_path)
                                                {!! Storage::disk('public')->get($template->back_svg_path) !!}
                                            @else
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-file-image fa-3x mb-3"></i>
                                                    <p>No back SVG content available</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="editor-info">
                                    <h5>Template Information</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{ $template->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type:</th>
                                            <td>{{ $template->product_type }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td>
                                                <span class="badge bg-{{ $template->status === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($template->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @if($frontDesignData)
                                        <tr>
                                            <th>Front Text Elements:</th>
                                            <td>{{ $frontTextCount }}</td>
                                        </tr>
                                        <tr>
                                            <th>Front Changeable Images:</th>
                                            <td>{{ $frontImageCount }}</td>
                                        </tr>
                                        @endif
                                        @if($backDesignData)
                                        <tr>
                                            <th>Back Text Elements:</th>
                                            <td>{{ $backTextCount }}</td>
                                        </tr>
                                        <tr>
                                            <th>Back Changeable Images:</th>
                                            <td>{{ $backImageCount }}</td>
                                        </tr>
                                        @endif
                                    </table>

                                    @if($frontImageCount)
                                    <h6 class="mt-4">Front Changeable Images</h6>
                                    <div class="changeable-images-list">
                                        @foreach($frontDesignData['changeable_images'] ?? [] as $image)
                                        @php $elementId = $image['id'] ?? null; @endphp
                                        <div class="changeable-image-item mb-2 p-2 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted d-block">{{ $elementId ?? 'Unknown ID' }}</small>
                                                    <span class="badge bg-info">{{ $image['type'] ?? 'image' }}</span>
                                                </div>
                                                <button type="button" class="btn btn-outline-primary btn-sm ms-2"
                                                    data-focus-side="front"
                                                    data-element-id="{{ $elementId }}"
                                                    @disabled(!$elementId)>Focus</button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif

                                    @if($backImageCount)
                                    <h6 class="mt-4">Back Changeable Images</h6>
                                    <div class="changeable-images-list">
                                        @foreach($backDesignData['changeable_images'] ?? [] as $image)
                                        @php $elementId = $image['id'] ?? null; @endphp
                                        <div class="changeable-image-item mb-2 p-2 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted d-block">{{ $elementId ?? 'Unknown ID' }}</small>
                                                    <span class="badge bg-info">{{ $image['type'] ?? 'image' }}</span>
                                                </div>
                                                <button type="button" class="btn btn-outline-primary btn-sm ms-2"
                                                    data-focus-side="back"
                                                    data-element-id="{{ $elementId }}"
                                                    @disabled(!$elementId)>Focus</button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif

                                    <div class="mt-4">
                                        <h6>Instructions</h6>
                                        <ul class="small text-muted">
                                            <li>Click on text elements to edit them</li>
                                            <li>Click on changeable images to replace them</li>
                                            <li>Changes are saved automatically when you click "Save Changes"</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>No Template Data Available</h5>
                            <p class="text-muted">This template doesn't have SVG design data to edit.</p>
                            <a href="{{ route('staff.templates.edit', $template->id ?? '') }}" class="btn btn-primary">
                                Edit Template Details
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.__inkwiseSvgTemplateEditorAutoInit = true;
    window.__inkwiseStaffEditorDesignData = @json([
        'front' => $frontDesignData,
        'back' => $backDesignData,
    ]);
</script>
@vite('resources/js/customer/studio/svg-template-editor.jsx')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const parseSvgDimension = (value) => {
        if (typeof value !== 'string') {
            return null;
        }
        const trimmed = value.trim();
        if (!trimmed.length) {
            return null;
        }
        const numeric = parseFloat(trimmed.replace(/[^0-9.\-]/g, ''));
        return Number.isFinite(numeric) ? numeric : null;
    };

    const normalizeSvgCanvas = (wrapper, svgElement) => {
        if (!svgElement) {
            return null;
        }

        const width = parseSvgDimension(svgElement.getAttribute('width'));
        const height = parseSvgDimension(svgElement.getAttribute('height'));

        if (!svgElement.hasAttribute('viewBox') && width && height) {
            svgElement.setAttribute('viewBox', `0 0 ${width} ${height}`);
        }

        svgElement.style.width = '100%';
        svgElement.style.height = 'auto';
        svgElement.style.maxWidth = '100%';
        svgElement.style.display = 'block';

        if (width && height) {
            svgElement.style.aspectRatio = `${width} / ${height}`;
            svgElement.dataset.canvasWidth = width;
            svgElement.dataset.canvasHeight = height;
            if (wrapper) {
                wrapper.style.aspectRatio = `${width} / ${height}`;
                wrapper.dataset.canvasWidth = width;
                wrapper.dataset.canvasHeight = height;
            }
        }

        if (wrapper) {
            wrapper.classList.add('svg-wrapper--normalized');
        }

        return { width, height };
    };

    const designPayload = window.__inkwiseStaffEditorDesignData || {};
    const frontWrapper = document.getElementById('front-design');
    const backWrapper = document.getElementById('back-design');
    const frontSvgElement = frontWrapper ? frontWrapper.querySelector('svg') : null;
    const backSvgElement = backWrapper ? backWrapper.querySelector('svg') : null;
    const state = {
        currentSide: 'front',
        editors: {
            front: null,
            back: null,
        },
    };

    const attachDesignData = (svgElement, payload) => {
        if (!svgElement) {
            return;
        }
        svgElement.setAttribute('data-svg-editor', 'true');
        if (!payload) {
            return;
        }
        try {
            const serialized = typeof payload === 'string' ? payload : JSON.stringify(payload);
            svgElement.dataset.svgData = serialized;
        } catch (error) {
            console.warn('SvgTemplateEditor: Failed to serialize design payload', error);
        }
    };

    normalizeSvgCanvas(frontWrapper, frontSvgElement);
    normalizeSvgCanvas(backWrapper, backSvgElement);

    attachDesignData(frontSvgElement, designPayload.front || null);
    attachDesignData(backSvgElement, designPayload.back || null);

    const instantiateEditor = (side) => {
        if (state.editors[side]) {
            return state.editors[side];
        }
        const svgElement = side === 'back' ? backSvgElement : frontSvgElement;
        if (!svgElement) {
            return null;
        }
        const readableSide = side === 'back' ? 'Back' : 'Front';
        const editor = new SvgTemplateEditor(svgElement, {
            onImageChange: function(element, imageUrl, file) {
                console.log(readableSide + ' image changed:', element, imageUrl, file);
                showNotification(readableSide + ' image replaced successfully', 'success');
            },
            onTextChange: function(element, oldText, newText) {
                console.log(readableSide + ' text changed:', oldText, '->', newText);
                showNotification(readableSide + ' text updated successfully', 'success');
            }
        });
        state.editors[side] = editor;
        return editor;
    };

    if (frontSvgElement) {
        instantiateEditor('front');
    }

    const switchSide = (targetSide) => {
        state.currentSide = targetSide;
        if (frontWrapper) {
            frontWrapper.style.display = targetSide === 'front' ? 'block' : 'none';
        }
        if (backWrapper) {
            backWrapper.style.display = targetSide === 'back' ? 'block' : 'none';
            if (targetSide === 'back') {
                instantiateEditor('back');
            }
        }
        updateSaveButton();
    };

    const updateSaveButton = () => {
        const saveBtn = document.getElementById('save-svg-btn');
        if (!saveBtn) {
            return;
        }
        const sideText = state.currentSide.charAt(0).toUpperCase() + state.currentSide.slice(1);
        saveBtn.innerHTML = `<i class="fas fa-save me-1"></i>Save ${sideText} Changes`;
    };

    const sideRadios = document.querySelectorAll('input[name="design-side"]');
    sideRadios.forEach((radio) => {
        radio.addEventListener('change', function() {
            if (!this.checked) {
                return;
            }
            const selectedSide = this.id.replace('-side', '');
            switchSide(selectedSide);
        });
    });

    updateSaveButton();

    const getCurrentEditor = () => {
        return state.currentSide === 'front' ? state.editors.front : state.editors.back;
    };

    const saveButton = document.getElementById('save-svg-btn');
    if (saveButton) {
        saveButton.addEventListener('click', async function() {
            const currentEditor = getCurrentEditor();
            if (!currentEditor) {
                showNotification('No SVG editor available for ' + state.currentSide + ' side', 'error');
                return;
            }

            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            saveButton.disabled = true;

            try {
                const result = await currentEditor.saveSvg({{ $template->id }}, state.currentSide);
                const sideText = state.currentSide.charAt(0).toUpperCase() + state.currentSide.slice(1);
                if (result.success) {
                    showNotification(sideText + ' design saved successfully', 'success');
                } else {
                    const errorMessage = result.error || 'Unknown error';
                    showNotification('Failed to save ' + state.currentSide + ' design: ' + errorMessage, 'error');
                }
            } catch (error) {
                showNotification('Error saving ' + state.currentSide + ' design: ' + error.message, 'error');
            } finally {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        });
    }

    const resetButton = document.getElementById('reset-svg-btn');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset all changes on the ' + state.currentSide + ' side? This will reload the original SVG.')) {
                location.reload();
            }
        });
    }

    const downloadButton = document.getElementById('download-svg-btn');
    if (downloadButton) {
        downloadButton.addEventListener('click', function() {
            const currentEditor = getCurrentEditor();
            if (!currentEditor) {
                showNotification('No SVG available to download for ' + state.currentSide + ' side', 'error');
                return;
            }

            const sideText = state.currentSide.charAt(0).toUpperCase() + state.currentSide.slice(1);
            const svgData = currentEditor.getSvgDataUrl();
            const link = document.createElement('a');
            link.href = svgData;
            link.download = '{{ $template->name ?? "template" }} - ' + sideText + '.svg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification(sideText + ' SVG downloaded successfully', 'success');
        });
    }

    const focusButtons = document.querySelectorAll('[data-focus-side][data-element-id]');
    focusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetSide = button.dataset.focusSide;
            const elementId = button.dataset.elementId;
            if (!targetSide || !elementId) {
                showNotification('This entry does not have a valid element reference.', 'error');
                return;
            }

            const runFocus = () => {
                const editor = instantiateEditor(targetSide);
                if (!editor) {
                    showNotification('Editor for ' + targetSide + ' side is not available yet.', 'error');
                    return;
                }

                const success = editor.focusElementById(elementId);
                if (!success) {
                    showNotification('Unable to find element #' + elementId + ' on the ' + targetSide + ' design.', 'error');
                } else {
                    const sideLabel = targetSide.charAt(0).toUpperCase() + targetSide.slice(1);
                    showNotification(sideLabel + ' element ' + elementId + ' highlighted.', 'success');
                }
            };

            if (targetSide !== state.currentSide) {
                switchSide(targetSide);
                setTimeout(runFocus, 100);
            } else {
                runFocus();
            }
        });
    });

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
});
</script>
@endsection
