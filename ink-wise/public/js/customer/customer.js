/* customer js */
document.addEventListener("DOMContentLoaded", () => {
    const loginModal = document.getElementById("loginModal");
    const registerModal = document.getElementById("registerModal");
    const openLogin = document.getElementById("openLogin");
    const openRegister = document.getElementById("openRegister");
    const closeLogin = document.getElementById("closeLogin");
    const closeRegister = document.getElementById("closeRegister");
    const openLoginFromRegister = document.getElementById("openLoginFromRegister");
    const openRegisterFromLogin = document.getElementById("openRegisterFromLogin");
    const userDropdownBtn = document.getElementById("userDropdownBtn");
    const userDropdownMenu = document.getElementById("userDropdownMenu");

    const show = el => el && el.classList.remove("hidden");
    const hide = el => el && el.classList.add("hidden");

    // Modal openers
    openLogin && openLogin.addEventListener("click", e => {
        e.preventDefault();
        show(loginModal);
        hide(registerModal);
    });
    openRegister && openRegister.addEventListener("click", e => {
        e.preventDefault();
        show(registerModal);
        hide(loginModal);
    });

    // Modal closers
    closeLogin && closeLogin.addEventListener("click", () => hide(loginModal));
    closeRegister && closeRegister.addEventListener("click", () => hide(registerModal));

    // Switchers
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

    // Unified Dropdown Toggle (remove all duplicates)
    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            userDropdownMenu.classList.toggle("hidden");
        });

        // Close on outside click
        document.addEventListener("click", (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.add("hidden");
            }
        });
    }

    // ESC key to close modals
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            hide(loginModal);
            hide(registerModal);
        }
    });

    // Backdrop click to close modals
    [loginModal, registerModal].forEach(modal => {
        modal && modal.addEventListener("click", (e) => {
            if (e.target === modal) hide(modal);
        });
    });

    // Auto-open modals (from backend)
    const open = (typeof window.__OPEN_MODAL__ === 'string') ? window.__OPEN_MODAL__ : null;
    if (open === 'login') show(loginModal), hide(registerModal);
    if (open === 'register') show(registerModal), hide(loginModal);

    // Auto-close modals after auth
    if (window.__IS_AUTHENTICATED__) {
        hide(loginModal);
        hide(registerModal);
    }
});
