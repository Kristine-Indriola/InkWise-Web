@extends('layouts.staffapp')

@php
    $templateType = $template->product_type ?? 'Invitation';
    $productTypeMap = [
        'Invitation' => 'Invitation',
        'Giveaway' => 'Giveaway',
        'Envelope' => 'Envelope'
    ];
@endphp

@push('styles')
    @vite('resources/css/admin/template/template.css')
    <style>
        .create-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .preview-section {
            margin-top: 32px;
        }

        .preview-section__title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 6px;
            color: #0f172a;
        }

        .preview-section__help {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .template-preview-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            max-width: 100%;
        }

        .preview-card {
            position: relative;
            min-height: 130px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
            box-shadow: 0 12px 24px -12px rgba(15, 23, 42, 0.2);
            border: 1px solid rgba(148, 185, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-card--primary {
            /* Same size as other cards */
        }

        .preview-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-card__label {
            position: absolute;
            left: 16px;
            bottom: 16px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.78);
            color: #f8fafc;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
        }

        .preview-placeholder {
            padding: 28px;
            border: 1px dashed rgba(148, 163, 184, 0.7);
            border-radius: 14px;
            color: #64748b;
            text-align: center;
            background: rgba(248, 250, 252, 0.7);
        }

        @media (max-width: 1024px) {
            .template-preview-gallery {
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
                gap: 8px;
            }

            .preview-card {
                min-height: 120px;
            }
        }

        @media (max-width: 640px) {
            .preview-card {
                min-height: 110px;
            }

            .template-preview-gallery {
                grid-template-columns: minmax(0, 1fr);
                gap: 6px;
            }
        }
    </style>
@endpush

@push('scripts')
    @vite('resources/js/admin/template/template.js')
    <script>
        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute) {
                const value = meta.getAttribute('content');
                if (value) {
                    return value;
                }
            }
            const hidden = document.querySelector('input[name="_token"]');
            return hidden ? hidden.value : '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.querySelector('.edit-form');
            if (!editForm) {
                return;
            }

            editForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const formData = new FormData(editForm);
                if (!formData.get('name')) {
                    alert('Please provide a name.');
                    return;
                }

                fetch(editForm.getAttribute('action'), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(async (res) => {
                        if (!res.ok) {
                            let message = 'Update failed';
                            try {
                                const data = await res.json();
                                message = data?.message || JSON.stringify(data);
                            } catch (err) {
                                const text = await res.text();
                                if (text) {
                                    message = text;
                                }
                            }
                            throw new Error(message);
                        }
                        return res.json();
                    })
                    .then((json) => {
                        if (json && json.success) {
                            alert('Updated successfully');
                            window.location = json.redirect || '{{ route("staff.templates.index") }}';
                        } else {
                            alert('Update succeeded but server response unexpected.');
                        }
                    })
                    .catch((err) => {
                        console.error(err);
                        alert('Update failed: ' + (err.message || 'Unknown'));
                    });
            });
        });
    </script>
@endpush

