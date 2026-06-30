@if (session('suggested_login_url'))
    <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-3" role="alert">
        <i class="bi bi-arrow-right-circle mt-1"></i>
        <div>
            <span class="d-block small">Portal incorrecto para este correo.</span>
            <a href="{{ session('suggested_login_url') }}" class="alert-link small fw-semibold">
                Ir al portal correcto
            </a>
        </div>
    </div>
@endif
