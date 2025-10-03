@extends('layouts.admin')

@section('title', 'Chat with '.($customer->user->name ?? $customer->first_name ?? 'Customer'))

@php
    $threadAnchor = $messages->last() ?? $messages->first();
    $threadMessageId = $threadAnchor?->id;
    $contactName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: ($customer->user->name ?? 'Customer');
    $contactEmail = $customer->user->email ?? null;
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-slate-500">Direct message thread</p>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $contactName }}</h1>
            <p class="text-xs text-slate-500">{{ $contactEmail ? 'Contact: '.$contactEmail : 'No email on file' }}</p>
        </div>
        <a href="{{ route('admin.messages.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
            <i class="fa-solid fa-arrow-left"></i>
            Back to inbox
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div class="text-sm font-semibold text-slate-700">Conversation history</div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">Customer</span>
        </div>

        <div class="flex max-h-[480px] flex-col gap-4 overflow-y-auto bg-slate-50 px-6 py-6">
            @forelse($messages as $message)
                @php
                    $isTeam = in_array(strtolower($message->sender_type ?? ''), ['user', 'admin', 'staff']);
                @endphp
                <div class="flex {{ $isTeam ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xl rounded-2xl px-4 py-3 text-sm shadow-sm" style="background-color: {{ $isTeam ? '#4f46e5' : '#ffffff' }}; color: {{ $isTeam ? '#ffffff' : '#0f172a' }};">
                        <div class="flex items-center justify-between text-[11px] uppercase tracking-wide {{ $isTeam ? 'text-indigo-100' : 'text-slate-400' }}">
                            <span>{{ $message->name ?? ($isTeam ? 'Team' : $contactName) }}</span>
                            <time>{{ optional($message->created_at)->format('M d, Y • h:i A') }}</time>
                        </div>
                        <div class="mt-2 whitespace-pre-wrap leading-relaxed">{{ $message->message }}</div>
                    </div>
                </div>
            @empty
                <div class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center text-slate-500">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fa-regular fa-comments text-xl"></i>
                    </div>
                    <p>No messages yet. Start the conversation with a quick reply below.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 px-6 py-4">
            <form method="POST" action="{{ $threadMessageId ? route('admin.messages.reply', $threadMessageId) : '#' }}" class="space-y-3">
                @csrf
                <label for="admin-reply" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Send a reply</label>
                <textarea id="admin-reply" name="message" rows="3" class="w-full resize-none rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="Type your message" required></textarea>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-400">Replies are stored in the message history and shown to the customer instantly.</span>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700" {{ $threadMessageId ? '' : 'disabled' }}>
                        <i class="fa-solid fa-paper-plane"></i>
                        Send reply
                    </button>
                </div>
            </form>

            @error('message')
                <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-600">{{ $message }}</div>
            @enderror

            @if(session('success'))
                <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
