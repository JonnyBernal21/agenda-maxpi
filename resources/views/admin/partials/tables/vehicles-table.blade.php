<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Modelo</th>
            <th>Año</th>
            <th>Color</th>
            <th>Placa</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Galería</th>
            <th>Reservas</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vehicles as $vehicle)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if ($vehicle->frontPhotoUrl())
                            <img
                                src="{{ $vehicle->frontPhotoUrl() }}"
                                alt="Frontal de {{ $vehicle->modelo }}"
                                class="vehicle-thumb"
                            >
                        @else
                            <span class="vehicle-thumb vehicle-thumb--empty">
                                <i class="bi bi-car-front"></i>
                            </span>
                        @endif
                        <span class="fw-semibold">{{ $vehicle->modelo }}</span>
                    </div>
                </td>
                <td>{{ $vehicle->año }}</td>
                <td>{{ $vehicle->color }}</td>
                <td><span class="table-badge">{{ $vehicle->plate }}</span></td>
                <td>{{ $vehicle->typeLabel() }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}</td>
                <td>
                    <div class="instructor-docs">
                        @include('admin.partials.instructor-doc-link', [
                            'url' => $vehicle->platePhotoUrl(),
                            'label' => 'Placa',
                        ])
                        @include('admin.partials.instructor-doc-link', [
                            'url' => $vehicle->circulationCardUrl(),
                            'label' => 'Tarjeta',
                        ])
                        @include('admin.partials.instructor-doc-link', [
                            'url' => $vehicle->frontPhotoUrl(),
                            'label' => 'Frontal',
                        ])
                    </div>
                </td>
                <td>{{ $vehicle->reservas_count }}</td>
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-vehicle"
                            title="Editar información"
                            aria-label="Editar {{ $vehicle->modelo }} ({{ $vehicle->plate }})"
                            data-id="{{ $vehicle->id }}"
                            data-modelo="{{ $vehicle->modelo }}"
                            data-anio="{{ $vehicle->año }}"
                            data-color="{{ $vehicle->color }}"
                            data-plate="{{ $vehicle->plate }}"
                            data-type="{{ $vehicle->type }}"
                            data-status="{{ $vehicle->status }}"
                            data-owner="{{ $vehicle->owner }}"
                            data-owner-id="{{ $vehicle->owner_id }}"
                            data-plate-photo-url="{{ $vehicle->platePhotoUrl() }}"
                            data-circulation-card-url="{{ $vehicle->circulationCardUrl() }}"
                            data-front-photo-url="{{ $vehicle->frontPhotoUrl() }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form
                            method="POST"
                            action="{{ route('admin.vehicles.destroy', $vehicle) }}"
                            class="js-soft-delete"
                            data-name="{{ $vehicle->modelo }} ({{ $vehicle->plate }})"
                            data-entity="vehículo"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-brand-outline btn-delete"
                                title="Eliminar"
                                aria-label="Eliminar {{ $vehicle->modelo }} ({{ $vehicle->plate }})"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
