@php
    $brand = '#111111';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $items = $evaluation->scoredItems();
@endphp
@component('emails.layout', ['heading' => 'Resultado de tu clase', 'title' => 'Resultado de clase', 'message' => $message])
    <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:{{ $brand }};">
        Hola, {{ $student->fullName() }}
    </p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.55;color:{{ $muted }};">
        Tu instructor registró la evaluación de la clase del {{ $reserva->date }}.
        Conserva este correo como comprobante de tu avance.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;margin-bottom:14px;border-collapse:collapse;">
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid {{ $border }};">
                <div style="font-size:10px;font-weight:700;color:{{ $muted }};text-transform:uppercase;">Promedio</div>
                <div style="font-size:22px;font-weight:700;color:{{ $brand }};">{{ number_format($evaluation->average, 1) }} / 5</div>
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid {{ $border }};">
                <div style="font-size:10px;font-weight:700;color:{{ $muted }};text-transform:uppercase;">Tipo de vehículo</div>
                <div style="font-size:13px;color:{{ $brand }};">{{ $evaluation->vehicleTypeLabel() }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;">
                <div style="font-size:10px;font-weight:700;color:{{ $muted }};text-transform:uppercase;">Unidad</div>
                <div style="font-size:13px;color:{{ $brand }};">{{ $evaluation->vehicle_label ?: '—' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
        Calificación por habilidad
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;margin-bottom:14px;border-collapse:collapse;">
        @foreach ($items as $item)
            <tr>
                <td style="padding:8px 10px;border-bottom:1px solid {{ $border }};font-size:13px;color:{{ $brand }};">
                    {{ $item['title'] }}
                    <div style="font-size:11px;color:{{ $muted }};">{{ $item['label'] }}</div>
                </td>
                <td style="padding:8px 10px;border-bottom:1px solid {{ $border }};text-align:right;font-weight:700;color:{{ $brand }};white-space:nowrap;">
                    {{ $item['score'] }}/5
                </td>
            </tr>
        @endforeach
    </table>

    @if ($evaluation->notes)
        <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
            Observaciones
        </p>
        <p style="margin:0;font-size:14px;line-height:1.55;color:{{ $muted }};">{{ $evaluation->notes }}</p>
    @endif
@endcomponent
