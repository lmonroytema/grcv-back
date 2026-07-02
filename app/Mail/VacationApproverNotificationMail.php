<?php

namespace App\Mail;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VacationApproverNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly VacationRequest $vacation,
        public readonly string $approverLabel
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Vacaciones Pendiente de Revisión',
            replyTo: [$this->vacation->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vacation-approver-notification',
            with: [
                'vacation' => $this->vacation,
                'approverLabel' => $this->approverLabel,
            ],
        );
    }
}
