@extends('layouts.admin')

@section('title', 'Password Reset Console')

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white shadow rounded-xl border border-slate-200">
        <div class="px-6 py-5 border-b border-slate-100">
            <h1 class="text-2xl font-bold text-slate-800">Password Reset Console</h1>
            <p class="mt-1 text-sm text-slate-600">Send secure password reset links to internal users without ever exposing their passwords.</p>
        </div>

        <div class="px-6 py-4 space-y-4">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3">
                    <ul class="space-y-1 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $unlocked)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-6">
                    <h2 class="text-lg font-semibold text-amber-900 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-amber-700">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        Verify your identity
                    </h2>
                    <p class="mt-3 text-sm text-amber-800">To protect account security, enter your admin password to unlock the reset console. Access will automatically lock again after 15 minutes of inactivity.</p>

                    <form method="POST" action="{{ route('admin.users.passwords.unlock') }}" class="mt-5 max-w-md space-y-4">
                        @csrf
                        <div>
                            <label for="unlock-password" class="block text-sm font-semibold text-slate-700">Admin password</label>
                            <input id="unlock-password" type="password" name="password" required class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" autocomplete="current-password">
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                            <i class="fa-solid fa-unlock"></i>
                            Unlock for 15 minutes
                        </button>
                    </form>
                </div>
            @else
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <form method="GET" action="{{ route('admin.users.passwords.index') }}" class="flex items-center gap-2">
                        <label for="search" class="sr-only">Search</label>
                        <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Search by name, role, or email" class="w-64 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <button type="submit" class="rounded-lg bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 transition">Search</button>
                    </form>

                    <form method="POST" action="{{ route('admin.users.passwords.lock') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                            <i class="fa-solid fa-lock"></i>
                            Lock console
                        </button>
                    </form>
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4 text-sm text-indigo-900">
                    <p class="font-semibold">Security reminder</p>
                    <p class="mt-1">Reset links expire quickly and can only be used once. Ask the recipient to complete their reset promptly and never forward reset emails.</p>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($users as $user)
                                @php
                                    $profile = $user->staff;
                                    $fullName = $profile ? trim(collect([$profile->first_name, $profile->middle_name, $profile->last_name])->filter()->implode(' ')) : null;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">{{ $fullName ?: '—' }}</div>
                                        <div class="text-xs text-slate-500">User #{{ $user->user_id }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 uppercase">{{ $user->role }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-sm text-slate-700">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                            <span class="inline-block h-2 w-2 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-slate-500' }}"></span>
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.users.passwords.send', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition" onclick="return confirm('Send a password reset link to {{ $user->email }}?');">
                                                <i class="fa-solid fa-paper-plane"></i>
                                                Send reset link
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No internal users matched your search.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="px-2 py-4">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
