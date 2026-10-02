<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentPaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $receipt
     */
    public function __construct(
        public Student $student,
        public array $receipt,
    ) {
        $this->student->loadMissing('course');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibo de pago — MaxPi Escuela de Manejo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.students.payment-receipt',
        );
    }
}
