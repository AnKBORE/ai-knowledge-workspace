'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var tables = document.querySelectorAll('.spaces-data-table, .membership-table');
    if (!tables.length || typeof window.DataTable !== 'function') return;

    tables.forEach(function (table) {
        var isSpacesTable = table.classList.contains('spaces-data-table');
        var isTechnicalTable = table.classList.contains('technical-admin-table');
        var isJournalTable = table.classList.contains('journal-table');
        var finalColumn = table.rows[0].cells.length - 1;

        var dataTable = new window.DataTable(table, {
            responsive: { details: { type: 'inline', target: 'td.dtr-control' } },
            autoWidth: false,
            searchDelay: 250,
            pageLength: 50,
            lengthMenu: [10, 25, 50, 100],
            order: isJournalTable ? [[1, 'desc']] : (isTechnicalTable ? [[2, 'asc'], [1, 'asc']] : (isSpacesTable ? [[2, 'asc']] : [[3, 'desc']])),
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'table-row-number dtr-control all' },
                { targets: finalColumn, orderable: false, searchable: false }
            ],
            drawCallback: function () {
                var api = this.api();
                var page = api.page.info();
                api.column(0, { page: 'current' }).nodes().each(function (cell, index) {
                    var number = cell.querySelector('.row-number-value');
                    if (number) number.textContent = page.start + index + 1;
                });
                var pagingLabels = { first: 'Première page', previous: 'Page précédente', next: 'Page suivante', last: 'Dernière page' };
                api.table().container().querySelectorAll('.dt-paging-button[data-dt-idx]').forEach(function (button) {
                    var label = pagingLabels[button.getAttribute('data-dt-idx')];
                    if (label) {
                        button.setAttribute('aria-label', label);
                        button.setAttribute('title', label);
                    }
                });
            },
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging'
            },
            language: {
                emptyTable: 'Aucune donnée à afficher.',
                info: 'Affichage de _START_ à _END_ sur _TOTAL_ résultats',
                infoEmpty: 'Aucun résultat à afficher',
                infoFiltered: '(filtrés parmi _MAX_ résultats)',
                lengthMenu: 'Afficher _MENU_ entrées',
                loadingRecords: 'Chargement…',
                processing: 'Traitement…',
                search: 'Rechercher :',
                zeroRecords: 'Aucun résultat ne correspond à votre recherche',
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                },
                aria: {
                    sortAscending: ': activer pour trier par ordre croissant',
                    sortDescending: ': activer pour trier par ordre décroissant'
                }
            }
        });

        function syncResponsiveControl(api) {
            var hasHiddenColumns = !!(api.responsive && typeof api.responsive.hasHidden === 'function' && api.responsive.hasHidden());
            table.classList.toggle('has-responsive-details', hasHiddenColumns);
        }
        window.jQuery(table).on('responsive-resize.dt', function (event, api) {
            syncResponsiveControl(api);
        });
        if (dataTable.responsive && typeof dataTable.responsive.recalc === 'function') dataTable.responsive.recalc();
        syncResponsiveControl(dataTable);

        window.jQuery(table).on('responsive-display.dt', function (event, api, row, expanded) {
            var button = row.node().querySelector('.dtr-control-btn');
            if (button) {
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                button.setAttribute('aria-label', expanded ? 'Masquer les informations complémentaires' : 'Afficher les informations complémentaires');
                var icon = button.querySelector('span');
                if (icon) icon.textContent = expanded ? '−' : '+';
            }
        });
    });
});
