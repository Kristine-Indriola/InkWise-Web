<!-- Categories Section -->
<section id="categories" class="categories-section py-16 section-with-media">
    <img src="<?php echo e(asset('customerVideo/Video/wed.jpg')); ?>" class="section-bg-media" alt="Categories background" loading="lazy">
    <div class="layout-container section-content text-center">
        
        <h2 class="text-3xl font-bold mb-6 coco-gothic categories-title" style="font-family: 'Cormorant Garamond', 'Coco Gothic', serif !important;">Categories</h2>
                <p class="text-lg text-gray-600 text-center mx-auto max-w-2xl cormorant">
    See what's hot! Browse our most popular invitation and giveaways categories to find the perfect design for your event. Discover trendy themes and create invitations everyone will love.
</p>  
    
        <!-- Grid for categories -->
        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 gap-12 justify-center">
            
            <!-- Invitations -->
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-4 coco-gothic" style="font-family: 'Cormorant Garamond', 'Coco Gothic', serif !important;">Invitations</h3>
                <div class="accordion-gallery flex space-x-2 overflow-hidden rounded-xl">
                    
                    <!-- Wedding -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/wedding/invitations')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/invite1.png')); ?>" 
                                   alt="Wedding Invitation" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                    
                    <!-- Baptism -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/baptism/invitations')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/invite2.png')); ?>" 
                                   alt="Baptism Invitation" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                    
                    <!-- Birthday -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/birthday/invitations')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/invite3.png')); ?>" 
                                   alt="Birthday Invitation" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                    
                    <!-- Corporate -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/corporate/invitations')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/invite4.png')); ?>" 
                                   alt="Corporate Invitation" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-4 cormorant">Elegant invitations with various themes.</p>
            </div>

            <!-- Giveaways -->
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-4 coco-gothic" style="font-family: 'Cormorant Garamond', 'Coco Gothic', serif !important;">Giveaways</h3>
                <div class="accordion-gallery flex space-x-2 overflow-hidden rounded-xl">
                    
                    <!-- Wedding -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/wedding/giveaways')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/weddinggive.png')); ?>" 
                                   alt="Wedding Giveaway" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                    
                    <!-- Baptism -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/baptism/giveaways')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/baptismgive.png')); ?>" 
                                   alt="Baptism Giveaway" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                    
                    <!-- Birthday -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/birthday/giveaways')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/birthdaygive.png')); ?>" 
                                   alt="Birthday Giveaway" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                    
                    <!-- Corporate -->
                    <div class="accordion-item flex-1 overflow-hidden transition-all duration-500 ease-in-out hover:flex-[4]">
                        <a href="<?php echo e(url('/templates/corporate/giveaways')); ?>">
                               <img src="<?php echo e(asset('Customerimages/image/corporategive.png')); ?>" 
                                   alt="Corporate Giveaway" 
                                   class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105 transparent-card-img">
                        </a>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-4 cormorant">Creative giveaways for every occasion.</p>
            </div>

        </div>
    </div>
</section>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/partials/categories.blade.php ENDPATH**/ ?>