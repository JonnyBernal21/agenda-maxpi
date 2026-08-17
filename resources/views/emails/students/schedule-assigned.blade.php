@php
    $brand = '#0f172a';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $count = $classes->count();
@endphp
@component('emails.layout', ['heading' => 'Horarios de clase asignados', 'title' => 'Horarios de clase'])
    <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:{{ $brand }};">
        Hola, {{ $student->fullName() }}
    </p>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.55;color:{{ $muted }};">
        Se asignaron <strong style="color:{{ $brand }};">{{ $count }} {{ $count === 1 ? 'clase' : 'clases' }}</strong>
        de tu curso <strong style="color:{{ $brand }};">{{ $student->course?->name ?? 'de manejo' }}</strong>.
        Conserva este correo como comprobante de tu horario.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:8px;overflow:hidden;border-collapse:collapse;">
        <tr style="background:{{ $brand }};color:#ffffff;">
            <th align="left" style="padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">#</th>
            <th align="left" style="padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Día</th>
            <th align="left" style="padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Fecha</th>
            <th align="left" style="padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Horario</th>
            <th align="left" style="padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Instructor</th>
            <th align="left" style="padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Vehículo</th>
        </tr>
        @foreach ($classes as $index => $class)
            <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                <td style="padding:9px 8px;border-top:1px solid {{ $border }};font-size:13px;color:{{ $brand }};">{{ $index + 1 }}</td>
                <td style="padding:9px 8px;border-top:1px solid {{ $border }};font-size:13px;color:{{ $brand }};">{{ $class['weekday'] ?? '' }}</td>
                <td style="padding:9px 8px;border-top:1px solid {{ $border }};font-size:13px;color:{{ $brand }};">{{ $class['date_label'] ?? $class['date'] }}</td>
                <td style="padding:9px 8px;border-top:1px solid {{ $border }};font-size:13px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $class['schedule_label'] ?? ($class['time'].' – '.$class['end_time']) }}</td>
                <td style="padding:9px 8px;border-top:1px solid {{ $border }};font-size:13px;color:{{ $brand }};">{{ $class['instructor'] }}</td>
                <td style="padding:9px 8px;border-top:1px solid {{ $border }};font-size:13px;color:{{ $brand }};">{{ $class['vehicle'] }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:18px 0 0;font-size:14px;line-height:1.55;color:{{ $muted }};">
        Cada clase dura 2 horas. Si necesitas un cambio de horario, contacta a la escuela.
    </p>
@endcomponent
