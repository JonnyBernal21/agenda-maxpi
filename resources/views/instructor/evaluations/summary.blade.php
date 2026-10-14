@extends('layouts.evaluation')

@section('title', 'Resumen de evaluación — ' . config('app.name'))

@section('content')
    @php
        $studentName = $reserva->student?->fullName() ?: 'Alumno';
        $completed = $reserva->status === 'completada';
        $dateLabel = \Illuminate\Support\Carbon::parse($reserva->date)
            ->locale('es')
            ->isoFormat('D [de] MMMM YYYY');
        $company = ($appSetting ?? null)?->companyName() ?? config('app.name');
    @endphp

    <header class="eval-shell__top">
        <div class="eval-shell__brand">
            @if (($appSetting ?? null)?->logoUrl())
                <img src="{{ $appSetting->logoUrl() }}" alt="">
            @endif
            <div>
                <strong>{{ $company }}</strong>
                <span>{{ $completed ? 'Evaluación completada' : 'Revisa y envía el resultado' }}</span>
            </div>
        </div>
        <span class="eval-shell__badge @if ($completed) is-done @endif">
            {{ $completed ? 'Completada' : 'En curso' }}
        </span>
    </header>

    <div class="eval-shell__body">
        <div class="eval-hero">
            <p class="eval-hero__kicker">Clase{{ $classNumber ? ' '.$classNumber : '' }}</p>
            <h1>Resumen de resultados</h1>
            <p>{{ $studentName }} · {{ $dateLabel }}</p>
        </div>

        <section class="eval-card eval-summary-hero">
            <div>
                <p class="eval-meta"><span>Promedio</span></p>
                <p class="eval-average">{{ number_format($evaluation->average, 1) }} <small>/ 5</small></p>
            </div>
            <div class="eval-facts eval-facts--compact">
                <p class="eval-meta"><span>Tipo de vehículo</span><strong>{{ $evaluation->vehicleTypeLabel() }}</strong></p>
                <p class="eval-meta"><span>Unidad</span><strong>{{ $evaluation->vehicle_label ?: '—' }}</strong></p>
            </div>
        </section>

        <section class="eval-card">
            <h2 class="eval-card__title">Calificación por habilidad</h2>
            <ul class="eval-results">
                @foreach ($items as $item)
                    <li>
                        <div>
                            <strong>{{ $item['title'] }}</strong>
                            <small>{{ $item['label'] }}</small>
                        </div>
                        <span>{{ $item['score'] }}/5</span>
                    </li>
                @endforeach
            </ul>
            @if ($evaluation->notes)
                <div class="eval-notes">
                    <strong>Observaciones</strong>
                    <p>{{ $evaluation->notes }}</p>
                </div>
            @endif
        </section>

        <div class="eval-shell__actions">
            @unless ($completed)
                <button type="submit" form="evalCancelForm" class="btn btn-brand-outline">Cancelar</button>
                <div class="eval-shell__actions-main">
                    <a href="{{ route('instructor.evaluations.edit', $reserva) }}" class="btn btn-brand-outline" data-eval-leave>Editar</a>
                    <form method="POST" action="{{ route('instructor.evaluations.complete', $reserva) }}" data-eval-leave>
                        @csrf
                        <button type="submit" class="btn btn-brand-outline">Completar sin enviar</button>
                    </form>
                    <form method="POST" action="{{ route('instructor.evaluations.send', $reserva) }}" data-eval-leave>
                        @csrf
                        <button type="submit" class="btn btn-brand">
                            <i class="bi bi-envelope"></i>
                            Enviar por correo y completar
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('instructor.dashboard') }}" class="btn btn-brand">Cerrar</a>
                @if (! $evaluation->emailed_at)
                    <form method="POST" action="{{ route('instructor.evaluations.send', $reserva) }}">
                        @csrf
                        <button type="submit" class="btn btn-brand-outline">
                            <i class="bi bi-envelope"></i>
                            Enviar por correo
                        </button>
                    </form>
                @endif
            @endunless
        </div>
    </div>

    @unless ($completed)
        <form
            method="POST"
            action="{{ route('instructor.evaluations.cancel', $reserva) }}"
            id="evalCancelForm"
            data-eval-cancel
            class="d-none"
        >
            @csrf
        </form>
    @endunless
@endsection
