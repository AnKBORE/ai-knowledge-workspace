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
                placeholder: $select.attr('data-placeholder') || 'Sélectionner une option',
                minimumResultsForSearch: 0,
                dropdownParent: $modal.length ? $modal : $(document.body),
                language: {
                    errorLoading: function () { return 'Les résultats ne peuvent pas être chargés.'; },
                    inputTooLong: function (args) { return 'Supprimez ' + (args.input.length - args.maximum) + ' caractère(s).'; },
                    inputTooShort: function (args) { return 'Saisissez au moins ' + args.minimum + ' caractère(s).'; },
                    loadingMore: function () { return 'Chargement de résultats supplémentaires…'; },
                    noResults: function () { return 'Aucun résultat trouvé'; },
                    searching: function () { return 'Recherche…'; },
                    removeAllItems: function () { return 'Supprimer tous les éléments'; },
                    removeItem: function () { return 'Supprimer l’élément'; },
                    search: function () { return 'Rechercher'; }
                }
            });
        });
    }

    // ---- Documents picker (task page) ----
    // A multi-select is unusable when the dropdown closes after every click:
    // closeOnSelect is disabled, a live counter shows how many documents bound
    // the task, and "clear all" resets the scope in one gesture.
    function initDocsPicker() {
        var $docs = $('#task-docs');
        if (!$docs.length || $docs.hasClass('select2-hidden-accessible')) return;

        $docs.select2({
            width: '100%',
            placeholder: $docs.attr('data-placeholder') || 'Sélectionner une option',
            closeOnSelect: false,
            minimumResultsForSearch: 0,
            dropdownParent: $(document.body),
            language: {
                noResults: function () { return 'Aucun document trouvé'; },
                searching: function () { return 'Recherche…'; },
                removeAllItems: function () { return 'Tout effacer'; },
                removeItem: function () { return 'Retirer'; }
            }
        });

        var $count = $('#task-docs-count');
        var $clear = $('#task-docs-clear');
        var template = $docs.attr('data-count-template') || '%d';

        function refresh() {
            var values = $docs.val();
            var n = values ? values.length : 0;

            if ($count.length) {
                $count.text(template.replace('%d', n)).prop('hidden', n === 0);
            }

            if ($clear.length) {
                $clear.prop('hidden', n === 0);
            }
        }

        $docs.on('change', refresh);
        $clear.on('click', function () {
            $docs.val(null).trigger('change');
        });

        refresh();
    }
    window.initializeSpaceSelects = initializeSelects;
    initDocsPicker();

    var $pageSelects = $('.space-admin select').filter(function () { return !this.closest('.modal'); });
    initializeSelects($pageSelects);
    document.addEventListener('shown.bs.modal', function (event) { initializeSelects(event.target); });
});
