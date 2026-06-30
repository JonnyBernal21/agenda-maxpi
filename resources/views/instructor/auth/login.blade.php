@extends('layouts.app')

@include('auth.partials.login-background-styles')

@section('title', 'Acceso instructor — ' . config('app.name'))

@section('body-class', 'auth-wrapper')

@section('body')
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="text-center mb-4 text-white">
                    <div class="auth-logo mx-auto">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h1 class="h4 fw-bold mb-1">Portal del instructor</h1>
                    <p class="opacity-75 small mb-0">Consulta tus clases asignadas</p>
                </div>

                <div class="card auth-card border-0">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h5 fw-semibold mb-1 text-center">Iniciar sesión</h2>
                        <p class="text-muted small text-center mb-4">Usa el correo registrado en el sistema</p>

                        @include('auth.partials.login-portal-hint')

                        @if ($errors->any())
                            <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                                <i class="bi bi-exclamation-circle"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('instructor.login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        class="form-control border-start-0 ps-0"
                                        placeholder="instructor@agenda-maxpi.test"
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        class="form-control border-start-0 ps-0"
                                        placeholder="••••••••"
                                    >
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                                <label class="form-check-label small" for="remember">Recordarme</label>
                            </div>

                            <button type="submit" class="btn btn-brand w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-arrow-in-right"></i>
                                Ingresar
                            </button>
                        </form>

                        <p class="text-center text-muted small mt-4 mb-2">
                            ¿Eres alumno?
                            <a href="{{ route('student.login') }}" class="text-decoration-none">Acceso alumno</a>
                        </p>
                        <p class="text-center text-muted small mb-0">
                            ¿Eres administrador?
                            <a href="{{ route('login') }}" class="text-decoration-none">Acceso admin</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
