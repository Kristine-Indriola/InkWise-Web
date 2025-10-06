<section id="contact" class="py-16 bg-white shadow-inner">
  <hr class="section-divider">

  <div class="layout-container">
    @php($settings = $siteSettings ?? \App\Models\SiteSetting::current())

    <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">{{ $settings->contact_heading }}</h2>

    @if(filled($settings->contact_subheading))
      <p class="text-center text-gray-600 mb-10">
        {{ $settings->contact_subheading }}
      </p>
    @endif

    <div class="grid md:grid-cols-2 gap-10">
      <!-- Contact Info -->
      <div class="text-black">
        @if(filled($settings->contact_company))
          <h3 class="text-xl font-semibold mb-4" style="font-family: 'Playfair Display', serif;">{{ $settings->contact_company }}</h3>
        @endif

        @if(filled($settings->contact_address))
          <p class="mb-3"><strong>📍 Address:</strong> {{ $settings->contact_address }}</p>
        @endif

        @if(filled($settings->contact_phone))
          <p class="mb-3"><strong>📞 Phone:</strong> {{ $settings->contact_phone }}</p>
        @endif

        @if(filled($settings->contact_email))
          <p class="mb-3"><strong>✉️ Email:</strong> {{ $settings->contact_email }}</p>
        @endif

        @php($hoursLines = $settings->contactHoursLines())
        @if($hoursLines)
          <p><strong>🕒 Business Hours:</strong><br>{!! collect($hoursLines)->map(fn ($line) => e($line))->implode('<br>') !!}</p>
        @endif
      </div>

      <!-- Contact Form -->
      <div>
        {{-- Success Message --}}
        @if(session('success'))
            <div id="successMessage" 
                 class="p-4 mb-4 text-green-800 bg-green-100 border border-green-300 rounded-lg transition-opacity duration-1000">
                ✅ {{ session('success') }}
            </div>

            <script>
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    const msg = document.getElementById('successMessage');
                    if (msg) {
                        msg.style.opacity = '0';
                        setTimeout(() => msg.remove(), 1000); // remove completely after fade
                    }
                }, 5000);
            </script>
        @endif

        <form class="space-y-4 bg-white p-6 rounded-2xl shadow-lg" 
              method="POST" 
              action="{{ route('messages.store') }}">
            @csrf

            {{-- Name --}}
            <input type="text" name="name" placeholder="Your Name" 
                   value="{{ old('name') }}"
                   class="w-full p-3 border rounded-lg text-black @error('name') border-red-500 animate-shake @enderror" required>
            @error('name')
                <p class="text-red-500 text-sm mt-1">⚠️ {{ $message }}</p>
            @enderror

            {{-- Email --}}
            <input type="email" name="email" placeholder="Your Email" 
                   value="{{ old('email') }}"
                   class="w-full p-3 border rounded-lg text-black @error('email') border-red-500 animate-shake @enderror" required>
            @error('email')
                <p class="text-red-500 text-sm mt-1">⚠️ {{ $message }}</p>
            @enderror

            {{-- Message --}}
            <textarea name="message" placeholder="Your Message" rows="4" 
                      class="w-full p-3 border rounded-lg text-black @error('message') border-red-500 animate-shake @enderror" required>{{ old('message') }}</textarea>
            @error('message')
                <p class="text-red-500 text-sm mt-1">⚠️ {{ $message }}</p>
            @enderror

            <button class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                Send Message
            </button>
        </form>
      </div>
    </div>
  </div>
</section>

{{-- Shake Animation --}}
<style>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}
.animate-shake {
  animation: shake 0.4s;
}
</style>
