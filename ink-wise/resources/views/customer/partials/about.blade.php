<section id="about" class="py-20 section-base animate-fade-in about-section" style="background: none !important; animation: none !important;">
  <div class="layout-container">
    <div class="about-elevated">
      <div class="about-left">
        <div class="about-image-wrapper">
          <span class="about-invite-badge">You&rsquo;re Invited</span>
          <div class="about-image-arch">
            <img src="{{ asset('customerVideo/Video/A.png') }}" alt="InkWise invitation sample" loading="lazy" />
          </div>
          <span class="about-line-accent"></span>
          <span class="about-circle-accent"></span>
        </div>
      </div>
      <div class="about-right">
        @php($settings = $siteSettings ?? \App\Models\SiteSetting::current())
        <div class="about-content">
          <h2 class="about-heading"><span>About</span> Us</h2>
          <p class="about-subheading">Design For Everybody</p>
          <p class="about-body">
            InkWise is your trusted partner in crafting elegant, personalized invitations and thoughtful giveaways. We blend modern editorial flair with heartfelt storytelling to celebrate every milestone big or small. From whimsical birthdays to timeless weddings, our design studio turns your ideas into keepsake-worthy pieces that delight every guest.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

