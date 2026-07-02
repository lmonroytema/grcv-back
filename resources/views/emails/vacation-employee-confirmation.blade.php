<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de Solicitud de Vacaciones</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Estimado/a {{ trim($vacation->first_name . ' ' . $vacation->last_name) }},</p>

    <p>Su solicitud de vacaciones ha sido registrada correctamente.</p>

    <p>
        <strong>Periodo:</strong> {{ optional($vacation->start_date)->format('Y-m-d') }} al {{ optional($vacation->end_date)->format('Y-m-d') }}<br>
        <strong>Días solicitados:</strong> {{ $vacation->days }}<br>
        <strong>Estado:</strong> Pendiente de aprobación
    </p>

    <p>Recibirá una notificación cuando su solicitud sea procesada.</p>

    <p>Saludos,<br>Sistema de Gestión de Vacaciones</p>
</body>
</html>
