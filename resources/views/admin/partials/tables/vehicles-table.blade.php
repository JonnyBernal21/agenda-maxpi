<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Modelo</th>
            <th>Año</th>
            <th>Color</th>
            <th>Placa</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Reservas</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vehicles as $vehicle)
            <tr>
                <td class="fw-semibold">{{ $vehicle->modelo }}</td>
                <td>{{ $vehicle->año }}</td>
                <td>{{ $vehicle->color }}</td>
                <td><span class="table-badge">{{ $vehicle->plate }}</span></td>
                <td>{{ $vehicle->typeLabel() }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}</td>
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
