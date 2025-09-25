// product-slide.js
// Attach handlers for the product slide-in panel: close button, backdrop, Esc key, focus management
(function(window, document){
    'use strict';

    function getFocusableElements(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'))
            .filter(el => !el.hasAttribute('disabled'));
    }

    var _locked = false;

    function lockBodyScroll() {
        if (_locked) return;
        var scrollY = window.scrollY || document.documentElement.scrollTop;
        document.documentElement.style.setProperty('--modal-scroll-top', -scrollY + 'px');
        document.body.style.position = 'fixed';
        document.body.style.top = -scrollY + 'px';
        _locked = true;
    }

    function unlockBodyScroll() {
        if (!_locked) return;
        var top = parseInt(document.body.style.top || '0') * -1;
        document.body.style.position = '';
        document.body.style.top = '';
        window.scrollTo(0, top);
        _locked = false;
    }

    function openPanel(panelWrapper) {
        if (!panelWrapper) return;
        // Make sure panel is visible and let the browser paint, then add 'show' to trigger CSS transition
        panelWrapper.style.display = 'block';
        // hide the rest of the app to screen readers
        var main = document.querySelector('main, #app, .app, body > .container');
        if (main) main.setAttribute('aria-hidden', 'true');
        requestAnimationFrame(function(){
            requestAnimationFrame(function(){ panelWrapper.classList.add('show'); });
        });
        lockBodyScroll();
        var focusables = getFocusableElements(panelWrapper);
        if (focusables.length) focusables[0].focus();
    }

    function closePanel(panelWrapper, restoreFocusTo) {
        if (!panelWrapper) return;
        panelWrapper.classList.remove('show');
        // allow animation to finish
        setTimeout(function(){
            try {
                if (panelWrapper.__productCleanup) panelWrapper.__productCleanup();
            } catch(e){}
            try { panelWrapper.remove(); } catch(e) { panelWrapper.style.display = 'none'; }
            unlockBodyScroll();
            // restore aria-hidden
            var main = document.querySelector('main, #app, .app, body > .container');
            if (main) main.removeAttribute('aria-hidden');
            if (restoreFocusTo && typeof restoreFocusTo.focus === 'function') restoreFocusTo.focus();
        }, 260);
    }

    function attachProductPanelHandlers(root) {
        var panelWrapper = root || document.getElementById('product-slide-panel');
        if (!panelWrapper) return;

        var panel = panelWrapper.querySelector('.panel');
        var backdrop = panelWrapper.querySelector('.panel-backdrop');
        var btnClose = panelWrapper.querySelector('#close-panel');
        var lastActive = document.activeElement;

        // Remove previous handlers if called again
        if (panel.__productHandlersAttached) return;
        panel.__productHandlersAttached = true;

    if (btnClose) btnClose.addEventListener('click', function(){ closePanel(panelWrapper, lastActive); });
    if (backdrop) backdrop.addEventListener('click', function(){ closePanel(panelWrapper, lastActive); });
    // Secondary close button in footer if present
    var secondary = panelWrapper.querySelector('#panel-close-secondary');
    if (secondary) secondary.addEventListener('click', function(){ closePanel(panelWrapper, lastActive); });

    // Thumbnail click handlers: swap main image and toggle selected state
    var mainImg = panelWrapper.querySelector('#panel-main-image');
    var thumbs = panelWrapper.querySelectorAll('.thumbnails .thumb');
    if (thumbs && thumbs.length && mainImg) {
        thumbs.forEach(function(btn){
            btn.addEventListener('click', function(){
                var src = btn.getAttribute('data-src') || (btn.querySelector('img') && btn.querySelector('img').src);
                if (!src) return;
                // update main image with a quick fade (toggle class if desired)
                try { mainImg.style.opacity = '0.01'; } catch(e){}
                // small timeout to allow CSS transition if present
                setTimeout(function(){ mainImg.src = src; try { mainImg.style.opacity = ''; } catch(e){} }, 80);

                // update selected class
                thumbs.forEach(function(t){ t.classList.remove('selected'); });
                btn.classList.add('selected');
            });
        });
    }

        // Escape to close
        function onKey(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                closePanel(panelWrapper, lastActive);
            }
            // simple focus trap: keep focus within panel
            if (e.key === 'Tab') {
                var focusables = getFocusableElements(panel);
                if (focusables.length === 0) return;
                var first = focusables[0];
                var last = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault(); last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault(); first.focus();
                }
            }
        }

    document.addEventListener('keydown', onKey);

        // Expose a cleanup function on the panel so re-initialization won't duplicate handlers
        panelWrapper.__productCleanup = function(){
            document.removeEventListener('keydown', onKey);
            try { delete panel.__productHandlersAttached; } catch(e){}
            try { delete panelWrapper.__productCleanup; } catch(e){}
        };

        openPanel(panelWrapper);
    }

    // Expose globally so index fetch can call it after injecting HTML
    window.attachProductPanelHandlers = attachProductPanelHandlers;

    // Auto-attach for panels already present at DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function(){
        attachProductPanelHandlers();
    });

})(window, document);
