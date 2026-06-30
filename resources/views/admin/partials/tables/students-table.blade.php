<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Alumno</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Curso</th>
            <th>Completadas</th>
            <th>Ciudad</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $student)
            <tr>
                <td>
                    <span class="fw-semibold">{{ $student->name }} {{ $student->last_name }}</span>
                </td>
                <td>{{ $student->email }}</td>
                <td>{{ $student->phone }}</td>
                <td>
                    @if ($student->course)
                        <span class="table-badge">{{ $student->course->name }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <span class="small">
                        {{ $student->completed_classes_count ?? 0 }} / {{ $student->course?->num_classes ?? 0 }}
                    </span>
                    <span class="text-muted small d-block">
                        {{ $student->remaining_classes }} restantes
                    </span>
                </td>
                <td>{{ $student->city }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
