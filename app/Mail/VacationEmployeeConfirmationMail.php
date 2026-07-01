<?php

namespace App\Mail;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VacationEmployeeConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly VacationRequest $vacation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmacion de Solicitud de Vacaciones',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vacation-employee-confirmation',
            with: [
                'vacation' => $this->vacation,
            ],
        );
    }
}
