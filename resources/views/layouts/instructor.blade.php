@extends('layouts.app')

@section('body-class', 'instructor-body')

@section('body')
    @include('layouts.partials.instructor-navbar')

    <main class="container py-4 py-md-5">
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
