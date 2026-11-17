// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Keep the suggested rubric name aligned with the selected activity.
 *
 * @module     local_evalimport/import_form
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update the name on activity changes while preserving any manual edit.
 *
 * @param {Object} names Suggested names indexed by activity ID.
 */
export const init = (names) => {
    const activity = document.getElementById('id_cmid');
    const name = document.getElementById('id_rubricname');
    if (!activity || !name) {
        return;
    }

    let suggested = names[activity.value];
    activity.addEventListener('change', () => {
        const next = names[activity.value];
        if (name.value === suggested && typeof next === 'string') {
            name.value = next;
        }
        suggested = next;
    });
};
