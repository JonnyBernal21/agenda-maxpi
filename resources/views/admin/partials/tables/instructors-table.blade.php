<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Instructor</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Ciudad</th>
            <th>Estado</th>
            <th>Reservas</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($instructors as $instructor)
            <tr>
                <td class="fw-semibold">{{ $instructor->name }} {{ $instructor->last_name }}</td>
                <td>{{ $instructor->email }}</td>
                <td>{{ $instructor->phone }}</td>
                <td>{{ $instructor->city }}</td>
                <td>{{ $instructor->state }}</td>
                <td>{{ $instructor->reservas_count }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
