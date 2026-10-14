@extends('layouts.evaluation')

@section('title', 'Evaluar clase — ' . config('app.name'))

@section('content')
    @php
        $selectedType = old('vehicle_type', $evaluation->vehicle_type ?: $reserva->vehicle?->type);
        $studentName = $reserva->student?->fullName() ?: 'Alumno';
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
                <span>Evaluación en curso</span>
            </div>
        </div>
        <span class="eval-shell__badge">En curso</span>
    </header>

    <div class="eval-shell__body">
        <div class="eval-hero">
            <p class="eval-hero__kicker">Clase{{ $classNumber ? ' '.$classNumber : '' }}</p>
            <h1>Evaluar a {{ $studentName }}</h1>
            <p>{{ $dateLabel }} · {{ $timeLabel }}</p>
        </div>

        <form method="POST" action="{{ route('instructor.evaluations.update', $reserva) }}" class="eval-form" id="classEvaluationForm" data-eval-leave>
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="eval-card">
                <h2 class="eval-card__title">Datos de la clase</h2>
                <div class="eval-facts">
                    <p class="eval-meta"><span>Alumno</span><strong>{{ $studentName }}</strong></p>
                    <p class="eval-meta"><span>Curso</span><strong>{{ $reserva->student?->course?->name ?: '—' }}</strong></p>
                    <p class="eval-meta"><span>Vehículo</span><strong>{{ $reserva->vehicle?->optionLabel() ?: '—' }}</strong></p>
                    <div>
                        <label class="form-label" for="vehicle_type">Tipo de vehículo</label>
                        <select id="vehicle_type" name="vehicle_type" class="form-select @error('vehicle_type') is-invalid @enderror" required>
                            <option value="manual" @selected($selectedType === 'manual')>Manual</option>
                            <option value="automatico" @selected($selectedType === 'automatico')>Automático</option>
                        </select>
                        @error('vehicle_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="eval-card">
                <h2 class="eval-card__title">Calificación por habilidad</h2>
                <p class="eval-card__lead">Califica cada criterio de 0 a 5. Elige el nivel que mejor describa lo observado.</p>

                <div class="eval-scale">
                    @foreach ($scale as $value => $label)
                        <span><strong>{{ $value }}</strong> {{ $label }}</span>
                    @endforeach
                </div>

                @foreach ($groups as $group)
                    <h3 class="eval-group-title">{{ $group['title'] }}</h3>
                    @foreach ($group['items'] as $item)
                        @php $score = (int) old('scores.'.$item['id'], $scores[$item['id']] ?? 0); @endphp
                        <article class="eval-skill">
                            <header class="eval-skill__head">
                                <div>
                                    <h4>{{ $loop->parent->iteration }}.{{ $loop->iteration }}. {{ $item['title'] }}</h4>
                                    <p>{{ $item['hint'] }}</p>
                                </div>
                                <strong class="eval-skill__score" data-eval-score>{{ $score }} / 5</strong>
                            </header>
                            <div class="eval-score-pills" role="radiogroup" aria-label="{{ $item['title'] }}">
                                @for ($value = 0; $value <= 5; $value++)
                                    <label class="eval-score-pill @if ($score === $value) is-active @endif">
                                        <input
                                            type="radio"
                                            name="scores[{{ $item['id'] }}]"
                                            value="{{ $value }}"
                                            @checked($score === $value)
                                            required
                                        >
                                        <span>{{ $value }}</span>
                                    </label>
                                @endfor
                            </div>
                        </article>
                    @endforeach
                @endforeach
            </section>

            <section class="eval-card">
                <h2 class="eval-card__title">Observaciones</h2>
                <label class="form-label" for="eval_notes">Comentarios para el alumno</label>
                <textarea
                    id="eval_notes"
                    name="notes"
                    rows="4"
                    class="form-control @error('notes') is-invalid @enderror"
                    placeholder="Fortalezas, correcciones y siguiente paso."
                >{{ old('notes', $evaluation->notes) }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </section>

            <div class="eval-shell__actions">
                <button type="submit" form="evalCancelForm" class="btn btn-brand-outline">Cancelar</button>
                <button type="submit" class="btn btn-brand">Terminar evaluación</button>
            </div>
        </form>

        <form
            method="POST"
            action="{{ route('instructor.evaluations.cancel', $reserva) }}"
            id="evalCancelForm"
            data-eval-cancel
            class="d-none"
        >
            @csrf
        </form>
    </div>
@endsection
