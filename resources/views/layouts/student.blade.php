@extends('layouts.app')

@section('body-class', 'admin-body')

@section('body')
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('student.dashboard') }}">
                <i class="bi bi-calendar2-week"></i>
                Agenda MaxPi
            </a>
            <button
                class="navbar-toggler border-0"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#studentNavbar"
                aria-controls="studentNavbar"
                aria-expanded="false"
                aria-label="Alternar navegación"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="studentNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 active" href="{{ route('student.dashboard') }}">
                            <i class="bi bi-calendar3"></i>
                            Mis clases
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-text d-none d-sm-inline d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle"></i>
                        {{ auth('student')->user()->fullName() }}
                    </span>
                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1">
                            <i class="bi bi-box-arrow-right"></i>
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-md-5">
        @if (session('success'))
            <div
                id="app-flash"
                data-type="success"
                data-message="{{ session('success') }}"
                hidden
            ></div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @yield('content')
    </main>

    @include('student.partials.book-class-modal')
@endsection
