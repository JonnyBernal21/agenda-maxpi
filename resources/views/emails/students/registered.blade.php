@php
    $brand = '#0f172a';
    $muted = '#64748b';
    $border = '#e2e8f0';
    $labelWidth = '38%';
    $rows = [
        ['Nombre', $student->fullName()],
        ['Correo', $student->email],
        ['Teléfono', $student->phone],
        ['Curso', $student->course?->name ?? 'Sin curso'],
        ['Clases del curso', (string) $student->allowedClassesCount()],
        ['Dirección', $student->address],
        ['Ciudad', $student->city],
        ['Estado', $student->state],
        ['C.P.', $student->zip],
        ['País', $student->country],
    ];
@endphp
@component('emails.layout', ['heading' => 'Confirmación de registro', 'title' => 'Registro de alumno'])
    <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:{{ $brand }};">
        Hola, {{ $student->fullName() }}
    </p>
    <p style="margin:0 0 20px;font-size:14px;line-height:1.55;color:{{ $muted }};">
        Tu registro como alumno se completó correctamente. Estos son los datos de tu inscripción:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }};border-radius:8px;overflow:hidden;">
        @foreach ($rows as $index => $row)
            <tr>
                <td style="width:{{ $labelWidth }};padding:10px 14px;background:{{ $index % 2 === 0 ? '#f8fafc' : '#ffffff' }};border-bottom:1px solid {{ $border }};font-size:12px;font-weight:700;color:{{ $muted }};text-transform:uppercase;letter-spacing:0.04em;">
                    {{ $row[0] }}
                </td>
                <td style="padding:10px 14px;background:{{ $index % 2 === 0 ? '#f8fafc' : '#ffffff' }};border-bottom:1px solid {{ $border }};font-size:14px;color:{{ $brand }};">
                    {{ $row[1] ?: '—' }}
                </td>
            </tr>
        @endforeach
    </table>

    <p style="margin:20px 0 0;font-size:14px;line-height:1.55;color:{{ $muted }};">
        En breve recibirás otro correo con tus horarios de clase, cuando queden asignados.
    </p>
@endcomponent
