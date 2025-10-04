@extends('layouts.admin')

@section('title', 'Password Reset Console')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/password-resets.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/password-resets.js') }}" defer></script>
@endpush

@section('content')
<main class="admin-page-shell password-reset-page" role="main">
    @if (session('status'))
        <div class="dashboard-alert dashboard-alert--success js-flash-message" role="status" aria-live="polite">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="dashboard-alert dashboard-alert--danger js-flash-message" role="alert" aria-live="assertive">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="page-header password-reset-page__header">
        <div>
            <h1 class="page-title">Password Reset Console</h1>
            <p class="page-subtitle">Send secure password reset links to internal users without ever exposing their passwords.</p>
        </div>
        @if ($unlocked)
            <form method="POST" action="{{ route('admin.users.passwords.lock') }}" class="page-header__quick-actions" data-lock-form>
                @csrf
                <button type="submit" class="pill-link pill-link--danger">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <span>Lock console</span>
                </button>
            </form>
        @endif
    </header>

    @if (! $unlocked)
        <section class="unlock-panel" aria-labelledby="unlockPanelTitle">
            <div class="unlock-panel__header">
                <span class="unlock-panel__icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <div>
                    <h2 class="unlock-panel__title" id="unlockPanelTitle">Verify your identity</h2>
                    <p class="unlock-panel__subtitle">To protect account security, enter your admin password to unlock the reset console. Access will automatically lock again after 15 minutes of inactivity.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.passwords.unlock') }}" class="unlock-form" data-unlock-form>
                @csrf
                <div class="form-field">
                    <label for="unlock-password" class="form-label">Admin password</label>
                    <input id="unlock-password" type="password" name="password" required class="form-input" autocomplete="current-password">
                </div>
                <button type="submit" class="console-btn console-btn--primary">
                    <i class="fa-solid fa-unlock" aria-hidden="true"></i>
                    <span class="console-btn__label">Unlock for 15 minutes</span>
                </button>
            </form>
        </section>
    @else
        <section class="console-toolbar" aria-label="Console search and filters">
            <form method="GET" action="{{ route('admin.users.passwords.index') }}" class="console-toolbar__search" role="search">
                <label for="search" class="sr-only">Search users</label>
                <div class="input-group">
                    <span class="input-group__icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Search by name, role, or email" class="form-input" autocomplete="off">
                    <button type="submit" class="console-btn console-btn--secondary">Search</button>
                </div>
            </form>
        </section>

        <section class="security-callout" aria-label="Security reminder">
            <p class="security-callout__title">Security reminder</p>
            <p class="security-callout__body">Reset links expire quickly and can only be used once. Ask the recipient to complete their reset promptly and never forward reset emails.</p>
        </section>

        <section class="password-reset-table" aria-label="Internal users">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Role</th>
                            <th scope="col">Email</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $profile = $user->staff;
                                $fullName = $profile ? trim(collect([$profile->first_name, $profile->middle_name, $profile->last_name])->filter()->implode(' ')) : null;
                                $roleClass = 'badge-role--' . \Illuminate\Support\Str::slug($user->role ?? 'staff');
                                $roleLabel = $user->role ? ucfirst($user->role) : 'Staff';
                                $statusSlug = \Illuminate\Support\Str::slug($user->status ?? 'inactive');
                                $statusLabel = $user->status ? ucfirst($user->status) : 'Inactive';
                            @endphp
                            <tr>
                                <td>
                                    <span class="user-name">{{ $fullName ?: '—' }}</span>
                                    <span class="user-meta">User #{{ $user->user_id }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-role {{ $roleClass }}">{{ $roleLabel }}</span>
                                </td>
                                <td>
                                    <span class="user-email">{{ $user->email }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-status badge-status--{{ $statusSlug }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('admin.users.passwords.send', $user) }}" class="table-action" data-reset-form>
                                        @csrf
                                        <button type="submit" class="console-btn console-btn--primary" data-confirm="Send a password reset link to {{ $user->email }}?">
                                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                            <span class="console-btn__label">Send reset link</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-empty-state">
                                    <p>No internal users matched your search.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="table-footer">
                    {{ $users->links() }}
                </div>
            @endif
        </section>
    @endif
</main>
@endsection
