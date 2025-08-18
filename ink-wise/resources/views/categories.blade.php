@extends('dashboard') <!-- Optional: if you want to extend your main layout -->

@section('content')
<section id="categories" class="py-16 relative overflow-hidden">
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Categories</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
      <a href="{{ route('templates', ['category' => 'baptism']) }}">
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300">
          <img src="{{ asset('image/baptism.png') }}" alt="Baptism" class="w-full h-48 object-cover">
          <div class="p-4 text-center">
            <h3 class="text-lg font-semibold text-gray-800">Baptism</h3>
          </div>
        </div>
      </a>
      <a href="{{ route('templates', ['category' => 'wedding']) }}">
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300">
          <img src="{{ asset('image/wedding.png') }}" alt="Wedding" class="w-full h-48 object-cover">
          <div class="p-4 text-center">
            <h3 class="text-lg font-semibold text-gray-800">Wedding</h3>
          </div>
        </div>
      </a>
      <a href="{{ route('templates', ['category' => 'corporate']) }}">
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300">
          <img src="{{ asset('image/corporate.png') }}" alt="Corporate" class="w-full h-48 object-cover">
          <div class="p-4 text-center">
            <h3 class="text-lg font-semibold text-gray-800">Corporate</h3>
          </div>
        </div>
      </a>
      <a href="{{ route('templates', ['category' => 'birthday']) }}">
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition transform hover:-translate-y-2 hover:scale-105 duration-300">
          <img src="{{ asset('image/birthday.png') }}" alt="Birthday" class="w-full h-48 object-cover">
          <div class="p-4 text-center">
            <h3 class="text-lg font-semibold text-gray-800">Birthday</h3>
          </div>
        </div>
      </a>
    </div>
  </div>
</section>
@endsection
