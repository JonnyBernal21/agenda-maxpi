@if ($reservas->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-calendar-x display-6 d-block mb-2 opacity-50"></i>
        <p class="mb-0">No hay clases registradas {{ ($showDate ?? false) ? 'en este periodo' : 'para esta fecha' }}.</p>
    </div>
@else
    <table class="table table-hover align-middle w-100 mb-0">
        <thead>
            <tr>
                @if ($showDate ?? false)
                    <th>Fecha</th>
                @endif
                <th>Hora</th>
                <th>Alumno</th>
                <th>Instructor</th>
                <th>Vehículo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reservas as $reserva)
                <tr>
                    @if ($showDate ?? false)
                        <td>
                            {{ \Illuminate\Support\Carbon::parse($reserva->date)->locale('es')->isoFormat('ddd D MMM') }}
                        </td>
                    @endif
                    <td class="fw-semibold">{{ substr($reserva->time, 0, 5) }}</td>
                    <td>
                        {{ trim($reserva->student?->name . ' ' . $reserva->student?->last_name) ?: '—' }}
                    </td>
                    <td>
                        {{ trim($reserva->instructor?->name . ' ' . $reserva->instructor?->last_name) ?: '—' }}
                    </td>
                    <td>{{ $reserva->vehicle?->modelo ?? '—' }} <span class="text-muted small">({{ $reserva->vehicle?->plate }})</span></td>
                    <td>
                        <span class="badge {{ \App\Support\ReservaStatus::badgeClass($reserva->status) }}">
                            {{ \App\Support\ReservaStatus::label($reserva->status) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
