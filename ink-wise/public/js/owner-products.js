// owner-products.js
// Lightweight UI helpers to align owner products front-end behavior with admin materials
(function(){
  'use strict';
  document.addEventListener('DOMContentLoaded', function(){
    // Hover elevation for table rows
    document.querySelectorAll('.table tbody tr').forEach(function(row){
      row.addEventListener('mouseenter', function(){
        row.style.transform = 'translateY(-2px)';
        row.style.boxShadow = '0 8px 18px rgba(14,46,120,0.06)';
      });
      row.addEventListener('mouseleave', function(){
        row.style.transform = '';
        row.style.boxShadow = '';
      });
    });

    // Generic toggle helper: elements with data-toggle-target attribute
    document.querySelectorAll('[data-toggle-target]').forEach(function(btn){
      var target = document.querySelector(btn.getAttribute('data-toggle-target'));
      if (!target) return;
      btn.addEventListener('click', function(e){
        e.stopPropagation();
        var open = target.getAttribute('data-open') === 'true';
        if (open) {
          target.style.display = 'none';
          target.setAttribute('data-open','false');
          btn.setAttribute('aria-expanded','false');
        } else {
          target.style.display = 'block';
          target.setAttribute('data-open','true');
          btn.setAttribute('aria-expanded','true');
        }
      });
      document.addEventListener('click', function(e){
        if (!target.contains(e.target) && e.target !== btn) {
          target.style.display = 'none';
          target.setAttribute('data-open','false');
          btn.setAttribute('aria-expanded','false');
        }
      });
    });
  });
})();
