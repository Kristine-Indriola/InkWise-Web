<div class="bg-white shadow rounded-xl border border-slate-200 mt-8">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Password Reset Console</h2>
            <p class="mt-1 text-sm text-slate-600">Send secure password reset links to staff without exposing their passwords.</p>
        </div>
        <a href="{{ route('admin.users.passwords.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
            <i class="fa-solid fa-up-right-from-square"></i>
            Open full console
        </a>
    </div>

    <div class="px-6 py-4 space-y-4">
        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3">
                <ul class="space-y-1 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $canSend = session()->has('admin.password_reset_unlocked_at') &&
                now()->diffInSeconds(
                    now()->setTimestamp(session('admin.password_reset_unlocked_at'))
                ) < 900;
        @endphp

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Unlock console</h3>
                <p class="mt-2 text-xs text-slate-600">Re-enter your admin password to enable sending reset links for 15 minutes.</p>
                <form method="POST" action="{{ route('admin.users.passwords.unlock') }}" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label for="dashboard-unlock-password" class="block text-xs font-semibold text-slate-600">Admin password</label>
                        <input id="dashboard-unlock-password" type="password" name="password" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" autocomplete="current-password">
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                        <i class="fa-solid fa-unlock"></i>
                        Unlock for 15 minutes
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Quick send</h3>
                <p class="mt-2 text-xs text-slate-600">Search by email to send a reset link directly from the dashboard.</p>
                <form method="GET" action="{{ route('admin.users.passwords.index') }}" class="mt-3 space-y-3">
                    <div>
                        <label for="dashboard-password-search" class="block text-xs font-semibold text-slate-600">Email or name</label>
                        <input id="dashboard-password-search" type="search" name="search" value="{{ request('search') }}" placeholder="e.g. staff@example.com" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        <i class="fa-solid fa-paper-plane"></i>
                        Open results
                    </button>
                </form>

                @if($canSend)
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
                        <strong>Unlocked:</strong> You may send reset links for the next {{ max(0, 15 - now()->diffInMinutes(now()->setTimestamp(session('admin.password_reset_unlocked_at')))) }} minutes.
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                        <strong>Locked:</strong> Unlock the console before sending reset links.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
