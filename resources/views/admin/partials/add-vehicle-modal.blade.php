<div
    class="modal fade"
    id="addVehicleModal"
    tabindex="-1"
    aria-labelledby="addVehicleModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.vehicles.store') }}"
    data-update-base="{{ url('admin/vehicles') }}"
    data-editing-id="{{ old('_form') === 'vehicle-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['vehicle', 'vehicle-edit'], true)) ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.vehicles.store') }}" class="modal-form-layout" id="vehicleAdminForm">
                @csrf
                <input type="hidden" name="_method" id="vehicleFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="vehicleFormType" value="{{ old('_form', 'vehicle') }}">
                <input type="hidden" name="editing_id" id="vehicleEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addVehicleModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-car-front" id="vehicleFormIcon"></i></span>
                        <span id="vehicleFormTitle">Agregar vehículo</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && in_array(old('_form'), ['vehicle', 'vehicle-edit'], true))
                        <div class="alert alert-danger" role="alert" id="vehicleFormErrorAlert">
                            Revisa los campos marcados e intenta de nuevo.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="vehicle_modelo" class="form-label">Modelo</label>
                            <input
                                type="text"
                                id="vehicle_modelo"
                                name="modelo"
                                value="{{ old('modelo') }}"
                                class="form-control @error('modelo') is-invalid @enderror"
                                placeholder="Ej. Hyundai i10"
                                required
                            >
                            @error('modelo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="vehicle_año" class="form-label">Año</label>
                            <input
                                type="text"
                                id="vehicle_año"
                                name="año"
                                value="{{ old('año') }}"
                                class="form-control @error('año') is-invalid @enderror"
                                placeholder="2024"
                                required
                            >
                            @error('año')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="vehicle_color" class="form-label">Color</label>
                            <input
                                type="text"
                                id="vehicle_color"
                                name="color"
                                value="{{ old('color') }}"
                                class="form-control @error('color') is-invalid @enderror"
                                placeholder="Blanco"
                                required
                            >
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="vehicle_plate" class="form-label">Placa</label>
                            <input
                                type="text"
                                id="vehicle_plate"
                                name="plate"
                                value="{{ old('plate') }}"
                                class="form-control @error('plate') is-invalid @enderror"
                                placeholder="ABC-1234"
                                required
                            >
                            @error('plate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="vehicle_type" class="form-label">Transmisión</label>
                            <select
                                id="vehicle_type"
                                name="type"
                                class="form-select @error('type') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar</option>
                                <option value="manual" @selected(old('type') === 'manual')>Manual</option>
                                <option value="automatico" @selected(old('type') === 'automatico')>Automático</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="vehicle_status" class="form-label">Estado</label>
                            <select
                                id="vehicle_status"
                                name="status"
                                class="form-select @error('status') is-invalid @enderror"
                                required
                            >
                                <option value="disponible" @selected(old('status', 'disponible') === 'disponible')>Disponible</option>
                                <option value="en_mantenimiento" @selected(old('status') === 'en_mantenimiento')>En mantenimiento</option>
                                <option value="fuera_de_servicio" @selected(old('status') === 'fuera_de_servicio')>Fuera de servicio</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="vehicle_owner" class="form-label">Propietario</label>
                            <input
                                type="text"
                                id="vehicle_owner"
                                name="owner"
                                value="{{ old('owner', 'Autoescuela MaxPi') }}"
                                class="form-control @error('owner') is-invalid @enderror"
                                placeholder="Autoescuela MaxPi"
                                required
                            >
                            @error('owner')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="vehicle_owner_id" class="form-label">ID propietario</label>
                            <input
                                type="text"
                                id="vehicle_owner_id"
                                name="owner_id"
                                value="{{ old('owner_id', 'MAXPI-001') }}"
                                class="form-control @error('owner_id') is-invalid @enderror"
                                placeholder="MAXPI-001"
                                required
                            >
                            @error('owner_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="vehicleFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="vehicleFormSubmitLabel">Guardar vehículo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
