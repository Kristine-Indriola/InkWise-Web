@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="stock">
    <h3>Customer Messages</h3>

    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#6a2ebc; color:white; text-align:left;">
                <th style="padding:10px;">Profile</th>
                <th style="padding:10px;">Name</th>
                <th style="padding:10px;">Email</th>
                <th style="padding:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            <tr style="border-bottom:1px solid #ddd;">
                <td style="padding:10px;">
                    <img src="{{ $customer->profile_picture ?? asset('images/customer.png') }}" 
                         alt="avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                </td>
                <td style="padding:10px;">{{ $customer->name }}</td>
                <td style="padding:10px;">{{ $customer->email }}</td>
                <td style="padding:10px;">
                    <a href="{{ route('admin.messages.chat', $customer->id) }}" 
                       style="padding:5px 10px; background:#6a2ebc; color:white; border-radius:5px; text-decoration:none;">
                        Open Chat
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding:10px; text-align:center;">No messages found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
