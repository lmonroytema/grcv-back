<?php

namespace App\Mail;

use App\Models\VacationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VacationApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly VacationRequest $vacation,
        public readonly string $approvedByName,
        public readonly string $recipientLabel
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Vacaciones Aprobada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vacation-approved',
            with: [
                'vacation' => $this->vacation,
                'approvedByName' => $this->approvedByName,
                'recipientLabel' => $this->recipientLabel,
            ],
        );
    }
}