@section('content')
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="edit-template-heading">
        <div>
            <h2 id="edit-template-heading">Edit {{ $templateType }} Template</h2>
            <p class="edit-subtitle">Update the details for this {{ strtolower($templateType) }} template</p>
        </div>

        <form action="{{ route('staff.templates.update', $template->id) }}" method="POST" class="edit-form">
            @csrf
            @method('PUT')

            @php
                $designValue = old('design', $template->design);
                if (is_array($designValue)) {
                    $designValue = json_encode($designValue);
                }
                if ($designValue === null || $designValue === '') {
                    $designValue = '{}';
                }
            @endphp
            <input type="hidden" name="design" id="design" value="{{ $designValue }}">

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name *</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" value="{{ $template->name }}" required>
                </div>
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding" {{ $template->event_type === 'Wedding' ? 'selected' : '' }}>Wedding</option>
                        <option value="Birthday" {{ $template->event_type === 'Birthday' ? 'selected' : '' }}>Birthday</option>
                        <option value="Baptism" {{ $template->event_type === 'Baptism' ? 'selected' : '' }}>Baptism</option>
                        <option value="Corporate" {{ $template->event_type === 'Corporate' ? 'selected' : '' }}>Corporate</option>
                        <option value="Other" {{ $template->event_type === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display">{{ $templateType }}</span>
                        <input type="hidden" id="product_type" name="product_type" value="{{ $templateType }}" required>
                    </div>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme/Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral" value="{{ $template->theme_style }}">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the template design, style, and intended use...">{{ $template->description }}</textarea>
            </div>

            @php
                $rawMetadata = $template->metadata;
                if (is_string($rawMetadata)) {
                    $decodedMetadata = json_decode($rawMetadata, true);
                    $metadataArray = is_array($decodedMetadata) ? $decodedMetadata : [];
                } elseif (is_array($rawMetadata)) {
                    $metadataArray = $rawMetadata;
                } elseif ($rawMetadata instanceof \Illuminate\Support\Collection) {
                    $metadataArray = $rawMetadata->toArray();
                } else {
                    $metadataArray = (array) $rawMetadata;
                }

                $storedPreviews = isset($metadataArray['previews']) && is_array($metadataArray['previews'])
                    ? $metadataArray['previews']
                    : [];
                $previewMeta = isset($metadataArray['preview_images_meta']) && is_array($metadataArray['preview_images_meta'])
                    ? $metadataArray['preview_images_meta']
                    : [];
                
                // Fallback to preview_front and preview_back columns if metadata previews don't have them
                if (!isset($storedPreviews['front']) && $template->preview_front) {
                    $storedPreviews['front'] = $template->preview_front;
                    if (!isset($previewMeta['front'])) {
                        $previewMeta['front'] = ['label' => 'Front Side', 'order' => 0];
                    }
                }
                if (!isset($storedPreviews['back']) && $template->preview_back) {
                    $storedPreviews['back'] = $template->preview_back;
                    if (!isset($previewMeta['back'])) {
                        $previewMeta['back'] = ['label' => 'Back Side', 'order' => 1];
                    }
                }

                $previewEntries = [];
                $position = 0;
                foreach ($storedPreviews as $key => $path) {
                    if (!$path) {
                        continue;
                    }
                    $meta = $previewMeta[$key] ?? [];
                    $label = $meta['label'] ?? null;
                    if (!is_string($label) || trim($label) === '') {
                        $label = \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', $key));
                    } else {
                        $label = trim($label);
                    }
                    $order = array_key_exists('order', $meta) ? (int) $meta['order'] : $position;
                    $previewEntries[] = [
                        'key' => $key,
                        'path' => $path,
                        'label' => $label,
                        'order' => $order,
                    ];
                    $position++;
                }

                $resolvePreviewUrl = function ($path) {
                    if (!$path) {
                        return null;
                    }
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        return $path;
                    }
                    try {
                        return \App\Support\ImageResolver::url($path);
                    } catch (\Throwable $e) {
                        $normalized = ltrim($path, '/');
                        if (\Illuminate\Support\Str::startsWith($normalized, 'storage/')) {
                            return asset($normalized);
                        }
                        return asset('storage/' . $normalized);
                    }
                };

                usort($previewEntries, function ($a, $b) {
                    if ($a['order'] === $b['order']) {
                        return strcmp($a['key'], $b['key']);
                    }
                    return $a['order'] <=> $b['order'];
                });

                $preparedPreviews = [];
                foreach ($previewEntries as $entry) {
                    $url = $resolvePreviewUrl($entry['path']);
                    if (!$url) {
                        continue;
                    }
                    $preparedPreviews[] = [
                        'key' => $entry['key'],
                        'url' => $url,
                        'label' => $entry['label'],
                    ];
                }
            @endphp

            <div class="preview-section" aria-live="polite">
                <h3 class="preview-section__title">Saved Template Preview</h3>
                <p class="preview-section__help">These images reflect the last saved layout from the template builder.</p>
                @if(count($preparedPreviews))
                    <div class="template-preview-gallery">
                        @foreach($preparedPreviews as $index => $preview)
                            <figure class="preview-card{{ $index === 0 ? ' preview-card--primary' : '' }}" role="group" aria-label="{{ $preview['label'] }}">
                                <img src="{{ $preview['url'] }}" alt="{{ $preview['label'] }}">
                                <figcaption class="preview-card__label">{{ $preview['label'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <div class="preview-placeholder">
                        No saved preview found. Open the template in the editor and save it to generate preview images.
                    </div>
                @endif
            </div>

            <div class="create-actions">
                <a href="{{ route('staff.templates.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Update Template</button>
            </div>
        </form>
    </section>
</main>
@endsection
