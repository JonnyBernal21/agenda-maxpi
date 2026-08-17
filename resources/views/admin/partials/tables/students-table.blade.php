<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Alumno</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Curso</th>
            <th>Completadas</th>
            <th>Ciudad</th>
            <th class="no-sort">Acciones</th>
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
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-view-student-schedule"
                            title="Ver horarios"
                            aria-label="Ver horarios de {{ $student->fullName() }}"
                            data-student-id="{{ $student->id }}"
                            data-student-name="{{ $student->fullName() }}"
                            data-schedule-url="{{ route('admin.students.schedule', $student) }}"
                        >
                            <i class="bi bi-calendar-week"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-student"
                            title="Editar información"
                            aria-label="Editar a {{ $student->fullName() }}"
                            data-id="{{ $student->id }}"
                            data-course-id="{{ $student->course_id }}"
                            data-name="{{ $student->name }}"
                            data-last-name="{{ $student->last_name }}"
                            data-email="{{ $student->email }}"
                            data-phone="{{ $student->phone }}"
                            data-address="{{ $student->address }}"
                            data-city="{{ $student->city }}"
                            data-state="{{ $student->state }}"
                            data-zip="{{ $student->zip }}"
                            data-country="{{ $student->country }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
