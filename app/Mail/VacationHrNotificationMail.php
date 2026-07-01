<?php

namespace App\Mail;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VacationHrNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly VacationRequest $vacation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva Solicitud de Vacaciones',
            replyTo: [$this->vacation->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vacation-hr-notification',
            with: [
                'vacation' => $this->vacation,
            ],
        );
    }
}
