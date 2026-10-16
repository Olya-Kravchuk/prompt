/**
 * Initialize prompt form loading effects.
 */
export const init = () => {
    const forms = document.querySelectorAll('#chatform');

    forms.forEach((form) => {
        form.addEventListener('submit', () => {
            const status = form.querySelector('#prompt-status');
            const button = form.querySelector('.send-button');
            const textarea = form.querySelector('#question');

            if (!textarea || !textarea.value.trim()) {
                return;
            }

            if (status) {
                status.hidden = false;
            }

            if (button) {
                button.classList.add('is-loading');
            }
        });
    });
};