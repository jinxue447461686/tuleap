/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

var tuleap = tuleap || {};

(function ($) {
    function formatItem(item) {
        var type = item.type ? item.type : 'other';

        if (type === 'group') {
            return '<i class="icon-group autocompleter-icon-group"></i>' + tuleap.escaper.html(item.text);

        } else if (type === 'user') {
            return '<div class="avatar autocompleter-avatar"></div>' + tuleap.escaper.html(item.text);

        } else {
            return tuleap.escaper.html(item.text);
        }
    }

    function createSearchChoice(term, data) {
        var data_that_matches_term = $(data).filter(function() {
            return this.text == term;
        });

        if (data_that_matches_term.length === 0) {
            return {
                id: term,
                text: term
            };
        }
    }

    tuleap.loadUserAndUgroupAutocompleter = function (input) {
        if (! input) {
            return;
        }

        $(input).select2({
            width: '100%',
            dropdownCssClass: 'autocompleter-users-and-ugroups-dropdown',
            tags: true,
            multiple: true,
            tokenSeparators: [",", " "],
            minimumInputLength: 3,
            placeholder: input.dataset.placeholder,
            ajax: {
                url: "/user/autocomplete.php",
                dataType: 'json',
                quietMillis: 250,
                data: function (term, page) {
                    return {
                        return_type                        : 'json_for_select_2',
                        'with-groups-of-user-in-project-id': input.dataset.projectId,
                        name: term
                    };
                },
                results: function (data, page) {
                    return {
                        results: data.results
                    };
                }
            },
            createSearchChoice: createSearchChoice,
            formatResult: formatItem,
            formatSelection: formatItem,
            escapeMarkup: function (m) { return m; }
        });
    };
})(jQuery);
