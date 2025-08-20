// -------- LOGIN / REGISTER MODALS --------
const loginModal = document.getElementById('loginModal');
const registerModal = document.getElementById('registerModal');
const openLoginBtn = document.getElementById('openLogin');
const closeLoginBtn = document.getElementById('closeLogin');
const closeRegisterBtn = document.getElementById('closeRegister');
const openRegisterFromLogin = document.getElementById('openRegisterFromLogin');
const openLoginFromRegister = document.getElementById('openLoginFromRegister');

// Open login modal
if(openLoginBtn){
    openLoginBtn.addEventListener('click', () => {
        loginModal.classList.remove('hidden');
    });
}

// Close login modal
if(closeLoginBtn){
    closeLoginBtn.addEventListener('click', () => {
        loginModal.classList.add('hidden');
    });
}

// Close register modal
if(closeRegisterBtn){
    closeRegisterBtn.addEventListener('click', () => {
        registerModal.classList.add('hidden');
    });
}

// Switch login -> register
if(openRegisterFromLogin){
    openRegisterFromLogin.addEventListener('click', () => {
        loginModal.classList.add('hidden');
        registerModal.classList.remove('hidden');
    });
}

// Switch register -> login
if(openLoginFromRegister){
    openLoginFromRegister.addEventListener('click', () => {
        registerModal.classList.add('hidden');
        loginModal.classList.remove('hidden');
    });
}

// -------- USER DROPDOWN --------
const userDropdownBtn = document.getElementById('userDropdownBtn');
const userDropdown = document.getElementById('userDropdown');

if(userDropdownBtn){
    userDropdownBtn.addEventListener('click', (e) => {
        e.preventDefault();
        userDropdown.classList.toggle('hidden');
    });

    // Close dropdown if clicked outside
    window.addEventListener('click', (e) => {
        if(!userDropdown.contains(e.target) && !userDropdownBtn.contains(e.target)){
            userDropdown.classList.add('hidden');
        }
    });
}

// Extra helpers (if you want to call manually)
function openLogin() {
    document.getElementById('loginModal').classList.remove('hidden');
}
function closeLogin() {
    document.getElementById('loginModal').classList.add('hidden');
}
function openRegister() {
    document.getElementById('registerModal').classList.remove('hidden');
}
function closeRegister() {
    document.getElementById('registerModal').classList.add('hidden');
}