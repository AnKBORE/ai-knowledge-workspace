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

    window.initializeSpaceSelects = initializeSelects;
    var $pageSelects = $('.space-admin select').filter(function () { return !this.closest('.modal'); });
    initializeSelects($pageSelects);
    document.addEventListener('shown.bs.modal', function (event) { initializeSelects(event.target); });
});
