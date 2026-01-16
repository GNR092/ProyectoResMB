<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Cotización</title>
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
        <div class="header">
            <h2>Solicitud de Cotización</h2>
        </div>
        <div class="content">
            <p>Estimado proveedor <strong><?= $proveedorNombre ?></strong>,</p>
            <p>Por medio de la presente, <strong><?= $razonSocialEsc ?></strong> le solicita amablemente la cotización de los productos/servicios descritos en el documento PDF adjunto.</p>
            <p><strong>Detalles de la Requisición:</strong></p>
            <ul>
                <li><strong>Folio:</strong> <?= $folio ?></li>
                <li><strong>Fecha de Solicitud:</strong> <?= $fecha ?></li>
            </ul>
            <p>Agradeceríamos enormemente que nos hiciera llegar su propuesta a la brevedad posible. Si tiene alguna duda o requiere información adicional, no dude en contactarnos por los medios habituales.</p>
            <p>Quedamos a su disposición.</p>
        </div>
        <div class="footer">
            <p><strong><?= $razonSocialEsc ?></strong></p>
        </div>
    </div>
</body>
</html>
