<section id="about" class="py-20 section-base animate-fade-in about-section" style="background: none !important; animation: none !important;">
  <div class="layout-container">
    <div class="about-elevated">
      <div class="about-left">
        <div class="about-image-wrapper">
          <span class="about-invite-badge">You&rsquo;re Invited</span>
          <div class="about-image-arch">
            <img src="<?php echo e(asset('customerVideo/Video/A.png')); ?>" alt="InkWise invitation sample" loading="lazy" />
          </div>
          <span class="about-line-accent"></span>
          <span class="about-circle-accent"></span>
        </div>
      </div>
      <div class="about-right">
        <?php ($settings = $siteSettings ?? \App\Models\SiteSetting::current()); ?>
        <?php ($aboutHeading = trim($settings->about_heading ?? 'About Us')); ?>
        <?php ($headingParts = $aboutHeading !== '' ? explode(' ', $aboutHeading, 2) : ['About', 'Us']); ?>
        <?php ($aboutBody = $settings->about_body ?: 'InkWise is your trusted partner in crafting elegant, personalized invitations and thoughtful giveaways. We blend modern editorial flair with heartfelt storytelling to celebrate every milestone big or small. From whimsical birthdays to timeless weddings, our design studio turns your ideas into keepsake-worthy pieces that delight every guest.'); ?>
        <div class="about-content">
          <h2 class="about-heading">
            <span><?php echo e($headingParts[0] ?? 'About'); ?></span>
            <?php if(!empty($headingParts[1])): ?>
              <?php echo e($headingParts[1]); ?>

            <?php endif; ?>
          </h2>
          <p class="about-subheading">Design For Everybody</p>
          <p class="about-body"><?php echo nl2br(e($aboutBody)); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/partials/about.blade.php ENDPATH**/ ?>