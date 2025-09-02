

<section id="templates" class="py-12">
  <hr class="section-divider">
  <h2 class="text-center text-3xl font-bold text-indigo-700 mb-10" style="font-family: 'Seasons', serif;">
    Find your perfect match
  </h2>

  <div class="flex flex-col gap-12 items-center">
    <!-- Birthday -->
    <a href="{{ route('templates.birthday') }}" class="block">
      <div class="relative flex items-center gap-8 gradient-pink p-6 rounded-3xl animate-slide-in hover:scale-105 transition-transform cursor-pointer">
        <img src="/costumerimage/star.png" class="absolute -top-6 -left-8 w-12 h-12 rotate-12" alt="">
        <img src="/costumerimage/gift.png" class="absolute -bottom-6 right-10 w-12 h-12" alt="">

        <div class="w-80 -rotate-3 rounded-3xl shadow-xl overflow-hidden border-4 border-pink-300">
          <img src="/costumerimage/birthday.png" alt="Birthday" class="w-full h-40 object-cover">
        </div>

        <div class="max-w-sm">
          <h3 class="text-pink-600 text-2xl text-center font-extrabold">Birthday</h3>
          <p class="text-pink-500 text-sm text-center">
            Choose from Unique Birthday Invitation and Giveaways Designs
          </p>
        </div>
      </div>
    </a>

    <!-- Wedding -->
    <a href="{{ route('templates.wedding') }}" class="block">
      <div class="relative flex items-center gap-8 gradient-yellow p-6 rounded-3xl animate-slide-in delay-150 hover:scale-105 transition-transform cursor-pointer">
        <img src="/costumerimage/ring.png" class="absolute -top-6 -right-8 w-20 h-20 rotate-12 z-20" alt="">
        <img src="/costumerimage/ribbon.png" class="absolute -bottom-6 left-10 w-12 h-12 rotate-12" alt="">

        <div class="max-w-sm">
          <h3 class="text-yellow-700 text-center text-2xl font-extrabold">Wedding</h3>
          <p class="text-yellow-600 text-sm text-center">
            Choose from Unique Wedding Invitation and Giveaways Designs
          </p>
        </div>

        <div class="w-80 rotate-3 rounded-3xl shadow-xl overflow-hidden border-4 border-yellow-400">
          <img src="/costumerimage/wedding.png" alt="Wedding" class="w-full h-40 object-cover">
        </div>
      </div>
    </a>

    <!-- Corporate -->
    <a href="{{ route('templates.corporate') }}" class="block">
      <div class="relative flex items-center gap-8 gradient-orange p-6 rounded-3xl animate-slide-in delay-300 hover:scale-105 transition-transform cursor-pointer">
        <img src="/costumerimage/Glass.png" class="absolute -top-6 -left-8 w-20 h-20 rotate-12 z-20" alt="">
        <img src="/costumerimage/paperclip.png" class="absolute -bottom-6 left-44 w-12 h-12 rotate-12 z-20" alt="">

        <div class="w-80 -rotate-2 rounded-3xl shadow-xl overflow-hidden border-4 border-orange-400">
          <img src="/costumerimage/corporate.png" alt="Corporate" class="w-full h-40 object-cover">
        </div>

        <div class="max-w-sm">
          <h3 class="text-orange-600 text-center text-2xl font-extrabold">Corporate</h3>
          <p class="text-black-500 text-center">
            Choose from Unique Corporate Invitation and Giveaways Designs
          </p>
        </div>
      </div>
    </a>

    <!-- Baptism -->
    <a href="{{ route('templates.baptism') }}" class="block">
      <div class="relative flex items-center gap-8 gradient-skyblue p-6 rounded-3xl animate-slide-in delay-500 hover:scale-105 transition-transform cursor-pointer">
        <img src="/costumerimage/footprint.png" class="absolute -top-6 -right-8 w-20 h-20 rotate-12 z-20" alt="">
        <img src="/costumerimage/cloud.png" class="absolute -bottom-8 left-32 w-12 h-12 rotate-12" alt="">

        <div class="max-w-sm">
          <h3 class="text-blue-600 text-2xl font-extrabold text-center">Baptism</h3>
          <p class="text-orange-500 text-sm text-center">
            Choose from Unique Baptism Invitation and Giveaways Designs
          </p>
        </div>

        <div class="w-80 rotate-3 rounded-3xl shadow-xl overflow-hidden border-4 border-blue-400">
          <img src="/costumerimage/baptism.png" alt="Baptism" class="w-full h-40 object-cover">
        </div>
      </div>
    </a>
  </div>
</section>
