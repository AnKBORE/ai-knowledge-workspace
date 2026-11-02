'use strict';

document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') return;

    var $ = window.jQuery;
    function initializeSelects(root) {
        $(root).find('select').addBack('select').each(function () {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) return;
            if ($select.closest('.dt-length').length) return;
            var $modal = $select.closest('.modal');
            $select.select2({
                width: '100%',
                placeholder: $select.attr('data-placeholder') || window.UI('s2_placeholder'),
                minimumResultsForSearch: 0,
                dropdownParent: $modal.length ? $modal : $(document.body),
                language: {
                    errorLoading: function () { return window.UI('s2_error'); },
                    inputTooLong: function (args) { return window.UI('s2_too_long') + (args.input.length - args.maximum) + window.UI('s2_too_long_tail'); },
                    inputTooShort: function (args) { return window.UI('s2_too_short') + args.minimum + window.UI('s2_too_short_tail'); },
                    loadingMore: function () { return window.UI('s2_more'); },
                    noResults: function () { return window.UI('s2_none'); },
                    searching: function () { return window.UI('s2_searching'); },
                    removeAllItems: function () { return window.UI('s2_remove_all'); },
                    removeItem: function () { return window.UI('s2_remove_one'); },
                    search: function () { return window.UI('s2_search'); }
                }
            });
        });
    }

    window.initializeSpaceSelects = initializeSelects;

    var $pageSelects = $('.space-admin select').filter(function () { return !this.closest('.modal'); });
    initializeSelects($pageSelects);
    document.addEventListener('shown.bs.modal', function (event) { initializeSelects(event.target); });
});
