@extends('layouts.customerprofile')

@section('title', 'Rate Orders')

@push('styles')
<style>
    /* ========= GENERAL LAYOUT ========= */
    .rating-page {
        background: #fff;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .rating-page h2 {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #111827;
    }
    .rating-page p {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
    }

    /* ========= RATE CARD ========= */
    .rate-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #fafafa;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        transition: border 0.25s, box-shadow 0.25s;
    }
    .rate-card:hover {
        border-color: #f97316;
        box-shadow: 0 3px 12px rgba(249, 115, 22, 0.15);
    }

    .rate-card-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }
    .rate-card-header img {
        width: 90px;
        height: 90px;
        border-radius: 0.5rem;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #fff;
    }
    .rate-card-header .details {
        flex: 1;
    }
    .rate-card-header .details strong {
        font-size: 1rem;
        color: #111827;
        display: block;
    }
    .rate-card-header .details span {
        display: block;
        color: #6b7280;
        font-size: 0.85rem;
    }

    /* ========= STAR RATING ========= */
    .stars {
        display: flex;
        gap: 0.4rem;
        margin: 0.5rem 0 1rem;
    }
    .stars button {
        font-size: 2rem;
        background: none;
        border: none;
        cursor: pointer;
        transition: transform 0.2s ease, color 0.2s ease;
        color: #d1d5db;
    }
    .stars button:hover {
        transform: scale(1.2);
        color: #fbbf24;
    }
    .stars button.is-active {
        color: #f59e0b;
    }

    /* ========= REVIEW BOX ========= */
    .review-box textarea {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        resize: vertical;
        font-size: 0.95rem;
        background: #fff;
        transition: border 0.2s;
    }
    .review-box textarea:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.15);
    }

    /* ========= PHOTO UPLOAD ========= */
    .photo-upload {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .photo-upload label {
        width: 90px;
        height: 90px;
        display: grid;
        place-items: center;
        border: 1.5px dashed #d1d5db;
        border-radius: 0.5rem;
        background: #f9fafb;
        cursor: pointer;
        font-size: 1.75rem;
        color: #9ca3af;
        transition: all 0.2s;
    }
    .photo-upload label:hover {
        border-color: #f97316;
        color: #f97316;
        background: #fff7ed;
    }
    .photo-preview {
        position: relative;
        width: 90px;
        height: 90px;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .photo-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-preview button {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 50%;
        background: rgba(0,0,0,0.6);
        color: #fff;
        font-weight: bold;
        cursor: pointer;
        display: grid;
        place-items: center;
        line-height: 1;
    }

    /* ========= TOAST (upload limit alert) ========= */
    .toast {
        position: fixed;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        background: #f87171;
        color: #fff;
        padding: 0.75rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        z-index: 1000;
    }
    .toast.show {
        opacity: 1;
        pointer-events: auto;
    }

    /* ========= SUBMIT BUTTON ========= */
    .submit-row {
        margin-top: 1.2rem;
        display: flex;
        justify-content: flex-end;
    }
    .submit-row button {
        background: linear-gradient(90deg, #f97316, #fb923c);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.6rem 1.5rem;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .submit-row button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(249, 115, 22, 0.25);
    }
    .submit-row button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* ========= MOBILE ========= */
    @media (max-width: 640px) {
        .rate-card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .rate-card-header img {
            width: 100%;
            height: auto;
        }
        .submit-row {
            justify-content: center;
        }
        .submit-row button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="rating-page">
    <h2>Rate your completed templates</h2>
    <p>Share your honest feedback about the design, print quality, and your overall experience.</p>

    @php
        $ordersList = collect($orders ?? []);
    @endphp

    @if($ordersList->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No completed orders waiting for review.</p>
        </div>
    @else
        @foreach($ordersList as $order)
            @php
                $id = data_get($order, 'id');
                $name = data_get($order, 'product_name', 'Completed Template');
                $image = data_get($order, 'image', asset('images/placeholder.png'));
                $isRated = !empty(data_get($order, 'metadata.rating'));
                $ratingRecord = data_get($order, 'rating');
                $ratingValue = data_get($ratingRecord, 'rating', data_get($order, 'metadata.rating'));
                $isRated = is_numeric($ratingValue);
                $ratingReview = data_get($ratingRecord, 'review', data_get($order, 'metadata.review'));
                $ratingPhotos = collect(data_get($ratingRecord, 'photos', data_get($order, 'metadata.rating_photos', [])))->filter();
                $ratingSubmittedAt = data_get($ratingRecord, 'submitted_at', data_get($order, 'metadata.rating_submitted_at'));
            @endphp

            <div class="rate-card">
                <div class="rate-card-header">
                    <img src="{{ $image }}" alt="{{ $name }}">
                    <div class="details">
                        <strong>{{ $name }}</strong>
                        <span>Order #{{ data_get($order, 'order_number', $id) }}</span>
                        <span>Qty: {{ data_get($order, 'quantity', '—') }}</span>
                    </div>
                </div>

                @if($isRated)
                    @php
                        $ratingLabel = is_numeric($ratingValue)
                            ? rtrim(rtrim(number_format((float) $ratingValue, 1), '0'), '.')
                            : null;
                        $submittedDisplay = null;
                        if ($ratingSubmittedAt) {
                            try {
                                $submittedDisplay = \Illuminate\Support\Carbon::parse($ratingSubmittedAt)->format('M d, Y g:i A');
                            } catch (\Throwable $e) {
                                $submittedDisplay = $ratingSubmittedAt;
                            }
                        }
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-semibold text-gray-900">Rating:</span>
                            <span class="flex items-center" aria-label="{{ $ratingLabel }} out of 5 stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-2xl {{ $i <= (int) round($ratingValue) ? 'text-yellow-500' : 'text-gray-300' }}">★</span>
                                @endfor
                            </span>
                            <span class="text-gray-500">({{ $ratingLabel }} / 5)</span>
                        </div>
                        @if(filled($ratingReview))
                            <p class="mt-2 text-sm text-gray-700">“{{ $ratingReview }}”</p>
                        @endif
                        @if($ratingPhotos->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($ratingPhotos as $photo)
                                    @php
                                        $photoUrl = \Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
                                            ? $photo
                                            : \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
                                    @endphp
                                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener" class="block h-20 w-20 overflow-hidden rounded-lg border">
                                        <img src="{{ $photoUrl }}" alt="Rating photo" class="h-full w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        @if($submittedDisplay)
                            <p class="mt-3 text-xs text-gray-400">Submitted {{ $submittedDisplay }}</p>
                        @endif
                        <p class="mt-3 text-sm text-gray-600">You already rated this order. Thank you for your feedback!</p>
                    </div>
                @else
                <form method="POST" action="{{ route('customer.order-ratings.store') }}" enctype="multipart/form-data" data-rating-form>
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $id }}">
                    <input type="hidden" name="rating" value="" data-rating-input>

                    <!-- Stars -->
                    <div class="stars" data-stars>
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" data-star-value="{{ $i }}">★</button>
                        @endfor
                    </div>

                    <!-- Review Text -->
                    <div class="review-box">
                        <textarea name="review" rows="3" placeholder="Describe the quality, design, and your experience..." maxlength="600" data-rating-textarea></textarea>
                        <small class="text-gray-400 text-xs float-right mt-1" data-rating-charcount>0/600</small>
                    </div>

                    <!-- Photo Upload -->
                    <div class="photo-upload" data-photo-upload>
                        <label>
                            <input type="file" name="photos[]" accept="image/*" multiple hidden data-photo-input>
                            +
                        </label>
                    </div>

                    <div class="submit-row">
                        <button type="submit" disabled data-rating-submit>Submit Review</button>
                    </div>
                </form>
                @endif
            </div>
        @endforeach
    @endif
</div>

<!-- Toast Message -->
<div class="toast" id="toast-limit">You can only upload up to 5 photos.</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const toast = document.getElementById("toast-limit");

    const showToast = () => {
        toast.classList.add("show");
        setTimeout(() => toast.classList.remove("show"), 2500);
    };

    document.querySelectorAll("[data-rating-form]").forEach(form => {
        const stars = [...form.querySelectorAll("[data-star-value]")];
        const input = form.querySelector("[data-rating-input]");
        const submit = form.querySelector("[data-rating-submit]");
        const textarea = form.querySelector("[data-rating-textarea]");
        const count = form.querySelector("[data-rating-charcount]");
        const uploadContainer = form.querySelector("[data-photo-upload]");
        const uploadInput = form.querySelector("[data-photo-input]");
        const fileBuffer = new DataTransfer();

        const fileKey = (file) => `${file.name}-${file.size}-${file.lastModified}`;

        const removeBufferedFile = (key) => {
            for (let i = 0; i < fileBuffer.items.length; i++) {
                const bufferedFile = fileBuffer.items[i].getAsFile();
                if (bufferedFile && fileKey(bufferedFile) === key) {
                    fileBuffer.items.remove(i);
                    break;
                }
            }
            // Update input files after removal
            const dt = new DataTransfer();
            Array.from(fileBuffer.files).forEach(file => dt.items.add(file));
            uploadInput.files = dt.files;
        };

        const addPreview = (file) => {
            const key = fileKey(file);
            const reader = new FileReader();
            reader.onload = (ev) => {
                const preview = document.createElement("div");
                preview.className = "photo-preview";
                preview.dataset.fileKey = key;
                preview.innerHTML = `
                    <img src="${ev.target.result}" alt="Review Photo">
                    <button type="button" aria-label="Remove photo">&times;</button>
                `;
                preview.querySelector("button")?.addEventListener("click", () => {
                    removeBufferedFile(key);
                    preview.remove();
                });
                uploadContainer.insertBefore(preview, uploadContainer.querySelector("label"));
            };
            reader.readAsDataURL(file);
        };

        // Star selection
        stars.forEach(star => {
            star.addEventListener("click", () => {
                const val = Number(star.dataset.starValue);
                input.value = val;
                stars.forEach(s => s.classList.toggle("is-active", Number(s.dataset.starValue) <= val));
                submit.disabled = val < 1;
            });
        });

        // Character counter
        textarea?.addEventListener("input", () => {
            const len = textarea.value.length;
            count.textContent = `${len}/600`;
        });

        // Image upload preview with limit and persistent buffer
        uploadInput?.addEventListener("change", (e) => {
            const files = Array.from(e.target.files);
            const maxPhotos = 5;
            let added = 0;

            for (const file of files) {
                if (fileBuffer.items.length >= maxPhotos) {
                    showToast();
                    break;
                }

                const key = fileKey(file);
                const alreadyIncluded = Array.from(fileBuffer.files).some(
                    (buffered) => fileKey(buffered) === key
                );

                if (alreadyIncluded) {
                    continue;
                }

                fileBuffer.items.add(file);
                addPreview(file);
                added++;
            }

            if (added === 0 && fileBuffer.items.length >= maxPhotos) {
                showToast();
            }

            // Create a new DataTransfer with current files for proper form submission
            const dt = new DataTransfer();
            Array.from(fileBuffer.files).forEach(file => dt.items.add(file));
            uploadInput.files = dt.files;
        });

        // Handle form submission with proper file handling
        form.addEventListener("submit", (e) => {
            e.preventDefault();

            // Create FormData with all form data
            const formData = new FormData(form);

            // Clear photos from FormData and add them properly
            formData.delete('photos[]');
            Array.from(fileBuffer.files).forEach(file => {
                formData.append('photos[]', file);
            });

            // Submit via fetch
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Validation error');
                    });
                }
            })
            .then(data => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Submission error:', error);
                alert('Error submitting rating: ' + error.message);
            });
        });
    });
});
</script>
@endpush
