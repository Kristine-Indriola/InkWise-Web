@extends('layouts.admin')

@section('title', 'Manage FAQ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/chatbot.css') }}">
@endpush

@section('content')
@php
    $totalEntries = $qas->count();
    $entriesWithImage = $qas->whereNotNull('answer_image_path')->count();
    $lastUpdated = $qas->max('updated_at');
@endphp

<main class="admin-page-shell chatbot-page" role="main">
    @if(session('success'))
        <div class="dashboard-alert" role="alert" aria-live="polite">
            {{ session('success') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">FAQ Management</h1>
            <p class="page-subtitle">Manage frequently asked questions to help customers find answers quickly.</p>
        </div>
        <a href="#chatbotForm" class="btn btn-primary" data-scroll-to="chatbotForm">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            <span>Add FAQ</span>
        </a>
    </header>

    <section class="summary-grid chatbot-summary" aria-label="Chatbot highlights">
        <article class="summary-card summary-card--knowledge">
            <div class="summary-card-header">
                <span class="summary-card-label">Total FAQs</span>
                <span class="summary-card-chip accent">FAQs</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value" data-summary="total" data-total="{{ $totalEntries }}">{{ number_format($totalEntries) }}</span>
                <span class="summary-card-icon" aria-hidden="true"><i class="fi fi-rr-book"></i></span>
            </div>
            <span class="summary-card-meta" data-summary-meta="total">FAQs available</span>
        </article>
        <article class="summary-card summary-card--media">
            <div class="summary-card-header">
                <span class="summary-card-label">FAQs with Media</span>
                <span class="summary-card-chip accent">Enhanced FAQs</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value" data-summary="media" data-total-media="{{ $entriesWithImage }}">{{ number_format($entriesWithImage) }}</span>
                <span class="summary-card-icon" aria-hidden="true"><i class="fi fi-rr-picture"></i></span>
            </div>
            <span class="summary-card-meta" data-summary-meta="media">FAQs with images</span>
        </article>
        <article class="summary-card summary-card--updated">
            <div class="summary-card-header">
                <span class="summary-card-label">Last Updated</span>
                <span class="summary-card-chip accent">Maintenance</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value" data-summary="updated" data-initial-updated="{{ $lastUpdated ? e(optional($lastUpdated)->diffForHumans()) : '—' }}">{{ $lastUpdated ? optional($lastUpdated)->diffForHumans() : '—' }}</span>
                <span class="summary-card-icon" aria-hidden="true"><i class="fi fi-rr-refresh"></i></span>
            </div>
            <span class="summary-card-meta">Most recent update</span>
        </article>
    </section>

    <section class="materials-toolbar chatbot-toolbar" aria-label="Chatbot utilities">
        <div class="materials-toolbar__search chatbot-search">
            <div class="search-input">
                <span class="search-icon">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input
                    id="qaSearch"
                    type="search"
                    class="form-control"
                    placeholder="Search FAQs..."
                    aria-label="Search FAQs"
                >
            </div>
        </div>
        <p class="chatbot-toolbar__hint" id="chatbotEntriesHint" data-total="{{ $totalEntries }}">
            <strong>{{ number_format($totalEntries) }}</strong> FAQs available.
        </p>
    </section>

    <div id="chatbotResultsLive" class="sr-only" role="status" aria-live="polite"></div>

    <div class="chatbot-grid">
        <section id="chatbotForm" class="chatbot-card chatbot-card--form" tabindex="-1" aria-labelledby="chatbotFormTitle">
            <h2 id="chatbotFormTitle" class="card-title">Add New FAQ</h2>
            <form action="{{ route('admin.chatbot.store') }}" method="POST" class="chatbot-form" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="createQuestion">Question</label>
                    <input id="createQuestion" type="text" name="question" class="form-control" value="{{ old('question') }}" required>
                </div>
                <div class="form-group">
                    <label for="createAnswer">Answer</label>
                    <div class="input-with-icon">
                        <textarea id="createAnswer" name="answer" class="form-control" rows="2" required></textarea>
                        <button type="button" class="attach-btn" id="createImageTrigger" title="Attach image">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M21.44 11.05l-9.19 9.19a5.5 5.5 0 01-7.78-7.78l9.19-9.19a3.5 3.5 0 115 5l-9.2 9.2a1.5 1.5 0 01-2.12-2.12l8.49-8.49" />
                                <line x1="19" y1="6" x2="19" y2="12" />
                                <line x1="16" y1="9" x2="22" y2="9" />
                            </svg>
                            <span class="sr-only">Attach image</span>
                        </button>
                    </div>
                    <input id="createAnswerImage" type="file" name="answer_image" accept="image/*" class="sr-only" aria-hidden="true">
                    <div class="qa-image-feedback text-muted small" id="createImageFeedback">No image selected.</div>
                    <div class="qa-image-preview-frame" id="createImagePreviewWrapper" hidden>
                        <img id="createImagePreview" alt="New answer image preview" class="qa-image-preview" loading="lazy">
                        <button type="button" class="qa-image-remove" id="createImageClear" title="Remove image">
                            <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                            <span class="sr-only">Remove image</span>
                        </button>
                    </div>
                </div>
                <div class="chatbot-form__actions">
                    <button type="submit" class="btn btn-primary">Add FAQ</button>
                </div>
            </form>
        </section>

        <section class="chatbot-card chatbot-card--list" tabindex="-1" aria-labelledby="chatbotListTitle">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="chatbotListTitle">Existing FAQs</h2>
                    <p class="section-subtitle">Edit answers or delete FAQs that are no longer relevant.</p>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table table-hover" id="qasTable" role="grid">
                    <thead>
                        <tr>
                            <th scope="col" class="col-question">Question</th>
                            <th scope="col">Answer</th>
                            <th scope="col" class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($qas as $qa)
                            <tr class="qa-row"
                                data-id="{{ $qa->id }}"
                                data-question="{{ htmlentities($qa->question, ENT_QUOTES, 'UTF-8') }}"
                                data-answer="{{ htmlentities($qa->answer, ENT_QUOTES, 'UTF-8') }}"
                                data-image-url="{{ $qa->answer_image_path ? htmlentities(asset('storage/' . $qa->answer_image_path), ENT_QUOTES, 'UTF-8') : '' }}"
                                data-has-image="{{ $qa->answer_image_path ? 'true' : 'false' }}"
                                data-updated="{{ optional($qa->updated_at)->toIso8601String() }}"
                                data-updated-human="{{ optional($qa->updated_at)->diffForHumans() }}">
                                <td class="qa-question">{{ $qa->question }}</td>
                                <td class="qa-answer">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($qa->answer), 200) }}
                                    @if($qa->answer_image_path)
                                        <div class="qa-answer-media text-muted small">📎 Image attached</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning edit-qa" title="Edit" data-id="{{ $qa->id }}">Edit</button>

                                    <form action="{{ route('admin.chatbot.destroy', $qa->id) }}" method="POST" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted small">No FAQs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
