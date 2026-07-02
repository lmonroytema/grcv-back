<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Solicitud de Vacaciones</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Se ha registrado una nueva solicitud de vacaciones.</p>

    <p>
        <strong>Empleado:</strong> {{ $vacation->full_name }}<br>
        <strong>DNI:</strong> {{ $vacation->dni }}<br>
        <strong>Área:</strong> {{ $vacation->area }}<br>
        <strong>Fecha de inicio:</strong> {{ optional($vacation->start_time)->format('Y-m-d H:i:s') }}<br>
        <strong>Fecha de fin:</strong> {{ optional($vacation->end_time)->format('Y-m-d H:i:s') }}<br>
        <strong>Días totales:</strong> {{ $vacation->days }}<br>
        <strong>Correo del empleado:</strong> {{ $vacation->email }}
    </p>

    <p>Revise la solicitud en el sistema para continuar con la gestión.</p>
</body>
</html>
