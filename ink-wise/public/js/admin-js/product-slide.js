// product-slide.js (DEPRECATED)
// This file was retained as an inert stub for backwards-compatibility in case
// any older pages still reference it. The product modal behavior has been
// consolidated into `public/js/admin/product.js`. The loader in the admin
// layout now injects that single script and stylesheet.

/* noop stub */
(function(){
    window.__productSlideAssetsLoaded = true;
    // Provide a harmless attachProductPanelHandlers if some legacy code calls it
    if (!window.attachProductPanelHandlers) {
        window.attachProductPanelHandlers = function(){ /* intentionally empty (deprecated) */ };
    }
})();
