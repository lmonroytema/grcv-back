<?php

namespace App\Services;

use App\Mail\VacationApproverNotificationMail;
use App\Mail\VacationEmployeeConfirmationMail;
use App\Mail\VacationHrNotificationMail;
use App\Models\Colaborador;
use App\Models\VacationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class VacationNotificationService
{
    public function sendCreatedNotifications(VacationRequest $vacation): array
    {
        $summary = [
            'employee' => [
                'sent' => false,
                'recipient' => null,
            ],
            'hr' => [
                'sent' => [],
                'failed' => [],
            ],
            'approvers' => [
                'sent' => [],
                'failed' => [],
                'resolved' => [],
            ],
            'warnings' => [],
        ];

        $employeeEmail = $this->normalizeEmail($vacation->email);
        if ($employeeEmail === null) {
            $summary['warnings'][] = 'No se pudo determinar el correo del solicitante.';
        } else {
            $summary['employee']['recipient'] = $employeeEmail;

            try {
                Mail::to($employeeEmail)->send(new VacationEmployeeConfirmationMail($vacation));
                $summary['employee']['sent'] = true;
            } catch (Throwable $exception) {
                report($exception);
                $summary['warnings'][] = 'No se pudo enviar la confirmacion al solicitante.';
            }
        }

        $this->sendHrNotifications($vacation, $summary);
        $this->sendApproverNotifications($vacation, $summary);

        return $summary;
    }

    private function sendHrNotifications(VacationRequest $vacation, array &$summary): void
    {
        $recipients = $this->uniqueEmails(config('vacations.notifications.hr_emails', []));

        if ($recipients === []) {
            $summary['warnings'][] = 'No hay correos configurados para RRHH.';
            return;
        }

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new VacationHrNotificationMail($vacation));
                $summary['hr']['sent'][] = $recipient;
            } catch (Throwable $exception) {
                report($exception);
                $summary['hr']['failed'][] = $recipient;
                $summary['warnings'][] = 'No se pudo enviar la notificacion a RRHH: '.$recipient;
            }
        }
    }

    private function sendApproverNotifications(VacationRequest $vacation, array &$summary): void
    {
        $resolution = $this->resolveApproverRecipients($vacation);
        $summary['approvers']['resolved'] = $resolution['resolved'];
        $summary['warnings'] = array_values(array_unique(array_merge($summary['warnings'], $resolution['warnings'])));

        foreach ($resolution['resolved'] as $approver) {
            try {
                Mail::to($approver['email'])->send(new VacationApproverNotificationMail(
                    $vacation,
                    $approver['label']
                ));
                $summary['approvers']['sent'][] = $approver['email'];
            } catch (Throwable $exception) {
                report($exception);
                $summary['approvers']['failed'][] = $approver['email'];
                $summary['warnings'][] = 'No se pudo enviar la notificacion al aprobador: '.$approver['email'];
            }
        }
    }

    private function resolveApproverRecipients(VacationRequest $vacation): array
    {
        $colaborador = Colaborador::query()
            ->where('n_documento', $vacation->dni)
            ->first();

        if (!$colaborador) {
            return [
                'resolved' => [],
                'warnings' => ['No se encontro el colaborador asociado para resolver aprobadores.'],
            ];
        }

        $resolved = [];
        $warnings = [];

        foreach (['aprobador_1', 'aprobador_2'] as $field) {
            $rawValue = trim((string) ($colaborador->{$field} ?? ''));

            if ($rawValue === '') {
                continue;
            }

            $match = $this->resolveApproverContact($rawValue);

            if ($match === null) {
                $warnings[] = sprintf('No se pudo resolver el correo de %s: %s.', $field, $rawValue);
                continue;
            }

            $resolved[$match['email']] = [
                'email' => $match['email'],
                'label' => $match['label'],
                'source' => $match['source'],
                'field' => $field,
                'value' => $rawValue,
            ];
        }

        if ($resolved === []) {
            $warnings[] = 'No se encontraron correos validos para los aprobadores.';
        }

        return [
            'resolved' => array_values($resolved),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function resolveApproverContact(string $value): ?array
    {
        $email = $this->normalizeEmail($value);
        if ($email !== null) {
            return [
                'email' => $email,
                'label' => $value,
                'source' => 'direct_email',
            ];
        }

        $normalizedValue = $this->normalizeText($value);
        if ($normalizedValue === '') {
            return null;
        }

        $colaborador = Colaborador::query()
            ->where('n_documento', $value)
            ->orWhereRaw('LOWER(TRIM(correo)) = ?', [Str::lower($value)])
            ->orWhereRaw('LOWER(TRIM(apellidos_y_nombres)) = ?', [$normalizedValue])
            ->first();

        if (!$colaborador) {
            return null;
        }

        $resolvedEmail = $this->normalizeEmail((string) $colaborador->correo);
        if ($resolvedEmail === null) {
            return null;
        }

        return [
            'email' => $resolvedEmail,
            'label' => (string) ($colaborador->apellidos_y_nombres ?: $value),
            'source' => 'colaborador',
        ];
    }

    private function uniqueEmails(array $values): array
    {
        $unique = [];

        foreach ($values as $value) {
            $email = $this->normalizeEmail((string) $value);

            if ($email !== null) {
                $unique[$email] = $email;
            }
        }

        return array_values($unique);
    }

    private function normalizeEmail(string $value): ?string
    {
        $email = Str::lower(trim($value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeText(string $value): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }
}