@endsection

<!-- Single reusable Edit Modal -->
<div class="modal" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editForm" method="POST" action="#" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit FAQ</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="editQuestion">Question</label>
            <input id="editQuestion" name="question" type="text" class="form-control" required>
          </div>
          <div class="form-group mt-3">
            <label for="editAnswer">Answer</label>
            <div class="input-with-icon">
                <textarea id="editAnswer" name="answer" class="form-control" rows="6" required></textarea>
                <button type="button" class="attach-btn" id="editImageTrigger" title="Attach image">
                    <i class="fi fi-rr-paperclip" aria-hidden="true"></i>
                    <span class="sr-only">Attach image</span>
                </button>
            </div>
            <input id="editImage" name="answer_image" type="file" accept="image/*" class="sr-only" aria-hidden="true">
            <div class="qa-image-feedback text-muted small" id="editImageFeedback">No image selected. Uploading a new file replaces the existing image.</div>
          </div>
          <div class="form-group mt-3" id="editImagePreviewWrapper" hidden>
            <label style="display:block; margin-bottom:8px;">Attached Image</label>
            <div class="qa-image-preview-frame">
                <img id="editImagePreview" alt="Answer image preview" class="qa-image-preview" loading="lazy">
                <button type="button" class="qa-image-remove" id="removeImageBtn" title="Remove image">
                    <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                    <span class="sr-only">Remove image</span>
                </button>
            </div>
          </div>
          <input type="hidden" id="removeImage" name="remove_image" value="0">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save FAQ</button>
          <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('qaSearch');
    const rows = Array.from(document.querySelectorAll('#qasTable tbody tr.qa-row'));
    const summaryTotalValue = document.querySelector('[data-summary="total"]');
    const summaryMediaValue = document.querySelector('[data-summary="media"]');
    const summaryUpdatedValue = document.querySelector('[data-summary="updated"]');
    const summaryTotalMeta = document.querySelector('[data-summary-meta="total"]');
    const summaryMediaMeta = document.querySelector('[data-summary-meta="media"]');
    const entriesHint = document.getElementById('chatbotEntriesHint');
    const liveRegion = document.getElementById('chatbotResultsLive');
    const createImageInput = document.getElementById('createAnswerImage');
    const createImageTrigger = document.getElementById('createImageTrigger');
    const createImagePreviewWrapper = document.getElementById('createImagePreviewWrapper');
    const createImagePreview = document.getElementById('createImagePreview');
    const createImageClear = document.getElementById('createImageClear');
    const createImageFeedback = document.getElementById('createImageFeedback');

    const editModalEl = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    const editQuestion = document.getElementById('editQuestion');
    const editAnswer = document.getElementById('editAnswer');
    const cancelBtn = document.getElementById('cancelBtn');
    const closeBtn = editModalEl ? editModalEl.querySelector('.btn-close') : null;
    const editImageInput = document.getElementById('editImage');
    const editImagePreviewWrapper = document.getElementById('editImagePreviewWrapper');
    const editImagePreview = document.getElementById('editImagePreview');
    const editImageTrigger = document.getElementById('editImageTrigger');
    const editImageFeedback = document.getElementById('editImageFeedback');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const removeImageInput = document.getElementById('removeImage');
    const deleteForms = document.querySelectorAll('.inline-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const flashWrapper = document.querySelector('.chatbot-page');

    function showFlash(message, type = 'success') {
        if (!flashWrapper) return;
        let flashEl = flashWrapper.querySelector('.alert-dynamic');
        if (!flashEl) {
            flashEl = document.createElement('div');
            flashEl.className = 'alert-dynamic alert-success';
            flashEl.setAttribute('role', 'alert');
            const header = flashWrapper.querySelector('.page-header');
            if (header && header.parentNode === flashWrapper) {
                flashWrapper.insertBefore(flashEl, header.nextSibling);
            } else {
                flashWrapper.insertBefore(flashEl, flashWrapper.firstChild);
            }
        }
        flashEl.textContent = message;
        flashEl.className = `alert-dynamic ${type === 'error' ? 'alert-danger' : 'alert-success'}`;
    }

    const updateUrlTemplate = @json(route('admin.chatbot.update', ['qa' => '__ID__']));
    const MODAL_FADE_MS = 160;
    let modalVisible = false;
    let hideTimer = null;
    const domParser = typeof DOMParser !== 'undefined' ? new DOMParser() : null;
    let createTempPreviewUrl = null;
    let tempPreviewUrl = null;
    let originalImageUrl = '';
    const defaultCreateFeedback = createImageFeedback ? createImageFeedback.textContent.trim() : '';
    const defaultEditFeedback = editImageFeedback ? editImageFeedback.textContent.trim() : '';

    const decodeHtml = (value) => {
        if (!value) return '';
        if (!domParser) return value;
        try {
            return domParser.parseFromString(value, 'text/html').body.textContent || value;
        } catch (err) {
            console.warn('Unable to decode HTML value', err);
            return value;
        }
    };

    const buildUpdateUrl = (id) => updateUrlTemplate.replace('__ID__', encodeURIComponent(id));

    const numberFormatter = new Intl.NumberFormat();

    function getVisibleRows() {
        return rows.filter(row => row.style.display !== 'none');
    }

    function updateSummary() {
        const visibleRows = getVisibleRows();
        const totalCount = rows.length;
        const visibleCount = visibleRows.length;
        const totalMediaCount = rows.filter(row => row.dataset.hasImage === 'true').length;
        const visibleMediaCount = visibleRows.filter(row => row.dataset.hasImage === 'true').length;

        if (summaryTotalValue) {
            summaryTotalValue.textContent = numberFormatter.format(visibleCount);
            summaryTotalValue.dataset.total = totalCount;
        }

        if (summaryTotalMeta) {
            const metaText = visibleCount === totalCount
                ? 'FAQs available'
                : `Showing ${numberFormatter.format(visibleCount)} of ${numberFormatter.format(totalCount)} FAQs`;
            summaryTotalMeta.textContent = metaText;
        }

        if (summaryMediaValue) {
            summaryMediaValue.textContent = numberFormatter.format(visibleMediaCount);
            summaryMediaValue.dataset.totalMedia = totalMediaCount;
        }

        if (summaryMediaMeta) {
            const metaText = visibleCount === totalCount
                ? 'FAQs with images'
                : `Showing ${numberFormatter.format(visibleMediaCount)} with media (of ${numberFormatter.format(totalMediaCount)})`;
            summaryMediaMeta.textContent = metaText;
        }

        if (summaryUpdatedValue) {
            let latestRow = null;
            let latestDateValue = null;
            visibleRows.forEach(row => {
                const updatedIso = row.dataset.updated;
                if (!updatedIso) {
                    return;
                }
                const parsedDate = new Date(updatedIso);
                if (!Number.isNaN(parsedDate.valueOf()) && (!latestDateValue || parsedDate > latestDateValue)) {
                    latestDateValue = parsedDate;
                    latestRow = row;
                }
            });

            if (latestRow) {
                summaryUpdatedValue.textContent = latestRow.dataset.updatedHuman || latestRow.dataset.updated || latestDateValue.toLocaleString();
            } else {
                summaryUpdatedValue.textContent = summaryUpdatedValue.dataset.initialUpdated || '—';
            }
        }

        if (entriesHint) {
            const totalDisplay = numberFormatter.format(totalCount);
            if (visibleCount === totalCount) {
                entriesHint.innerHTML = `<strong>${totalDisplay}</strong> FAQs available.`;
            } else {
                entriesHint.innerHTML = `Showing <strong>${numberFormatter.format(visibleCount)}</strong> of <strong>${totalDisplay}</strong> FAQs.`;
            }
            entriesHint.dataset.total = totalCount;
        }

        if (liveRegion) {
            if (visibleCount === totalCount) {
                liveRegion.textContent = `${visibleCount} FAQs shown.`;
            } else {
                liveRegion.textContent = `${visibleCount} of ${totalCount} FAQs shown.`;
            }
        }
    }

    function revokeCreateTempUrl() {
        if (createTempPreviewUrl) {
            try {
                URL.revokeObjectURL(createTempPreviewUrl);
            } catch (err) {
                console.warn('Unable to revoke temp create image URL', err);
            }
            createTempPreviewUrl = null;
        }
    }

    function setCreateImagePreview(url = '', { temporary = false } = {}) {
        if (!createImagePreviewWrapper || !createImagePreview) return;

        if (temporary) {
            if (createTempPreviewUrl && createTempPreviewUrl !== url) {
                revokeCreateTempUrl();
            }
            createTempPreviewUrl = url;
        } else {
            revokeCreateTempUrl();
        }

        if (url) {
            createImagePreview.src = url;
            createImagePreviewWrapper.hidden = false;
        } else {
            createImagePreview.src = '';
            createImagePreviewWrapper.hidden = true;
        }
    }

    function setCreateFeedback(message) {
        if (createImageFeedback) {
            createImageFeedback.textContent = message;
        }
    }

    function revokeEditTempUrl() {
        if (tempPreviewUrl) {
            try {
                URL.revokeObjectURL(tempPreviewUrl);
            } catch (err) {
                console.warn('Unable to revoke temp image URL', err);
            }
            tempPreviewUrl = null;
        }
    }

    function setImagePreview(url = '', { temporary = false } = {}) {
        if (!editImagePreviewWrapper || !editImagePreview) return;

        if (temporary) {
            if (tempPreviewUrl && tempPreviewUrl !== url) {
                revokeEditTempUrl();
            }
            tempPreviewUrl = url;
        } else {
            revokeEditTempUrl();
        }

        if (url) {
            editImagePreview.src = url;
            editImagePreviewWrapper.hidden = false;
        } else {
            editImagePreview.src = '';
            editImagePreviewWrapper.hidden = true;
        }

        if (removeImageBtn) {
            removeImageBtn.style.display = url ? '' : 'none';
        }
    }

    function setEditFeedback(message) {
        if (editImageFeedback) {
            editImageFeedback.textContent = message;
        }
    }

    function resetEditForm() {
        if (editForm) {
            editForm.reset();
            editForm.action = '#';
        }
        if (editQuestion) editQuestion.value = '';
        if (editAnswer) editAnswer.value = '';
        if (editImageInput) editImageInput.value = '';
        if (removeImageInput) removeImageInput.value = '0';
        originalImageUrl = '';
        setImagePreview('');
        setEditFeedback(defaultEditFeedback);
    }

    function hideModal(immediate = false) {
        if (!editModalEl) {
            resetEditForm();
            return;
        }
        if (!modalVisible && !immediate) {
            resetEditForm();
            return;
        }
        if (hideTimer) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        editModalEl.classList.remove('show');
        editModalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        const delay = immediate ? 0 : MODAL_FADE_MS;
        hideTimer = setTimeout(() => {
            editModalEl.style.display = 'none';
            modalVisible = false;
            resetEditForm();
            hideTimer = null;
        }, delay);
    }

    function showModal() {
        if (!editModalEl) return;
        if (hideTimer) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        editModalEl.style.display = 'flex';
        editModalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        requestAnimationFrame(() => {
            editModalEl.classList.add('show');
            modalVisible = true;
        });
    }

    if (editModalEl) {
        editModalEl.setAttribute('aria-hidden', 'true');
        editModalEl.classList.remove('show');
        editModalEl.style.display = 'none';
    }

    const editButtons = document.querySelectorAll('.edit-qa');
    editButtons.forEach(btn => btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const row = document.querySelector('tr.qa-row[data-id="' + id + '"]');
        if (!row) return;

        const question = decodeHtml(row.dataset.question);
        const answer = decodeHtml(row.dataset.answer);
        const imageUrl = decodeHtml(row.dataset.imageUrl || '');

        if (editQuestion) editQuestion.value = question;
        if (editAnswer) editAnswer.value = answer;

        if (editForm) {
            editForm.action = buildUpdateUrl(id);
        }

        originalImageUrl = imageUrl;
        if (removeImageInput) removeImageInput.value = '0';
        if (editImageInput) editImageInput.value = '';
        setImagePreview(originalImageUrl);
        setEditFeedback(originalImageUrl ? 'Current image attached.' : defaultEditFeedback);

        showModal();
    }));

    if (createImageTrigger && createImageInput) {
        createImageTrigger.addEventListener('click', function () {
            createImageInput.click();
        });
    }

    if (createImageInput) {
        createImageInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) {
                setCreateImagePreview('');
                setCreateFeedback(defaultCreateFeedback);
                return;
            }

            const file = this.files[0];
            const fileUrl = URL.createObjectURL(file);
            setCreateImagePreview(fileUrl, { temporary: true });
            setCreateFeedback(file.name);
        });
    }

    if (createImageClear) {
        createImageClear.addEventListener('click', function (event) {
            event.preventDefault();
            if (createImageInput) createImageInput.value = '';
            setCreateImagePreview('');
            setCreateFeedback(defaultCreateFeedback);
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault();
            hideModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            hideModal();
        });
    }

    if (editModalEl) {
        editModalEl.addEventListener('click', function (event) {
            if (event.target === editModalEl) {
                hideModal();
            }
        });
    }

    if (editImageTrigger && editImageInput) {
        editImageTrigger.addEventListener('click', function () {
            editImageInput.click();
        });
    }

    if (editImageInput) {
        editImageInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) {
                setImagePreview(originalImageUrl);
                setEditFeedback(originalImageUrl ? 'Current image attached.' : defaultEditFeedback);
                if (removeImageInput) removeImageInput.value = '0';
                return;
            }

            const file = this.files[0];
            const fileUrl = URL.createObjectURL(file);
            setImagePreview(fileUrl, { temporary: true });
            setEditFeedback(file.name);
            if (removeImageInput) removeImageInput.value = '0';
        });
    }

    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function (event) {
            event.preventDefault();
            const hasNewFile = editImageInput && editImageInput.files && editImageInput.files.length > 0;

            if (hasNewFile) {
                editImageInput.value = '';
                setImagePreview(originalImageUrl);
                setEditFeedback(originalImageUrl ? 'Current image attached.' : defaultEditFeedback);
                if (removeImageInput) removeImageInput.value = '0';
                return;
            }

            if (originalImageUrl) {
                setImagePreview('');
                setEditFeedback('Image will be removed.');
                if (removeImageInput) removeImageInput.value = '1';
                originalImageUrl = '';
            } else {
                setImagePreview('');
                setEditFeedback(defaultEditFeedback);
                if (removeImageInput) removeImageInput.value = '0';
            }
        });
    }

    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!confirm('Delete this Q&A?')) {
                return;
            }

            const row = form.closest('tr.qa-row');
            if (!row) {
                form.submit();
                return;
            }

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData,
            }).then(response => {
                if (response.ok) {
                    row.remove();
                    const index = rows.indexOf(row);
                    if (index > -1) {
                        rows.splice(index, 1);
                    }
                    updateSummary();
                    showFlash('FAQ deleted successfully.');
                } else {
                    throw new Error('Failed to delete');
                }
            }).catch(error => {
                console.error('Delete error:', error);
                form.submit();
            });
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalVisible) {
            event.preventDefault();
            hideModal();
        }
    });

    hideModal(true);

    document.querySelectorAll('[data-scroll-to]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            const targetId = this.getAttribute('data-scroll-to');
            if (!targetId) {
                return;
            }
            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (typeof target.focus === 'function') {
                target.focus({ preventScroll: true });
            }
        });
    });

    if (search) {
        search.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            rows.forEach(r => {
                const question = (r.dataset.question || '').toLowerCase();
                const answer = (r.dataset.answer || '').toLowerCase();
                r.style.display = (question.includes(q) || answer.includes(q)) ? '' : 'none';
            });
            updateSummary();
        });
    }

    rows.forEach(r => {
        r.dataset.question = decodeHtml(r.dataset.question);
        r.dataset.answer = decodeHtml(r.dataset.answer);
        if (r.dataset.imageUrl) {
            r.dataset.imageUrl = decodeHtml(r.dataset.imageUrl);
        }
    });

    updateSummary();
});
</script>
@endsection
