

<!-- Categories Section -->
<section id="categories" class="categories-section py-16">
    <hr class="section-divider">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold mb-6">Categories</h2>
        <p class="text-lg text-gray-600">Browse through our ready-made templates designed for all occasions.</p>
        
        <!-- Grid for categories -->
        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 gap-12 justify-center">
            
            <!-- Invitations -->
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Invitations</h3>
                <div class="accordion-gallery flex space-x-2 overflow-hidden rounded-xl">
                    
                    <!-- Wedding -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/wedding/invitations') }}">
                            <img src="{{ asset('customerimage/invite1.png') }}" 
                                 alt="Wedding Invitation" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                    
                    <!-- Baptism -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/baptism') }}">
                            <img src="{{ asset('customerimage/invite2.png') }}" 
                                 alt="Baptism Invitation" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                    
                    <!-- Birthday -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/birthday') }}">
                            <img src="{{ asset('customerimage/invite3.png') }}" 
                                 alt="Birthday Invitation" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                    
                    <!-- Corporate -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/corporate') }}">
                            <img src="{{ asset('customerimage/invite4.png') }}" 
                                 alt="Corporate Invitation" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-4">Elegant invitations with various themes.</p>
            </div>

            <!-- Giveaways -->
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Giveaways</h3>
                <div class="accordion-gallery flex space-x-2 overflow-hidden rounded-xl">
                    
                    <!-- Wedding -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/wedding/giveaways') }}">
                            <img src="{{ asset('customerimage/weddinggive.png') }}" 
                                 alt="Wedding Giveaway" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                    
                    <!-- Baptism -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/baptism/giveaways') }}">
                            <img src="{{ asset('customerimage/baptismgive.png') }}" 
                                 alt="Baptism Giveaway" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                    
                    <!-- Birthday -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/birthday/giveaways') }}">
                            <img src="{{ asset('customerimage/birthdaygive.png') }}" 
                                 alt="Birthday Giveaway" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                    
                    <!-- Corporate -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="{{ url('/templates/corporate/giveaways') }}">
                            <img src="{{ asset('customerimage/corporategive.png') }}" 
                                 alt="Corporate Giveaway" 
                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 bg-white p-2">
                        </a>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-4">Creative giveaways for every occasion.</p>
            </div>

        </div>
    </div>
</section>
