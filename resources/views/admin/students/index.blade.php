@extends('layouts.admin')

@section('title', 'Alumnos — ' . config('app.name'))

@section('content')
    <div class="page-header">
        <h1 class="page-title mb-1">Alumnos</h1>
        <p class="page-subtitle mb-0">Listado de alumnos registrados y progreso de clases por curso.</p>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-people',
        'title' => 'Registro de alumnos',
        'subtitle' => count($students) . ' alumnos en total',
        'action' => '<button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="bi bi-person-plus"></i> Agregar alumno</button>',
        'table' => view('admin.partials.tables.students-table', compact('students'))->render(),
    ])
@endsection

@push('scripts')
    @vite('resources/js/admin-datatables.js')
@endpush
