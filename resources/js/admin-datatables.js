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
        new DataTable(table, {
            language: spanishLanguage,
            pageLength: 10,
            lengthMenu: [10, 25, 50],
            order: [],
            autoWidth: false,
            dom: '<"datatable-toolbar d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3"lf>t<"datatable-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3"ip>',
        });
    });
});
