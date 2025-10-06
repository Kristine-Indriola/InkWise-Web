<hr class="section-divider">
<section id="about"
  class="py-12 bg-gradient-to-r from-[#e97d69] via-[#fcb2a6] to-[#fed9d3] animate-fade-in-up">

  <div class="layout-container text-center">
    @php($settings = $siteSettings ?? \App\Models\SiteSetting::current())

    <h2 class="categories-section text-3xl font-bold mb-6">{{ $settings->about_heading }}</h2>

    @if(filled($settings->about_body))
      <p class="text-lg text-gray-700">
        {{ $settings->about_body }}
      </p>
    @endif
  </div>
</section>

