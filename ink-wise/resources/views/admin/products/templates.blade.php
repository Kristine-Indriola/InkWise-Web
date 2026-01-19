{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\templates.blade.php --}}
{{-- Page 1: Templates --}}
@php
    $templatesCollection = $templates instanceof \Illuminate\Support\Collection ? $templates : collect($templates);

    // Determine which templates to show based on current route
    $currentRoute = request()->route()->getName();
    $showOnlyType = null;

    if (str_contains($currentRoute, 'invitation')) {
        $showOnlyType = 'invitation';
    } elseif (str_contains($currentRoute, 'giveaway')) {
        $showOnlyType = 'giveaway';
    } elseif (str_contains($currentRoute, 'envelope')) {
        $showOnlyType = 'envelope';
    }

    if ($showOnlyType) {
        $filteredTemplates = $templatesCollection->filter(function($template) use ($showOnlyType) {
            return strtolower($template->product_type ?? '') === strtolower($showOnlyType);
        });

        if ($filteredTemplates->isEmpty()) {
            $filteredTemplates = $templatesCollection;
        }
    } else {
        // Fallback to showing all templates if route detection fails
        $invitationTemplates = $templatesCollection->filter(function($template) {
            return strtolower($template->product_type ?? '') === 'invitation';
        });
        $giveawayTemplates = $templatesCollection->filter(function($template) {
            return strtolower($template->product_type ?? '') === 'giveaway';
        });
        $otherTemplates = $templatesCollection->filter(function($template) {
            $type = strtolower($template->product_type ?? '');
            return $type !== 'invitation' && $type !== 'giveaway';
        });
    }
@endphp

