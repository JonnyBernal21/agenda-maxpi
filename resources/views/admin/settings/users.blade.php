@extends('layouts.admin')

@section('title', 'Usuarios — ' . config('app.name'))

@section('content')
    @include('admin.partials.settings-shell', [
        'tab' => 'usuarios',
        'slot' => view('admin.partials.panel-table', [
            'icon' => 'bi-people',
            'title' => 'Usuarios del sistema',
            'subtitle' => count($users).' usuarios en total',
            'action' => '<button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> Agregar usuario</button>',
            'table' => view('admin.partials.tables.users-table', compact('users'))->render(),
        ])->render(),
    ])

    @include('admin.partials.add-user-modal')
@endsection

@push('scripts')
    @vite('resources/js/admin-datatables.js')
@endpush
