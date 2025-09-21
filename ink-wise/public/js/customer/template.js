/* customertemplate js */
// === Modal Logic ===
function openTemplateModal(type) {
    const modal = document.getElementById("templateModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalTemplates = document.getElementById("modalTemplates");

    modal.classList.remove("hidden");
    modal.classList.add("ocean-fade-in"); // 🌊 add fade when opening

    if (type === "invitation") {
        modalTitle.innerText = "Wedding Invitations";
        modalTemplates.innerHTML = `
            <img src="/customerimage/template.png" class="rounded-lg shadow-md hover:scale-105 transition">
            <img src="/customerimage/template.png" class="rounded-lg shadow-md hover:scale-105 transition">
        `;
    } else if (type === "giveaways") {
        modalTitle.innerText = "Wedding Giveaways";
        modalTemplates.innerHTML = `
            <img src="/customerimage/glass.png" class="rounded-lg shadow-md hover:scale-105 transition">
            <img src="/customerimage/glass.png" class="rounded-lg shadow-md hover:scale-105 transition">
        `;
    }
}

function closeTemplateModal() {
    const modal = document.getElementById("templateModal");
    modal.classList.add("hidden");
    modal.classList.remove("ocean-fade-in"); // reset when closing
}

// === Floating Animations for Logo ===
document.addEventListener("DOMContentLoaded", () => {
    const logo = document.querySelector(".logo-i");
    if (logo) {
        logo.animate([
            { transform: "translateY(0px)" },
            { transform: "translateY(-5px)" },
            { transform: "translateY(0px)" }
        ], {
            duration: 3000,
            iterations: Infinity
        });
    }
});

// === Active Nav Highlight ===
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('header nav a.nav-link');

    if (!navLinks.length) return;

    const currentPath = window.location.pathname.replace(/\/$/, ''); // remove trailing slash
    navLinks.forEach(link => {
        try {
            const linkPath = new URL(link.href).pathname.replace(/\/$/, '');
            if (linkPath === currentPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        } catch (err) {
            // ignore invalid URLs
        }
    });
});
