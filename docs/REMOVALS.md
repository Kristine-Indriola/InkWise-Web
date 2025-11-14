Removed files and rationale

- editing_base.js (deleted)
  - Reason: contained a legacy/smaller copy of editor helper functions duplicated by `public/js/customer/editing.js`.
  - No templates or pages referenced `editing_base.js`; deleting it reduces duplication and prevents accidental inclusion of stale code.
  - If any behavior is missing after removal, the canonical implementation lives at `public/js/customer/editing.js` and can be patched there.

If you want this removed file restored or parts merged back, I can recreate its unique bits into the main editor JS and add a short test harness.