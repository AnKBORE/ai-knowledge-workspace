'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var tables = document.querySelectorAll('.spaces-data-table, .membership-table');
    if (!tables.length || typeof window.DataTable !== 'function') return;

    tables.forEach(function (table) {
        var isSpacesTable = table.classList.contains('spaces-data-table');
        var isTechnicalTable = table.classList.contains('technical-admin-table');
        var isJournalTable = table.classList.contains('journal-table');
        var isDocumentsTable = table.classList.contains('documents-table');
        var isAdminInventory = table.classList.contains('admin-inventory-table');
        var finalColumn = table.rows[0].cells.length - 1;

        var dataTable = new window.DataTable(table, {
            responsive: { details: { type: 'inline', target: 'td.dtr-control' } },
            autoWidth: false,
            searchDelay: 250,
            pageLength: 50,
            lengthMenu: [10, 25, 50, 100],
            order: isDocumentsTable ? [[0, 'asc']] : (isJournalTable ? [[1, 'desc']] : (isAdminInventory ? [[1, 'asc']] : (isTechnicalTable ? [[2, 'asc'], [1, 'asc']] : (isSpacesTable ? [[2, 'asc']] : [[3, 'desc']])))),
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'table-row-number all dtr-control' },
                { targets: finalColumn, orderable: false, searchable: false }
            ],
            drawCallback: function () {
                var api = this.api();
                var page = api.page.info();
                api.column(0, { page: 'current' }).nodes().each(function (cell, index) {
                    var number = cell.querySelector('.row-number-value');
                    if (number) number.textContent = page.start + index + 1;
                });
                var pagingLabels = { first: window.UI('dt_first'), previous: window.UI('dt_previous'), next: window.UI('dt_next'), last: window.UI('dt_last') };
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
                emptyTable: window.UI('dt_empty'),
                info: window.UI('dt_info'),
                infoEmpty: window.UI('dt_info_empty'),
                infoFiltered: window.UI('dt_info_filtered'),
                lengthMenu: window.UI('dt_length_menu'),
                loadingRecords: window.UI('dt_loading'),
                processing: window.UI('dt_processing'),
                search: window.UI('dt_search'),
                zeroRecords: window.UI('dt_zero'),
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                },
                aria: {
                    sortAscending: window.UI('dt_sort_asc'),
                    sortDescending: window.UI('dt_sort_desc')
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
                button.setAttribute('aria-label', expanded ? window.UI('row_hide') : window.UI('row_show'));
                var icon = button.querySelector('span');
                if (icon) icon.textContent = expanded ? '−' : '+';
            }
        });
    });
});

/* DataTables inside a hidden tab computes its column widths from a zero-width
   container: recalculate when the tab is shown. */
document.addEventListener('shown.bs.tab', function (event) {
    if (!window.DataTable || typeof window.DataTable.tables !== 'function') return;

    var target = event.target.getAttribute('data-bs-target');
    var pane = target ? document.querySelector(target) : null;
    if (!pane) return;

    // Target the table of the revealed pane only: adjusting every table would
    // touch hidden ones whose width is meaningless.
    var table = pane.querySelector('table.spaces-data-table');
    if (!table || !window.jQuery) { return; }

    var api = window.jQuery(table).DataTable();
    api.columns.adjust();

    if (api.responsive && typeof api.responsive.recalc === 'function') {
        api.responsive.recalc();
    }
});
