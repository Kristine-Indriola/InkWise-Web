@extends('layouts.admin')

@section('content')
<div class="chat-container" style="max-width:600px; margin:auto; padding:20px;">
    <h2>Chat with {{ $customer->name }}</h2>

    <div id="chat-messages" style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:auto;">
        @php $currentDate = null; @endphp
        @foreach($messages as $msg)
            @php
                $msgDate = $msg->created_at->toDateString();
                $today = \Carbon\Carbon::today()->toDateString();
                $yesterday = \Carbon\Carbon::yesterday()->toDateString();

                $displayDate = $msgDate == $today ? 'Today' : ($msgDate == $yesterday ? 'Yesterday' : $msg->created_at->format('M d, Y'));
            @endphp

            @if ($currentDate !== $msgDate)
                <div style="text-align:center; margin:10px 0; font-size:12px; color:gray;">
                    — {{ $displayDate }} —
                </div>
                @php $currentDate = $msgDate; @endphp
            @endif

            <div style="margin:10px; text-align: {{ ($msg->sender_id == $currentUser['id'] && $msg->sender_type == $currentUser['type']) ? 'right' : 'left' }};">
                <div style="display:inline-block; max-width:80%;">
                    <span style="background: {{ ($msg->sender_id == $currentUser['id'] && $msg->sender_type == $currentUser['type']) ? '#4caf50' : '#eee' }};
                                 color: {{ ($msg->sender_id == $currentUser['id'] && $msg->sender_type == $currentUser['type']) ? 'white' : 'black' }};
                                 padding:8px; border-radius:10px; display:block;">
                        {{ $msg->message }}
                    </span>
                    <small style="font-size:11px; color:gray;">
                        {{ $msg->created_at->format('h:i A') }}
                    </small>
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route(request()->is('admin/*') ? 'admin.messages.send' : 'staff.messages.send', $customer->id) }}" 
          style="margin-top:10px; display:flex; gap:5px;">
        @csrf
        <input type="text" name="message" placeholder="Type your message..." required
               style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
        <button type="submit" style="padding:8px 15px; border:none; background:#4caf50; color:white; border-radius:5px;">
            Send
        </button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let chatBox = document.getElementById("chat-messages");
    chatBox.scrollTop = chatBox.scrollHeight;
});
</script>
@endsection
