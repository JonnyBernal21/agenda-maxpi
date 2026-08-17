@php
    $brand = '#0f172a';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $bg = '#f8fafc';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $bg }};font-family:Arial,Helvetica,sans-serif;color:{{ $brand }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $bg }};padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid {{ $border }};border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:{{ $brand }};color:#ffffff;padding:22px 28px;">
                            <div style="font-size:18px;font-weight:700;letter-spacing:0.02em;">{{ config('app.name') }}</div>
                            <div style="margin-top:4px;font-size:13px;opacity:0.82;">{{ $heading }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            {!! $slot !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;border-top:1px solid {{ $border }};color:{{ $muted }};font-size:12px;">
                            Este correo fue enviado automáticamente por {{ config('app.name') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
