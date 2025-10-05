@extends('layouts.admin')

@section('title', 'Manage Staff')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/staff.css') }}">
@endpush

@section('content')
<main class="admin-page-shell staff-page" role="main">
    @if(session('warning'))
        <div class="dashboard-alert alert-warning" role="alert" aria-live="polite">
            {{ session('warning') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">Staff Management</h1>
            <p class="page-subtitle">Review admin and staff accounts, update roles, and archive inactive profiles.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="pill-link pill-link--primary" aria-label="Add new staff">
            <i class="fa-solid fa-plus"></i>
            <span>Add Staff</span>
        </a>
    </header>

    <div id="staff-summary">
        @include('admin.users.partials.summary', compact('totalStaff', 'activeStaff', 'pendingStaff'))
    </div>

    <section class="staff-toolbar" aria-label="Staff filters and actions">
        <form method="GET" action="{{ route('admin.users.index') }}" class="materials-toolbar__search" role="search">
            <div class="search-input">
                <span class="search-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search staff by name or email..."
                    class="form-control"
                    aria-label="Search staff"
                    data-search-url="{{ route('admin.users.index') }}"
                >
            </div>
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
    </section>

    <section class="staff-table" aria-label="Staff list">
        <div id="staff-table">
            @include('admin.users.partials.table', ['users' => $users])
        </div>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('input[name="search"]');
    const summaryContainer = document.getElementById('staff-summary');
    const tableContainer = document.getElementById('staff-table');
    let searchDebounceTimer = null;
    let activeFetchController = null;

    function initHighlightedRow() {
        const highlightedRow = document.getElementById('highlighted-staff');
        if (highlightedRow) {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            highlightedRow.classList.add('recently-approved');
            highlightedRow.setAttribute('tabindex', '-1');
            highlightedRow.focus({ preventScroll: true });
            setTimeout(() => highlightedRow.removeAttribute('tabindex'), 2500);
        }
    }

    function fetchResults(term) {
        if (!searchInput || !summaryContainer || !tableContainer) {
            return;
        }

        if (activeFetchController) {
            activeFetchController.abort();
        }

        activeFetchController = new AbortController();
        const baseUrl = searchInput.dataset.searchUrl;
        const params = new URLSearchParams();
        if (term) {
            params.set('search', term);
        }

        const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            signal: activeFetchController.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function (data) {
                if (data.summary) {
                    summaryContainer.innerHTML = data.summary;
                }
                if (data.table) {
                    tableContainer.innerHTML = data.table;
                }
                initHighlightedRow();

                if (typeof history.replaceState === 'function') {
                    history.replaceState({}, '', url);
                }
            })
            .catch(function (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                console.error('Live staff search failed:', error);
            });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function (event) {
            const term = event.target.value.trim();
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }
            searchDebounceTimer = setTimeout(function () {
                fetchResults(term);
            }, 300);
        });

        if (searchInput.value.trim()) {
            fetchResults(searchInput.value.trim());
        }
    }

    initHighlightedRow();
});
</script>
@endsection
