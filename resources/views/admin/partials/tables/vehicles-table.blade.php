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
            </tr>
        @endforeach
    </tbody>
</table>
