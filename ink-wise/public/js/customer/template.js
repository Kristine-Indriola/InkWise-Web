document.addEventListener('DOMContentLoaded', () => {
    const logo = document.querySelector('.logo-i');
    if (logo && typeof logo.animate === 'function') {
        logo.animate([
            { transform: 'translateY(0px)' },
            { transform: 'translateY(-5px)' },
            { transform: 'translateY(0px)' }
        ], {
            duration: 3000,
            iterations: Infinity
        });
    }
});
