@extends('layouts.app')

@section('body-class', 'instructor-body')

@section('body')
    @include('layouts.partials.instructor-navbar')

    <main class="app-content container-fluid py-3 py-lg-4">
        @if (session('success'))
            <div
                id="app-flash"
                data-type="success"
                data-message="{{ session('success') }}"
                hidden
            ></div>
        @endif

        @yield('content')
    </main>
@endsection
