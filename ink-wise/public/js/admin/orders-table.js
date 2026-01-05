// Debug: confirm this script is loaded by the page
console.log('[inkwise] orders-table.js loaded');

(function(){
  // Query helpers
  const $ = (s, root=document) => root.querySelector(s);
  const $$ = (s, root=document) => Array.from(root.querySelectorAll(s));

  const searchInput = $('#ordersSearch');
  const filterBtns = $$('.filter-btn');
  const table = document.querySelector('.admin-orders-table');
  const tbody = table ? table.tBodies[0] : null;
  // view toggle removed — grid/table switch not used anymore

  function normalizeText(str){ return (str||'').toString().toLowerCase().trim(); }

  // Search handler
  function applySearch(){
    const q = normalizeText(searchInput.value);
    if(!tbody) return;
    const rows = Array.from(tbody.rows);
    rows.forEach(row => {
      const text = normalizeText(row.textContent);
      row.style.display = text.indexOf(q) === -1 ? 'none' : '';
    });
  }

  if(searchInput){
    searchInput.addEventListener('input', debounce(applySearch, 200));
  }

  // Filter buttons
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.getAttribute('data-filter');
      filterBtns.forEach(b => b.setAttribute('aria-pressed','false'));
      btn.setAttribute('aria-pressed','true');
      applyFilter(filter);
    });
  });

  function applyFilter(filter){
    if(!tbody) return;
    const rows = Array.from(tbody.rows);
    rows.forEach(row => {
      if(filter === 'all') { row.style.display = ''; return; }
      const statusCell = row.cells[5]; // status column index
      const status = normalizeText(statusCell ? statusCell.textContent : '');
      row.style.display = status.indexOf(filter) === -1 ? 'none' : '';
    });
  }

  // grid view removed

  // per-page control removed: pagination is handled by Laravel links

  // Archive order handler
  document.addEventListener('click', function (e) {
    const archiveBtn = e.target.closest && e.target.closest('.btn-archive');
    if (!archiveBtn) return;
    const orderId = archiveBtn.getAttribute('data-order-id');
    if (!orderId) return;
    if (!confirm('Archive this order? This will hide it from the active orders list.')) return;

    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta ? tokenMeta.getAttribute('content') : null;
    
    // Determine if we're on staff or admin page
    const isStaffPage = window.location.pathname.startsWith('/staff');
    const baseUrl = isStaffPage ? '/staff' : '/admin';
    
    fetch(baseUrl + '/orders/' + orderId + '/archive', {
      method: 'PATCH',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token || '',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    }).then(async resp => {
      // Try to parse JSON if available
      const contentType = resp.headers.get('content-type') || '';
      let body = null;
      if (contentType.indexOf('application/json') !== -1) {
        body = await resp.json().catch(() => null);
      } else {
        body = await resp.text().catch(() => null);
      }

      if (resp.ok) {
        // remove row
        const row = document.querySelector('tr[data-order-id="' + orderId + '"]');
        if (row) row.remove();
        // optional success message
        if (body && body.message) alert(body.message);
        return;
      }

      // Handle common failure cases
      if (resp.status === 419) {
        alert('Session expired or CSRF token mismatch. Please refresh the page and try again.');
        return;
      }
      if (resp.status === 403) {
        alert((body && body.error) ? body.error : 'You are not authorized to archive this order.');
        return;
      }
      if (resp.status >= 300 && resp.status < 400) {
        // likely a redirect to login
        alert('Server redirected — you may need to login.');
        return;
      }

      // Generic failure
      const errMsg = (body && (body.error || body.message)) ? (body.error || body.message) : (typeof body === 'string' ? body : 'Unknown error');
      alert('Failed to archive order: ' + errMsg);
    }).catch(err => { alert('Network error while archiving order'); console.error(err); });
  });

  // Utility: debounce
  function debounce(fn, wait){ let t; return function(){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, arguments), wait); }; }

})();
