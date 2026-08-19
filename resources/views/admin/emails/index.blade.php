@extends('layouts.admin')

@section('title', 'Correos — ' . config('app.name'))

@section('content')
    @php
        $template = $preview['template'];
        $query = array_filter(['student_id' => $selectedStudent?->id]);
    @endphp

    <div class="page-header">
        <h1 class="page-title mb-1">Correos</h1>
        <p class="page-subtitle mb-0">Vista previa de las plantillas que se envían a los alumnos.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 col-xl-3">
            <div class="email-template-list">
                @foreach ($templates as $item)
                    <a
                        href="{{ route('admin.emails.index', array_merge($query, ['template' => $item['key']])) }}"
                        class="email-template-card @if ($item['key'] === $template['key']) is-active @endif"
                    >
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-card__icon mb-0">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </div>
                            <div>
                                <p class="email-template-card__name mb-1">{{ $item['name'] }}</p>
                                <p class="email-template-card__desc mb-2">{{ $item['description'] }}</p>
                                <span class="badge text-bg-light border">{{ $item['audience'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="col-lg-8 col-xl-9">
            <div class="email-preview panel-card">
                <div class="panel-card__header">
                    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-3">
                        <dl class="email-preview-meta mb-0">
                            <div>
                                <dt>Asunto</dt>
                                <dd>{{ $preview['subject'] }}</dd>
                            </div>
                            <div>
                                <dt>Destinatario</dt>
                                <dd>
                                    {{ $preview['recipient_name'] }}
                                    <span class="text-muted fw-normal">· {{ $preview['recipient_email'] }}</span>
                                </dd>
                            </div>
                        </dl>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="btn-group" role="group" aria-label="Tamaño de vista previa">
                                <input type="radio" class="btn-check" name="previewDevice" id="previewDesktop" checked>
                                <label class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1" for="previewDesktop">
                                    <i class="bi bi-laptop"></i>
                                    Escritorio
                                </label>
                                <input type="radio" class="btn-check" name="previewDevice" id="previewMobile">
                                <label class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1" for="previewMobile">
                                    <i class="bi bi-phone"></i>
                                    Móvil
                                </label>
                            </div>
                            <a
                                href="{{ $htmlUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1"
                            >
                                <i class="bi bi-box-arrow-up-right"></i>
                                Nueva pestaña
                            </a>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.emails.index') }}" class="email-preview-filters mt-3">
                        <input type="hidden" name="template" value="{{ $template['key'] }}">
                        <label for="emailPreviewStudent" class="form-label mb-1 small text-muted">Datos del correo</label>
                        <select
                            id="emailPreviewStudent"
                            name="student_id"
                            class="form-select form-select-sm"
                            onchange="this.form.submit()"
                        >
                            <option value="">Datos de ejemplo</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected($selectedStudent?->id === $student->id)>
                                    {{ $student->fullName() }}{{ $student->email ? ' · '.$student->email : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @if ($preview['using_sample_student'])
                        <p class="small text-muted mb-0 mt-2">
                            Se muestran datos de ejemplo. Elige un alumno para ver su correo real.
                        </p>
                    @elseif ($preview['using_sample_schedule'])
                        <div class="alert alert-warning py-2 px-3 small mb-0 mt-3">
                            Este alumno no tiene clases asignadas. El horario de la vista previa es de ejemplo.
                        </div>
                    @endif
                </div>

                <div class="email-preview-stage">
                    <div class="email-preview-frame-wrap">
                        <iframe
                            id="emailPreviewFrame"
                            class="email-preview-frame"
                            src="{{ $htmlUrl }}"
                            title="Vista previa de {{ $template['name'] }}"
                            sandbox="allow-same-origin"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('emailPreviewFrame')?.addEventListener('load', function () {
            const doc = this.contentDocument;
            if (!doc) {
                return;
            }

            this.style.height = Math.max(doc.documentElement.scrollHeight, 640) + 'px';
        });
    </script>
@endpush
