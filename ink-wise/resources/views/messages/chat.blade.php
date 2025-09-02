@extends('layouts.app')

@section('content')
<div class="chat-container" style="max-width:600px; margin:auto; padding:20px;">
    <h2>Chat</h2>
    
    <div id="chat-messages" class="messages" 
         style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:auto;">
        @php
            $currentDate = null;
        @endphp

        @foreach($messages as $msg)
            @php
                $msgDate = $msg->created_at->toDateString();
                $today = \Carbon\Carbon::today()->toDateString();
                $yesterday = \Carbon\Carbon::yesterday()->toDateString();

                if ($msgDate == $today) {
                    $displayDate = "Today";
                } elseif ($msgDate == $yesterday) {
                    $displayDate = "Yesterday";
                } else {
                    $displayDate = $msg->created_at->format('M d, Y');
                }
            @endphp

            {{-- Show date header when date changes --}}
            @if ($currentDate !== $msgDate)
                <div style="text-align:center; margin:10px 0; font-size:12px; color:gray;">
                    — {{ $displayDate }} —
                </div>
                @php $currentDate = $msgDate; @endphp
            @endif

            {{-- Message bubble --}}
            <div style="margin:10px; text-align: {{ $msg->sender_id == Auth::id() ? 'right' : 'left' }};">
                <div style="display:inline-block; max-width:80%;">
                    <span style="background: {{ $msg->sender_id == Auth::id() ? '#4caf50' : '#eee' }};
                                 color: {{ $msg->sender_id == Auth::id() ? 'white' : 'black' }};
                                 padding:8px; border-radius:10px; display:block;">
                        {{ $msg->message }}
                    </span>
                    <small style="font-size: 11px; color: gray;">
                        {{ $msg->created_at->format('h:i A') }}
                    </small>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Message input --}}
    <form method="POST" action="{{ route('messages.send', $userId) }}" 
          style="margin-top:10px; display:flex; gap:5px;">
        @csrf
        <input type="text" name="message" placeholder="Type your message..." required
               style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
        <button type="submit" 
                style="padding:8px 15px; border:none; background:#4caf50; color:white; border-radius:5px;">
            Send
        </button>
    </form>
</div>

{{-- Auto-scroll script --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let chatBox = document.getElementById("chat-messages");
        chatBox.scrollTop = chatBox.scrollHeight;
    });
</script>
@endsection
