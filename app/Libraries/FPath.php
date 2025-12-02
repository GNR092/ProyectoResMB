<?php
namespace App\Libraries;
class FPath
{
    public const FCOTIZACION =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'cotizaciones' . DIRECTORY_SEPARATOR;
    public const FORDEN =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'ordenes' . DIRECTORY_SEPARATOR;
    public const FPDF =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'pdf_solicitudes' . DIRECTORY_SEPARATOR;
    public const FSOLICITUD =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'solicitud' . DIRECTORY_SEPARATOR;
    public const FUSER = WRITEPATH . 'users' . DIRECTORY_SEPARATOR;
    public const FFACTURAS =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'facturas' . DIRECTORY_SEPARATOR;
    public const FREMISIONES =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'remisiones' . DIRECTORY_SEPARATOR;
    public const FENTRADAS_FACTURAS =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'entradas_facturas' . DIRECTORY_SEPARATOR;
    public const FFACTURAS_SERVICIOS =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'facturas_servicios' . DIRECTORY_SEPARATOR;
    public const FCOMPROBANTES =
        WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'comprobantes' . DIRECTORY_SEPARATOR;
}