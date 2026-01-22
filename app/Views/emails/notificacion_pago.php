<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificación de Requisición de Pago</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px; }
        .container { padding: 20px; border: 1px solid #ddd; border-radius: 8px; max-width: 600px; margin: auto; }
        h2 { color: #004a99; }
        ul { list-style-type: none; padding: 0; }
        li { margin-bottom: 10px; }
        strong { color: #333; }
        .footer { margin-top: 20px; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Nueva Requisición de Pago</h2>
        <p>Se ha generado una nueva requisición de pago que requiere su atención.</p>
        <ul>
            <li><strong>Folio de Solicitud:</strong> <?= esc($folio) ?></li>
            <li><strong>Proveedor:</strong> <?= esc($proveedor) ?></li>
            <li><strong>Total a Pagar:</strong> $<?= esc($total) ?></li>
            <li><strong>Razón Social:</strong> <?= esc($razonSocial) ?></li>
        </ul>
        <p>El documento de Requisición de Pago se encuentra adjunto a este correo.</p>
        <p class="footer">Saludos cordiales,<br>Sistema de Adquisiciones</p>
    </div>
</body>
</html>
