// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prompt history accordion.
 *
 * @module     block_prompt/main
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const questions = document.querySelectorAll('.prompt-question');

    questions.forEach((question) => {
        question.addEventListener('click', () => {
            const item = question.closest('.prompt-item');

            const isOpen = question.getAttribute('aria-expanded') === 'true';

            question.setAttribute('aria-expanded', String(!isOpen));
            item.classList.toggle('is-open', !isOpen);
        });
    });
};