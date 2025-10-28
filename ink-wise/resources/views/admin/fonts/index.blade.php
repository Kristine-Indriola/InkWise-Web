@extends('layouts.admin')

@section('title', 'Font Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Font Management</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#uploadFontModal">
                            <i class="fas fa-upload"></i> Upload Font
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="syncGoogleFonts">
                            <i class="fas fa-sync"></i> Sync Google Fonts
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="sourceFilter">
                                <option value="all">All Sources</option>
                                <option value="uploaded">Uploaded</option>
                                <option value="google">Google Fonts</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="categoryFilter">
                                <option value="all">All Categories</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search fonts...">
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activeOnly">
                                <label class="form-check-label" for="activeOnly">
                                    Active only
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Font Grid -->
                    <div id="fontsGrid" class="row">
                        <!-- Fonts will be loaded here via AJAX -->
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        <div id="pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Font Modal -->
<div class="modal fade" id="uploadFontModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Upload Font</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="uploadFontForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="fontName">Font Name</label>
                        <input type="text" class="form-control" id="fontName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="fontDisplayName">Display Name (Optional)</label>
                        <input type="text" class="form-control" id="fontDisplayName" name="display_name">
                    </div>
                    <div class="form-group">
                        <label for="fontFile">Font File</label>
                        <input type="file" class="form-control-file" id="fontFile" name="font_file"
                               accept=".ttf,.otf,.woff,.woff2" required>
                        <small class="form-text text-muted">Supported formats: TTF, OTF, WOFF, WOFF2 (Max 5MB)</small>
                    </div>
                    <div class="form-group">
                        <label for="fontCategory">Category</label>
                        <input type="text" class="form-control" id="fontCategory" name="category"
                               placeholder="e.g., serif, sans-serif, script">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Font</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.font-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.font-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.font-preview {
    font-size: 24px;
    margin: 10px 0;
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: #f8f9fa;
}

.font-name {
    font-weight: bold;
    margin-bottom: 5px;
}

.font-meta {
    font-size: 12px;
    color: #6c757d;
}

.font-actions {
    margin-top: 10px;
}

.badge-google {
    background-color: #4285f4;
}

.badge-uploaded {
    background-color: #28a745;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let filters = {
        source: 'all',
        category: 'all',
        search: '',
        active_only: false
    };

    // Load initial fonts
    loadFonts();
    loadCategories();

    // Filter change handlers
    $('#sourceFilter, #categoryFilter, #activeOnly').on('change', function() {
        filters.source = $('#sourceFilter').val();
        filters.category = $('#categoryFilter').val();
        filters.active_only = $('#activeOnly').is(':checked');
        currentPage = 1;
        loadFonts();
    });

    $('#searchInput').on('input', debounce(function() {
        filters.search = $(this).val();
        currentPage = 1;
        loadFonts();
    }, 300));

    // Upload font form
    $('#uploadFontForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route("admin.fonts.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#uploadFontModal').modal('hide');
                $('#uploadFontForm')[0].reset();
                loadFonts();
                toastr.success('Font uploaded successfully!');
            },
            error: function(xhr) {
                const errors = xhr.responseJSON.errors;
                let errorMessage = 'Upload failed:';
                for (let field in errors) {
                    errorMessage += '\n' + errors[field][0];
                }
                toastr.error(errorMessage);
            }
        });
    });

    // Sync Google Fonts
    $('#syncGoogleFonts').on('click', function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

        $.ajax({
            url: '{{ route("admin.fonts.sync-google") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                loadFonts();
                toastr.success(response.message);
            },
            error: function(xhr) {
                toastr.error('Failed to sync Google Fonts');
            },
            complete: function() {
                $('#syncGoogleFonts').prop('disabled', false).html('<i class="fas fa-sync"></i> Sync Google Fonts');
            }
        });
    });

    function loadFonts() {
        $.ajax({
            url: '{{ route("admin.fonts.index") }}',
            data: {
                page: currentPage,
                source: filters.source,
                category: filters.category,
                search: filters.search,
                active_only: filters.active_only ? 1 : 0
            },
            success: function(response) {
                renderFonts(response.data);
                renderPagination(response);
            }
        });
    }

    function loadCategories() {
        $.ajax({
            url: '{{ route("admin.fonts.categories") }}',
            success: function(categories) {
                const $categoryFilter = $('#categoryFilter');
                $categoryFilter.empty();
                $categoryFilter.append('<option value="all">All Categories</option>');
                categories.forEach(category => {
                    $categoryFilter.append(`<option value="${category}">${category}</option>`);
                });
            }
        });
    }

    function renderFonts(fonts) {
        const $grid = $('#fontsGrid');
        $grid.empty();

        if (fonts.length === 0) {
            $grid.append('<div class="col-12"><p class="text-center text-muted">No fonts found.</p></div>');
            return;
        }

        fonts.forEach(font => {
            const sourceBadge = font.source === 'google'
                ? '<span class="badge badge-google">Google</span>'
                : '<span class="badge badge-uploaded">Uploaded</span>';

            const activeBadge = font.is_active
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>';

            const card = `
                <div class="col-md-6 col-lg-4">
                    <div class="font-card">
                        <div class="font-name">${font.display_name || font.name}</div>
                        <div class="font-meta">
                            ${sourceBadge} ${activeBadge}
                            ${font.category ? `<span class="badge badge-info">${font.category}</span>` : ''}
                        </div>
                        <div class="font-preview" style="font-family: '${font.css_family}';">
                            The quick brown fox jumps over the lazy dog
                        </div>
                        <div class="font-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleActive(${font.id}, ${!font.is_active})">
                                ${font.is_active ? 'Deactivate' : 'Activate'}
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteFont(${font.id})">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $grid.append(card);
        });
    }

    function renderPagination(response) {
        // Simple pagination implementation
        const $pagination = $('#pagination');
        $pagination.empty();

        if (response.last_page > 1) {
            // Add pagination controls here if needed
        }
    }

    // Global functions for font actions
    window.toggleActive = function(id, activate) {
        $.ajax({
            url: `{{ url('/admin/fonts') }}/${id}`,
            method: 'PUT',
            data: { is_active: activate },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                loadFonts();
                toastr.success(`Font ${activate ? 'activated' : 'deactivated'} successfully!`);
            },
            error: function() {
                toastr.error('Failed to update font status');
            }
        });
    };

    window.deleteFont = function(id) {
        if (!confirm('Are you sure you want to delete this font?')) return;

        $.ajax({
            url: `{{ url('/admin/fonts') }}/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                loadFonts();
                toastr.success('Font deleted successfully!');
            },
            error: function() {
                toastr.error('Failed to delete font');
            }
        });
    };
});

// Debounce utility
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endsection