<div class="page page1 active-page" data-page="0">
    <div class="templates-hero">
        <div>
            @if($showOnlyType === 'invitation')
                <h2 class="templates-title">Choose an invitation template</h2>
                <p class="templates-subtitle">Browse curated invitation templates. Pick one to pre-fill your invitation details instantly.</p>
            @elseif($showOnlyType === 'giveaway')
                <h2 class="templates-title">Choose a giveaway template</h2>
                <p class="templates-subtitle">Browse curated giveaway templates. Pick one to pre-fill your giveaway details instantly.</p>
            @elseif($showOnlyType === 'envelope')
                <h2 class="templates-title">Choose an envelope template</h2>
                <p class="templates-subtitle">Browse curated envelope templates. Pick one to pre-fill your envelope details instantly.</p>
            @else
                <h2 class="templates-title">Choose a template to start your build</h2>
                <p class="templates-subtitle">Browse curated invitations and giveaways. Pick one to pre-fill product details instantly.</p>
                <button type="button" class="btn-secondary" onclick="Navigation.showPage(1)">Create Custom Product</button>
            @endif
        </div>
    </div>

    <div class="templates-container">
        @if($showOnlyType)
            {{-- Show only the relevant template type --}}
            @php
                $templateTypeLabel = ucfirst($showOnlyType);
                $templateDescription = match($showOnlyType) {
                    'invitation' => 'Elegant invites for weddings, celebrations, and corporate events.',
                    'giveaway' => 'Thoughtful keepsakes and corporate gifts ready for quick customization.',
                    'envelope' => 'Elegant envelopes to complement your invitations.',
                    default => 'Templates for your products.'
                };
            @endphp
            <section class="template-section" aria-labelledby="{{ $showOnlyType }}-templates-heading">
                <div class="template-section-header">
                    <div>
                        <h3 id="{{ $showOnlyType }}-templates-heading">{{ $templateTypeLabel }} Templates</h3>
                        <p>{{ $templateDescription }}</p>
                    </div>
                    <span class="template-count">{{ $filteredTemplates->count() }} templates</span>
                </div>
                @if($filteredTemplates->isNotEmpty())
                    <div class="templates-grid">
                        @foreach($filteredTemplates as $template)
                            <div class="template-card" tabindex="0" role="button" data-template-id="{{ $template->id }}">
                                <div class="template-image-container">
                                    @php
                                        $frontImg = $template->front_image ?? $template->preview_front ?? $template->preview;
                                        $backImg = $template->back_image ?? $template->preview_back ?? null;
                                    @endphp
                                    @if($frontImg)
                                        <img src="@imageUrl($frontImg)" alt="{{ $template->name }}" class="template-img">
                                    @else
                                        <span>No preview</span>
                                    @endif
                                    @if($backImg)
                                        <img src="@imageUrl($backImg)" alt="Back of {{ $template->name }}" class="back-thumb">
                                    @endif
                                </div>
                                <div class="card-overlay">
                                    <h3>{{ $template->name }}</h3>
                                    <p>{{ $template->description }}</p>
                                    <div class="card-meta">
                                        <span class="meta-pill">{{ $template->event_type ?? '—' }}</span>
                                        <span class="meta-pill">{{ $template->theme_style ?? '—' }}</span>
                                    </div>
                                    <div class="card-actions">
                                        <button type="button" class="btn continue-btn"
                                            data-template-id="{{ $template->id }}"
                                            data-template-name="{{ $template->name }}"
                                            data-template-description="{{ $template->description }}"
                                            data-template-event_type="{{ $template->event_type }}"
                                            data-template-product_type="{{ $template->product_type }}"
                                            data-template-theme_style="{{ $template->theme_style }}"
                                            data-template-preview="{{ $template->preview ? \App\Support\ImageResolver::url($template->preview) : '' }}"
                                            data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}"
                                        >Use template</button>
                                        <button type="button" class="btn delete-btn template-reback-btn" data-reback-url="{{ route('admin.templates.reback', $template->id) }}" data-template-name="{{ $template->name }}">Reback</button>
                                        @if(!empty($template->front_image) || !empty($template->preview))
                                            <button type="button" class="btn view-front-btn" data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}">View Front</button>
                                        @endif
                                        @if(!empty($template->back_image) || !empty($template->preview_back))
                                            <button type="button" class="btn view-back-btn" data-back-url="{{ ($template->back_image ?? $template->preview_back) ? \App\Support\ImageResolver::url($template->back_image ?? $template->preview_back) : '' }}">View Back</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="templates-empty">
                        <p>No {{ $showOnlyType }} templates available yet. Create a new template to get started.</p>
                        @if(strtolower($showOnlyType) !== 'invitation')
                            <button type="button" class="btn-secondary" onclick="Navigation.showPage(1)">Create Custom {{ ucfirst($showOnlyType) }}</button>
                        @endif
                    </div>
                @endif
            </section>
        @else
            {{-- Fallback: Show all template types (original behavior) --}}
            <section class="template-section" aria-labelledby="invitation-templates-heading">
                <div class="template-section-header">
                    <div>
                        <h3 id="invitation-templates-heading">Invitation Templates</h3>
                        <p>Elegant invites for weddings, celebrations, and corporate events.</p>
                    </div>
                    <span class="template-count">{{ $invitationTemplates->count() }} templates</span>
                </div>
                @if($invitationTemplates->isNotEmpty())
                    <div class="templates-grid">
                        @foreach($invitationTemplates as $template)
                            <div class="template-card" tabindex="0" role="button" data-template-id="{{ $template->id }}">
                                <div class="template-image-container">
                                    @php
                                        $frontImg = $template->front_image ?? $template->preview_front ?? $template->preview;
                                        $backImg = $template->back_image ?? $template->preview_back ?? null;
                                    @endphp
                                    @if($frontImg)
                                        <img src="@imageUrl($frontImg)" alt="{{ $template->name }}" class="template-img">
                                    @else
                                        <span>No preview</span>
                                    @endif
                                    @if($backImg)
                                        <img src="@imageUrl($backImg)" alt="Back of {{ $template->name }}" class="back-thumb">
                                    @endif
                                </div>
                                <div class="card-overlay">
                                    <h3>{{ $template->name }}</h3>
                                    <p>{{ $template->description }}</p>
                                    <div class="card-meta">
                                        <span class="meta-pill">{{ $template->event_type ?? '—' }}</span>
                                        <span class="meta-pill">{{ $template->theme_style ?? '—' }}</span>
                                    </div>
                                    <div class="card-actions">
                                        <button type="button" class="btn continue-btn"
                                            data-template-id="{{ $template->id }}"
                                            data-template-name="{{ $template->name }}"
                                            data-template-description="{{ $template->description }}"
                                            data-template-event_type="{{ $template->event_type }}"
                                            data-template-product_type="{{ $template->product_type }}"
                                            data-template-theme_style="{{ $template->theme_style }}"
                                            data-template-preview="{{ $template->preview ? \App\Support\ImageResolver::url($template->preview) : '' }}"
                                            data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}"
                                        >Use template</button>
                                        <button type="button" class="btn delete-btn template-reback-btn" data-reback-url="{{ route('admin.templates.reback', $template->id) }}" data-template-name="{{ $template->name }}">Reback</button>
                                        @if(!empty($template->front_image) || !empty($template->preview))
                                            <button type="button" class="btn view-front-btn" data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}">View Front</button>
                                        @endif
                                        @if(!empty($template->back_image) || !empty($template->preview_back))
                                            <button type="button" class="btn view-back-btn" data-back-url="{{ ($template->back_image ?? $template->preview_back) ? \App\Support\ImageResolver::url($template->back_image ?? $template->preview_back) : '' }}">View Back</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="templates-empty">
                        <p>No invitation templates available yet. Create a new template to get started.</p>
                    </div>
                @endif
            </section>

            <section class="template-section" aria-labelledby="giveaway-templates-heading">
                <div class="template-section-header">
                    <div>
                        <h3 id="giveaway-templates-heading">Giveaway Templates</h3>
                        <p>Thoughtful keepsakes and corporate gifts ready for quick customization.</p>
                    </div>
                    <span class="template-count">{{ $giveawayTemplates->count() }} templates</span>
                </div>
                @if($giveawayTemplates->isNotEmpty())
                    <div class="templates-grid">
                        @foreach($giveawayTemplates as $template)
                            <div class="template-card" tabindex="0" role="button" data-template-id="{{ $template->id }}">
                                <div class="template-image-container">
                                    @php
                                        $frontImg = $template->front_image ?? $template->preview_front ?? $template->preview;
                                        $backImg = $template->back_image ?? $template->preview_back ?? null;
                                    @endphp
                                    @if($frontImg)
                                        <img src="@imageUrl($frontImg)" alt="{{ $template->name }}" class="template-img">
                                    @else
                                        <span>No preview</span>
                                    @endif
                                    @if($backImg)
                                        <img src="@imageUrl($backImg)" alt="Back of {{ $template->name }}" class="back-thumb">
                                    @endif
                                </div>
                                <div class="card-overlay">
                                    <h3>{{ $template->name }}</h3>
                                    <p>{{ $template->description }}</p>
                                    <div class="card-meta">
                                        <span class="meta-pill">{{ $template->event_type ?? '—' }}</span>
                                        <span class="meta-pill">{{ $template->theme_style ?? '—' }}</span>
                                    </div>
                                    <div class="card-actions">
                                        <button type="button" class="btn continue-btn"
                                            data-template-id="{{ $template->id }}"
                                            data-template-name="{{ $template->name }}"
                                            data-template-description="{{ $template->description }}"
                                            data-template-event_type="{{ $template->event_type }}"
                                            data-template-product_type="{{ $template->product_type }}"
                                            data-template-theme_style="{{ $template->theme_style }}"
                                            data-template-preview="{{ $template->preview ? \App\Support\ImageResolver::url($template->preview) : '' }}"
                                            data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}"
                                        >Use template</button>
                                        <button type="button" class="btn delete-btn template-reback-btn" data-reback-url="{{ route('admin.templates.reback', $template->id) }}" data-template-name="{{ $template->name }}">Reback</button>
                                        @if(!empty($template->front_image) || !empty($template->preview))
                                            <button type="button" class="btn view-front-btn" data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}">View Front</button>
                                        @endif
                                        @if(!empty($template->back_image) || !empty($template->preview_back))
                                            <button type="button" class="btn view-back-btn" data-back-url="{{ ($template->back_image ?? $template->preview_back) ? \App\Support\ImageResolver::url($template->back_image ?? $template->preview_back) : '' }}">View Back</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="templates-empty">
                        <p>No giveaway templates yet. Upload a giveaway concept to populate this section.</p>
                    </div>
                @endif
            </section>

            @if($otherTemplates->isNotEmpty())
                <section class="template-section" aria-labelledby="other-templates-heading">
                    <div class="template-section-header">
                        <div>
                            <h3 id="other-templates-heading">Other Templates</h3>
                            <p>Templates with custom product types.</p>
                        </div>
                        <span class="template-count">{{ $otherTemplates->count() }} templates</span>
                    </div>
                    <div class="templates-grid">
                        @foreach($otherTemplates as $template)
                            <div class="template-card" tabindex="0" role="button" data-template-id="{{ $template->id }}">
                                <div class="template-image-container">
                                    @php
                                        $frontImg = $template->front_image ?? $template->preview_front ?? $template->preview;
                                        $backImg = $template->back_image ?? $template->preview_back ?? null;
                                    @endphp
                                    @if($frontImg)
                                        <img src="@imageUrl($frontImg)" alt="{{ $template->name }}" class="template-img">
                                    @else
                                        <span>No preview</span>
                                    @endif
                                    @if($backImg)
                                        <img src="@imageUrl($backImg)" alt="Back of {{ $template->name }}" class="back-thumb">
                                    @endif
                                </div>
                                <div class="card-overlay">
                                    <h3>{{ $template->name }}</h3>
                                    <p>{{ $template->description }}</p>
                                    <div class="card-meta">
                                        <span class="meta-pill">{{ $template->product_type ?? '—' }}</span>
                                        <span class="meta-pill">{{ $template->theme_style ?? '—' }}</span>
                                    </div>
                                    <div class="card-actions">
                                        <button type="button" class="btn continue-btn"
                                            data-template-id="{{ $template->id }}"
                                            data-template-name="{{ $template->name }}"
                                            data-template-description="{{ $template->description }}"
                                            data-template-event_type="{{ $template->event_type }}"
                                            data-template-product_type="{{ $template->product_type }}"
                                            data-template-theme_style="{{ $template->theme_style }}"
                                            data-template-preview="{{ $template->preview ? \App\Support\ImageResolver::url($template->preview) : '' }}"
                                            data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}"
                                        >Use template</button>
                                        <button type="button" class="btn delete-btn template-reback-btn" data-reback-url="{{ route('admin.templates.reback', $template->id) }}" data-template-name="{{ $template->name }}">Reback</button>
                                        @if(!empty($template->front_image) || !empty($template->preview))
                                            <button type="button" class="btn view-front-btn" data-front-url="{{ ($template->front_image ?? $template->preview) ? \App\Support\ImageResolver::url($template->front_image ?? $template->preview) : '' }}">View Front</button>
                                        @endif
                                        @if(!empty($template->back_image) || !empty($template->preview_back))
                                            <button type="button" class="btn view-back-btn" data-back-url="{{ ($template->back_image ?? $template->preview_back) ? \App\Support\ImageResolver::url($template->back_image ?? $template->preview_back) : '' }}">View Back</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

    </div>
