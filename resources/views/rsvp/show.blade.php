@extends('layouts.app')

@section('title', 'Confirmar asistencia — ' . config('app.name'))

@section('body-class', 'rsvp-body')

@section('body')
    @php
        $studentName = $reserva->student?->name ?: 'alumno';
        $dateLabel = \Illuminate\Support\Carbon::parse($reserva->date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $timeLabel = \App\Models\Reservas::normalizeTime((string) $reserva->time).' - '.\App\Models\Reservas::slotEndTime((string) $reserva->time);
        $company = ($appSetting ?? null)?->companyName() ?? config('app.name');
        $answered = filled($reserva->rsvp_response);
    @endphp

    <div class="rsvp-page">
        <div class="rsvp-card">
            <div class="rsvp-card__brand">
                @if (($appSetting ?? null)?->logoUrl())
                    <img src="{{ $appSetting->logoUrl() }}" alt="">
                @endif
                <strong>{{ $company }}</strong>
            </div>

            <p class="rsvp-card__kicker">Recordatorio de clase</p>
            <h1>Hola, {{ $studentName }}</h1>
            <p class="rsvp-card__lead">¿Asistirás a tu clase?</p>

            <dl class="rsvp-facts">
                <div>
                    <dt>Fecha</dt>
                    <dd>{{ $dateLabel }}</dd>
                </div>
                <div>
                    <dt>Horario</dt>
                    <dd>{{ $timeLabel }}</dd>
                </div>
                @if ($reserva->instructor)
                    <div>
                        <dt>Instructor</dt>
                        <dd>{{ $reserva->instructor->fullName() }}</dd>
                    </div>
                @endif
                @if ($reserva->vehicle)
                    <div>
                        <dt>Vehículo</dt>
                        <dd>{{ $reserva->vehicle->optionLabel() }}</dd>
                    </div>
                @endif
            </dl>

            @if (session('success'))
                <div class="rsvp-alert rsvp-alert--ok">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="rsvp-alert rsvp-alert--error">{{ $errors->first() }}</div>
            @endif

            @if ($locked)
                <p class="rsvp-card__note">Esta clase ya no admite confirmación.</p>
            @else
                <form method="POST" action="{{ route('reservas.rsvp.store', $reserva->rsvp_token) }}" class="rsvp-choices">
                    @csrf
                    <button type="submit" name="response" value="asistira" class="rsvp-choice rsvp-choice--yes @if ($reserva->rsvp_response === 'asistira') is-active @endif">
                        <i class="bi bi-check-circle-fill"></i>
                        Sí, asistiré
                    </button>
                    <button type="submit" name="response" value="no_asistira" class="rsvp-choice rsvp-choice--no @if ($reserva->rsvp_response === 'no_asistira') is-active @endif">
                        <i class="bi bi-x-circle-fill"></i>
                        No podré asistir
                    </button>
                </form>
                <p class="rsvp-card__note">
                    {{ $answered ? 'Puedes cambiar tu respuesta mientras la clase no se haya iniciado.' : 'Toca una opción para confirmar.' }}
                </p>
            @endif
        </div>
    </div>
@endsection
