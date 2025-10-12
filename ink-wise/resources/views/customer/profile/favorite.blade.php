@extends('layouts.customerprofile')

@section('content')
<!-- Page-specific assets -->
<link rel="stylesheet" href="{{ asset('css/customer/favorite.css') }}">
<script src="{{ asset('js/customer/favorite.js') }}" defer></script>

<div class="bg-white rounded-2xl p-6 shadow-sm">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">My Favorites</h2>
    <p class="text-sm text-gray-500">Saved invitations & giveaways</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="favoritesContainer">
    {{-- Sample favorites will be injected here by server-side sample data or JS fallback --}}

    @php
      $sampleInvitations = [
        ['id'=>101,'title'=>'Rustic Wedding Invitation','image'=>asset('customerimages/image/invitation.png'), 'type'=>'Invitation'],
        ['id'=>102,'title'=>'Modern Floral Invite','image'=>asset('customerimages/image/invitation2.png'), 'type'=>'Invitation'],
      ];
      $sampleGiveaways = [
        ['id'=>201,'title'=>'Anniversary Giveaway Pack','image'=>asset('customerimages/image/giveaway.png'), 'type'=>'Giveaway'],
        ['id'=>202,'title'=>'Birthday Favor Bundle','image'=>asset('customerimages/image/giveaway2.png'), 'type'=>'Giveaway'],
      ];

      $favorites = array_merge($sampleInvitations, $sampleGiveaways);
    @endphp

    @foreach($favorites as $fav)
      <div class="favorite-card bg-gray-50 rounded-xl p-3 shadow-sm flex gap-3 items-start" data-id="{{ $fav['id'] }}">
        <img src="{{ $fav['image'] }}" alt="{{ $fav['title'] }}" class="w-24 h-24 object-cover rounded-lg">
        <div class="flex-1">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-lg font-medium">{{ $fav['title'] }}</h3>
              <p class="text-sm text-gray-500 mt-1">{{ $fav['type'] }}</p>
            </div>
            <div class="text-right">
              <button class="remove-favorite text-red-500 hover:text-red-600 text-sm" aria-label="Remove favorite">Remove</button>
            </div>
          </div>
          <p class="text-sm text-gray-600 mt-2">A short preview description goes here. This item is saved to your favorites for quick access.</p>
        </div>
      </div>
    @endforeach

  </div>
</div>
@endsection
