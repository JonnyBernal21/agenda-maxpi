@php
    $brand = '#111111';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $cell = 'padding:6px 10px;line-height:1.25;vertical-align:top;';
    $labelStyle = 'font-size:10px;font-weight:700;color:'.$muted.';text-transform:uppercase;letter-spacing:0.03em;line-height:1.2;';
    $valueStyle = 'font-size:12px;color:'.$brand.';line-height:1.3;padding-top:1px;';
    $split = 'width:50%;border-bottom:1px solid '.$border.';';
    $payments = $receipt['payments'] ?? [];
@endphp
@component('emails.layout', ['heading' => 'Recibo de pago', 'title' => 'Recibo de pago', 'message' => $message])
    <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:{{ $brand }};">
        Hola, {{ $receipt['student_name'] }}
    </p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.55;color:{{ $muted }};">
        Este es el desglose de tu pago. Conserva este correo como comprobante.
    </p>

    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
        Datos del recibo
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;margin-bottom:14px;border-collapse:collapse;">
        <tr>
            <td style="{{ $cell }}{{ $split }}border-right:1px solid {{ $border }};background:#fafafa;">
                <div style="{{ $labelStyle }}">Folio</div>
                <div style="{{ $valueStyle }}">{{ $receipt['folio'] }}</div>
            </td>
            <td style="{{ $cell }}{{ $split }}background:#fafafa;">
                <div style="{{ $labelStyle }}">Fecha de emisión</div>
                <div style="{{ $valueStyle }}">{{ $receipt['issued_at'] }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="{{ $cell }}border-bottom:1px solid {{ $border }};background:#ffffff;">
                <div style="{{ $labelStyle }}">Alumno</div>
                <div style="{{ $valueStyle }}">{{ $receipt['student_name'] }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}{{ $split }}border-right:1px solid {{ $border }};background:#fafafa;">
                <div style="{{ $labelStyle }}">Correo</div>
                <div style="{{ $valueStyle }}">{{ $receipt['email'] }}</div>
            </td>
            <td style="{{ $cell }}{{ $split }}background:#fafafa;">
                <div style="{{ $labelStyle }}">Teléfono</div>
                <div style="{{ $valueStyle }}">{{ $receipt['phone'] }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}{{ $split }}border-right:1px solid {{ $border }};background:#ffffff;">
                <div style="{{ $labelStyle }}">Curso</div>
                <div style="{{ $valueStyle }}">{{ $receipt['course'] }}</div>
            </td>
            <td style="{{ $cell }}{{ $split }}background:#ffffff;">
                <div style="{{ $labelStyle }}">Clases</div>
                <div style="{{ $valueStyle }}">{{ $receipt['classes'] }}</div>
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }}width:50%;border-right:1px solid {{ $border }};background:#fafafa;">
                <div style="{{ $labelStyle }}">Modalidad</div>
                <div style="{{ $valueStyle }}">{{ $receipt['modality'] }}</div>
            </td>
            <td style="{{ $cell }}width:50%;background:#fafafa;">
                <div style="{{ $labelStyle }}">Método de pago</div>
                <div style="{{ $valueStyle }}">{{ $receipt['method'] }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
        Desglose
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;margin-bottom:14px;border-collapse:collapse;">
        <tr style="background:#111111;color:#F5C400;">
            <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Concepto</th>
            <th align="right" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Importe</th>
        </tr>
        <tr style="background:#ffffff;">
            <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;color:{{ $brand }};">Curso · {{ $receipt['course'] }}</td>
            <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $receipt['course_amount_label'] }}</td>
        </tr>
        @if (($receipt['home_fee'] ?? 0) > 0)
            <tr style="background:#fafafa;">
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;color:{{ $brand }};">
                    Tarifa a domicilio{{ $receipt['home_fee_input'] ? ' · '.$receipt['home_fee_input'] : '' }}{{ ! empty($receipt['home_fee_times_label']) ? ' × '.$receipt['home_fee_times_label'] : '' }}
                </td>
                <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $receipt['home_fee_label'] }}</td>
            </tr>
            <tr style="background:#ffffff;">
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;color:{{ $brand }};">Subtotal</td>
                <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $receipt['subtotal_label'] }}</td>
            </tr>
        @endif
        @if (($receipt['discount'] ?? 0) > 0)
            <tr style="background:#ffffff;">
                <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;color:{{ $brand }};">
                    Descuento{{ $receipt['discount_input'] ? ' · '.$receipt['discount_input'] : '' }}
                </td>
                <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;font-weight:700;color:#16a34a;white-space:nowrap;">− {{ $receipt['discount_label'] }}</td>
            </tr>
        @endif
        <tr style="background:#fafafa;">
            <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;font-weight:700;color:{{ $brand }};">Total del curso</td>
            <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:13px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $receipt['total_label'] }}</td>
        </tr>
        <tr style="background:#ffffff;">
            <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;color:{{ $brand }};">
                Forma de pago
                @if (! empty($receipt['is_per_class']))
                    · {{ $receipt['classes'] }} {{ $receipt['classes'] === 1 ? 'clase' : 'clases' }} de {{ $receipt['amount_per_class_label'] }}
                @endif
            </td>
            <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:12px;font-weight:700;color:{{ $brand }};">{{ $receipt['plan'] }}</td>
        </tr>
    </table>

    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
        Pagos registrados
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;margin-bottom:14px;border-collapse:collapse;">
        @if ($payments === [])
            <tr>
                <td style="padding:14px 10px;font-size:12px;color:{{ $muted }};">Aún no hay abonos registrados. El saldo queda pendiente.</td>
            </tr>
        @else
            <tr style="background:#111111;color:#F5C400;">
                <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">#</th>
                <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Fecha</th>
                <th align="left" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Concepto</th>
                <th align="right" style="padding:10px 8px;line-height:1.3;font-size:10px;text-transform:uppercase;letter-spacing:0.04em;">Monto</th>
            </tr>
            @foreach ($payments as $index => $payment)
                <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fafafa' }};">
                    <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $payment['number'] }}</td>
                    <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $payment['date'] }}</td>
                    <td style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;color:{{ $brand }};">{{ $payment['type'] }} · {{ $payment['method'] }}</td>
                    <td align="right" style="padding:10px 8px;line-height:1.4;border-top:1px solid {{ $border }};font-size:11px;font-weight:700;color:{{ $brand }};white-space:nowrap;">{{ $payment['amount_label'] }}</td>
                </tr>
            @endforeach
        @endif
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:6px;overflow:hidden;border-collapse:collapse;margin-bottom:16px;">
        <tr>
            <td style="padding:12px 14px;background:#fafafa;border-right:1px solid {{ $border }};width:50%;">
                <div style="{{ $labelStyle }}">Abonado</div>
                <div style="font-size:18px;font-weight:700;color:{{ $brand }};padding-top:4px;">{{ $receipt['paid_label'] }}</div>
            </td>
            <td style="padding:12px 14px;background:{{ ! empty($receipt['is_paid']) ? '#f0fdf4' : '#fffbeb' }};width:50%;">
                <div style="{{ $labelStyle }}">{{ ! empty($receipt['is_paid']) ? 'Estado' : 'Saldo pendiente' }}</div>
                <div style="font-size:18px;font-weight:700;color:{{ ! empty($receipt['is_paid']) ? '#166534' : '#92400e' }};padding-top:4px;">
                    {{ ! empty($receipt['is_paid']) ? 'Liquidado' : $receipt['balance_label'] }}
                </div>
            </td>
        </tr>
    </table>

    @if (! empty($receipt['is_per_class']) && empty($receipt['is_paid']))
        <p style="margin:0 0 16px;font-size:13px;line-height:1.55;color:{{ $muted }};">
            El plan es pago por clase: {{ $receipt['amount_per_class_label'] }} en cada clase hasta liquidar el total.
        </p>
    @else
        <p style="margin:0 0 16px;font-size:13px;line-height:1.55;color:{{ $muted }};">
            Si tienes dudas sobre este recibo, contacta a la escuela.
        </p>
    @endif
@endcomponent
