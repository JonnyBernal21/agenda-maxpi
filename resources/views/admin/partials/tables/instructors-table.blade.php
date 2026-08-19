<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Instructor</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Ciudad</th>
            <th>Documentos</th>
            <th>Reservas</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($instructors as $instructor)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if ($instructor->photoUrl())
                            <img
                                src="{{ $instructor->photoUrl() }}"
                                alt="Foto de {{ $instructor->fullName() }}"
                                class="instructor-avatar"
                            >
                        @else
                            <span class="instructor-avatar instructor-avatar--empty">
                                <i class="bi bi-person"></i>
                            </span>
                        @endif
                        <span class="fw-semibold">{{ $instructor->name }} {{ $instructor->last_name }}</span>
                    </div>
                </td>
                <td>{{ $instructor->email }}</td>
                <td>{{ $instructor->phone }}</td>
                <td>{{ $instructor->city }}</td>
                <td>
                    <div class="instructor-docs">
                        @include('admin.partials.instructor-doc-link', [
                            'url' => $instructor->documentUrl($instructor->dni_front_path),
                            'label' => 'DNI frente',
                        ])
                        @include('admin.partials.instructor-doc-link', [
                            'url' => $instructor->documentUrl($instructor->dni_back_path),
                            'label' => 'DNI reverso',
                        ])
                        @include('admin.partials.instructor-doc-link', [
                            'url' => $instructor->documentUrl($instructor->address_proof_path),
                            'label' => 'Domicilio',
                        ])
                    </div>
                </td>
                <td>{{ $instructor->reservas_count }}</td>
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-instructor"
                            title="Editar información"
                            aria-label="Editar a {{ $instructor->fullName() }}"
                            data-id="{{ $instructor->id }}"
                            data-name="{{ $instructor->name }}"
                            data-last-name="{{ $instructor->last_name }}"
                            data-email="{{ $instructor->email }}"
                            data-phone="{{ $instructor->phone }}"
                            data-address="{{ $instructor->address }}"
                            data-city="{{ $instructor->city }}"
                            data-state="{{ $instructor->state }}"
                            data-zip="{{ $instructor->zip }}"
                            data-country="{{ $instructor->country }}"
                            data-photo-url="{{ $instructor->photoUrl() }}"
                            data-dni-front-url="{{ $instructor->documentUrl($instructor->dni_front_path) }}"
                            data-dni-back-url="{{ $instructor->documentUrl($instructor->dni_back_path) }}"
                            data-address-proof-url="{{ $instructor->documentUrl($instructor->address_proof_path) }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form
                            method="POST"
                            action="{{ route('admin.instructors.destroy', $instructor) }}"
                            class="js-soft-delete"
                            data-name="{{ $instructor->fullName() }}"
                            data-entity="instructor"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-brand-outline btn-delete"
                                title="Eliminar"
                                aria-label="Eliminar a {{ $instructor->fullName() }}"
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
