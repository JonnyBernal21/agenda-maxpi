<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Curso</th>
            <th>Clases</th>
            <th>Costo</th>
            <th>Alumnos</th>
            <th>Descripción</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($courses as $course)
            <tr>
                <td>
                    <span class="fw-semibold">{{ $course->name }}</span>
                </td>
                <td><span class="table-badge">{{ $course->num_classes }} clases</span></td>
                <td>{{ $course->costLabel() }}</td>
                <td>{{ $course->students_count }}</td>
                <td>{{ \Illuminate\Support\Str::limit($course->description, 80) }}</td>
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-course"
                            title="Editar información"
                            aria-label="Editar {{ $course->name }}"
                            data-id="{{ $course->id }}"
                            data-name="{{ $course->name }}"
                            data-description="{{ $course->description }}"
                            data-cost="{{ $course->cost }}"
                            data-temario="{{ $course->temario }}"
                            data-num-classes="{{ $course->num_classes }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form
                            method="POST"
                            action="{{ route('admin.courses.destroy', $course) }}"
                            class="js-soft-delete"
                            data-name="{{ $course->name }}"
                            data-entity="curso"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-brand-outline btn-delete"
                                title="Eliminar"
                                aria-label="Eliminar {{ $course->name }}"
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
