@php
    $agenda = $dayAgenda ?? ['date' => now()->toDateString(), 'date_label' => '', 'title' => 'Agenda de hoy', 'subtitle' => '', 'classes' => [], 'is_today' => true, 'calendar' => ['month' => now()->format('Y-m'), 'month_label' => '', 'weeks' => []]];
    $calendar = $agenda['calendar'] ?? ['month' => now()->format('Y-m'), 'month_label' => $agenda['month_label'] ?? '', 'weeks' => []];
    $canStartClass = $canStartClass ?? false;
@endphp

<section
    class="day-agenda"
    id="dayAgenda"
    data-url="{{ $agendaUrl }}"
    data-date="{{ $agenda['date'] }}"
    data-month="{{ $calendar['month'] }}"
>
    <div class="day-agenda__card">
        <header class="day-agenda__head">
            <div class="day-agenda__intro">
                <span class="day-agenda__icon" aria-hidden="true">
                    <i class="bi bi-calendar2-week"></i>
                </span>
                <div>
                    <h2 class="day-agenda__title" data-agenda-title>{{ $agenda['title'] }}</h2>
                    <p class="day-agenda__subtitle" data-agenda-subtitle>{{ $agenda['subtitle'] }}</p>
                </div>
            </div>
        </header>

        <div class="day-agenda__toolbar">
            <div class="day-agenda__toolbar-side">
                <button type="button" class="day-agenda__nav-btn" data-agenda-prev aria-label="Día anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button type="button" class="day-agenda__nav-btn" data-agenda-next aria-label="Día siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <button type="button" class="day-agenda__date" data-agenda-date title="Ir a hoy">
                <span data-agenda-date-text>{{ $agenda['date_label'] }}</span>
                <i class="bi bi-calendar4" aria-hidden="true"></i>
            </button>
            <div class="day-agenda__toolbar-side day-agenda__toolbar-side--end">
                <button type="button" class="day-agenda__nav-btn" data-agenda-next aria-label="Día siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <div class="day-agenda__list" data-agenda-list>
                @forelse ($agenda['classes'] as $item)
                    <div class="day-agenda__row">
                    <button
                        type="button"
                        class="day-agenda__item"
                        data-agenda-item='@json($item)'
                    >
                        <span class="day-agenda__time">{{ $item['time_label'] }}</span>
                        <span class="day-agenda__copy">
                            <strong>{{ $item['title'] }}</strong>
                            <small>
                                {{ $item['student'] }}
                                @if ($item['course'])
                                    · {{ $item['course'] }}
                                @endif
                            </small>
                        </span>
                        <span class="day-agenda__status day-agenda__status--{{ $item['live_status'] }}">
                            {{ $item['status_label'] }}
                        </span>
                        <i class="bi bi-chevron-right day-agenda__chevron" aria-hidden="true"></i>
                    </button>
                    @if ($canStartClass && !empty($item['can_start']))
                        <form method="POST" action="{{ $item['start_url'] }}" class="day-agenda__start-form">
                            @csrf
                            <button type="submit" class="day-agenda__start">Iniciar</button>
                        </form>
                    @elseif ($canStartClass && !empty($item['can_continue']))
                        <a class="day-agenda__start" href="{{ $item['form_url'] }}">Continuar</a>
                    @elseif ($canStartClass && !empty($item['can_view_evaluation']))
                        <a class="day-agenda__start day-agenda__start--ghost" href="{{ $item['summary_url'] }}">Ver evaluación</a>
                    @endif
                    </div>
                @empty
                    <div class="day-agenda__empty">
                        <i class="bi bi-calendar-x"></i>
                        <p>No hay clases para este día.</p>
                    </div>
                @endforelse
            </div>

        <div class="day-agenda__picker">
                <div class="day-agenda__month" data-agenda-calendar>
                    <div class="day-agenda__month-head">
                        <h3 class="day-agenda__month-title" data-agenda-month-label>{{ $calendar['month_label'] }}</h3>
                        <div class="day-agenda__month-nav">
                            <button type="button" class="day-agenda__month-btn" data-agenda-month-prev aria-label="Mes anterior">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button type="button" class="day-agenda__month-btn" data-agenda-month-next aria-label="Mes siguiente">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="day-agenda__weekdays" aria-hidden="true">
                        <span>Lu</span>
                        <span>Ma</span>
                        <span>Mi</span>
                        <span>Ju</span>
                        <span>Vi</span>
                        <span>Sá</span>
                        <span>Do</span>
                    </div>

                    <div class="day-agenda__days" data-agenda-days>
                        @foreach ($calendar['weeks'] ?? [] as $week)
                            @foreach ($week as $day)
                                <button
                                    type="button"
                                    class="day-agenda__day{{ empty($day['in_month']) ? ' is-outside' : '' }}{{ !empty($day['is_today']) ? ' is-today' : '' }}{{ !empty($day['is_selected']) ? ' is-selected' : '' }}{{ !empty($day['has_class']) ? ' has-class' : '' }}"
                                    data-agenda-day="{{ $day['date'] }}"
                                >{{ $day['day'] }}</button>
                            @endforeach
                        @endforeach
                    </div>

                    <p class="day-agenda__legend">
                        <span><i class="day-agenda__dot day-agenda__dot--class"></i> Clase</span>
                        <span><i class="day-agenda__dot day-agenda__dot--eval"></i> Evaluación</span>
                        <span><i class="day-agenda__dot day-agenda__dot--cita"></i> Cita</span>
                    </p>
                </div>
        </div>
    </div>
</section>
