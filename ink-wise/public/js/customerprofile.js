// customerprofile.js

// Sidebar active state
document.querySelectorAll('.nav-item').forEach((el) => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        el.classList.add('active');
    });
});

// Help bubble close
document.getElementById('helpClose')?.addEventListener('click', () => {
    document.getElementById('help').style.display = 'none';
});

// Profile photo upload + preview
const input = document.getElementById('photoInput');
const img = document.getElementById('avatarImg');
const fallback = document.getElementById('avatarFallback');
const removeBtn = document.getElementById('removePhoto');

input?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        img.src = event.target.result;
        img.classList.remove('hidden');
        fallback.classList.add('hidden');
    }
    reader.readAsDataURL(file);
});

removeBtn?.addEventListener('click', () => {
    input.value = '';
    img.src = '';
    img.classList.add('hidden');
    fallback.classList.remove('hidden');
});
