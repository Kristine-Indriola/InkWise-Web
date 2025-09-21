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

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const fullAddressInput = document.getElementById("full_address");

    form.addEventListener("submit", () => {
        const house = document.querySelector("[name='house_number']").value;
        const street = document.querySelector("[name='street']").value;
        const barangay = document.querySelector("[name='barangay']").value;
        const city = document.querySelector("[name='city']").value;
        const province = document.querySelector("[name='province']").value;
        const postal = document.querySelector("[name='postal_code']").value;
        const country = document.querySelector("[name='country']").value;

        let fullAddress = `${house} ${street}, ${barangay}, ${city}, ${province}, ${postal}, ${country}`;
        fullAddress = fullAddress.replace(/\s+/g, " ").replace(/, ,/g, ",").trim();

        fullAddressInput.value = fullAddress;
    });
});

