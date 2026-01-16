<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\OrdenCompraModel;
use App\Models\PagoModel;
use App\Models\ProductoModel;
use App\Models\HistorialProductosModel;
use CodeIgniter\RESTful\ResourceController;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;
use App\Libraries\ImageProcessor;
use App\Libraries\SolicitudTipo;
use App\Libraries\Status;
use App\Libraries\MBSMail;
use App\Libraries\MetodoPago;
use App\Controllers\GenerarPDF;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Models\UsuariosModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Api extends ResourceController
{
    protected $format = 'json';
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }

    public function test($id)
    {
        return $this->respond($this->api->getSolicitudPago($id));
    }

    //region Productos

    /**
     * Busca productos por consulta y tipo.
     * @return \CodeIgniter\HTTP\Response
     */
    public function search()
    {
        $query = $this->request->getVar('query');
        $type = $this->request->getVar('type');

        if (empty($query)) {
            return $this->fail('La consulta no puede estar vacía.', HttpStatus::BAD_REQUEST);
        }
        $results = $this->api->getProductsByQuery($query, $type);
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene todos los productos.
     * @return \CodeIgniter\HTTP\Response
     */
    public function allProducts()
    {
        $results = $this->api->getAllProducts();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene un producto por su ID.
     * @param int|null $id El ID del producto.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getProductById($id)
    {
        $result = $this->api->getProductById($id);
        if ($result === null) {
            return $this->failNotFound('Producto no encontrado.');
        }
        return $this->respond($result, HttpStatus::OK);
    }
    //endregion

    //region Proveedores

    /**
     * Obtiene todos los proveedores con solo ID y Nombre.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getAllProviders()
    {
        $results = $this->api->getProveedorIdAndRazonSocial();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene un proveedor por su ID.
     * @param int|null $id El ID del proveedor.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getProviderById(int $id)
    {
        $result = $this->api->getProveedorByID($id);
        if ($result === null) {
            return $this->failNotFound('Proveedor no encontrado.');
        }
        return $this->respond($result, HttpStatus::OK);
    }
    //endregion

    //region Departamentos

    /**
     * Obtiene todos los departamentos.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getDepartments()
    {
        $results = $this->api->getAllDepartments();
        return $this->respond($results, HttpStatus::OK);
    }
    //endregion

    //region Solicitudes (Consultas)

    /**
     * Obtiene todo el historial de solicitudes.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getHistorial()
    {
        $result = $this->api->getAllSolicitud();
        return $this->respond($result, HttpStatus::OK);
    }

    /**
     * Obtiene el historial de solicitudes por departamento.
     * @param int $id El ID del departamento.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getHistorialByDepartment($id)
    {
        $results = $this->api->getSolicitudByDepartment($id);
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene los detalles de una solicitud específica.
     * @param int|null $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudDetails($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de solicitud numérico.');
        }

        $details = $this->api->getSolicitudWithProducts((int) $id);

        if (empty($details)) {
            return $this->failNotFound(
                'No se encontraron detalles para la solicitud con ID: ' . $id,
            );
        }

        return $this->respond($details);
    }

    /**
     * Obtiene todas las solicitudes cotizadas.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudesCotizadas()
    {
        $results = $this->api->getSolicitudesCotizadas();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene todas las solicitudes en revisión.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudesEnRevision()
    {
        $results = $this->api->getSolicitudesEnRevision();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene las solicitudes pendientes de aprobación para el departamento del jefe actual.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getPendientesAprobacionJefe()
    {
        if (session('login_type') !== 'boss') {
            return $this->failForbidden('Acceso denegado. Solo para jefes de departamento.');
        }

        $idDepartamento = session('id_departamento_usuario');
        $idJefe = session('id');

        $results = $this->api->getSolicitudesByStatusAndDept(
            Status::Aprobacion_pendiente,
            $idDepartamento,
            $idJefe,
        );
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene las solicitudes de un usuario por su ID.
     * @param int|null $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudesUsers($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de usuario numérico.');
        }

        return $this->respond(
            $this->api->getSolicitudesUsersByDepartment((int) $id),
            HttpStatus::OK,
        );
    }
    //endregion

    //region Solicitudes (Acciones)

    /**
     * Actualiza los montos y comentarios de una solicitud.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function actualizarMontos()
    {
        $json = $this->request->getJSON();

        if (
            !isset($json->id_solicitud) ||
            !isset($json->productos) ||
            !is_array($json->productos)
        ) {
            return $this->failValidationErrors(
                'Se requiere ID de solicitud y un array de productos/servicios.',
            );
        }

        $idSolicitud = (int) $json->id_solicitud;
        $itemsPayload = $json->productos;
        $comentarios = $json->comentarios ?? null;
        $idCuenta = $json->id_cuenta ?? null;

        $idCotizacionSeleccionada = $json->id_cotizacion_seleccionada ?? null;

        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();

        $solicitud = $solicitudModel->find($idSolicitud);
        if (!$solicitud) {
            return $this->failNotFound('La esolicitud no existe.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $updateData = [];
            if ($idCotizacionSeleccionada) {
                $cotizacionSeleccionada = $cotizacionModel->find($idCotizacionSeleccionada);
                if ($cotizacionSeleccionada) {
                    $updateData['ID_Proveedor'] = $cotizacionSeleccionada['ID_Proveedor'];
                }
            }

            if ($idCuenta) {
                $updateData['ID_Cuenta'] = $idCuenta;
            }

            if (!empty($updateData)) {
                $solicitudModel->update($idSolicitud, $updateData);
            }
            
            if ((int)$solicitud['Tipo'] === SolicitudTipo::Servicios) {
                $solicitudServiciosModel = new SolicitudServiciosModel();
                $solicitudItemsDB = $solicitudServiciosModel
                    ->where('ID_Solicitud', $idSolicitud)
                    ->findAll();

                if (count($itemsPayload) !== count($solicitudItemsDB)) {
                    throw new \Exception(
                        'El número de servicios en el payload no coincide con el número de servicios existentes en la solicitud.',
                    );
                }

                foreach ($itemsPayload as $index => $item) {
                    if (!isset($item->nombre) || !isset($item->importe)) {
                        throw new \Exception('Cada servicio debe tener nombre e importe.');
                    }

                    $idSolicitudItem = $solicitudItemsDB[$index]['ID_SolicitudServ'];
                    $nombre = (string) $item->nombre;
                    $importe = (float) $item->importe;

                    $solicitudServiciosModel->update($idSolicitudItem, [
                        'Nombre' => $nombre,
                        'Importe' => $importe,
                    ]);
                }
            } else {
                $solicitudProductModel = new SolicitudProductModel();
                $solicitudItemsDB = $solicitudProductModel
                    ->where('ID_Solicitud', $idSolicitud)
                    ->findAll();

                if (count($itemsPayload) !== count($solicitudItemsDB)) {
                    throw new \Exception(
                        'El número de productos en el payload no coincide con el número de productos existentes en la solicitud.',
                    );
                }

                foreach ($itemsPayload as $index => $p) {
                    if (
                        !isset($p->codigo) ||
                        !isset($p->nombre) ||
                        !isset($p->cantidad) ||
                        !isset($p->importe)
                    ) {
                        throw new \Exception(
                            'Cada producto debe tener código, nombre, cantidad e importe.',
                        );
                    }

                    $idSolicitudProd = $solicitudItemsDB[$index]['ID_SolicitudProd'];
                    $codigo = (string) $p->codigo;
                    $nombre = (string) $p->nombre;
                    $cantidad = (int) $p->cantidad;
                    $importe = (float) $p->importe;

                    $solicitudProductModel->update($idSolicitudProd, [
                        'Codigo' => $codigo,
                        'Nombre' => $nombre,
                        'Cantidad' => $cantidad,
                        'Importe' => $importe,
                    ]);
                }
            }

            $solicitudModel->update($idSolicitud, ['ComentariosUser' => $comentarios]);

            $details = $this->api->getSolicitudWithProducts($idSolicitud);
            $nuevoTotal = 0;
            if (!empty($details['productos'])) {
                if ((int)$solicitud['Tipo'] === SolicitudTipo::Servicios) {
                    foreach ($details['productos'] as $item) {
                        $nuevoTotal += (float) $item['Importe'];
                    }
                } else {
                    foreach ($details['productos'] as $p) {
                        $nuevoTotal += (float) $p['Cantidad'] * (float) $p['Importe'];
                    }
                }
            }

            $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
            if ($cotizacion) {
                $cotizacionModel->update($cotizacion['ID_Cotizacion'], ['Total' => $nuevoTotal]);
            } else {
                throw new \Exception('No se encontró cotización asociada a la solicitud.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception(
                    'Falla en la transacción de base de datos al actualizar montos.',
                );
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Solicitud actualizada correctamente.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[actualizarMontos] ' . $e->getMessage());
            return $this->failServerError(
                'Ocurrió un error inesperado al actualizar los montos: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Permite a un jefe de departamento aprobar o rechazar una solicitud de un empleado.
     * @return \CodeIgniter\HTTP\Response
     */
    public function dictaminarSolicitudJefe()
    {
        if (session('login_type') !== 'boss') {
            return $this->failForbidden('Acceso denegado. Solo para jefes de departamento.');
        }

        $json = $this->request->getJSON();
        // Validamos que exista ID y acción
        if (!isset($json->ID_Solicitud) || !isset($json->accion)) {
            return $this->failValidationErrors(
                'Se requiere ID de solicitud y una acción (aprobar/rechazar).',
            );
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $accion = $json->accion;
        $comentarios = $json->comentarios ?? null;

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        // Verificaciones de seguridad
        if ($solicitud['ID_Dpto'] != session('id_departamento_usuario')) {
            return $this->failForbidden('Esta solicitud no pertenece a su departamento.');
        }

        // Verificar estado actual
        if ($solicitud['Estado'] !== 'Aprobacion pendiente' && $solicitud['Estado'] !== Status::Aprobacion_pendiente) {
            return $this->fail('La solicitud ya ha sido procesada o no está pendiente de aprobación.', HttpStatus::BAD_REQUEST);
        }

        try {
            // Si la acción es aprobar, pasa a 'En espera'.
            // Si es rechazar, pasa explícitamente a 'Rechazada'.
            $nuevoEstado = ($accion === 'aprobar' || $accion === Status::Aprobar)
                ? Status::En_espera
                : 'Rechazada';

            $solicitudModel->update($idSolicitud, [
                'Estado' => $nuevoEstado,
                'ComentariosAdmin' => $comentarios,
            ]);

            return $this->respondUpdated([
                'success' => true,
                'message' => 'La solicitud ha sido ' .
                    ($nuevoEstado === 'Rechazada' ? 'rechazada.' : 'aprobada y enviada a Compras.'),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[dictaminarSolicitudJefe] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado al actualizar la solicitud.');
        }
    }

    public function aprobarYCotizar()
    {
        if (session('login_type') !== 'boss') {
            return $this->failForbidden('Acceso denegado. Solo para jefes de departamento.');
        }

        $json = $this->request->getJSON();
        if (!isset($json->ID_Solicitud)) {
            return $this->failValidationErrors('Se requiere ID de solicitud.');
        }

        $idSolicitud = (int) $json->ID_Solicitud;

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['ID_Dpto'] != session('id_departamento_usuario')) {
            return $this->failForbidden('Esta solicitud no pertenece a su departamento.');
        }
        if ($solicitud['Estado'] !== Status::Aprobacion_pendiente) {
            return $this->fail('La solicitud ya ha sido procesada.', HttpStatus::BAD_REQUEST);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {

            $nuevoEstado = Status::En_espera;

            // Si la solicitud es de Servicios
            if ($solicitud['Tipo'] == SolicitudTipo::Servicios) {
                $nuevoEstado = 'Cotizando';
            }

            // Actualizamos con el estado dinámico
            $solicitudModel->update($idSolicitud, ['Estado' => $nuevoEstado]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            // Mensaje dinámico según el estado resultante
            $mensajeExito = $nuevoEstado == 'Cotizando'
                ? 'Solicitud aprobada y enviada a etapa de Cotización.'
                : 'Solicitud aprobada y enviada a Compras para su cotización.';

            return $this->respondUpdated([
                'success' => true,
                'message' => $mensajeExito,
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[aprobarYCotizar] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Crea una nueva cotización para una solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function crearCotizacion()
    {
        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $razonSocialModel = new RazonSocialModel();
        $proveedorModel = new ProveedorModel();

        $json = $this->request->getJSON();
        if (!$json) {
            return $this->fail('Invalid JSON.', HttpStatus::BAD_REQUEST);
        }

        if (
            !isset($json->ID_Solicitud) ||
            !isset($json->ID_Proveedores) ||
            !is_array($json->ID_Proveedores) ||
            empty($json->ID_Proveedores) ||
            !isset($json->ID_Usuario)
        ) {
            return $this->failValidationErrors(
                'Se requiere ID de solicitud, un array de IDs de proveedor y el ID de usuario.',
            );
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $idProveedores = $json->ID_Proveedores;
        $idUsuarioCotiza = (int) $json->ID_Usuario;

        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== Status::En_espera) {
            return $this->fail(
                'La solicitud ya no está en estado "En espera". Estado actual: ' .
                    $solicitud['Estado'],
                HttpStatus::BAD_REQUEST,
            );
        }

        $details = $this->api->getSolicitudWithProducts($idSolicitud);
        $razon = $razonSocialModel->find($solicitud['ID_RazonSocial']);
        $razonNombre = $razon['Nombre'];

        $total = 0;
        if ($solicitud['Tipo'] != SolicitudTipo::Servicios) {
            if (!empty($details['productos'])) {
                foreach ($details['productos'] as $p) {
                    $total += (float) $p['Cantidad'] * (float) $p['Importe'];
                }
            }
        } else {
            if (!empty($details['productos'])) {
                foreach ($details['productos'] as $p) {
                    $total += (float) $p['Importe'];
                }
            }
        }

        $pdf = new GenerarPDF();
        $pdf->generarYGuardarRequisicion($idSolicitud);
        $attachmentPath = FPath::FPDF . 'Requisicion-MBSP-' . $idSolicitud . '.pdf';

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $mail = new MBSMail();

            foreach ($idProveedores as $idProveedor) {
                $idProveedor = (int) $idProveedor;
                $proveedor = $proveedorModel->find($idProveedor);

                $cotizacionData = [
                    'ID_Solicitud' => $idSolicitud,
                    'ID_Proveedor' => $idProveedor,
                    'Total' => $total,
                    'ID_Usuario_Cotiza' => $idUsuarioCotiza,
                ];
                $cotizacionModel->insert($cotizacionData);
                $solicitudModel->update($idSolicitud, ['ID_Proveedor' => $idProveedor]);

                $to = getenv('EMAIL_TO_TEST');
                if (empty($to)) {
                    if (!$proveedor || empty($proveedor['Correo'])) {
                        throw new \Exception(
                            "No se pudo encontrar un correo electrónico para el proveedor con ID: {$idProveedor}.",
                        );
                    }
                    $to = $proveedor['Correo'];
                }

                $proveedorNombre = $proveedor ? esc($proveedor['RazonSocial']) : 'Proveedor';
                $folio = esc($solicitud['No_Folio']);
                $fecha = esc($solicitud['Fecha']);
                $razonSocialEsc = esc($razonNombre);

                $subject = "Solicitud de Cotización - Folio {$folio} - {$razonSocialEsc}";

                $message = '';
                $message .=
                    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Solicitud de Cotización</title>';
                $message .= '<style>';
                $message .=
                    'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }';
                $message .=
                    '.container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }';
                $message .=
                    '.header { padding: 15px 20px; background-color: #004a99; color: #ffffff; text-align: center; border-radius: 8px 8px 0 0; }';
                $message .= '.header h2 { margin: 0; font-size: 24px; }';
                $message .= '.content { padding: 25px 20px; }';
                $message .= '.content p { margin: 0 0 15px; }';
                $message .=
                    '.content ul { list-style: none; padding: 0; margin: 15px 0; border-left: 3px solid #004a99; padding-left: 15px; }';
                $message .= '.content li { margin-bottom: 8px; }';
                $message .=
                    '.footer { margin-top: 20px; padding: 15px 20px; font-size: 0.85em; color: #6c757d; text-align: center; background-color: #f4f4f4; border-radius: 0 0 8px 8px; }';
                $message .= '</style></head><body>';
                $message .= '<div class="container">';
                $message .= '<div class="header"><h2>Solicitud de Cotización</h2></div>';
                $message .= '<div class="content">';
                $message .= "<p>Estimado proveedor <strong>{$proveedorNombre}</strong>,</p>";
                $message .= "<p>Por medio de la presente, <strong>{$razonSocialEsc}</strong> le solicita amablemente la cotización de los productos/servicios descritos en el documento PDF adjunto.</p>";
                $message .= '<p><strong>Detalles de la Requisición:</strong></p>';
                $message .= "<ul><li><strong>Folio:</strong> {$folio}</li><li><strong>Fecha de Solicitud:</strong> {$fecha}</li></ul>";
                $message .=
                    '<p>Agradeceríamos enormemente que nos hiciera llegar su propuesta a la brevedad posible. Si tiene alguna duda o requiere información adicional, no dude en contactarnos por los medios habituales.</p>';
                $message .= '<p>Quedamos a su disposición.</p>';
                $message .= '</div>';
                $message .= "<div class=\"footer\"><p><strong>{$razonSocialEsc}</strong></p></div>";
                $message .= '</div></body></html>';

                $option = [
                    'attachments' => [$attachmentPath],
                    'fromName' => $razonNombre,
                ];

                $mail->send_email($to, $subject, $message, $option);
            }

            $solicitudModel->update($idSolicitud, ['Estado' => Status::Cotizando]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError(
                    'Ocurrió un error en la transacción de la base de datos.',
                );
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Cotizaciones creadas y solicitud actualizada.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[crearCotizacion] ' . $e->getMessage());
            return $this->failServerError(
                'Ocurrió un error inesperado al crear las cotizaciones: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Envía una solicitud a revisión.
     * @return \CodeIgniter\HTTP\Response
     */
    public function enviarSolicitudARevision()
    {
        $request = $this->request->getPost();

        if (!isset($request['ID_Solicitud'])) {
            return $this->failValidationErrors('Se requiere ID de solicitud.');
        }

        $idSolicitud = (int) $request['ID_Solicitud'];
        $idCotizacionSeleccionada = $request['id_cotizacion_seleccionada'] ?? null;

        $solicitud = $this->api->getSolicitudById($idSolicitud);
        $cotizacionModel = new CotizacionModel();
        $tipoPago = MetodoPago::EnEspera;

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== Status::Cotizando) {
            return $this->fail(
                'La solicitud no está en estado "Cotizado".',
                HttpStatus::BAD_REQUEST,
            );
        }

        switch ($request['tipo_pago']) {
            case 'efectivo':
                $tipoPago = MetodoPago::Efectivo;
                break;
            case 'credito':
                $tipoPago = MetodoPago::Credito;
                break;
        }

        try {
            if ($idCotizacionSeleccionada) {
                $cotizacionModel
                    ->where('ID_Solicitud', $idSolicitud)
                    ->where('ID_Cotizacion !=', $idCotizacionSeleccionada)
                    ->delete();
            }

            $this->api->updateSolicitudById($idSolicitud, [
                'Estado' => 'En revision',
                'MetodoPago' => $tipoPago,
            ]);
            $files = $this->request->getFiles();
            $folder = FPath::FCOTIZACION . $solicitud['Fecha'];

            if ($files) {
                $cotizacion = $this->api->getCotizacionBySolicitudID($idSolicitud);
                $idCotizacion = $cotizacion['ID_Cotizacion'];
                $tmp = [];
                $count = 0;
                foreach ($files['cotizacion_files'] as $file) {
                    $baseFileName =
                        'cotizacion_' . $idCotizacion . '_' . $solicitud['Fecha'] . '_' . $count++;
                    $savedFileName = ImageProcessor::processAndSave($file, $folder, $baseFileName);
                    if ($savedFileName) {
                        $tmp[] = $savedFileName;
                    }
                }
                if (!empty($tmp)) {
                    $cfls['Cotizacion_Files'] = implode(',', $tmp);
                    $this->api->updateCotizacionById($idCotizacion, $cfls);
                }
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Solicitud enviada a revisión.',
            ]);
        } catch (\Exception $e) {
            log_message('error', '[enviarSolicitudARevision] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado.');
        }
    }

    /**
     * Dictamina una solicitud (aprueba o rechaza).
     * @return \CodeIgniter\HTTP\Response
     */
    public function dictaminarSolicitud()
    {
        $json = $this->request->getJSON();

        if (!isset($json->ID_Solicitud) || !isset($json->Estado)) {
            return $this->failValidationErrors('Se requiere ID de solicitud y el nuevo estado.');
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $nuevoEstado = (string) $json->Estado;
        $comentarios = $json->ComentariosAdmin ?? null;

        if (!in_array($nuevoEstado, ['Aprobada', 'Rechazada'])) {
            return $this->fail('El estado proporcionado no es válido.', HttpStatus::BAD_REQUEST);
        }
        if ($nuevoEstado === Status::Rechazada && empty(trim((string) $comentarios))) {
            return $this->fail(
                'Para rechazar una solicitud, los comentarios son obligatorios.',
                HttpStatus::BAD_REQUEST,
            );
        }

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== Status::En_Revision) {
            return $this->fail(
                'La solicitud no está en estado "En revision".',
                HttpStatus::BAD_REQUEST,
            );
        }

        try {
            $dataToUpdate = ['Estado' => $nuevoEstado, 'ComentariosAdmin' => $comentarios];
            if ($nuevoEstado === Status::Rechazada) {
                $dataToUpdate['TipoComentarioAdmin'] = 'Rechazo';
            } elseif ($nuevoEstado === Status::Aprobada && !empty(trim((string) $comentarios))) {
                $dataToUpdate['TipoComentarioAdmin'] = 'Observacion';
            }

            if ($nuevoEstado === Status::Aprobada) {
                $dataToUpdate['Fecha_Aprobacion'] = date('Y-m-d H:i:s');
                $dataToUpdate['ID_Usuario_Autoriza'] = session('id');
            }
            $solicitudModel->update($idSolicitud, $dataToUpdate);
            return $this->respondUpdated([
                'success' => true,
                'message' => 'El dictamen de la solicitud se ha guardado correctamente.',
            ]);
        } catch (\Exception $e) {
            log_message('error', '[dictaminarSolicitud] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado al guardar el dictamen.');
        }
    }

    //endregion

    //region Cotizaciones

    /**
     * Obtiene los detalles de una cotizacion específica.
     * @param int|null $id El ID de la cotizacion.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getCotizacionDetails($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de cotizacion numérico.');
        }

        $details = $this->api->getSolicitudWithCotizacion((int) $id);

        if (empty($details)) {
            return $this->failNotFound(
                'No se encontraron detalles para la cotizacion con ID: ' . $id,
            );
        }

        return $this->respond($details);
    }
    //endregion

    //region Ordenes de Compra

    /**
     * Genera una nueva Orden de Compra a partir de una solicitud aprobada.
     * @param int $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function GenerarOrden($id)
    {
        if (!is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de solicitud numérico.');
        }

        $solicitudModel = new SolicitudModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();

        $solicitud = $solicitudModel->find($id);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        if ($solicitud['Estado'] !== Status::Aprobada) {
            return $this->fail(
                'Solo se puede generar una orden de compra para solicitudes aprobadas. Estado actual: ' .
                    $solicitud['Estado'],
                HttpStatus::BAD_REQUEST,
            );
        }

        $cotizacion = $cotizacionModel->where('ID_Solicitud', $id)->first();
        if (!$cotizacion) {
            return $this->failNotFound(
                'No se encontró una cotización asociada a esta solicitud para obtener los datos del proveedor y el total.',
            );
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $ordenData = [
                'ID_Cotizacion' => $cotizacion['ID_Cotizacion'],
                'ID_Proveedor' => $cotizacion['ID_Proveedor'],
                'Estado' => Status::Por_Pagar,
                'Fecha' => date('Y-m-d'),
            ];

            $ordenCompraModel->insert($ordenData);
            $idOrdenCompra = $ordenCompraModel->getInsertID();

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', '[GenerarOrden] Falla en la transacción de base de datos.');
                return $this->failServerError(
                    'No se pudo completar la transacción para generar la orden.',
                );
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Orden de Compra generada exitosamente.',
                'id_orden_compra' => $idOrdenCompra,
            ]);
        } catch (\Exception $e) {
            log_message('error', '[GenerarOrden] ' . $e->getMessage());
            return $this->failServerError(
                'Ocurrió un error inesperado al generar la Orden de Compra.',
            );
        }
    }

    /**
     * Obtiene los datos de una orden de compra, incluyendo información de la cotización y la solicitud asociada.
     *
     * @param int|null $id El ID de la Orden de Compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenCompraData($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de orden de compra numérico.');
        }

        $data = $this->api->getOrdenCompraData((int) $id);

        if (empty($data)) {
            return $this->failNotFound(
                'No se encontraron datos para la orden de compra con ID: ' . $id,
            );
        }

        return $this->respond($data);
    }

    /**
     * Obtiene una orden de compra por el ID de la solicitud.
     *
     * @param int|null $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenBySolicitudID($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de solicitud numérico.');
        }

        $data = $this->api->getOrdenByIDSolicitud((int) $id);

        if (empty($data)) {
            return $this->failNotFound(
                'No se encontraron datos para la orden de compra con ID de solicitud: ' . $id,
            );
        }

        return $this->respond($data);
    }

    /**
     * Obtiene todas las órdenes de compra con su información asociada.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function getAllOrdenCompraData()
    {
        $data = $this->api->getAllOrdenCompraData();

        if (empty($data)) {
            return $this->failNotFound('No se encontraron órdenes de compra.');
        }

        return $this->respond($data);
    }

    /**
     * Obtiene los detalles de una orden de compra específica.
     * @param int|null $id El ID de la solicitud para la orden de compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenCompra($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de solicitud numérico.');
        }

        $details = $this->api->getOrdenCompra((int) $id);

        if (empty($details)) {
            return $this->failNotFound(
                'No se encontraron detalles para la orden de compra con ID de solicitud: ' . $id,
            );
        }

        return $this->respond($details);
    }

    public function getOrdenesCompraPendientesRecepcion()
    {
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $proveedorModel = new ProveedorModel();

        $ordenesPendientes = $ordenCompraModel
            ->whereIn('Estado', [Status::Por_Pagar, Status::En_Proceso_Pago])
            ->findAll();

        $formattedOrdenes = [];
        foreach ($ordenesPendientes as $orden) {
            $cotizacion = $cotizacionModel->find($orden['ID_Cotizacion']);
            if (!$cotizacion) {
                continue;
            }

            $solicitud = $solicitudModel->find($cotizacion['ID_Solicitud']);
            if (!$solicitud) {
                continue;
            }

            $proveedor = $proveedorModel->find($orden['ID_Proveedor']);

            $formattedOrdenes[] = [
                'ID_OrdenCompra' => $orden['ID_OrdenCompra'],
                'ID_Solicitud' => $cotizacion['ID_Solicitud'],
                'No_Folio' => $solicitud['No_Folio'],
                'ProveedorNombre' => $proveedor['RazonSocial'] ?? 'N/A',
                'Total' => $cotizacion['Total'],
                'MetodoPago' => $solicitud['MetodoPago'],
            ];
        }

        return $this->respond($formattedOrdenes, HttpStatus::OK);
    }

    /**
     * Cambia el estado de una orden de compra y opcionalmente sube un archivo de factura y/o un comprobante.
     *
     * @param int $idSolicitud El ID de la solicitud asociada a la orden de compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function cambiarEstadoOrden($idSolicitud)
    {
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();
        $solicitudModel = new SolicitudModel();

        $nuevoEstado = null;
    
        if ($this->request->is('json')) {
            $json = $this->request->getJSON();
            $nuevoEstado = $json->nuevoEstado ?? null;
        } else {
            $nuevoEstado = $this->request->getPost('nuevoEstado');
        }

        $facturaFile = $this->request->getFile('factura');
        $comprobanteFile = $this->request->getFile('ficha');

        if (empty($nuevoEstado) && !$facturaFile && !$comprobanteFile) {
            return $this->failValidationErrors(
                'No se especificó un nuevo estado ni se adjuntaron archivos.',
            );
        }

        $cot = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
        if (!$cot) {
            return $this->failNotFound('Cotización no encontrada para la solicitud.');
        }

        $orden = $ordenCompraModel->where('ID_Cotizacion', $cot['ID_Cotizacion'])->first();
        if (!$orden) {
            return $this->failNotFound('Orden no encontrada.');
        }

        $solicitud = $solicitudModel->find($cot['ID_Solicitud']);
        if (!$solicitud) {
            return $this->failNotFound('Solicitud no encontrada.');
        }

        try {
            $idCotizacion = $cot['ID_Cotizacion'];
            $idOrdenCompra = $orden['ID_OrdenCompra'];
            $idProveedor = $cot['ID_Proveedor'];
            $randomString = uniqid();

            if ($facturaFile && $facturaFile->isValid()) {
                $baseFileName = "Factura-{$idSolicitud}-{$idCotizacion}-{$idOrdenCompra}-{$idProveedor}-{$randomString}";
                $savedFile = ImageProcessor::processAndSave(
                    $facturaFile,
                    FPath::FFACTURAS,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, ['File_Factura' => $savedFile]);
                } else {
                    return $this->failServerError('No se pudo guardar el archivo de la factura.');
                }
            }

            if ($comprobanteFile && $comprobanteFile->isValid()) {
                $baseFileName = "Ficha-{$idSolicitud}-{$idCotizacion}-{$idOrdenCompra}-{$idProveedor}-{$randomString}";
                $savedFile = ImageProcessor::processAndSave(
                    $comprobanteFile,
                    FPath::FCOMPROBANTES,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, ['File_Comprobante' => $savedFile]);
                } else {
                    return $this->failServerError('No se pudo guardar el archivo del comprobante.');
                }
            }

            if (!empty($nuevoEstado)) {
                if ($nuevoEstado === 'Pagada') {
                    $ordenActualizada = $ordenCompraModel->find($idOrdenCompra);

                    $missingFiles = [];
                    if (empty($ordenActualizada['File_Factura'])) {
                        $missingFiles[] = 'Factura';
                    }
                    if (empty($ordenActualizada['File_Comprobante'])) {
                        $missingFiles[] = 'Ficha de pago';
                    }

                    if (!empty($missingFiles)) {
                        return $this->failValidationErrors(
                            'No se puede cerrar la orden de compra. Faltan los siguientes archivos: ' .
                                implode(' y ', $missingFiles) .
                                '.',
                        );
                    }

                    try {
                        $solicitudModel = new SolicitudModel();
                        $proveedorModel = new ProveedorModel();
                        $razonSocialModel = new RazonSocialModel();
    
                        $solicitud = $solicitudModel->find($idSolicitud);
                        $proveedor = $proveedorModel->find($ordenActualizada['ID_Proveedor']);
                        $razon = $razonSocialModel->find($solicitud['ID_RazonSocial']);
    
                        if (!$solicitud || !$proveedor || !$razon) {
                            throw new \Exception('Datos insuficientes para enviar la ficha de pago.');
                        }
    
                        $attachmentPath = FPath::FCOMPROBANTES . $ordenActualizada['File_Comprobante'];
    
                        if (!file_exists($attachmentPath)) {
                            throw new \Exception(
                                'El archivo de la ficha de pago no se encontró en la ruta esperada: ' .
                                    $attachmentPath,
                            );
                        }
    
                        $mail = new MBSMail();
    
                        $subject = "Ficha de Pago - Solicitud Folio {$solicitud['No_Folio']}";
    
                        $totalAPagar = '$' . number_format($cot['Total'], 2);
                        $proveedorNombre = esc($proveedor['RazonSocial'] ?? 'Proveedor');
                        $folio = esc($solicitud['No_Folio']);
                        $razonNombre = esc($razon['Nombre']);
    
                        $toProveedor = getenv('EMAIL_TO_TEST') ?: $proveedor['Correo'] ?? null;
                        if ($toProveedor) {
                            $messageProveedor = view('emails/ficha_pago', [
                                'recipientName' => $proveedorNombre,
                                'folio' => $folio,
                                'totalAPagar' => $totalAPagar,
                                'proveedorNombre' => $proveedorNombre,
                                'razonNombre' => $razonNombre,
                            ]);
                            $mail->send_email($toProveedor, $subject, $messageProveedor, [
                                'attachments' => [$attachmentPath],
                                'fromName' => $razonNombre,
                            ]);
                        } else {
                            log_message(
                                'warning',
                                'No se pudo enviar ficha de pago al proveedor (correo no disponible).',
                            );
                        }
    
                        $ccCompras = getenv('EMAIL_TO_COMPRAS');
                        if ($ccCompras) {
                            $messageCompras = view('emails/ficha_pago', [
                                'recipientName' => 'Departamento de Compras',
                                'folio' => $folio,
                                'totalAPagar' => $totalAPagar,
                                'proveedorNombre' => $proveedorNombre,
                                'razonNombre' => $razonNombre,
                            ]);
                            $mail->send_email($ccCompras, $subject, $messageCompras, [
                                'attachments' => [$attachmentPath],
                                'fromName' => $razonNombre,
                                'isHtml' => true,
                            ]);
                        } else {
                            log_message(
                                'warning',
                                'No se pudo enviar ficha de pago a Compras (correo no configurado).',
                            );
                        }
    
                        $ccTesoreria = getenv('EMAIL_TO_TESORERIA');
                        if ($ccTesoreria) {
                            $messageTesoreria = view('emails/ficha_pago', [
                                'recipientName' => 'Departamento de Tesorería',
                                'folio' => $folio,
                                'totalAPagar' => $totalAPagar,
                                'proveedorNombre' => $proveedorNombre,
                                'razonNombre' => $razonNombre,
                            ]);
                            $mail->send_email($ccTesoreria, $subject, $messageTesoreria, [
                                'attachments' => [$attachmentPath],
                                'fromName' => $razonNombre,
                                'isHtml' => true,
                            ]);
                        } else {
                            log_message(
                                'warning',
                                'No se pudo enviar ficha de pago a Tesorería (correo no configurado).',
                            );
                        }
                    } catch (\Exception $e) {
                        log_message(
                            'error',
                            '[cambiarEstadoOrden] Error al enviar email de ficha de pago: ' .
                                $e->getMessage(),
                        );
                    }

                    if ($solicitud['Tipo'] == SolicitudTipo::Servicios) {
                        $pdfGenerator = new \App\Controllers\GenerarPDF();
                        $pdfPath = $pdfGenerator->GenerarFacturaServicioPDF(
                            $solicitud['ID_Solicitud']
                        );
    
                        if (empty($pdfPath)) {
                            log_message(
                                'error',
                                'Error al generar factura de servicio para la solicitud ID: ' . $solicitud['ID_Solicitud']
                            );
                        } else {
                            $ordenCompraModel->update($idOrdenCompra, [
                                'File_FacturaServicioPDF' => basename($pdfPath),
                            ]);
                        }
                    }
                }

                $updateResult = $ordenCompraModel->update($idOrdenCompra, [
                    'Estado' => $nuevoEstado,
                ]);

                if ($updateResult === false) {
                    $errors = $ordenCompraModel->errors();
                    $errorMessage = $errors
                        ? implode(', ', $errors)
                        : 'La actualización del estado falló.';
                    throw new \Exception($errorMessage);
                }
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Operación completada exitosamente.',
                'nuevoEstado' => $nuevoEstado ?? $orden['Estado'],
            ]);
        } catch (\Exception $e) {
            log_message('error', '[cambiarEstadoOrden - Simple] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Función específica para generar la OC, enviarla por correo y cambiar su estado.
     * Llamada solo desde la vista de 'ordenes_compra'.
     */
    public function enviarOrdenAProveedor($idSolicitud = null, $userid = null)
    {
        if ($idSolicitud === null) {
            return $this->failValidationErrors('Se requiere un ID de solicitud.');
        }
        if ($userid === null) {
            return $this->failValidationErrors('Se requiere un ID.');
        }

        $solicitudModel = new SolicitudModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $proveedorModel = new ProveedorModel();
        $razonSocialModel = new RazonSocialModel();

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $solicitud = $solicitudModel->find($idSolicitud);
            if (!$solicitud) {
                throw new \Exception('Solicitud no encontrada.');
            }

            if ($solicitud['Estado'] !== Status::Aprobada) {
                throw new \Exception('Solo se puede enviar una orden desde el estado "Aprobada".');
            }

            $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
            if (!$cotizacion) {
                throw new \Exception('No se encontró una cotización asociada a esta solicitud.');
            }

            $proveedor = $proveedorModel->find($cotizacion['ID_Proveedor']);
            $razon = $razonSocialModel->find($solicitud['ID_RazonSocial']);
            $razonNombre = $razon['Nombre'];

            $ordenData = [
                'ID_Cotizacion' => $cotizacion['ID_Cotizacion'],
                'ID_Proveedor' => $cotizacion['ID_Proveedor'],
                'Estado' => Status::Espera_Programacion,
                'Fecha' => date('Y-m-d'),
            ];

            $ordenCompraModel->insert($ordenData);

            $pdf = new GenerarPDF();
            $pdfPath = $pdf->generarYGuardarOrden($idSolicitud, $userid);
            if (empty($pdfPath)) {
                throw new \Exception('No se pudo generar o guardar el PDF de la Orden de Compra.');
            }

            //Solicitud tipo 2 (servicio) no se ejecuta
            if ($solicitud['Tipo'] != 2) {

                $to = getenv('EMAIL_TO_TEST');
                if (empty($to)) {
                    if (!$proveedor || empty($proveedor['Correo'])) {
                        throw new \Exception(
                            "No se pudo encontrar un correo electrónico para el proveedor con ID: {$cotizacion['ID_Proveedor']}.",
                        );
                    }
                    $to = $proveedor['Correo'];
                }
                $proveedorNombre = esc($proveedor['RazonSocial'] ?? 'Proveedor');
                $folio = esc($solicitud['No_Folio']);

                $subject = "Nueva Orden de Compra - {$razonNombre} - Folio {$folio}";

                $message = '';
                $message .=
                    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nueva Orden de Compra</title>';
                $message .= '<style>';
                $message .=
                    'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }';
                $message .=
                    '.container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }';
                $message .=
                    '.header { padding: 15px 20px; background-color: #004a99; color: #ffffff; text-align: center; border-radius: 8px 8px 0 0; }';
                $message .= '.header h2 { margin: 0; font-size: 24px; }';
                $message .= '.content { padding: 25px 20px; }';
                $message .= '.content p { margin: 0 0 15px; }';
                $message .=
                    '.content ul { list-style: none; padding: 0; margin: 15px 0; border-left: 3px solid #004a99; padding-left: 15px; }';
                $message .= '.content li { margin-bottom: 8px; }';
                $message .=
                    '.footer { margin-top: 20px; padding: 15px 20px; font-size: 0.85em; color: #6c757d; text-align: center; background-color: #f4f4f4; border-radius: 0 0 8px 8px; }';
                $message .= '</style></head><body>';
                $message .= '<div class="container">';
                $message .= '<div class="header"><h2>Nueva Orden de Compra</h2></div>';
                $message .= '<div class="content">';
                $message .= "<p>Estimado proveedor <strong>{$proveedorNombre}</strong>,</p>";
                $message .= "<p>Por medio de la presente, <strong>{$razonNombre}</strong> se complace en enviarle la Orden de Compra correspondiente a su cotización.</p>";
                $message .= '<p><strong>Detalles de la Orden:</strong></p>';
                $message .=
                    "<ul><li><strong>Folio de Requisición:</strong> {$folio}</li><li><strong>Fecha de Orden:</strong> " .
                    date('d/m/Y') .
                    '</li></ul>';
                $message .=
                    '<p>En el documento PDF adjunto encontrará todos los detalles de los productos/servicios solicitados, así como los términos y condiciones aplicables.</p>';
                $message .=
                    '<p>Agradecemos su colaboración y quedamos a la espera de la confirmación de recibido. Para cualquier consulta, no dude en contactar a nuestro departamento de compras.</p>';
                $message .= '<p>Saludos cordiales,</p>';
                $message .= '</div>';
                $message .= "<div class=\"footer\"><p><strong>Departamento de Compras</strong><br>{$razonNombre}</p></div>";
                $message .= '</div></body></html>';

                $option = [
                    'attachments' => [$pdfPath],
                    'fromName' => $razonNombre,
                ];

                $mail = new MBSMail();
                $mail->send_email($to, $subject, $message, $option);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Orden Creada, estado actualizado y correo enviado.',
                'nuevoEstado' => Status::Espera_Programacion,
            ]);
        } catch (\Exception $e) {
            log_message('error', '[enviarOrdenAProveedor] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function enviarATesoreria()
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $data = $this->request->getJSON(true);

        if (empty($data['ID_Solicitud'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se proporcionó el ID de la solicitud.',
            ]);
        }

        $solicitudModel = new SolicitudModel();
        $id = $data['ID_Solicitud'];

        $solicitud = $solicitudModel->find($id);

        if (!$solicitud) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Solicitud no encontrada.',
            ]);
        }

        $solicitudModel->update($id, ['Estado' => 'En Proceso de Pago']);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Solicitud enviada a Tesorería con éxito.',
        ]);
    }

    //endregion

    //region Limpiar Almacenamiento

    /**
     * Obtiene el contenido de una carpeta en el almacenamiento.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getStorageList()
    {
        $path = $this->request->getGet('path') ?? '';

        try {
            $results = $this->api->getStorageContent($path);
            return $this->respond($results, HttpStatus::OK);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), HttpStatus::BAD_REQUEST);
        }
    }

    /**
     * Sirve un archivo desde el almacenamiento para previsualización.
     * Versión ultra-robusta: funciona incluso si 'fileinfo' está desactivado en PHP.
     * @return \CodeIgniter\HTTP\Response|\CodeIgniter\HTTP\DownloadResponse
     */
    public function serveFile()
    {
        $relativePath = urldecode($this->request->getGet('path') ?? '');
        $basePath = realpath(WRITEPATH . 'uploads');

        $potentialPath =
            $basePath .
            DIRECTORY_SEPARATOR .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $fullPath = realpath($potentialPath);

        if (!$fullPath || !is_file($fullPath) || strpos($fullPath, $basePath) !== 0) {
            return $this->failNotFound('Archivo no encontrado o acceso denegado.');
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $disallowedExtensions = ['html', 'htm', 'js', 'css'];

        if (in_array($ext, $disallowedExtensions)) {
            return $this->failForbidden('Acceso denegado a este tipo de archivo.');
        }

        $mime = false;

        if (function_exists('mime_content_type')) {
            $mime = @\mime_content_type($fullPath);
        }

        if (!$mime) {
            $mimeMap = [
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'txt' => 'text/plain',
                'xml' => 'application/xml',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
            ];
            $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
            ->setHeader('Content-Length', (string) filesize($fullPath))
            ->setBody(file_get_contents($fullPath));
    }
    //endregion

    /**
     * Actualiza los datos de un usuario utilizando su correo electrónico como identificador.
     * Espera un JSON con "email" y "data".
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function updateUser()
    {
        $json = $this->request->getJSON();

        if (!isset($json->email) || !is_string($json->email)) {
            return $this->failValidationErrors('Se requiere un correo electrónico válido.');
        }

        if (!isset($json->data) || !is_object($json->data)) {
            return $this->failValidationErrors(
                'Se requiere un objeto "data" con los campos a actualizar.',
            );
        }

        $email = $json->email;
        $data = (array) $json->data;

        $userModel = new UsuariosModel();
        $user = $userModel->where('Correo', $email)->first();

        if (!$user) {
            return $this->fail(
                'No se pudo actualizar el usuario. Usuario no encontrado.',
                HttpStatus::BAD_REQUEST,
            );
        }

        $dataToUpdate = [];

        if (isset($data['username'])) {
            if (empty($data['username'])) {
                return $this->failValidationErrors('El nombre de usuario no puede estar vacío.');
            }
            if ($data['username'] !== $user['Nombre']) {
                $dataToUpdate['Nombre'] = $data['username'];
            }
        }

        if (isset($data['password'])) {
            if (empty($data['password'])) {
                return $this->failValidationErrors('La nueva contraseña no puede estar vacía.');
            }
            if (strlen($data['password']) < 8) {
                return $this->failValidationErrors(
                    'La contraseña debe tener al menos 8 caracteres.',
                );
            }
            if (
                !isset($data['old_password']) ||
                !password_verify($data['old_password'], $user['ContrasenaP'])
            ) {
                return $this->fail(
                    'La contraseña anterior es incorrecta.',
                    HttpStatus::BAD_REQUEST,
                );
            }
            $dataToUpdate['ContrasenaP'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['password_g'])) {
            if (empty($data['password_g'])) {
                return $this->failValidationErrors(
                    'La nueva contraseña auxiliar no puede estar vacía.',
                );
            }
            if (strlen($data['password_g']) < 8) {
                return $this->failValidationErrors(
                    'La contraseña auxiliar debe tener al menos 8 caracteres.',
                );
            }
            if (
                !isset($data['user_password']) ||
                !password_verify($data['user_password'], $user['ContrasenaP'])
            ) {
                return $this->fail(
                    'La contraseña de usuario principal es incorrecta.',
                    HttpStatus::BAD_REQUEST,
                );
            }
            $dataToUpdate['ContrasenaG'] = password_hash($data['password_g'], PASSWORD_DEFAULT);
        }

        if (empty($dataToUpdate)) {
            return $this->respond(
                ['success' => false, 'message' => 'No hay cambios para realizar.'],
                HttpStatus::OK,
            );
        }

        $result = $this->api->updateUserByEmail($email, $dataToUpdate);

        if ($result) {
            return $this->respondUpdated([
                'success' => true,
                'message' =>
                    'Usuario actualizado correctamente. Recargar página para ver los cambios.',
            ]);
        } else {
            return $this->fail(
                'No se pudo actualizar el usuario. Verifique los datos proporcionados.',
                HttpStatus::BAD_REQUEST,
            );
        }
    }

    public function upload_signature()
    {
        $userId = session('id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $file = $this->request->getFile('signature');

        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors(
                'No se ha subido ningún archivo o el archivo no es válido.',
            );
        }

        $result = $this->api->save_signature($userId, $file);

        if ($result['success']) {
            return $this->respond(['success' => true, 'message' => $result['message']]);
        } else {
            return $this->fail($result['message'], HttpStatus::BAD_REQUEST);
        }
    }

    public function downloadAttachmentsAsZip($idSolicitud = null)
    {
        if ($idSolicitud === null || !is_numeric($idSolicitud)) {
            return $this->failValidationErrors('Se requiere un ID de solicitud numérico.');
        }

        $solicitudData = $this->api->getOrdenCompra((int) $idSolicitud);

        if (empty($solicitudData)) {
            return $this->failNotFound(
                'No se encontraron datos de la orden para la solicitud con ID: ' . $idSolicitud,
            );
        }

        $filePaths = [];
        $basePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;

        if (!empty($solicitudData['No_Folio'])) {
            $requisicionPath =
                'pdf_solicitudes' .
                DIRECTORY_SEPARATOR .
                'Requisicion-' .
                $solicitudData['No_Folio'] .
                '.pdf';
            if (file_exists($basePath . $requisicionPath)) {
                $filePaths['Requisicion-' . $solicitudData['No_Folio'] . '.pdf'] =
                    $basePath . $requisicionPath;
            }
        }

        if (
            !empty($solicitudData['cotizacion']['Cotizacion_Files']) &&
            !empty($solicitudData['Fecha'])
        ) {
            $cotFiles = explode(',', $solicitudData['cotizacion']['Cotizacion_Files']);
            foreach ($cotFiles as $file) {
                $trimmedFile = trim($file);
                if (empty($trimmedFile)) {
                    continue;
                }
                $cotizacionPath =
                    'cotizaciones' .
                    DIRECTORY_SEPARATOR .
                    $solicitudData['Fecha'] .
                    DIRECTORY_SEPARATOR .
                    $trimmedFile;
                if (file_exists($basePath . $cotizacionPath)) {
                    $filePaths[$trimmedFile] = $basePath . $cotizacionPath;
                }
            }
        }

        if (!empty($solicitudData['No_Folio'])) {
            $ordenCompraPath =
                'pdf_ordenes' .
                DIRECTORY_SEPARATOR .
                'OrdenCompra-' .
                $solicitudData['No_Folio'] .
                '.pdf';
            if (file_exists($basePath . $ordenCompraPath)) {
                $filePaths['OrdenCompra-' . $solicitudData['No_Folio'] . '.pdf'] =
                    $basePath . $ordenCompraPath;
            }
        }

        if (!empty($solicitudData['OrdenCompra']['File_Comprobante'])) {
            $comprobantePath =
                'comprobantes' .
                DIRECTORY_SEPARATOR .
                $solicitudData['OrdenCompra']['File_Comprobante'];
            if (file_exists($basePath . $comprobantePath)) {
                $filePaths[$solicitudData['OrdenCompra']['File_Comprobante']] =
                    $basePath . $comprobantePath;
            }
        }

        if (!empty($solicitudData['OrdenCompra']['File_Factura'])) {
            $facturaPath =
                'facturas' . DIRECTORY_SEPARATOR . $solicitudData['OrdenCompra']['File_Factura'];
            if (file_exists($basePath . $facturaPath)) {
                $filePaths[$solicitudData['OrdenCompra']['File_Factura']] =
                    $basePath . $facturaPath;
            }
        }

        if (empty($filePaths)) {
            return $this->failNotFound('No se encontraron archivos adjuntos para descargar.');
        }

        $zip = new \ZipArchive();
        $zipFileName = ($solicitudData['No_Folio'] ?? $idSolicitud) . '.zip';
        $tempDir = WRITEPATH . 'temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $zipTempPath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        if ($zip->open($zipTempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return $this->failServerError('No se pudo crear el archivo ZIP.');
        }

        foreach ($filePaths as $displayName => $realPath) {
            $zip->addFile($realPath, $displayName);
        }
        $zip->close();

        $zipContent = @file_get_contents($zipTempPath);
        @unlink($zipTempPath);

        if ($zipContent === false) {
            return $this->failServerError('No se pudo leer el archivo ZIP temporal para enviarlo.');
        }

        return $this->response
            ->setBody($zipContent)
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
            ->setHeader('Content-Length', (string) strlen($zipContent));
        log_message('debug', print_r($solicitud, true));
        return $solicitud ?: [];
    }

    public function exportarRequisiciones()
    {
        $solicitudModel = new SolicitudModel();
        $solicitudes = $solicitudModel
            ->select(
                'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
            )
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->where('Solicitud.Estado', 'En espera')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No. Folio');
        $sheet->setCellValue('B1', 'Usuario');
        $sheet->setCellValue('C1', 'Departamento');
        $sheet->setCellValue('D1', 'Fecha');
        $sheet->setCellValue('E1', 'Estado');

        $row = 2;
        foreach ($solicitudes as $solicitud) {
            $sheet->setCellValue('A' . $row, $solicitud['No_Folio']);
            $sheet->setCellValue('B' . $row, $solicitud['UsuarioNombre']);
            $sheet->setCellValue('C' . $row, $solicitud['DepartamentoNombre']);
            $sheet->setCellValue('D' . $row, $solicitud['Fecha']);
            $sheet->setCellValue('E' . $row, $solicitud['Estado']);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $filename = 'requisiciones_pendientes.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function exportarHistorial()
    {
        $fecha = $this->request->getGet('fecha');
        $porMes = $this->request->getGet('por_mes');
        $estado = $this->request->getGet('estado');
        $dpto = $this->request->getGet('dpto');

        $solicitudModel = new SolicitudModel();
        $builder = $solicitudModel
            ->select(
                'Solicitud.No_Folio, Solicitud.Fecha, Departamentos.Nombre as DepartamentoNombre, Solicitud.Estado',
            )
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left');

        if ($fecha) {
            if ($porMes) {
                $builder->where("to_char(Solicitud.Fecha, 'YYYY-MM')", $fecha);
            } else {
                $builder->where('Solicitud.Fecha', $fecha);
            }
        }
        if ($estado) {
            $builder->where('Solicitud.Estado', $estado);
        }
        if ($dpto) {
            $builder->where('Departamentos.Nombre', $dpto);
        }

        $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Folio');
        $sheet->setCellValue('B1', 'Fecha');
        $sheet->setCellValue('C1', 'Departamento');
        $sheet->setCellValue('D1', 'Estado');

        $row = 2;
        foreach ($solicitudes as $solicitud) {
            $sheet->setCellValue('A' . $row, $solicitud['No_Folio']);
            $sheet->setCellValue('B' . $row, $solicitud['Fecha']);
            $sheet->setCellValue('C' . $row, $solicitud['DepartamentoNombre']);
            $sheet->setCellValue('D' . $row, $solicitud['Estado']);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'historial_requisiciones.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }

    public function getAllPagos()
    {
        $pagoModel = new PagoModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $proveedorModel = new ProveedorModel();

        $pagos = $pagoModel->findAll();

        $formattedPagos = [];
        foreach ($pagos as $pago) {
            $ordenCompra = $ordenCompraModel->find($pago['ID_OrdenCompra']);
            if (!$ordenCompra) {
                continue;
            }

            $cotizacion = $cotizacionModel->find($ordenCompra['ID_Cotizacion']);
            if (!$cotizacion) {
                continue;
            }

            $solicitud = $solicitudModel->find($cotizacion['ID_Solicitud']);
            if (!$solicitud) {
                continue;
            }

            $proveedor = $proveedorModel->find($ordenCompra['ID_Proveedor']);

            $formattedPagos[] = [
                'ID_Pago' => $pago['ID_Pago'],
                'Folio' => $solicitud['No_Folio'],
                'Proveedor' => $proveedor['RazonSocial'] ?? 'N/A',
                'Total' => $cotizacion['Total'],
                'Estado' => $ordenCompra['Estado'],
            ];
        }

        return $this->respond($formattedPagos, HttpStatus::OK);
    }

    public function confirmarRecepcion()
    {
        $ordenCompraModel = new OrdenCompraModel();
        $productoModel = new ProductoModel();
        $solicitudProductModel = new SolicitudProductModel();

        $idOrdenCompra = $this->request->getPost('id_orden_compra');
        $productosRecibidosJson = $this->request->getPost('productos_recibidos');
        $remisionFile = $this->request->getFile('remision_file');

        if (!$idOrdenCompra || !$productosRecibidosJson) {
            return $this->failValidationErrors(
                'Faltan datos de la orden de compra o productos recibidos.',
            );
        }

        $productosRecibidos = json_decode($productosRecibidosJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->failValidationErrors('Formato de productos recibidos inválido.');
        }

        $ordenCompra = $ordenCompraModel->find($idOrdenCompra);
        if (!$ordenCompra) {
            return $this->failNotFound('Orden de compra no encontrada.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($remisionFile) {
                $baseFileName = 'remision_' . $ordenCompra['ID_OrdenCompra'] . '_' . uniqid();
                $savedFile = ImageProcessor::processAndSave(
                    $remisionFile,
                    FPath::FREMISIONES,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, ['File_Remision' => $savedFile]);
                } else {
                    throw new \Exception('No se pudo guardar el archivo de remisión.');
                }
            }

            $facturaEntradaFile = $this->request->getFile('factura_entrada_file');
            if ($facturaEntradaFile) {
                $baseFileName =
                    'factura_entrada_' . $ordenCompra['ID_OrdenCompra'] . '_' . uniqid();
                $savedFile = ImageProcessor::processAndSave(
                    $facturaEntradaFile,
                    FPath::FENTRADAS_FACTURAS,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, [
                        'File_FacturaEntrada' => $savedFile,
                    ]);
                } else {
                    throw new \Exception('No se pudo guardar el archivo de factura de entrada.');
                }
            }

            $totalProductosOrden = 0;
            $totalRecibidoOrden = 0;
            $todosProductosRecibidosCompletamente = true;

            $productosOriginales = $solicitudProductModel
                ->where(
                    'ID_Solicitud',
                    $this->api->getCotizacionById($ordenCompra['ID_Cotizacion'])['ID_Solicitud'],
                )
                ->findAll();
            $productosOriginalesMap = [];
            foreach ($productosOriginales as $po) {
                $productosOriginalesMap[$po['ID_SolicitudProd']] = $po;
            }

            foreach ($productosRecibidos as $prodRecibido) {
                $idSolicitudProd = $prodRecibido['id_solicitud_prod'];
                $cantidadRecibida = (int) $prodRecibido['cantidad_recibida'];
                $idProducto = $prodRecibido['id_producto'];

                $productoOriginal = $productosOriginalesMap[$idSolicitudProd] ?? null;

                if (!$productoOriginal) {
                    throw new \Exception(
                        "Producto solicitado ID {$idSolicitudProd} no encontrado.",
                    );
                }

                $cantidadPedida = (int) $productoOriginal['Cantidad'];
                $totalProductosOrden += $cantidadPedida;

                if ($idProducto) {
                    $producto = $productoModel->find($idProducto);
                    if ($producto) {
                        $productoModel->update($idProducto, [
                            'Existencia' => $producto['Existencia'] + $cantidadRecibida,
                        ]);
                    }
                }

                $solicitudProduct = $solicitudProductModel->find($idSolicitudProd);
                if ($solicitudProduct) {
                    $nuevaCantidadRecibida =
                        ($solicitudProduct['Cantidad_Recibida'] ?? 0) + $cantidadRecibida;
                    $estadoRecepcion = Status::RECEPCION_PARCIAL;
                    if ($nuevaCantidadRecibida >= $cantidadPedida) {
                        $estadoRecepcion = Status::RECEPCION_TOTAL;
                        $nuevaCantidadRecibida = $cantidadPedida;
                    } else {
                        $todosProductosRecibidosCompletamente = false;
                    }
                    $solicitudProductModel->update($idSolicitudProd, [
                        'Cantidad_Recibida' => $nuevaCantidadRecibida,
                        'Estado_Recepcion' => $estadoRecepcion,
                    ]);
                    $totalRecibidoOrden += $nuevaCantidadRecibida;
                } else {
                    $todosProductosRecibidosCompletamente = false;
                }
            }

            $nuevoEstadoOrden = Status::POR_PAGAR;
            if ($totalRecibidoOrden > 0) {
                $nuevoEstadoOrden = Status::RECEPCION_PARCIAL;
                if (
                    $todosProductosRecibidosCompletamente &&
                    $totalRecibidoOrden >= $totalProductosOrden
                ) {
                    $nuevoEstadoOrden = Status::RECIBIDA_TOTALMENTE;
                }
            }
            $ordenCompraModel->update($idOrdenCompra, ['Estado' => $nuevoEstadoOrden]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception(
                    'Falla en la transacción de base de datos al confirmar recepción.',
                );
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Recepción confirmada correctamente.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[confirmarRecepcion] ' . $e->getMessage());
            return $this->failServerError('Error al confirmar la recepción: ' . $e->getMessage());
        }
    }

    public function registrarBajaDestruccion()
    {
        $productoModel = new ProductoModel();
        $historialProductosModel = new HistorialProductosModel();

        $idProducto = $this->request->getPost('id_producto');
        $cantidadBaja = (int) $this->request->getPost('cantidad_baja');
        $motivoBaja = $this->request->getPost('motivo_baja');
        $fechaBaja = $this->request->getPost('fecha_baja');
        $idUsuario = session('id');

        if (!$idProducto || $cantidadBaja <= 0 || !$motivoBaja || !$fechaBaja || !$idUsuario) {
            return $this->failValidationErrors('Faltan datos obligatorios o son inválidos.');
        }

        $producto = $productoModel->find($idProducto);
        if (!$producto) {
            return $this->failNotFound('Producto no encontrado.');
        }

        if ($cantidadBaja > $producto['Existencia']) {
            return $this->failValidationErrors(
                'La cantidad a dar de baja excede la existencia actual del producto.',
            );
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $existenciaAnt = $producto['Existencia'];
            $nuevaExistencia = $existenciaAnt - $cantidadBaja;

            $productoModel->update($idProducto, ['Existencia' => $nuevaExistencia]);

            $historialData = [
                'ID_Producto' => $idProducto,
                'ID_Usuario' => $idUsuario,
                'CodigoAnt' => $producto['Codigo'],
                'NombreAnt' => $producto['Nombre'],
                'ExistenciaAnt' => $existenciaAnt,
                'CodigoNew' => $producto['Codigo'],
                'NombreNew' => $producto['Nombre'],
                'ExistenciaNew' => $nuevaExistencia,
                'Razon' => 'Baja por Destrucción: ' . $motivoBaja,
                'created_at' => $fechaBaja . ' ' . date('H:i:s'),
            ];
            $historialProductosModel->insert($historialData);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos al registrar baja.');
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Baja por destrucción registrada correctamente.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[registrarBajaDestruccion] ' . $e->getMessage());
            return $this->failServerError(
                'Error al registrar la baja por destrucción: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Genera un archivo XML básico para una factura de servicio.
     *
     * @param array $solicitud Los datos de la solicitud.
     * @param array $ordenCompra Los datos de la orden de compra.
     * @return string|null La ruta al archivo XML generado, o null en caso de error.
     */
    private function GenerarFacturaServicioXML(array $solicitud, array $ordenCompra): ?string
    {
        try {
            if (!is_dir(FPath::FFACTURAS_SERVICIOS)) {
                mkdir(FPath::FFACTURAS_SERVICIOS, 0777, true);
            }

            $folioFactura = $solicitud['No_Folio'] . '-SER';
            $fechaEmision = date('Y-m-d H:i:s');
            $rfcEmisor = $solicitud['ComplejoRFC'];
            $nombreEmisor = $solicitud['Complejo'];
            $rfcReceptor = $solicitud['Proveedor']['RFC'] ?? 'XAXX010101000';
            $nombreReceptor = $solicitud['Proveedor']['RazonSocial'] ?? 'Público en General';

            $importeTotal = 0;
            $serviciosXml = '';
            foreach ($solicitud['servicios'] as $servicio) {
                $importeTotal += (float) $servicio['Importe'];
                $serviciosXml .= "<Concepto>\n";
                $serviciosXml .= '  <Descripcion>' . esc($servicio['Nombre']) . "</Descripcion>\n";
                $serviciosXml .= "  <Cantidad>1</Cantidad>\n";
                $serviciosXml .= "  <Unidad>Servicio</Unidad>\n";
                $serviciosXml .=
                    '  <ValorUnitario>' .
                    number_format($servicio['Importe'], 2, '.', '') .
                    "</ValorUnitario>\n";
                $serviciosXml .=
                    '  <Importe>' .
                    number_format($servicio['Importe'], 2, '.', '') .
                    "</Importe>\n";
                $serviciosXml .= "</Concepto>\n";
            }

            $ivaMonto = $solicitud['IVA'] === 't' ? $importeTotal * 0.16 : 0;
            $granTotal = $importeTotal + $ivaMonto;

            $xmlContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xmlContent .= "<FacturaServicio>\n";
            $xmlContent .= " <Encabezado>\n";
            $xmlContent .= ' <FolioFactura>' . esc($folioFactura) . "</FolioFactura>\n";
            $xmlContent .= ' <FechaEmision>' . esc($fechaEmision) . "</FechaEmision>\n";
            $xmlContent .=
                ' <MetodoPago>' .
                esc(MetodoPago::getText($solicitud['MetodoPago'])) .
                "</MetodoPago>\n";
            $xmlContent .= " </Encabezado>\n";
            $xmlContent .= " <Emisor>\n";
            $xmlContent .= ' <RFC>' . esc($rfcEmisor) . "</RFC>\n";
            $xmlContent .= ' <Nombre>' . esc($nombreEmisor) . "</Nombre>\n";
            $xmlContent .= " </Emisor>\n";
            $xmlContent .= " <Receptor>\n";
            $xmlContent .= ' <RFC>' . esc($rfcReceptor) . "</RFC>\n";
            $xmlContent .= ' <Nombre>' . esc($nombreReceptor) . "</Nombre>\n";
            $xmlContent .= " </Receptor>\n";
            $xmlContent .= " <Conceptos>\n";
            $xmlContent .= $serviciosXml;
            $xmlContent .= " </Conceptos>\n";
            $xmlContent .= " <Totales>\n";
            $xmlContent .=
                ' <SubTotal>' . number_format($importeTotal, 2, '.', '') . "</SubTotal>\n";
            if ($solicitud['IVA'] === 't') {
                $xmlContent .= ' <IVA>' . number_format($ivaMonto, 2, '.', '') . "</IVA>\n";
            }
            $xmlContent .= ' <Total>' . number_format($granTotal, 2, '.', '') . "</Total>\n";
            $xmlContent .= " </Totales>\n";
            $xmlContent .= "</FacturaServicio>\n";

            $fileName = 'FacturaServicio-' . $solicitud['No_Folio'] . '.xml';
            $filePath = FPath::FFACTURAS_SERVICIOS . $fileName;

            file_put_contents($filePath, $xmlContent);
            return $filePath;
        } catch (\Exception $e) {
            log_message(
                'error',
                '[GenerarFacturaServicioXML] Error al generar XML de factura de servicio: ' .
                    $e->getMessage(),
            );
            return null;
        }
    }
    //endregion
    public function programarPagos()
    {
        $json = $this->request->getJSON();
        if (!isset($json->ids) || !is_array($json->ids)) {
            return $this->failValidationErrors('Se requiere un array de IDs de solicitud.');
        }

        $ids = $json->ids;
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($ids as $idSolicitud) {
                $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
                if ($cotizacion) {
                    $ordenCompraModel
                        ->where('ID_Cotizacion', $cotizacion['ID_Cotizacion'])
                        ->set(['Estado' => Status::Programada])
                        ->update();
                }
            }
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('Error en la transacción de la base de datos.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Pagos programados correctamente.',
            ]);
        } catch (\Exception $e) {
            log_message('error', '[programarPagos] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado al programar los pagos.');
        }
    }
    public function getPagosProgramados()
    {
        $ordenCompraModel = new OrdenCompraModel();

        $data = $ordenCompraModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Proveedor.RazonSocial as Proveedor',
                'Cotizacion.Total',
                'OrdenCompra.Estado',
                'Solicitud.MetodoPago',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->where('OrdenCompra.Estado', Status::Programada)
            ->groupBy([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Proveedor.RazonSocial',
                'Cotizacion.Total',
                'OrdenCompra.Estado',
                'Solicitud.MetodoPago',
                'Solicitud.Fecha',
            ])
            ->orderBy('Solicitud.Fecha', 'DESC')
            ->findAll();

        return $this->respond($data);
    }

    public function exportarPagosProgramados()
    {
        $metodoPagoFiltro = $this->request->getGet('metodo_pago');

        $ordenCompraModel = new OrdenCompraModel();

        $builder = $ordenCompraModel
            ->select([
                'Solicitud.No_Folio',
                'Proveedor.RazonSocial as Proveedor',
                'Cotizacion.Total',
                'Solicitud.MetodoPago',
                'OrdenCompra.Estado',
                'Solicitud.Fecha as FechaSolicitud',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->where('OrdenCompra.Estado', Status::Programada)
            ->orderBy('Solicitud.Fecha', 'DESC');

        if ($metodoPagoFiltro !== null && $metodoPagoFiltro !== 'todos') {
            $builder->where('Solicitud.MetodoPago', $metodoPagoFiltro);
        }

        $pagos = $builder->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No. Folio');
        $sheet->setCellValue('B1', 'Proveedor');
        $sheet->setCellValue('C1', 'Total a Pagar');
        $sheet->setCellValue('D1', 'Método de Pago');
        $sheet->setCellValue('E1', 'Estado');
        $sheet->setCellValue('F1', 'Fecha de Solicitud');

        $row = 2;
        foreach ($pagos as $pago) {
            $metodoPagoTexto = '';
            if ($pago['MetodoPago'] == '0') {
                $metodoPagoTexto = 'Contado';
            } elseif ($pago['MetodoPago'] == '1') {
                $metodoPagoTexto = 'Crédito';
            } else {
                $metodoPagoTexto = 'Desconocido';
            }

            $sheet->setCellValue('A' . $row, $pago['No_Folio']);
            $sheet->setCellValue('B' . $row, $pago['Proveedor']);
            $sheet->setCellValue('C' . $row, $pago['Total']);
            $sheet->setCellValue('D' . $row, $metodoPagoTexto);
            $sheet->setCellValue('E' . $row, $pago['Estado']);
            $sheet->setCellValue('F' . $row, $pago['FechaSolicitud']);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $filename = 'pagos_programados.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function getPagosPendientes()
    {
        $data = $this->api->getPagosPendientes();

        if (empty($data)) {
            return $this->respond(['success' => false, 'message' => 'No se encontraron pagos pendientes.']);
        }

        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function getFichasPago()
    {
        $data = $this->api->getFichasPago();

        if (empty($data)) {
            return $this->respond(['success' => false, 'message' => 'No se encontraron fichas de pago.']);
        }

        return $this->respond(['success' => true, 'data' => $data]);
    }
} //endregion
