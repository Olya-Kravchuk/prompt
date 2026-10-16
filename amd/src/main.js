/**
 * Prompt history accordion.
 *
 * @module     block_prompt/main
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const promptQuestions = document.querySelectorAll('.prompt-question');

    promptQuestions.forEach((question) => {
        question.addEventListener('click', () => {
            const item = question.closest('.prompt-item');

            const isOpen = question.getAttribute('aria-expanded') === 'true';

            question.setAttribute('aria-expanded', String(!isOpen));
            item.classList.toggle('is-open', !isOpen);
        });
    });
};