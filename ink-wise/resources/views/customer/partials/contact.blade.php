<section id="contact" class="py-16 section-base">
  

  <div class="layout-container section-content">
    @php($settings = $siteSettings ?? \App\Models\SiteSetting::current())
    @php($contactHeading = trim($settings->contact_heading ?? '') ?: 'Get In Touch')
    @php($contactSubheading = trim($settings->contact_subheading ?? '') ?: 'We would love to hear from you. Reach out anytime.')
    @php($contactCompany = trim($settings->contact_company ?? '') ?: 'InkWise Studio')
    @php($contactAddress = trim($settings->contact_address ?? '') ?: '123 Main Street, Suite 200, Metro City, State 12345')
    @php($contactPhone = trim($settings->contact_phone ?? '') ?: '(555) 123-4567')
    @php($contactEmail = trim($settings->contact_email ?? '') ?: 'hello@inkwise.studio')
    @php($contactHoursLines = $settings->contactHoursLines())
    @php($contactHoursLines = $contactHoursLines && count($contactHoursLines) ? $contactHoursLines : ['Mon-Fri: 9:00 AM - 6:00 PM', 'Sat: 10:00 AM - 3:00 PM'])

    <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">{{ $contactHeading }}</h2>

    <p class="text-center text-gray-600 mb-10">
      {{ $contactSubheading }}
    </p>

    <div class="grid md:grid-cols-2 gap-10">
      <div class="text-black">
        <h3 class="text-xl font-semibold mb-4" style="font-family: 'Playfair Display', serif;">{{ $contactCompany }}</h3>

        <p class="mb-3"><strong>📍 Address:</strong> {{ $contactAddress }}</p>

        <p class="mb-3"><strong>📞 Phone:</strong> {{ $contactPhone }}</p>

        <p class="mb-3"><strong>✉️ Email:</strong> {{ $contactEmail }}</p>

        <p><strong>🕒 Business Hours:</strong><br>{!! collect($contactHoursLines)->map(fn ($line) => e($line))->implode('<br>') !!}</p>
      </div>

        <!-- Contact Form -->
        <div>
        @auth
          <div class="space-y-4 bg-white p-6 rounded-2xl shadow-lg text-center">
            <p class="text-gray-700">
              You're signed in. Start a live chat with our support team for quicker help.
            </p>
            <a href="{{ route('customerprofile.index', ['chat' => 'open']) }}"
               class="inline-flex justify-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
               onclick="openCustomerSupportChat(event)">
              Message Chat
            </a>
          </div>
        @else
          <div class="space-y-4 bg-white p-6 rounded-2xl shadow-lg text-center">
            <p class="text-gray-700">
              Please create an Inkwise account or sign in to send us a message.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <a href="{{ route('customer.register.form') }}" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">Register</a>
              <a href="{{ route('dashboard', ['modal' => 'login']) }}" class="px-6 py-3 border border-blue-500 text-blue-500 rounded-lg hover:bg-blue-50 transition">Log In</a>
            </div>
          </div>
        @endauth
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

@push('scripts')
<script>
(function() {
  if (window.openCustomerSupportChat) {
    return;
  }
  const chatRedirectUrl = '{{ route('customerprofile.index', ['chat' => 'open']) }}';

  window.openCustomerSupportChat = function(ev) {
    const openBtn = document.getElementById('openChatBtn');
    const modal = document.getElementById('chatModal');

    if (openBtn) {
      if (ev) ev.preventDefault();
      openBtn.click();
    } else if (modal) {
      if (ev) ev.preventDefault();
      modal.classList.remove('hidden');
      const floating = document.getElementById('chatFloatingBtn');
      if (floating) {
        floating.classList.add('hidden');
      }
    } else {
      if (!ev || !ev.defaultPrevented) {
        window.location.href = chatRedirectUrl;
      }
      return;
    }
    if (typeof window.loadChatThread === 'function') {
      window.loadChatThread();
    }
    const input = document.getElementById('customerChatInput');
    if (input) {
      input.focus();
    }
  };
})();
</script>
@endpush
