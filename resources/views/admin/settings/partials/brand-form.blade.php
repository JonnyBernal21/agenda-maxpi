<form
    method="POST"
    action="{{ route('admin.settings.update') }}"
    enctype="multipart/form-data"
    id="settingsBrandForm"
>
    @csrf
    @method('PUT')

    <p class="fw-semibold mb-3">Identidad</p>
    <div class="row g-4">
        <div class="col-md-6">
            <label for="company_name" class="form-label">Nombre de la empresa</label>
            <input
                type="text"
                id="company_name"
                name="company_name"
                value="{{ old('company_name', $setting->companyName()) }}"
                class="form-control @error('company_name') is-invalid @enderror"
                maxlength="80"
                required
            >
            @error('company_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <p class="small text-muted mt-2 mb-0">Este texto aparece en el encabezado junto al logo.</p>
        </div>

        <div class="col-md-6">
            <label for="company_logo" class="form-label">Logo de la empresa</label>
            <input
                type="file"
                id="company_logo"
                name="logo"
                class="form-control @error('logo') is-invalid @enderror"
                accept="image/png,image/jpeg,image/webp"
            >
            @error('logo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <p class="small text-muted mt-2 mb-0">PNG, JPG o WEBP. Se muestra pequeño al lado del título.</p>
        </div>

        <div class="col-12" id="settingsLogoPreviewWrap" @if (! $setting->logoUrl()) style="display:none" @endif>
            <p class="form-label mb-2">Vista previa</p>
            <div class="settings-logo-preview">
                <img
                    id="settingsLogoPreview"
                    src="{{ $setting->logoUrl() }}"
                    alt="Logo actual"
                >
                <label class="form-check mb-0">
                    <input type="checkbox" name="remove_logo" value="1" class="form-check-input" id="settingsRemoveLogo">
                    <span class="form-check-label">Quitar logo</span>
                </label>
            </div>
        </div>
    </div>

    <hr class="my-4">
    <p class="fw-semibold mb-3">Contacto</p>
    <div class="row g-4">
        <div class="col-md-6">
            <label for="school_phone" class="form-label">Teléfono</label>
            <input
                type="text"
                id="school_phone"
                name="phone"
                value="{{ old('phone', $setting->phone) }}"
                class="form-control @error('phone') is-invalid @enderror"
                placeholder="+52 55 1234 5678"
            >
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="school_email" class="form-label">Correo de la escuela</label>
            <input
                type="email"
                id="school_email"
                name="email"
                value="{{ old('email', $setting->email) }}"
                class="form-control @error('email') is-invalid @enderror"
                placeholder="contacto@escuela.com"
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <hr class="my-4">
    <p class="fw-semibold mb-3">Ubicación</p>
    <div class="row g-4">
        <div class="col-12">
            <label for="school_address" class="form-label">Dirección de la escuela</label>
            <input
                type="text"
                id="school_address"
                name="address"
                value="{{ old('address', $setting->address) }}"
                class="form-control @error('address') is-invalid @enderror"
                placeholder="Calle, número y colonia"
            >
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-4">
            <label for="school_city" class="form-label">Ciudad</label>
            <input
                type="text"
                id="school_city"
                name="city"
                value="{{ old('city', $setting->city) }}"
                class="form-control @error('city') is-invalid @enderror"
            >
            @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-4">
            <label for="school_state" class="form-label">Estado</label>
            <input
                type="text"
                id="school_state"
                name="state"
                value="{{ old('state', $setting->state) }}"
                class="form-control @error('state') is-invalid @enderror"
            >
            @error('state')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-4">
            <label for="school_zip" class="form-label">Código postal</label>
            <input
                type="text"
                id="school_zip"
                name="zip"
                value="{{ old('zip', $setting->zip) }}"
                class="form-control @error('zip') is-invalid @enderror"
            >
            @error('zip')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="school_country" class="form-label">País</label>
            <input
                type="text"
                id="school_country"
                name="country"
                value="{{ old('country', $setting->countryName()) }}"
                class="form-control @error('country') is-invalid @enderror"
            >
            @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <hr class="my-4">
    <p class="fw-semibold mb-3">Regional</p>
    <div class="row g-4">
        <div class="col-md-6">
            <label for="school_timezone" class="form-label">Zona horaria</label>
            <select
                id="school_timezone"
                name="timezone"
                class="form-select @error('timezone') is-invalid @enderror"
                required
            >
                @foreach (\App\Support\SchoolProfile::TIMEZONES as $value => $label)
                    <option value="{{ $value }}" @selected(old('timezone', $setting->timezone()) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('timezone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <p class="small text-muted mt-2 mb-0">Se usa en el calendario, reportes y fechas del sistema.</p>
        </div>
        <div class="col-md-6">
            <label for="school_currency" class="form-label">Moneda</label>
            <select
                id="school_currency"
                name="currency"
                class="form-select @error('currency') is-invalid @enderror"
                required
            >
                @foreach (\App\Support\SchoolProfile::CURRENCIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $setting->currency()) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('currency')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-brand d-inline-flex align-items-center gap-2">
            <i class="bi bi-check-lg"></i>
            Guardar ajustes
        </button>
    </div>
</form>
