@extends('layouts.app')

@section('body-class', 'admin-body')

@section('body')
    @include('layouts.partials.admin-navbar')

    <main class="container py-4 py-md-5">
        @if (session('success'))
            <div
                id="app-flash"
                data-type="success"
                data-message="{{ session('success') }}"
                hidden
            ></div>
        @elseif ($errors->any())
            <div
                id="app-flash"
                data-type="error"
                data-message="{{ $errors->first() }}"
                hidden
            ></div>
        @endif

        @yield('content')
    </main>

    @canany(['students.manage', 'students.edit'])
        @include('admin.partials.add-student-modal')
    @endcanany
    @can('students.edit')
        @include('admin.partials.student-payment-modal')
    @endcan
    @canany(['students.view', 'students.manage', 'students.edit'])
        @include('admin.partials.student-schedule-modal')
    @endcanany
    @canany(['students.manage', 'reservas.manage'])
        @include('admin.partials.assign-schedule-modal')
        @include('admin.partials.schedule-summary-modal')
    @endcanany
    @can('instructors.manage')
        @include('admin.partials.add-instructor-modal')
    @endcan
    @can('vehicles.manage')
        @include('admin.partials.add-vehicle-modal')
    @endcan
    @can('courses.manage')
        @include('admin.partials.add-course-modal')
    @endcan
    @can('expenses.manage')
        @include('admin.partials.add-expense-modal')
    @endcan
    @can('reservas.manage')
        @include('admin.partials.schedule-class-modal')
    @endcan
@endsection

@push('scripts')
    @vite(['resources/js/admin-panel.js', 'resources/js/admin-reservas.js'])
@endpush
