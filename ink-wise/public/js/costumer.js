/* Costumer js */
document.addEventListener("DOMContentLoaded", () => {
  const loginModal    = document.getElementById("loginModal");
  const registerModal = document.getElementById("registerModal");

  const openLogin              = document.getElementById("openLogin");
  const openRegister           = document.getElementById("openRegister");
  const closeLogin             = document.getElementById("closeLogin");
  const closeRegister          = document.getElementById("closeRegister");
  const openLoginFromRegister  = document.getElementById("openLoginFromRegister");
  const openRegisterFromLogin  = document.getElementById("openRegisterFromLogin");

  const userDropdownBtn = document.getElementById("userDropdownBtn");
  const userDropdown    = document.getElementById("userDropdown");

  const show = el => el && el.classList.remove("hidden");
  const hide = el => el && el.classList.add("hidden");
  const closeDropdown = () => userDropdown && userDropdown.classList.add("hidden");

  // --- Openers ---
  openLogin && openLogin.addEventListener("click", e => { 
    e.preventDefault(); 
    show(loginModal); 
    hide(registerModal); 
    closeDropdown(); 
  });
  openRegister && openRegister.addEventListener("click", e => { 
    e.preventDefault(); 
    show(registerModal); 
    hide(loginModal); 
    closeDropdown(); 
  });

  // --- Closers ---
  closeLogin && closeLogin.addEventListener("click", () => hide(loginModal));
  closeRegister && closeRegister.addEventListener("click", () => hide(registerModal));

  // --- Switchers ---
  openLoginFromRegister && openLoginFromRegister.addEventListener("click", e => {
    e.preventDefault(); 
    show(loginModal); 
    hide(registerModal); 
  });
  openRegisterFromLogin && openRegisterFromLogin.addEventListener("click", e => {
    e.preventDefault(); 
    show(registerModal); 
    hide(loginModal); 
  });

  // --- Dropdown ---
  if (userDropdownBtn && userDropdown) {
    userDropdownBtn.addEventListener("click", () => {
      userDropdown.classList.toggle("hidden");
    });
    document.addEventListener("click", (e) => {
      if (!userDropdown.contains(e.target) && !userDropdownBtn.contains(e.target)) {
        userDropdown.classList.add("hidden");
      }
    });
  }

  // --- Close modals with ESC key ---
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      hide(loginModal);
      hide(registerModal);
    }
  });

  // --- Close modals on backdrop click ---
  [loginModal, registerModal].forEach(modal => {
    modal && modal.addEventListener("click", (e) => {
      if (e.target === modal) hide(modal);
    });
  });

  // --- Auto-open logic (backend can set window.__OPEN_MODAL__) ---
  const open = (typeof window.__OPEN_MODAL__ === 'string') ? window.__OPEN_MODAL__ : null;
  if (open === 'login')   { show(loginModal); hide(registerModal); }
  if (open === 'register'){ show(registerModal); hide(loginModal); }

  // --- Auto-close after login/register success ---
  if (window.__IS_AUTHENTICATED__) {
    hide(loginModal);
    hide(registerModal);
  }
});
