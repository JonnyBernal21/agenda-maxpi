@extends('layouts.admin')

@section('title', 'Vehículos — ' . config('app.name'))

@section('content')
    <div class="page-header">
        <h1 class="page-title mb-1">Vehículos</h1>
        <p class="page-subtitle mb-0">Flota disponible para clases prácticas.</p>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-car-front',
        'title' => 'Registro de vehículos',
        'subtitle' => count($vehicles) . ' vehículos en total',
        'action' => '<button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addVehicleModal"><i class="bi bi-car-front"></i> Agregar vehículo</button>',
        'table' => view('admin.partials.tables.vehicles-table', compact('vehicles'))->render(),
    ])
@endsection

@push('scripts')
    @vite('resources/js/admin-datatables.js')
@endpush
