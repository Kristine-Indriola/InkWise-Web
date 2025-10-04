document.addEventListener('DOMContentLoaded', () => {
    const unlockInput = document.querySelector('#unlock-password');
    if (unlockInput) {
        unlockInput.focus();
    }

    document.querySelectorAll('.js-flash-message').forEach(message => {
        setTimeout(() => {
            message.classList.add('is-dismissed');
        }, 6000);
    });

    document.querySelectorAll('[data-reset-form]').forEach(form => {
        form.addEventListener('submit', event => {
            const button = form.querySelector('button');
            const confirmMessage = button?.dataset?.confirm;
            if (confirmMessage && !window.confirm(confirmMessage)) {
                event.preventDefault();
                return;
            }

            if (button && !button.classList.contains('is-loading')) {
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
                button.setAttribute('disabled', 'disabled');
            }
        });
    });

    document.querySelectorAll('[data-lock-form]').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button');
            if (button) {
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
                button.setAttribute('disabled', 'disabled');
            }
        });
    });
});
