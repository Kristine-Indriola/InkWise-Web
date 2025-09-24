@extends('layouts.admin')

@section('title', 'Chat with ' . $customer->name)

@section('content')
<div class="chat-container" style="max-width:900px; margin:0 auto;">

    <h3 style="margin-bottom:20px;">Chat with {{ $customer->name }}</h3>

    <!-- Messages -->
    <div class="chat-box" 
         style="border:1px solid #ddd; border-radius:10px; padding:20px; height:500px; overflow-y:auto; background:#f9f9f9; margin-bottom:20px;">
        
        @forelse($messages as $msg)
            <div style="margin-bottom:15px; 
                        display:flex; 
                        flex-direction: {{ $msg->sender_type == 'user' ? 'row-reverse' : 'row' }};">
                
                <div style="max-width:70%; 
                            padding:10px 15px; 
                            border-radius:15px;
                            background: {{ $msg->sender_type == 'user' ? '#6a2ebc' : '#e0e0e0' }};
                            color: {{ $msg->sender_type == 'user' ? 'white' : 'black' }};
                            ">
                    {{ $msg->message }}
                    <div style="font-size:12px; margin-top:5px; opacity:0.8;">
                        {{ $msg->created_at->format('M d, Y h:i A') }}
                    </div>
                </div>
            </div>
        @empty
            <p style="text-align:center; color:#888;">No messages yet.</p>
        @endforelse
    </div>

    <!-- Reply Form -->
    <form method="POST" action="{{ route('admin.messages.send', $customer->id) }}" style="display:flex; gap:10px;">
        @csrf
        <textarea name="message" placeholder="Type your reply..." 
                  style="flex:1; padding:10px; border:1px solid #ccc; border-radius:8px; resize:none;" required></textarea>
        <button type="submit" 
                style="padding:10px 20px; background:#6a2ebc; color:white; border:none; border-radius:8px; cursor:pointer;">
            Send
        </button>
    </form>

    @if(session('success'))
        <div style="margin-top:15px; padding:10px; background:#d4edda; color:#155724; border-radius:5px;">
            {{ session('success') }}
        </div>
    @endif

</div>
@endsection
