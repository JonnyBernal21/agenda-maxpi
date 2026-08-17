<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Student $student)
    {
        $this->student->loadMissing('course');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registro de alumno — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.students.registered',
        );
    }
}
