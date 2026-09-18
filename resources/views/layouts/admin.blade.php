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

    @include('admin.partials.add-student-modal')
    @include('admin.partials.student-schedule-modal')
    @include('admin.partials.add-instructor-modal')
    @include('admin.partials.add-vehicle-modal')
    @include('admin.partials.add-course-modal')
    @include('admin.partials.add-expense-modal')
    @include('admin.partials.schedule-class-modal')
    @include('admin.partials.assign-schedule-modal')
    @include('admin.partials.schedule-summary-modal')
@endsection

@push('scripts')
    @vite(['resources/js/admin-panel.js', 'resources/js/admin-reservas.js'])
@endpush
