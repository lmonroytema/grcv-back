<?php

return [
    'notifications' => [
        'hr_emails' => array_values(array_filter(array_map(
            static fn (string $email): string => trim($email),
            explode(',', (string) env('HR_NOTIFICATION_EMAILS', 'aqueque@tema.com.pe'))
        ), static fn (string $email): bool => $email !== '')),
        'approval_emails' => array_values(array_filter(array_map(
            static fn (string $email): string => trim($email),
            explode(',', (string) env('VACATION_APPROVAL_NOTIFICATION_EMAILS', (string) env('HR_NOTIFICATION_EMAILS', 'aqueque@tema.com.pe')))
        ), static fn (string $email): bool => $email !== '')),
    ],
];
