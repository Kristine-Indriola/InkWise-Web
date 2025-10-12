document.addEventListener('DOMContentLoaded', function(){
  const container = document.getElementById('favoritesContainer');
  if (!container) return;
  container.addEventListener('click', function(e){
    const btn = e.target.closest('.remove-favorite');
    if (!btn) return;
    const card = btn.closest('.favorite-card');
    if (!card) return;
    const id = card.getAttribute('data-id');
    // For now, just remove visually. In production, call server to remove favorite.
    card.style.transition = 'opacity .18s ease, transform .18s ease';
    card.style.opacity = '0';
    card.style.transform = 'scale(.98)';
    setTimeout(()=>card.remove(), 220);
    // Optionally: fire a fetch to delete favorite: /customer/favorites/{id}
  });
});
