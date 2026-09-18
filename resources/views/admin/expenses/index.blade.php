@extends('layouts.admin')

@section('title', 'Gastos — ' . config('app.name'))

@section('content')
    <div class="page-header">
        <h1 class="page-title mb-1">Gastos</h1>
        <p class="page-subtitle mb-0">Registro de salidas de dinero de la autoescuela.</p>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-receipt',
        'title' => 'Registro de gastos',
        'subtitle' => count($expenses) . ' gastos en total',
        'action' => '<button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="bi bi-plus-lg"></i> Agregar gasto</button>',
        'table' => view('admin.partials.tables.expenses-table', compact('expenses'))->render(),
    ])
@endsection

@push('scripts')
    @vite('resources/js/admin-datatables.js')
@endpush
