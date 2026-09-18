<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $user)
            <tr>
                <td>
                    <span class="fw-semibold">{{ $user->name }}</span>
                    @if ($user->is(auth()->user()))
                        <span class="table-badge ms-1">Tú</span>
                    @endif
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="table-badge">{{ $user->role?->name ?? 'Sin rol' }}</span>
                </td>
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-user"
                            title="Editar información"
                            aria-label="Editar a {{ $user->name }}"
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-email="{{ $user->email }}"
                            data-role-id="{{ $user->role_id }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if (! $user->is(auth()->user()))
                            <form
                                method="POST"
                                action="{{ route('admin.users.destroy', $user) }}"
                                class="js-soft-delete"
                                data-name="{{ $user->name }}"
                                data-entity="usuario"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="btn btn-brand-outline btn-delete"
                                    title="Eliminar"
                                    aria-label="Eliminar a {{ $user->name }}"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
