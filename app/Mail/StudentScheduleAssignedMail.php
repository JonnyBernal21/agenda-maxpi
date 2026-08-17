<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StudentScheduleAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array<string, mixed>>  $classes
     */
    public function __construct(
        public Student $student,
        public Collection $classes,
    ) {
        $this->student->loadMissing('course');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus horarios de clases — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.students.schedule-assigned',
        );
    }
}
