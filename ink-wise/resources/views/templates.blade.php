@extends('dashboard') <!-- Optional: if using layout -->

@section('content')
<div class="max-w-7xl mx-auto px-6 text-center py-16">
    <h2 class="text-3xl font-bold mb-6">{{ ucfirst($category) }} Templates</h2>
    <p class="text-lg text-gray-600 mb-8">Choose a template type below:</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 justify-center">
        <a href="{{ route('template.preview', ['id' => 'invitation']) }}">
            <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition-transform hover:scale-105">
                Invitation
            </div>
        </a>
        <a href="{{ route('template.preview', ['id' => 'giveaway']) }}">
            <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition-transform hover:scale-105">
                Giveaways
            </div>
        </a>
    </div>
</div>
@endsection
