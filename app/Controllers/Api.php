<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use App\Models\OrdenCompraModel;
use CodeIgniter\RESTful\ResourceController;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;
use App\Libraries\SolicitudTipo;
use App\Libraries\Status;
use App\Libraries\MBSMail;
use App\Libraries\MetodoPago;
use App\Controllers\GenerarPDF;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Models\UsuariosModel;

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
    // =================================================================================================================
    /**
     * Busca productos por consulta y tipo.
     * @return \CodeIgniter\HTTP\Response
     */
    public function search()
    {
        $query = $this->request->getVar('query'); // LA busqueda
        $type = $this->request->getVar('type'); // El tipo de busqueda, puede ser 'Código' o 'Producto'

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
    // =================================================================================================================
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
    // =================================================================================================================
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
    // =================================================================================================================
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
    // =================================================================================================================

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
                'Se requiere ID de solicitud y un array de productos.',
            );
        }

        $idSolicitud = (int) $json->id_solicitud;
        $productosPayload = $json->productos;
        $comentarios = $json->comentarios ?? null;

        $idCotizacionSeleccionada = $json->id_cotizacion_seleccionada ?? null;

        $solicitudModel = new SolicitudModel();
        $solicitudProductModel = new SolicitudProductModel();
        $cotizacionModel = new CotizacionModel();

        $solicitud = $solicitudModel->find($idSolicitud);
        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($idCotizacionSeleccionada) {
                $cotizacionSeleccionada = $cotizacionModel->find($idCotizacionSeleccionada);
                if ($cotizacionSeleccionada) {
                    $solicitudModel->update($idSolicitud, [
                        'ID_Proveedor' => $cotizacionSeleccionada['ID_Proveedor'],
                    ]);
                }
            }

            $solicitudProductsDB = $solicitudProductModel
                ->where('ID_Solicitud', $idSolicitud)
                ->findAll();

            if (count($productosPayload) !== count($solicitudProductsDB)) {
                throw new \Exception(
                    'El número de productos en el payload no coincide con el número de productos existentes en la solicitud.',
                );
            }

            foreach ($productosPayload as $index => $p) {
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

                $idSolicitudProd = $solicitudProductsDB[$index]['ID_SolicitudProd'];
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

            $solicitudModel->update($idSolicitud, ['ComentariosUser' => $comentarios]);

            $details = $this->api->getSolicitudWithProducts($idSolicitud);
            $nuevoTotal = 0;
            if (!empty($details['productos'])) {
                foreach ($details['productos'] as $p) {
                    $nuevoTotal += (float) $p['Cantidad'] * (float) $p['Importe'];
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
        if (!isset($json->ID_Solicitud) || !isset($json->accion)) {
            return $this->failValidationErrors(
                'Se requiere ID de solicitud y una acción (aprobar/rechazar).',
            );
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $accion = $json->accion; // 'aprobar' o 'rechazar'
        $comentarios = $json->comentarios ?? null;

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

        try {
            $nuevoEstado = $accion === Status::Aprobar ? Status::En_espera : Status::Dept_Rechazada;
            $solicitudModel->update($idSolicitud, [
                'Estado' => $nuevoEstado,
                'ComentariosAdmin' => $comentarios,
            ]);

            return $this->respondUpdated([
                'success' => $accion === Status::Aprobar,
                'message' =>
                    'La solicitud ha sido ' .
                    ($accion === Status::Aprobar
                        ? 'aprobada y enviada a Compras.'
                        : Status::Rechazada . '.'),
            ]);
        } catch (\Exception $e) {
            log_message('error', '[dictaminarSolicitudJefe] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado.');
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
            empty($json->ID_Proveedores)
        ) {
            return $this->failValidationErrors(
                'Se requiere ID de solicitud y un array de IDs de proveedor.',
            );
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $idProveedores = $json->ID_Proveedores;

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
            $this->api->CreateFolder($folder);

            if ($files) {
                $cotizacion = $this->api->getCotizacionBySolicitudID($idSolicitud);
                $idCotizacion = $cotizacion['ID_Cotizacion'];
                $tmp = [];
                $count = 0;
                foreach ($files['cotizacion_files'] as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        $timestamp = date('Y-m-d_H-i-s');
                        $extension = $file->getExtension();
                        $nuevoNombre =
                            'cotizacion_' .
                            $idCotizacion .
                            '_' .
                            $timestamp .
                            '_' .
                            $count++ .
                            '.' .
                            $extension;
                        $tmp[] = $nuevoNombre;
                        $file->move($folder, $nuevoNombre);
                    }
                }
                $cfls['Cotizacion_Files'] = implode(',', $tmp);
                $this->api->updateCotizacionById($idCotizacion, $cfls);
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
            if ($nuevoEstado === Status::Aprobada) {
                $dataToUpdate['Fecha_Aprobacion'] = date('Y-m-d H:i:s');
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
    // =================================================================================================================
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
    // =================================================================================================================
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

        // 2. Verificar estado de la solicitud
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
            // 3. Crear la Orden de Compra
            $ordenData = [
                'ID_Cotizacion' => $cotizacion['ID_Cotizacion'],
                'ID_Proveedor' => $cotizacion['ID_Proveedor'],
                'Estado' => Status::Por_Pagar, // Estado inicial de la orden
                'Fecha' => date('Y-m-d'), // Fecha de creación
            ];

            $ordenCompraModel->insert($ordenData);
            $idOrdenCompra = $ordenCompraModel->getInsertID();

            // $pdfGenerator = new \App\Controllers\GenerarPDF();
            // $pdfGenerator->ordenDeCompra($idOrdenCompra);

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

    public function cambiarEstadoOrden($idSolicitud)
    {
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();

        $nuevoEstado = $this->request->getPost('nuevoEstado');

        if (!$nuevoEstado) {
            return $this->failValidationErrors('No se especificó el nuevo estado.');
        }

        $facturaFile = $this->request->getFile('factura');

        if (!$facturaFile || !$facturaFile->isValid()) {
            $errorString = 'No se subió un archivo de factura válido.';
            if ($facturaFile && $facturaFile->getErrorString()) {
                $errorString = $facturaFile->getErrorString();
            }
            return $this->failValidationErrors($errorString);
        }

        $newName = $facturaFile->getRandomName();
        $cot = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
        $orden = $ordenCompraModel->where('ID_Cotizacion', $cot['ID_Cotizacion'])->first();

        if (!$orden) {
            return $this->failNotFound('Orden no encontrada.');
        }

        if (!$facturaFile->move(FPath::FFACTURAS, $newName)) {
            return $this->failServerError('No se pudo guardar el archivo de la factura.');
        }

        try {
            $ordenCompraModel->update($orden['ID_OrdenCompra'], ['File_Factura' => $newName]);
            // Lógica simple: solo actualizar el estado
            $updateResult = $ordenCompraModel->update($orden['ID_OrdenCompra'], [
                'Estado' => $nuevoEstado,
            ]);

            if ($updateResult === false) {
                $errors = $ordenCompraModel->errors();
                $errorMessage = $errors
                    ? implode(', ', $errors)
                    : 'La actualización del estado falló.';
                throw new \Exception($errorMessage);
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
                'nuevoEstado' => $nuevoEstado,
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
                'Estado' => Status::Por_Pagar,
                'Fecha' => date('Y-m-d'),
            ];

            $ordenCompraModel->insert($ordenData);

            // Logica para correos de proveedor
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

            $pdf = new GenerarPDF();
            $pdfPath = $pdf->generarYGuardarOrden($idSolicitud, $userid);
            if (empty($pdfPath)) {
                throw new \Exception('No se pudo generar o guardar el PDF de la Orden de Compra.');
            }

            $option = [
                'attachments' => [$pdfPath],
                'fromName' => $razonNombre,
            ];

            $mail = new MBSMail();
            $mail->send_email($to, $subject, $message, $option);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Orden Creada, estado actualizado y correo enviado.',
                'nuevoEstado' => Status::En_Proceso_Pago,
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

        // Actualiza el estado
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

        $potentialPath = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $fullPath = realpath($potentialPath);

        if (!$fullPath || !is_file($fullPath) || strpos($fullPath, $basePath) !== 0) {
            return $this->failNotFound('Archivo no encontrado o acceso denegado.');
        }

        $mime = false;
        // Opción nativa
        if (function_exists('mime_content_type')) {
            $mime = @\mime_content_type($fullPath);
        }

        // Opción manual
        if (!$mime) {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mimeMap = [
                'pdf'  => 'application/pdf',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'svg'  => 'image/svg+xml',
                'txt'  => 'text/plain',
                'html' => 'text/html',
                'htm'  => 'text/html',
                'css'  => 'text/css',
                'js'   => 'application/javascript',
                'json' => 'application/json',
                'xml'  => 'application/xml',
                'zip'  => 'application/zip',
                'rar'  => 'application/x-rar-compressed',
            ];
            $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
            ->setHeader('Content-Length', (string)filesize($fullPath))
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

        // Actualizar nombre de usuario
        if (isset($data['username'])) {
            if (empty($data['username'])) {
                return $this->failValidationErrors('El nombre de usuario no puede estar vacío.');
            }
            if ($data['username'] !== $user['Nombre']) {
                $dataToUpdate['Nombre'] = $data['username'];
            }
        }

        // Actualizar contraseña principal
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

        // Actualizar contraseña auxiliar
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

    private function _handleFileUpload(int $id, string $fileType)
    {
        $ordenCompraModel = new OrdenCompraModel();
        $orden = $ordenCompraModel->find($id);

        if (!$orden) {
            return $this->failNotFound('No se encontró la orden de compra con ID: ' . $id);
        }

        $file = $this->request->getFile($fileType === 'factura' ? 'factura_file' : 'pago_file');

        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors(
                'No se ha subido ningún archivo o el archivo no es válido.',
            );
        }

        $folder = $fileType === 'factura' ? FPath::FFACTURAS : FPath::FCOMPROBANTES;
        $this->api->CreateFolder($folder);

        $newName = $file->getRandomName();
        $file->move($folder, $newName);

        $fieldName = $fileType === 'factura' ? 'File_Factura' : 'File_Comprobante';
        $ordenCompraModel->update($id, [$fieldName => $newName]);

        return $this->respond(['success' => true, 'message' => 'Archivo subido correctamente.']);
    }

    public function uploadFactura($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de orden de compra numérico.');
        }
        return $this->_handleFileUpload((int) $id, 'factura');
    }

    public function uploadPago($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de orden de compra numérico.');
        }
        return $this->_handleFileUpload((int) $id, 'pago');
    }
}