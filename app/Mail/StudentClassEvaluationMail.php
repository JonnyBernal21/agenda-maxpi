<?php

namespace App\Mail;

use App\Models\ClassEvaluation;
use App\Models\Reservas;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentClassEvaluationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public Reservas $reserva,
        public ClassEvaluation $evaluation,
    ) {
        $this->student->loadMissing('course');
        $this->reserva->loadMissing(['vehicle', 'instructor']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resultado de tu clase — MaxPi Escuela de Manejo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.students.class-evaluation',
        );
    }
}