</div>

{{-- Image Preview Modal --}}
<div id="imageModal" class="image-modal">
    <span id="closeModal" class="close-modal">&times;</span>
    <img id="modalImage" src="" alt="Template Preview" class="modal-image">
</div>

{{-- Reback Confirmation Modal --}}
<div id="rebackModal" class="reback-modal">
    <div class="reback-modal-content">
        <h3 id="rebackModalTitle">Send template back to staff for revisions?</h3>
        <div class="reback-modal-body">
            <label for="rebackNote">Provide a note for the staff about the required changes:</label>
            <textarea id="rebackNote" name="rebackNote" rows="4" placeholder="Enter your feedback here..."></textarea>
        </div>
        <div class="reback-modal-actions">
            <button type="button" id="rebackCancel" class="btn-cancel">Cancel</button>
            <button type="button" id="rebackConfirm" class="btn-confirm">OK</button>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.template-img').forEach(img => {
        img.addEventListener('click', function() {
            document.getElementById('modalImage').src = this.src;
            document.getElementById('imageModal').style.display = 'flex';
        });
    });
    document.getElementById('closeModal').onclick = function() {
        document.getElementById('imageModal').style.display = 'none';
        document.getElementById('modalImage').src = '';
    };
    document.getElementById('imageModal').onclick = function(e) {
        if(e.target === this) {
            this.style.display = 'none';
            document.getElementById('modalImage').src = '';
        }
    };

    // Close reback modal when clicking outside
    document.getElementById('rebackModal').onclick = function(e) {
        if(e.target === this) {
            this.style.display = 'none';
        }
    };

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('imageModal').style.display = 'none';
            document.getElementById('modalImage').src = '';
            document.getElementById('rebackModal').style.display = 'none';
        }
    });

    // View front/back handlers
    document.querySelectorAll('.view-front-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = btn.dataset.frontUrl;
            if (!url) return alert('No front image available');
            document.getElementById('modalImage').src = url;
            document.getElementById('imageModal').style.display = 'flex';
        });
    });

    document.querySelectorAll('.view-back-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = btn.dataset.backUrl;
            if (!url) return alert('No back image available');
            document.getElementById('modalImage').src = url;
            document.getElementById('imageModal').style.display = 'flex';
        });
    });

    // legacy inline form handler removed; delete actions are handled via AJAX buttons below

    // Handle delete via fetch for buttons (replaces the previous inline form)
    // Helper: get CSRF token from meta tag or fallback to a form hidden input
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.getAttribute) {
            const v = meta.getAttribute('content');
            if (v) return v;
        }
        const hidden = document.querySelector('input[name="_token"]');
        return hidden ? hidden.value : '';
    }

    // Reback modal variables
    let currentRebackBtn = null;
    let currentRebackUrl = null;
    let currentRebackCsrfToken = null;
    let currentRebackOriginalLabel = null;

    // Set up reback modal handlers once
    document.getElementById('rebackCancel').onclick = function() {
        document.getElementById('rebackModal').style.display = 'none';
    };

    document.getElementById('rebackConfirm').onclick = function() {
        const note = document.getElementById('rebackNote').value.trim();
        if (note === '') {
            alert('Please provide a note for the staff.');
            return;
        }

        // Hide modal
        document.getElementById('rebackModal').style.display = 'none';

        // Disable button
        if (currentRebackBtn) {
            currentRebackBtn.disabled = true;
            currentRebackBtn.textContent = 'Sending...';
        }

        const payload = new URLSearchParams();
        payload.append('_token', currentRebackCsrfToken);
        payload.append('note', note);

        fetch(currentRebackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: payload.toString()
        }).then(res => {
            if (res.ok) {
                const card = currentRebackBtn ? currentRebackBtn.closest('.template-card') : null;
                if (card) card.remove();
                alert('Template returned to staff.');
                return;
            }
            return res.json().then(data => {
                throw new Error(data?.message || 'Reback failed.');
            }).catch(() => {
                throw new Error('Reback failed.');
            });
        }).catch(err => {
            alert(err && err.message ? err.message : 'Reback failed.');
        }).finally(() => {
            if (currentRebackBtn) {
                currentRebackBtn.disabled = false;
                currentRebackBtn.textContent = currentRebackOriginalLabel;
            }
        });
    };

    document.querySelectorAll('.template-reback-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            try {
                const name = btn.dataset.templateName || 'this template';
                const url = btn.dataset.rebackUrl;
                const csrfToken = getCsrfToken();
                const originalLabel = btn.textContent;

                // Store current context
                currentRebackBtn = btn;
                currentRebackUrl = url;
                currentRebackCsrfToken = csrfToken;
                currentRebackOriginalLabel = originalLabel;

                // Set modal title
                document.getElementById('rebackModalTitle').textContent = 'Send "' + name + '" back to staff for revisions?';

                // Clear previous note
                document.getElementById('rebackNote').value = '';

                // Show modal
                document.getElementById('rebackModal').style.display = 'flex';

            } catch (err) {
                console.error('Reback handler failed', err);
                alert('Reback failed: ' + (err && err.message ? err.message : 'unknown error'));
            }
        });
    });

    // Handle "Use template" button clicks
    document.querySelectorAll('.continue-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var templateId = this.dataset.templateId;
            var templateInput = document.getElementById('template_id');
            if (templateInput) {
                templateInput.value = templateId;
                // Fetch template data and populate form
                fetchTemplateData(templateId);
                // Update preview images if function exists
                if (typeof updatePreviewImages === 'function') {
                    updatePreviewImages();
                }
            }
        });
    });

    // Function to fetch template data and populate form
    function fetchTemplateData(templateId) {
        fetch(`/admin/products/template/${templateId}/data`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Populate form fields with template data
            populateFormWithTemplateData(data);
        })
        .catch(error => {
            console.error('Error fetching template data:', error);
        });
    }

    // Function to populate form fields with template data
    function populateFormWithTemplateData(data) {
        // Populate basic info fields
        const invitationNameField = document.getElementById('invitationName');
        if (invitationNameField && data.name) {
            invitationNameField.value = data.name;
        }

        const eventTypeField = document.getElementById('eventType');
        if (eventTypeField && data.event_type) {
            eventTypeField.value = data.event_type;
        }

        const productTypeField = document.getElementById('productType');
        if (productTypeField && data.product_type) {
            productTypeField.value = data.product_type;
        }

        const themeStyleField = document.getElementById('themeStyle');
        if (themeStyleField && data.theme_style) {
            themeStyleField.value = data.theme_style;
        }

        // Populate description
        const descriptionEditor = document.getElementById('description-editor');
        const descriptionTextarea = document.getElementById('description');
        if (descriptionEditor && data.description) {
            descriptionEditor.innerHTML = data.description;
            if (descriptionTextarea) {
                descriptionTextarea.value = data.description;
            }
        }

        // Update preview images
        if (data.front_image) {
            updatePreviewImage('preview-front-img', data.front_image);
        }
        if (data.back_image) {
            updatePreviewImage('preview-back-img', data.back_image);
        }

        // Populate design data sections if available
        if (data.design_data) {
            populateDesignDataSections(data.design_data);
        }

        // Navigate to next page (Basic Info)
        if (typeof Navigation !== 'undefined' && Navigation.showPage) {
            Navigation.showPage(1);
        }
    }

    // Function to populate design data sections (paper stocks, addons, colors, bulk orders)
    function populateDesignDataSections(designData) {
        // Populate paper stocks
        if (designData.paper_stocks && Array.isArray(designData.paper_stocks)) {
            populatePaperStocks(designData.paper_stocks);
        }

        // Populate addons
        if (designData.addons && Array.isArray(designData.addons)) {
            populateAddons(designData.addons);
        }

        // Populate colors
        if (designData.colors && Array.isArray(designData.colors)) {
            populateColors(designData.colors);
        }

        // Populate bulk orders
        if (designData.bulk_orders && Array.isArray(designData.bulk_orders)) {
            populateBulkOrders(designData.bulk_orders);
        }
    }

    // Function to populate paper stocks section
    function populatePaperStocks(paperStocks) {
        const container = document.getElementById('paper-stocks-container');
        if (!container) return;

        // Clear existing entries
        const existingEntries = container.querySelectorAll('.paper-stock-entry');
        existingEntries.forEach(entry => entry.remove());

        paperStocks.forEach((stock, index) => {
            addPaperStockEntry(stock, index);
        });
    }

    // Function to populate addons section
    function populateAddons(addons) {
        const container = document.getElementById('addons-container');
        if (!container) return;

        // Clear existing entries
        const existingEntries = container.querySelectorAll('.addon-entry');
        existingEntries.forEach(entry => entry.remove());

        addons.forEach((addon, index) => {
            addAddonEntry(addon, index);
        });
    }

    // Function to populate colors section
    function populateColors(colors) {
        const container = document.getElementById('colors-container');
        if (!container) return;

        // Clear existing entries
        const existingEntries = container.querySelectorAll('.color-entry');
        existingEntries.forEach(entry => entry.remove());

        colors.forEach((color, index) => {
            addColorEntry(color, index);
        });
    }

    // Function to populate bulk orders section
    function populateBulkOrders(bulkOrders) {
        const container = document.getElementById('bulk-orders-container');
        if (!container) return;

        // Clear existing entries
        const existingEntries = container.querySelectorAll('.bulk-order-entry');
        existingEntries.forEach(entry => entry.remove());

        bulkOrders.forEach((order, index) => {
            addBulkOrderEntry(order, index);
        });
    }

    // Helper functions to add entries (these need to be defined in the main form JavaScript)
    function addPaperStockEntry(data = null, index = null) {
        if (typeof window.addPaperStockEntry === 'function') {
            window.addPaperStockEntry(data, index);
        }
    }

    function addAddonEntry(data = null, index = null) {
        if (typeof window.addAddonEntry === 'function') {
            window.addAddonEntry(data, index);
        }
    }

    function addColorEntry(data = null, index = null) {
        if (typeof window.addColorEntry === 'function') {
            window.addColorEntry(data, index);
        }
    }

    function addBulkOrderEntry(data = null, index = null) {
        if (typeof window.addBulkOrderEntry === 'function') {
            window.addBulkOrderEntry(data, index);
        }
    }

    // Helper function to update preview images
    function updatePreviewImage(containerId, imageUrl) {
        const container = document.getElementById(containerId);
        if (container && imageUrl) {
            if (container.tagName !== 'IMG') {
                const img = document.createElement('img');
                img.id = containerId;
                img.src = imageUrl;
                img.alt = containerId.includes('front') ? 'Front preview' : 'Back preview';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '200px';
                container.parentNode.replaceChild(img, container);
            } else {
                container.src = imageUrl;
            }
        }
    }
    </script>

    <style>
    /* Layout and Containers */
    .templates-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 26px;
        margin-bottom: 22px;
        background: linear-gradient(135deg, rgba(148,185,255,0.16), rgba(90,141,224,0.12));
        border-radius: 18px;
        border: 1px solid rgba(148,185,255,0.25);
    }

    .templates-title {
        font-size: 1.4rem;
        margin: 0 0 6px;
        font-weight: 600;
        color: #0f172a;
    }

    .templates-subtitle {
        margin: 0;
        color: #475569;
        font-size: 0.95rem;
    }

    .templates-container {
        display: flex;
        flex-direction: column;
        gap: 28px;
        max-height: 700px;
        overflow-y: auto;
        padding-right: 6px;
    }

    .template-section {
        background: #ffffff;
        border: 1px solid rgba(148,185,255,0.25);
        border-radius: 16px;
        padding: 22px 24px 26px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .template-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .template-section-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #0f172a;
    }

    .template-section-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.9rem;
    }

    .template-count {
        background: rgba(148,185,255,0.18);
        color: #2563eb;
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        animation: fadeIn 0.8s ease-out;
    }

    .templates-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px 24px;
        background: #f8faff;
        border-radius: 12px;
        color: #6b7280;
        font-size: 0.95rem;
        border: 1px dashed rgba(148,185,255,0.4);
    }

    /* Template Cards */
    .template-card {
        min-height: 420px;
        display: flex;
        flex-direction: column;
        border-radius: 14px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.12);
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(148,185,255,0.22);
    }

    .template-card:hover,
    .template-card:focus {
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18);
        transform: translateY(-6px);
        border-color: rgba(90,141,224,0.4);
    }
    

    .template-image-container {
        flex: 0 0 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, #f8faff, #eef2ff);
        border-radius: 14px 14px 0 0;
        position: relative;
    }

    .back-thumb {
        position: absolute;
        right: 8px;
        bottom: 8px;
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(15,23,42,0.12);
        border: 2px solid rgba(255,255,255,0.9);
    }

    .template-img {
        cursor: pointer;
        max-height: 200px;
        max-width: 90%;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.12);
        transition: transform 0.3s ease;
    }

    .template-img:hover {
        transform: scale(1.05);
    }

    .card-overlay {
        flex: 1;
        padding: 18px 18px 20px;
        background: rgba(255, 255, 255, 0.96);
        border-radius: 0 0 14px 14px;
        overflow-y: auto;
        transition: background 0.3s ease;
    }

    .card-overlay h3 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 10px;
        color: #0f172a;
    }

    .card-overlay p {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0 0 14px 0;
        max-height: 60px;
        overflow: hidden;
    }

    .card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .meta-pill {
        background: rgba(148,185,255,0.18);
        color: #2563eb;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .card-actions {
        margin-top: auto;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 12px;
    }

    .card-actions form {
        margin: 0;
    }

    .card-actions .continue-btn {
        padding: 10px 18px;
        font-size: 0.9rem;
        border-radius: 10px;
        box-shadow: 0 10px 20px rgba(148,185,255,0.28);
    }

    .card-actions .delete-btn {
        background: #f8f0f0;
        color: #b91c1c;
        padding: 10px 16px;
        font-size: 0.85rem;
        border-radius: 10px;
        border: 1px solid rgba(220,53,69,0.24);
        box-shadow: 0 8px 16px rgba(220,53,69,0.15);
    }

    .card-actions .delete-btn:hover,
    .card-actions .delete-btn:focus {
        background: #dc2626;
        color: #fff;
        border-color: #dc2626;
        transform: translateY(-1px);
    }

    /* Modal Styles */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s ease;
    }

    .close-modal {
        position: absolute;
        top: 30px;
        right: 40px;
        font-size: 2.5rem;
        color: #fff;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .close-modal:hover {
        color: #ccc;
    }

    .modal-image {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    /* Reback Modal Styles */
    .reback-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }

    .reback-modal-content {
        background: #fff;
        border-radius: 12px;
        padding: 32px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .reback-modal-content h3 {
        margin: 0 0 16px 0;
        font-size: 1.2rem;
        color: #0f172a;
        font-weight: 600;
    }

    .reback-modal-body {
        margin-bottom: 20px;
    }

    .reback-modal-body label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #374151;
    }

    .reback-modal-body textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        resize: vertical;
        min-height: 80px;
    }

    .reback-modal-body textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .reback-modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .reback-modal-actions button {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-cancel {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
    }

    .btn-confirm {
        background: #16a34a;
        color: white;
    }

    .btn-confirm:hover {
        background: #15803d;
        transform: translateY(-1px);
    }

    /* Button Styles */
    .btn {
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
        text-decoration: none; /* For links */
        display: inline-block; /* Ensure links behave like buttons */
        text-align: center;
    }

    .btn-edit {
        background: #6b7280; /* Neutral gray for edit */
        color: white;
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-edit:hover {
        background: #4b5563;
        transform: translateY(-1px);
    }

    .btn-delete {
        background: #dc3545; /* Red for delete */
        color: white;
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-1px);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .templates-hero {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 18px 20px;
        }

        .template-section {
            padding: 18px 18px 22px;
        }

        .template-section-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .template-count {
            align-self: flex-start;
        }

        .templates-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .template-card {
            height: auto;
            min-height: 360px;
        }

        .template-image-container {
            flex: 0 0 180px;
        }

        .card-overlay {
            padding: 16px;
        }

        .modal-image {
            max-width: 95vw;
            max-height: 80vh;
        }
    }

</style>
