document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
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

    document.querySelectorAll('[data-change-form]').forEach(form => {
        form.addEventListener('submit', event => {
            const button = form.querySelector('button[type="submit"]');
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

    document.querySelectorAll('[data-change-panel]').forEach(panel => {
        panel.addEventListener('toggle', () => {
            if (panel.open) {
                document.querySelectorAll('[data-change-panel]').forEach(otherPanel => {
                    if (otherPanel !== panel) {
                        otherPanel.open = false;
                    }
                });
                const input = panel.querySelector('input[type="password"]');
                if (input) {
                    setTimeout(() => input.focus(), 0);
                }
                body.classList.add('is-password-change-open');
            } else {
                body.classList.remove('is-password-change-open');
            }
        });

        panel.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                panel.open = false;
            }
        });
    });

    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', () => {
            const wrapper = button.closest('.password-input');
            const input = wrapper?.querySelector('[data-password-input]');
            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            const icon = button.querySelector('i');
            if (icon) {
                if (isHidden) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.add('fa-eye');
                    icon.classList.remove('fa-eye-slash');
                }
            }

            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });

    document.querySelectorAll('[data-cancel-change]').forEach(button => {
        button.addEventListener('click', () => {
            const panel = button.closest('[data-change-panel]');
            const form = button.closest('form');
            if (form) {
                form.reset();
                form.querySelectorAll('[data-password-input]').forEach(input => {
                    input.type = 'password';
                });
            }
            const toggleButtons = panel?.querySelectorAll('[data-toggle-password]');
            toggleButtons?.forEach(toggle => {
                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-eye');
                    icon.classList.remove('fa-eye-slash');
                }
                toggle.setAttribute('aria-label', 'Show password');
            });
            if (panel) {
                panel.open = false;
            }
        });
    });

    document.querySelectorAll('[data-change-overlay]').forEach(overlay => {
        overlay.addEventListener('click', () => {
            const panel = overlay.closest('[data-change-panel]');
            if (panel) {
                panel.open = false;
            }
        });
    });
});
