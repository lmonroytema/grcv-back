<?php

namespace App\Services;

use App\Models\VacationRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VacationPdfService
{
    public function generate(VacationRequest $vacation): string
    {
        $attachmentPath = $this->resolveAttachmentPath($vacation->confirmation_image);
        $attachmentMime = $attachmentPath !== null ? mime_content_type($attachmentPath) ?: null : null;
        $attachmentDataUri = null;

        if ($attachmentPath !== null && is_string($attachmentMime) && str_starts_with($attachmentMime, 'image/')) {
            $attachmentDataUri = 'data:'.$attachmentMime.';base64,'.base64_encode((string) file_get_contents($attachmentPath));
        }

        $pdf = Pdf::loadView('pdfs.vacation-request', [
            'vacation' => $vacation,
            'attachmentDataUri' => $attachmentDataUri,
            'attachmentFilename' => $vacation->confirmation_image,
        ]);

        $filename = sprintf(
            '%s-%s-%s.pdf',
            optional($vacation->end_date)->format('Ymd') ?? now()->format('Ymd'),
            preg_replace('/[^0-9]/', '', $vacation->dni) ?: 'dni',
            Str::limit(Str::slug($vacation->full_name, '-'), 45, '') ?: 'solicitud'
        );

        Storage::disk('public')->put('vacations/pdfs/'.$filename, $pdf->output());

        return $filename;
    }

    public function resolvePdfPath(string $filename): ?string
    {
        $storagePath = storage_path('app/public/vacations/pdfs/'.$filename);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return $this->resolveLegacyPath($filename);
    }

    public function resolveAttachmentPath(string $filename): ?string
    {
        $storagePath = storage_path('app/public/vacations/attachments/'.$filename);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return $this->resolveLegacyPath($filename);
    }

    private function resolveLegacyPath(string $filename): ?string
    {
        $legacyBase = rtrim((string) env('LEGACY_UPLOADS_PATH', ''), '\\/');
        if ($legacyBase === '') {
            return null;
        }

        $legacyPath = $legacyBase.DIRECTORY_SEPARATOR.$filename;

        return is_file($legacyPath) ? $legacyPath : null;
    }
}
