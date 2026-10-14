<?php

namespace App\Http\Controllers;

use App\Models\Reservas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassRsvpController extends Controller
{
    public function show(string $token): View
    {
        $reserva = $this->reserva($token);

        return view('rsvp.show', [
            'reserva' => $reserva,
            'locked' => $this->locked($reserva),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $reserva = $this->reserva($token);

        if ($this->locked($reserva)) {
            return back()->withErrors([
                'response' => 'Esta clase ya no admite confirmación.',
            ]);
        }

        $validated = $request->validate([
            'response' => ['required', 'in:asistira,no_asistira'],
        ]);

        $reserva->update([
            'rsvp_response' => $validated['response'],
            'rsvp_at' => now(),
            'status' => $validated['response'] === 'asistira' && $reserva->status === 'pendiente'
                ? 'confirmada'
                : $reserva->status,
        ]);

        $message = $validated['response'] === 'asistira'
            ? 'Gracias. Confirmamos que sí asistirás a tu clase.'
            : 'Registramos que no podrás asistir. La escuela se pondrá en contacto contigo.';

        return redirect()
            ->route('reservas.rsvp', $token)
            ->with('success', $message);
    }

    private function reserva(string $token): Reservas
    {
        return Reservas::query()
            ->with(['student', 'instructor', 'vehicle'])
            ->where('rsvp_token', $token)
            ->firstOrFail();
    }

    private function locked(Reservas $reserva): bool
    {
        return in_array($reserva->status, ['cancelada', 'completada', 'en_curso'], true);
    }
}
