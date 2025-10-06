@extends('layouts.owner.app')
@include('layouts.owner.sidebar')

@section('title', 'Approved Staff')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">


<main class="materials-page admin-page-shell materials-container" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Approved Staff</h1>
            <p class="page-subtitle">List of active staff accounts</p>
        </div>
    </header>
    <div class="page-inner">
        <div class="panel">
            <div style="display:flex; justify-content: flex-end; margin-bottom: 12px;">
                <button id="showPendingBtn" class="btn btn-secondary" type="button" title="Show pending accounts">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v6l4 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Pending Staff</span>
                </button>
            </div>
            @if(session('success'))
                <div class="bg-green-100 p-2 mb-2">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 p-2 mb-2">{{ session('error') }}</div>
            @endif
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvedStaff as $staff)
                            <tr>
                                <td class="fw-bold">{{ $staff->user->user_id }}</td>
                                <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                                <td>{{ $staff->user->email }}</td>
                                <td>{{ $staff->contact_number }}</td>
                                <td><span class="badge stock-ok">Approved</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center" style="padding:18px; color:#64748b;">No approved staff found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="pendingModal" class="modal hidden" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:1000;background:rgba(0,0,0,0.18);display:flex;align-items:center;justify-content:center;">
                <div class="modal-content" style="background:#fff;padding:32px 24px;border-radius:16px;min-width:340px;max-width:98vw;box-shadow:0 12px 32px rgba(0,0,0,0.12);position:relative;">
                    <button id="closeModal" class="btn btn-secondary" style="position:absolute;top:12px;right:12px;padding:2px 8px;min-width:0;" type="button" title="Close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <h2 class="page-title" style="font-size:1.2rem;margin-bottom:18px;">Pending Staff Accounts</h2>
                    @if($pendingStaff->isEmpty())
                        <p>No pending staff accounts.</p>
                    @else
                        <div class="table-wrapper" style="box-shadow:none;border-radius:10px;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingStaff as $staff)
                                    <tr>
                                        <td>{{ $staff->user->user_id }}</td>
                                        <td>{{ $staff->first_name }} {{ $staff->middle_name ?? '' }} {{ $staff->last_name }}</td>
                                        <td>{{ $staff->user->email }}</td>
                                        <td>{{ $staff->contact_number }}</td>
                                        <td style="display:flex;gap:8px;">
                                            <form method="POST" action="{{ route('owner.staff.approve', $staff->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success" title="Approve">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    <span class="sr-only">Approve</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('owner.staff.reject', $staff->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this account?');">
                                                @csrf
                                                <button type="submit" class="btn btn-danger" title="Reject">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    <span class="sr-only">Reject</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const showBtn = document.getElementById('showPendingBtn');
    const modal = document.getElementById('pendingModal');
    const closeBtn = document.getElementById('closeModal');
    if(showBtn && modal && closeBtn) {
        showBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        });
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        });
        modal.addEventListener('click', (e) => {
            if(e.target === modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        });
        document.addEventListener('keydown', (e) => {
            if(e.key === 'Escape') {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        });
    }
});
</script>

@endsection
</div> <!-- .page-inner -->
</main>
