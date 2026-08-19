@php
    $brand = '#111111';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $count = $classes->count();
    $cell = 'padding:6px 10px;line-height:1.25;vertical-align:top;';
    $labelStyle = 'font-size:10px;font-weight:700;color:'.$muted.';text-transform:uppercase;letter-spacing:0.03em;line-height:1.2;';
    $valueStyle = 'font-size:12px;color:'.$brand.';line-height:1.3;padding-top:1px;';
    $split = 'width:50%;border-bottom:1px solid '.$border.';';
@endphp
@component('emails.layout', ['heading' => 'Registro y horarios de clase', 'title' => 'Registro y horarios', 'message' => $message])
    <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:{{ $brand }};">
        Hola, {{ $student->fullName() }}
    </p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.55;color:{{ $muted }};">
        Confirmamos tu registro y te compartimos el horario asignado.
        Conserva este correo como comprobante.
    </p>

    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
        Datos de registro
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;margin-bottom:14px;border-collapse:collapse;">
        <tr>
            <td colspan="2" style="{{ $cell }}border-bottom:1px solid {{ $border }};background:#fafafa;">
                <div style="{{ $labelStyle }}">Nombre</div>
                <div style="{{ $valueStyle }}">{{ $student->fullName() }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}{{ $split }}border-right:1px solid {{ $border }};background:#ffffff;">
                <div style="{{ $labelStyle }}">Correo</div>
                <div style="{{ $valueStyle }}">{{ $student->email ?: '—' }}</div>
            </td>
            <td style="{{ $cell }}{{ $split }}background:#ffffff;">
                <div style="{{ $labelStyle }}">Teléfono</div>
                <div style="{{ $valueStyle }}">{{ $student->phone ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}{{ $split }}border-right:1px solid {{ $border }};background:#fafafa;">
                <div style="{{ $labelStyle }}">Curso</div>
                <div style="{{ $valueStyle }}">{{ $student->course?->name ?? 'Sin curso' }}</div>
            </td>
            <td style="{{ $cell }}{{ $split }}background:#fafafa;">
                <div style="{{ $labelStyle }}">Clases del curso</div>
                <div style="{{ $valueStyle }}">{{ $student->allowedClassesCount() }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="{{ $cell }}border-bottom:1px solid {{ $border }};background:#ffffff;">
                <div style="{{ $labelStyle }}">Dirección</div>
                <div style="{{ $valueStyle }}">{{ $student->address ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}{{ $split }}border-right:1px solid {{ $border }};background:#fafafa;">
                <div style="{{ $labelStyle }}">Ciudad</div>
                <div style="{{ $valueStyle }}">{{ $student->city ?: '—' }}</div>
            </td>
            <td style="{{ $cell }}{{ $split }}background:#fafafa;">
                <div style="{{ $labelStyle }}">Estado</div>
                <div style="{{ $valueStyle }}">{{ $student->state ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}width:50%;border-right:1px solid {{ $border }};background:#ffffff;">
                <div style="{{ $labelStyle }}">C.P.</div>
                <div style="{{ $valueStyle }}">{{ $student->zip ?: '—' }}</div>
            </td>
            <td style="{{ $cell }}width:50%;background:#ffffff;">
                <div style="{{ $labelStyle }}">País</div>
                <div style="{{ $valueStyle }}">{{ $student->country ?: '—' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
        Horario ({{ $count }} {{ $count === 1 ? 'clase' : 'clases' }})
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;border-collapse:collapse;">
        <tr style="background:#111111;color:#F5C400;">
            <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">#</th>
            <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Día</th>
            <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Fecha</th>
            <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Horario</th>
            <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Vehículo</th>
        </tr>
        @foreach ($classes as $index => $class)
            <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fafafa' }};">
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $index + 1 }}</td>
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $class['weekday'] ?? '' }}</td>
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $class['date_label'] ?? $class['date'] }}</td>
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $class['schedule_label'] ?? ($class['time'].' – '.$class['end_time']) }}</td>
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $class['vehicle_type'] ?? $class['vehicle'] }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:18px 0 16px;font-size:13px;line-height:1.55;color:{{ $muted }};">
        Cada clase dura 2 horas. Si necesitas un cambio de horario, contacta a la escuela.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;border-collapse:collapse;">
        <tr>
            <td style="width:4px;background:#f5c400;font-size:0;line-height:0;">&nbsp;</td>
            <td style="padding:16px 18px 8px;background:#fafafa;">
                <p style="margin:0 0 4px;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $brand }};">
                    Términos y condiciones
                </p>
                <p style="margin:0 0 12px;font-size:12px;line-height:1.5;color:{{ $muted }};">
                    Al recibir este comprobante, el alumno acepta las siguientes políticas de asistencia y cancelación.
                </p>
            </td>
        </tr>
        <tr>
            <td style="width:4px;background:#f5c400;font-size:0;line-height:0;">&nbsp;</td>
            <td style="padding:0 18px 14px;background:#fafafa;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;width:28px;">
                            <span style="display:inline-block;font-size:10px;font-weight:700;letter-spacing:0.04em;color:{{ $brand }};">01</span>
                        </td>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <p style="margin:0 0 3px;font-size:12px;font-weight:700;color:{{ $brand }};">Confirmación de asistencia</p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:{{ $muted }};">Es responsabilidad del alumno estar pendiente de confirmar su asistencia a cada clase programada.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <span style="display:inline-block;font-size:10px;font-weight:700;letter-spacing:0.04em;color:{{ $brand }};">02</span>
                        </td>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <p style="margin:0 0 3px;font-size:12px;font-weight:700;color:{{ $brand }};">Plazo de confirmación</p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:{{ $muted }};">La cita podrá confirmarse desde este correo 1 hora antes del horario asignado.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <span style="display:inline-block;font-size:10px;font-weight:700;letter-spacing:0.04em;color:{{ $brand }};">03</span>
                        </td>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <p style="margin:0 0 3px;font-size:12px;font-weight:700;color:{{ $brand }};">Falta de confirmación</p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:{{ $muted }};">Toda cita no confirmada se registrará como cancelación o inasistencia.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <span style="display:inline-block;font-size:10px;font-weight:700;letter-spacing:0.04em;color:{{ $brand }};">04</span>
                        </td>
                        <td style="padding:10px 0;border-top:1px solid {{ $border }};vertical-align:top;">
                            <p style="margin:0 0 3px;font-size:12px;font-weight:700;color:{{ $brand }};">Cancelaciones y penalización</p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:{{ $muted }};">A partir de la segunda clase cancelada, la sesión no será reponible. Para recuperarla se aplicará una penalización de $350.00 MXN.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endcomponent
