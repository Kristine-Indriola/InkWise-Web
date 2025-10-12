<!--
  materials_helpers partial
  Purpose: provide small, page-scoped helpers (JS utilities and optional tiny CSS) so owner blades can use
  the structure and components provided by public/css/admin-css/materials.css as a base without editing it.

  NOTE: This file intentionally does NOT change materials.css. It only provides:
  - small JS helpers (toggle menus, modal open/close, simple form helpers)
  - optional small class aliases for owner blades when necessary
  - a short guide comment for devs to follow materials.css conventions
-->

{{-- Small, non-invasive CSS helpers scoped to owner pages only --}}
<style>
  /* Keep these tiny and page-scoped so materials.css remains the single source of truth for visuals */
  .owner-page-compact .page-inner { max-width: 1200px; margin: 0 auto; }
  .owner-page-compact .materials-toolbar__search .form-control { min-width: 160px; }
  /* Hide helper-outline in production; useful for debugging layout mismatches */
  .owner-debug-outline *:not(.no-outline) { outline: 1px dashed rgba(99,102,241,0.12) !important; }
</style>

{{-- Small JS utilities to align behavior across owner pages --}}
<script>
  window.__ownerHelpers = {
    toggleElement(el) {
      if (!el) return;
      if (el.style.display === 'none' || el.hidden) {
        el.style.display = '';
        el.hidden = false;
      } else {
        el.style.display = 'none';
        el.hidden = true;
      }
    },
    openModal(modalEl) {
      if (!modalEl) return;
      modalEl.classList.remove('hidden');
      modalEl.style.display = 'flex';
      // trap focus lightly
      const focusable = modalEl.querySelector('button, [href], input, select, textarea, [tabindex]');
      if (focusable) focusable.focus();
    },
    closeModal(modalEl) {
      if (!modalEl) return;
      modalEl.classList.add('hidden');
      modalEl.style.display = 'none';
    },
    // Quick helper to submit a form by name or element
    submitForm(form) {
      try { (typeof form === 'string' ? document.querySelector(form) : form).submit(); } catch(e) { }
    }
  };
</script>
