@extends('layouts.admin')

@section('title', 'Manage Chatbot Q&A')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/chatbot.css') }}">

<div class="chatbot-container">
    <div class="header-row">
        <h2 class="mb-0">Chatbot Questions & Answers</h2>
        <input id="qaSearch" type="search" placeholder="Search questions or answers..." class="form-control search-input">
    </div>

    @if(session('success'))
        <div class="alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid">
        <form action="{{ route('admin.chatbot.store') }}" method="POST" class="card card-form" enctype="multipart/form-data">
            @csrf
            <h4 class="card-title">Add New Q&A</h4>
            <div class="form-group">
                <label>Question</label>
                <input type="text" name="question" class="form-control" value="{{ old('question') }}" required>
            </div>
            <div class="form-group">
                <label>Answer</label>
<div class="form-group input-with-icon">

    <textarea name="answer" class="form-control" rows="2" required></textarea>
    <button type="button" class="attach-btn" id="createImageTrigger" title="Attach image">
         <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" 
             stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
             viewBox="0 0 24 24">
            <path d="M21.44 11.05l-9.19 9.19a5.5 5.5 0 01-7.78-7.78l9.19-9.19a3.5 3.5 0 115 5l-9.2 9.2a1.5 1.5 0 01-2.12-2.12l8.49-8.49"/>
            <line x1="19" y1="6" x2="19" y2="12"/>
            <line x1="16" y1="9" x2="22" y2="9"/>
        </svg>
        <span class="sr-only">Attach image</span>
    </button>
</div>
                <input id="createAnswerImage" type="file" name="answer_image" accept="image/*" class="sr-only" aria-hidden="true">
                <div class="qa-image-feedback text-muted small" id="createImageFeedback">No image selected.</div>
                <div class="qa-image-preview-frame" id="createImagePreviewWrapper" style="display:none;">
                    <img id="createImagePreview" alt="New answer image preview" class="qa-image-preview" loading="lazy">
                    <button type="button" class="qa-image-remove" id="createImageClear" title="Remove image">
                        <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                        <span class="sr-only">Remove image</span>
                    </button>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">Add Q&A</button>
            </div>
        </form>

        <div class="card card-list">
            <h4 class="card-title">Existing Q&A</h4>

            <div class="table-responsive">
                <table class="table table-hover" id="qasTable">
                    <thead>
                        <tr>
                            <th class="col-question">Question</th>
                            <th>Answer</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($qas as $qa)
                            <tr class="qa-row"
                                data-id="{{ $qa->id }}"
                                data-question="{{ htmlentities($qa->question, ENT_QUOTES, 'UTF-8') }}"
                                data-answer="{{ htmlentities($qa->answer, ENT_QUOTES, 'UTF-8') }}"
                                data-image-url="{{ $qa->answer_image_path ? htmlentities(asset('storage/' . $qa->answer_image_path), ENT_QUOTES, 'UTF-8') : '' }}">
                                <td class="qa-question">{{ $qa->question }}</td>
                                <td class="qa-answer">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($qa->answer), 200) }}
                                    @if($qa->answer_image_path)
                                        <div class="qa-answer-media text-muted small" style="margin-top:6px; display:block;">📎 Image attached</div>
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
                        @endforeach
                        @if($qas->isEmpty())
                            <tr>
                                <td colspan="3" class="text-muted small">No Q&A found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Single reuseable Edit Modal -->
<div class="modal" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editForm" method="POST" action="#">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Q&A</h5>
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
                                <div class="form-group mt-3" id="editImagePreviewWrapper" style="display:none;">
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
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // search/filter rows
    const search = document.getElementById('qaSearch');
    const rows = Array.from(document.querySelectorAll('#qasTable tbody tr.qa-row'));
    search && search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        rows.forEach(r => {
            const question = (r.dataset.question || '').toLowerCase();
            const answer = (r.dataset.answer || '').toLowerCase();
            r.style.display = (question.includes(q) || answer.includes(q)) ? '' : 'none';
        });
    });

    // Create form elements
    const createImageInput = document.getElementById('createAnswerImage');
    const createImageTrigger = document.getElementById('createImageTrigger');
    const createImagePreviewWrapper = document.getElementById('createImagePreviewWrapper');
    const createImagePreview = document.getElementById('createImagePreview');
    const createImageClear = document.getElementById('createImageClear');
    const createImageFeedback = document.getElementById('createImageFeedback');

    // Modal elements
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
    const flashWrapper = document.querySelector('.chatbot-container');

    function showFlash(message, type = 'success') {
        if (!flashWrapper) return;
        let flashEl = flashWrapper.querySelector('.alert-dynamic');
        if (!flashEl) {
            flashEl = document.createElement('div');
            flashEl.className = 'alert-dynamic alert-success';
            flashEl.setAttribute('role', 'alert');
            flashWrapper.insertBefore(flashEl, flashWrapper.firstElementChild?.nextElementSibling || flashWrapper.firstChild);
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
            createImagePreviewWrapper.style.display = '';
        } else {
            createImagePreview.src = '';
            createImagePreviewWrapper.style.display = 'none';
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
            editImagePreviewWrapper.style.display = '';
        } else {
            editImagePreview.src = '';
            editImagePreviewWrapper.style.display = 'none';
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

    // edit button opens modal and populates fields
    const editButtons = document.querySelectorAll('.edit-qa');
    editButtons.forEach(btn => btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const row = document.querySelector('tr.qa-row[data-id="'+id+'"]');
    if (!row) return;

        // Decode HTML entities for dataset values
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

    // Cancel button click handler
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
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
                    showFlash('Q&A deleted successfully.');
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

    // Modal hidden event listener replacement (manual cleanup on any form submission)
    if (editModalEl) {
        editModalEl.addEventListener('submit', function () {
            hideModal(true);
        }, { capture: true });
    }

    // decode HTML entities for all rows on page load
    rows.forEach(r => {
        r.dataset.question = decodeHtml(r.dataset.question);
        r.dataset.answer = decodeHtml(r.dataset.answer);
        if (r.dataset.imageUrl) {
            r.dataset.imageUrl = decodeHtml(r.dataset.imageUrl);
        }
    });
});
</script>
@endsection

@endsection
