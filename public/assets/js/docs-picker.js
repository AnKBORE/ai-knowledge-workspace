'use strict';

/**
 * Source-document picker (task page).
 *
 * Deliberately standalone: the previous attempts lived inside larger files, and
 * an error anywhere earlier in those files prevented this code from running at
 * all — which is why the control kept falling back to a plain <select>.
 * A dedicated, defensive file is the only way to guarantee it initialises.
 */
(function () {
    var attempts = 0;

    function boot() {
        if (!window.jQuery || !window.jQuery.fn) {
            return false;
        }

        if (typeof window.jQuery.fn.select2 !== 'function') {
            // The plugin is missing: say so in the console and keep the control
            // usable with a native fallback instead of an unstyled box.
            var field = document.querySelector('.task-docs-field');

            if (field) {
                field.classList.add('is-native');
            }

            if (window.console && console.warn) {
                console.warn('[docs-picker] jQuery.fn.select2 is not available — native fallback applied.');
            }

            return true;
        }

        var $ = window.jQuery;
        var $docs = $('#task-docs');

        if (!$docs.length) {
            return false;
        }

        if ($docs.hasClass('select2-hidden-accessible')) {
            return true;
        }

        var template = $docs.attr('data-count-template') || '%d';

        var options = {
            width: '100%',
            placeholder: $docs.attr('data-placeholder') || 'Sélectionner une option',
            closeOnSelect: false,
            dropdownParent: $(document.body),
            language: {
                noResults: function () { return 'Aucun document trouvé'; },
                searching: function () { return 'Recherche…'; }
            }
        };

        // A multiple select puts its search inline by default; the request is a
        // search bar INSIDE the dropdown, which is done by decorating the
        // dropdown adapter. Falls back to the inline search if the build does
        // not expose the AMD modules.
        try {
            var Utils = $.fn.select2.amd.require('select2/utils');
            var Dropdown = $.fn.select2.amd.require('select2/dropdown');
            var DropdownSearch = $.fn.select2.amd.require('select2/dropdown/search');
            var AttachBody = $.fn.select2.amd.require('select2/dropdown/attachBody');

            options.dropdownAdapter = Utils.Decorate(
                Utils.Decorate(Dropdown, DropdownSearch),
                AttachBody
            );
            options.minimumResultsForSearch = Infinity;
        } catch (error) {
            options.minimumResultsForSearch = 0;
        }

        try {
            $docs.select2(options);
        } catch (error) {
            return false;
        }

        var $count = $('#task-docs-count');
        var $clear = $('#task-docs-clear');
        var $field = $docs.closest('.task-docs-field');

        function refresh() {
            var values = $docs.val();
            var n = values ? values.length : 0;

            if ($count.length) {
                $count.text(template.replace('%d', n)).prop('hidden', n === 0);
            }

            if ($clear.length) {
                $clear.prop('hidden', n === 0);
            }

            if ($field.length) {
                $field.toggleClass('has-selection', n > 0);
            }
        }

        $docs.on('change', refresh);

        if ($clear.length) {
            $clear.on('click', function (event) {
                event.preventDefault();
                $docs.val(null).trigger('change');
            });
        }

        refresh();
        $docs.attr('data-select2-applied', '1');

        return true;
    }

    function start() {
        if (boot()) { return; }

        // The element may not exist yet on a slow render; retry a few times
        // instead of giving up silently.
        if (attempts >= 40) { return; }

        attempts += 1;
        window.setTimeout(start, 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
