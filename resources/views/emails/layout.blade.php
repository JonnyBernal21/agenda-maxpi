@php
    $brand = '#111111';
    $yellow = '#f5c400';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $bg = '#f4f4f5';
    $logoPath = public_path('logo.png');
    $canEmbed = isset($message) && is_object($message) && method_exists($message, 'embed');
    $logoSrc = $canEmbed && is_file($logoPath)
        ? $message->embed($logoPath)
        : asset('logo.png');
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
                        <td align="center" style="background:#FFFFFF;padding:14px 24px;">
                            <img src="{{ $logoSrc }}" alt="MaxPi Escuela de Manejo" width="100" style="display:block;margin:0 auto;width:100px;max-width:100px;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="height:6px;line-height:6px;font-size:0;background:{{ $yellow }};">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 28px 0;text-align:center;font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:{{ $brand }};">
                            {{ $heading }}
                        </td>   
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 28px;">
                            {!! $slot !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 28px;border-top:1px solid {{ $border }};color:{{ $muted }};font-size:12px;text-align:center;">
                            MaxPi Escuela de Manejo
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
