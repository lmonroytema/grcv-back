<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Vacaciones Pendiente de Revision</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Hola{{ $approverLabel !== '' ? ' ' . $approverLabel : '' }},</p>

    <p>Tienes una solicitud de vacaciones pendiente de revision.</p>

    <p>
        <strong>Empleado:</strong> {{ $vacation->full_name }}<br>
        <strong>DNI:</strong> {{ $vacation->dni }}<br>
        <strong>Area:</strong> {{ $vacation->area }}<br>
        <strong>Periodo:</strong> {{ optional($vacation->start_date)->format('Y-m-d') }} al {{ optional($vacation->end_date)->format('Y-m-d') }}<br>
        <strong>Dias:</strong> {{ $vacation->days }}<br>
        <strong>Correo del empleado:</strong> {{ $vacation->email }}
    </p>

    <p>Por favor, revisa la solicitud en el sistema.</p>
</body>
</html>
