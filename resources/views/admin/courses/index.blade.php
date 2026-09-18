@extends('layouts.admin')

@section('title', 'Cursos — ' . config('app.name'))

@section('content')
    <div class="page-header">
        <h1 class="page-title mb-1">Cursos</h1>
        <p class="page-subtitle mb-0">Catálogo de programas, costo y número de clases.</p>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-journal-text',
        'title' => 'Registro de cursos',
        'subtitle' => count($courses) . ' cursos en total',
        'action' => '<button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="bi bi-journal-plus"></i> Agregar curso</button>',
        'table' => view('admin.partials.tables.courses-table', compact('courses'))->render(),
    ])
@endsection

@push('scripts')
    @vite('resources/js/admin-datatables.js')
@endpush
