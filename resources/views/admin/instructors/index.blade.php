@extends('layouts.admin')

@section('title', 'Instructores — ' . config('app.name'))

@section('content')
    <div class="page-header">
        <h1 class="page-title mb-1">Instructores</h1>
        <p class="page-subtitle mb-0">Personal docente disponible para agendar clases.</p>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-person-badge',
        'title' => 'Registro de instructores',
        'subtitle' => count($instructors) . ' instructores en total',
        'action' => '<button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addInstructorModal"><i class="bi bi-person-badge"></i> Agregar instructor</button>',
        'table' => view('admin.partials.tables.instructors-table', compact('instructors'))->render(),
    ])
@endsection

@push('scripts')
    @vite('resources/js/admin-datatables.js')
@endpush
