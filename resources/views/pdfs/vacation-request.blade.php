<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Vacaciones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { margin-bottom: 24px; }
        .title { font-size: 22px; font-weight: bold; margin: 0 0 4px; }
        .subtitle { color: #475569; margin: 0; }
        .card { border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; margin-bottom: 18px; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; min-width: 135px; }
        .attachment { margin-top: 16px; }
        img { max-width: 100%; height: auto; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Solicitud de Vacaciones</p>
        <p class="subtitle">Gestión, Registro y Control de Vacaciones</p>
    </div>

    <div class="card">
        <div class="row"><span class="label">DNI:</span> {{ $vacation->dni }}</div>
        <div class="row"><span class="label">Nombre completo:</span> {{ $vacation->full_name }}</div>
        <div class="row"><span class="label">Correo:</span> {{ $vacation->email }}</div>
        <div class="row"><span class="label">Área:</span> {{ $vacation->area }}</div>
        <div class="row"><span class="label">Inicio:</span> {{ optional($vacation->start_date)->format('d/m/Y') }}</div>
        <div class="row"><span class="label">Fin:</span> {{ optional($vacation->end_date)->format('d/m/Y') }}</div>
        <div class="row"><span class="label">Días:</span> {{ $vacation->days }}</div>
        <div class="row"><span class="label">Estado:</span> {{ match($vacation->estado){0 => 'Rechazado', 2 => 'Aprobado', default => 'Pendiente'} }}</div>
    </div>

    <div class="card attachment">
        <div class="row"><span class="label">Adjunto:</span> {{ $attachmentFilename }}</div>
        @if ($attachmentDataUri)
            <img src="{{ $attachmentDataUri }}" alt="Adjunto de confirmación">
        @else
            <p>El archivo adjunto no es una imagen embebible en el PDF.</p>
        @endif
    </div>
</body>
</html>
