<x-mail::message>
# Hola, {{ $student->fullName() }}

Tu curso **{{ $student->course?->name ?? 'de manejo' }}** ya tiene horario asignado. Estas son tus clases (estado: pendiente):

<x-mail::table>
| Fecha | Horario | Instructor | Vehículo |
|:------|:--------|:-----------|:---------|
@foreach ($classes as $class)
| {{ $class['date_label'] ?? $class['date'] }} | {{ $class['time'] }} – {{ $class['end_time'] }} | {{ $class['instructor'] }} | {{ $class['vehicle'] }} |
@endforeach
</x-mail::table>

Puedes consultar tu agenda en la plataforma:

<x-mail::button :url="route('student.login')">
Iniciar sesión
</x-mail::button>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
