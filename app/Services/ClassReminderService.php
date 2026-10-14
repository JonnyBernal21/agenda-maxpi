<?php

namespace App\Services;

use App\Models\Reservas;
use App\Support\WhatsAppNumber;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClassReminderService
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * @return array{whatsapp: string, message: string, confirm_url: string}|null
     */
    public function payload(Reservas $reserva): ?array
    {
        $reserva->loadMissing(['student', 'instructor', 'vehicle']);
        $whatsapp = WhatsAppNumber::digits($reserva->student?->phone);

        if (! $whatsapp) {
            return null;
        }

        $company = $this->settings->current()->companyName();
        $student = $reserva->student?->name ?: 'alumno';
        $date = Carbon::parse($reserva->date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $start = Reservas::normalizeTime((string) $reserva->time);
        $end = Reservas::slotEndTime((string) $reserva->time);
        $url = $this->confirmUrl($reserva);

        $message = implode("\n", [
            "Hola, {$student}",
            '',
            "Te enviamos un recordatorio de tu clase en {$company}.",
            '',
            "Fecha: {$date}",
            "Horario: {$start} - {$end}",
            '',
            '¿Asistirás a tu clase?',
            'Confirma aquí:',
            $url,
        ]);

        return [
            'whatsapp' => $whatsapp,
            'message' => $message,
            'confirm_url' => $url,
        ];
    }

    public function confirmUrl(Reservas $reserva): string
    {
        return route('reservas.rsvp', $this->token($reserva), absolute: true);
    }

    public function token(Reservas $reserva): string
    {
        if (filled($reserva->rsvp_token)) {
            return $reserva->rsvp_token;
        }

        do {
            $token = Str::lower(Str::random(12));
        } while (Reservas::query()->where('rsvp_token', $token)->exists());

        $reserva->forceFill(['rsvp_token' => $token])->save();

        return $token;
    }
}
