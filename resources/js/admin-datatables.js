import $ from 'jquery';
import DataTable from 'datatables.net-bs5';

window.$ = window.jQuery = $;

const spanishLanguage = {
    emptyTable: 'No hay datos disponibles',
    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
    infoFiltered: '(filtrado de _MAX_ registros totales)',
    lengthMenu: 'Mostrar _MENU_ registros',
    loadingRecords: 'Cargando...',
    processing: 'Procesando...',
    search: 'Buscar:',
    zeroRecords: 'No se encontraron resultados',
    paginate: {
        first: 'Primero',
        last: 'Último',
        next: 'Siguiente',
        previous: 'Anterior',
    },
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.admin-datatable').forEach((table) => {
        const scroll = table.classList.contains('admin-datatable--scroll');
        const isStudentsTable = table.classList.contains('students-datatable');

        const dt = new DataTable(table, {
            language: spanishLanguage,
            pageLength: 10,
            lengthMenu: [10, 25, 50],
            order: [],
            autoWidth: !isStudentsTable,
            scrollX: scroll,
            scrollY: scroll ? '60vh' : undefined,
            scrollCollapse: scroll,
            columnDefs: isStudentsTable
                ? [
                    { orderable: false, searchable: false, targets: 'th.no-sort' },
                    { width: '26%', targets: 0 },
                    { width: '14%', targets: 1 },
                    { width: '14%', targets: 2 },
                    { width: '12%', targets: 3 },
                    { width: '12%', targets: 4 },
                    { width: '22%', orderable: false, searchable: false, targets: 5 },
                ]
                : [
                    { orderable: false, searchable: false, targets: 'th.no-sort' },
                ],
            dom: '<"datatable-toolbar d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3"lf>t<"datatable-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3"ip>',
        });

        if (isStudentsTable) {
            bindStudentRowDetails(dt, table);
            dt.columns.adjust();
        }
    });
});

const bindStudentRowDetails = (dt, table) => {
    const closeRow = (row, button) => {
        row.child.hide();
        button?.setAttribute('aria-expanded', 'false');
        button?.closest('tr')?.classList.remove('is-expanded');
    };

    table.addEventListener('click', (event) => {
        const button = event.target.closest('.students-table__toggle');

        if (!button) {
            return;
        }

        const tr = button.closest('tr');
        const row = dt.row(tr);
        const details = tr.querySelector('.students-table__details')?.innerHTML;

        if (!details) {
            return;
        }

        if (row.child.isShown()) {
            closeRow(row, button);
            return;
        }

        row.child(details, 'students-table__child').show();
        tr.nextElementSibling?.classList.add('students-table__child');
        button.setAttribute('aria-expanded', 'true');
        tr.classList.add('is-expanded');
    });

    dt.on('draw', () => {
        table.querySelectorAll('.students-table__toggle[aria-expanded="true"]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
            button.closest('tr')?.classList.remove('is-expanded');
        });
    });
};
