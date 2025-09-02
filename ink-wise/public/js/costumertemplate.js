
/* Costumertemplate js */
// === Modal Logic ===
function openTemplateModal(type) {
    const modal = document.getElementById("templateModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalTemplates = document.getElementById("modalTemplates");

    modal.classList.remove("hidden");

    if (type === "invitation") {
        modalTitle.innerText = "Wedding Invitations";
        modalTemplates.innerHTML = `
            <img src="/costumerimage/template.png" class="rounded-lg shadow-md hover:scale-105 transition">
            <img src="/costumerimage/template.png" class="rounded-lg shadow-md hover:scale-105 transition">
        `;
    } else if (type === "giveaways") {
        modalTitle.innerText = "Wedding Giveaways";
        modalTemplates.innerHTML = `
            <img src="/costumerimage/glass.png" class="rounded-lg shadow-md hover:scale-105 transition">
            <img src="/costumerimage/glass.png" class="rounded-lg shadow-md hover:scale-105 transition">
        `;
    }
}

function closeTemplateModal() {
    const modal = document.getElementById("templateModal");
    modal.classList.add("hidden");
}

// === Floating Animations for Logo ===
document.addEventListener("DOMContentLoaded", () => {
    const logo = document.querySelector(".logo-i");
    logo.animate([
        { transform: "translateY(0px)" },
        { transform: "translateY(-5px)" },
        { transform: "translateY(0px)" }
    ], {
        duration: 3000,
        iterations: Infinity
    });
});
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
function goToTemplatePage(type) {
    if (type === 'wedding-invitation') {
        window.location.href = "/templates/wedding/invitations"; 
    } else if (type === 'wedding-giveaway') {
        window.location.href = "/templates/wedding/giveaways";
    }
}

//DESIGN

