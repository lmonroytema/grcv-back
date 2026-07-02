<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Vacaciones Aprobada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Estimado/a {{ $recipientLabel }},</p>

    <p>La solicitud de vacaciones del trabajador {{ $vacation->full_name }} ha sido aprobada correctamente.</p>

    <p>
        <strong>Empleado:</strong> {{ $vacation->full_name }}<br>
        <strong>DNI:</strong> {{ $vacation->dni }}<br>
        <strong>Área:</strong> {{ $vacation->area }}<br>
        <strong>Periodo:</strong> {{ optional($vacation->start_date)->format('Y-m-d') }} al {{ optional($vacation->end_date)->format('Y-m-d') }}<br>
        <strong>Días aprobados:</strong> {{ $vacation->days }}<br>
        <strong>Aprobado por:</strong> {{ $approvedByName }}
    </p>

    <p>Puede revisar el estado actualizado desde el sistema.</p>

    <p>Saludos,<br>Sistema de Gestión de Vacaciones</p>
</body>
</html>
