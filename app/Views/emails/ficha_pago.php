<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Pago Generada</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .header { padding: 15px 20px; background-color: #004a99; color: #ffffff; text-align: center; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { padding: 25px 20px; }
        .content p { margin: 0 0 15px; }
        .content ul { list-style: none; padding: 0; margin: 15px 0; border-left: 3px solid #004a99; padding-left: 15px; }
        .content li { margin-bottom: 8px; }
        .footer { margin-top: 20px; padding: 15px 20px; font-size: 0.85em; color: #6c757d; text-align: center; background-color: #f4f4f4; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h2>Ficha de Pago Generada</h2></div>
        <div class="content">
            <p>Estimado <strong><?= esc($recipientName) ?></strong>,</p>
            <p>Se ha generado una ficha de pago para la solicitud con folio <strong><?= esc($folio) ?></strong> por un monto total de <strong><?= esc($totalAPagar) ?></strong>.</p>
            <p>El comprobante de pago se adjunta a este correo.</p>
            <p><strong>Detalles de la Solicitud:</strong></p>
            <ul>
                <li><strong>Folio de Solicitud:</strong> <?= esc($folio) ?></li>
                <li><strong>Proveedor:</strong> <?= esc($proveedorNombre) ?></li>
                <li><strong>Monto:</strong> <?= esc($totalAPagar) ?></li>
            </ul>
            <p>Por favor, verifique el archivo adjunto para más detalles.</p>
            <p>Saludos cordiales,</p>
            <p>Departamento de Tesorería</p>
        </div>
        <div class="footer"><p><strong><?= esc($razonNombre) ?></strong></p></div>
    </div>
</body>
</html>
