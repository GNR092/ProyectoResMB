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
use App\Models\RazonSocialModel;

class Api extends ResourceController
{
    protected $format = 'json';
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
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
        // // 1. Validar permisos (solo admin o compras pueden modificar montos)
        // if (!in_array(session('login_type'), ['admin', 'compras'])) {
        //     return $this->failForbidden('Acceso denegado. Permiso insuficiente para modificar montos de solicitudes.');
        // }

        $json = $this->request->getJSON();

        // 2. Validar datos de entrada
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

        $solicitudModel = new SolicitudModel();
        $solicitudProductModel = new SolicitudProductModel();
        $cotizacionModel = new CotizacionModel();

        // Verificar que la solicitud exista
        $solicitud = $solicitudModel->find($idSolicitud);
        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        // Iniciar transacción
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $solicitudProductsDB = $solicitudProductModel->where('ID_Solicitud', $idSolicitud)->findAll();

            if (count($productosPayload) !== count($solicitudProductsDB)) {
                throw new \Exception('El número de productos en el payload no coincide con el número de productos existentes en la solicitud.');
            }

            // 3. Actualizar productos
            foreach ($productosPayload as $index => $p) {
                if (!isset($p->codigo) || !isset($p->nombre) || !isset($p->cantidad) || !isset($p->importe)) {
                    throw new \Exception('Cada producto debe tener código, nombre, cantidad e importe.');
                }

                $idSolicitudProd = $solicitudProductsDB[$index]['ID_SolicitudProd']; // Obtener ID_SolicitudProd de la base de datos
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

            // 4. Actualizar comentarios de la solicitud
            $solicitudModel->update($idSolicitud, ['ComentariosUser' => $comentarios]);

            // 5. Recalcular y actualizar el total de la cotización
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
                // Si no hay cotización, se podría crear una o lanzar un error.
                // Por ahora, lanzaremos un error.
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


        $json = $this->request->getJSON();
        $mail = new MBSMail();
        $to = getenv('EMAIL_TO_TEST'); //Cambiar en producción para enviar al proveedor
        $subject = 'Cotización de requisición de compra';

        if (!isset($json->ID_Solicitud) || !isset($json->ID_Proveedor)) {
            return $this->failValidationErrors('Se requiere ID de solicitud y de proveedor.');
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $idProveedor = (int) $json->ID_Proveedor;

        $details = $this->api->getSolicitudWithProducts($idSolicitud);

        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== 'En espera') {
            return $this->fail(
                'La solicitud ya no está en estado "En espera".',
                HttpStatus::BAD_REQUEST,
            );
        }

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

        $message = "
                    <p>Estimado proveedor,</p>
                    <p>Le contactamos de parte de  $razonNombre</p>
                    <p>Adjunto a este correo encontrará la requisición para su cotización.</p>
                    <p>Quedamos a la espera de su pronta respuesta.</p>
                    <br>
                    <p>Saludos cordiales,</p>
        ";

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $cotizacionData = [
                'ID_Solicitud' => $idSolicitud,
                'ID_Proveedor' => $idProveedor,
                'Total' => $total,
            ];
            $pdf = new GenerarPDF();
            $pdf->generarYGuardarRequisicion($idSolicitud);
            $option = [
                'attachments' => [FPath::FPDF . 'Requisicion-MBSP-' . $idSolicitud . '.pdf'],
                'fromName' => $razonNombre,
            ];

            $cotizacionModel->insert($cotizacionData);
            $solicitudModel->update($idSolicitud, [
                'Estado' => 'Cotizando',
                'ID_Proveedor' => $idProveedor,
            ]);
            $mail->send_email($to, $subject, $message, $option);

            $db->transComplete();

            return $this->respondCreated([
                'success' => true,
                'message' => 'Cotización creada y solicitud actualizada.',
            ]);
        } catch (\Exception $e) {
            log_message('error', '[crearCotizacion] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado al crear la cotización.');
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
        $solicitud = $this->api->getSolicitudById($idSolicitud);
        $cotizacion = $this->api->getCotizacionBySolicitudID($idSolicitud);
        $idCotizacion = $cotizacion['ID_Cotizacion'];
        $tipoPago = MetodoPago::EnEspera;

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== 'Cotizando') {
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
            $this->api->updateSolicitudById($idSolicitud, [
                'Estado' => 'En revision',
                'MetodoPago' => $tipoPago,
            ]);
            $files = $this->request->getFiles();
            $folder = FPath::FCOTIZACION . $solicitud['Fecha'];
            $this->api->CreateFolder($folder);

            if ($files) {
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
        if ($nuevoEstado === 'Rechazada' && empty(trim((string) $comentarios))) {
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
        if ($solicitud['Estado'] !== 'En revision') {
            return $this->fail(
                'La solicitud no está en estado "En revision".',
                HttpStatus::BAD_REQUEST,
            );
        }

        try {
            $dataToUpdate = ['Estado' => $nuevoEstado, 'ComentariosAdmin' => $comentarios];
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

        if ($solicitud['Estado'] !== 'Aprobada') {
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
                'Estado' => Status::En_Proceso_Pago, // Estado inicial de la orden
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
        $solicitudModel = new \App\Models\SolicitudModel();
        $json = $this->request->getJSON(true);
        $nuevoEstado = $json['nuevoEstado'] ?? null;

        if (!$nuevoEstado) {
            return $this->failValidationErrors('No se especificó el nuevo estado.');
        }

        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('Solicitud no encontrada.');
        }

        try {
            // Lógica simple: solo actualizar el estado
            $updateResult = $solicitudModel->update($idSolicitud, ['Estado' => $nuevoEstado]);

            if ($updateResult === false) {
                $errors = $solicitudModel->errors();
                $errorMessage = $errors ? implode(', ', $errors) : 'La actualización del estado falló.';
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
    public function enviarOrdenAProveedor($idSolicitud = null)
    {
        if ($idSolicitud === null) {
            return $this->failValidationErrors('Se requiere un ID de solicitud.');
        }

        // Modelos que usaremos
        $solicitudModel = new \App\Models\SolicitudModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();

        $nuevoEstadoSolicitud = 'Por Pagar';

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $solicitud = $solicitudModel->find($idSolicitud);
            if (!$solicitud) {
                throw new \Exception('Solicitud no encontrada.');
            }

            if ($solicitud['Estado'] !== 'Aprobada') {
                throw new \Exception('Solo se puede enviar una orden desde el estado "Aprobada".');
            }

            $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
            if (!$cotizacion) {
                throw new \Exception('No se encontró una cotización asociada a esta solicitud.');
            }

            $ordenData = [
                'ID_Cotizacion' => $cotizacion['ID_Cotizacion'],
                'ID_Proveedor'  => $cotizacion['ID_Proveedor'],
                'Estado'        => Status::En_Proceso_Pago, // Estado inicial de la OC
                'Fecha'         => date('Y-m-d')
            ];

            $ordenCompraModel->insert($ordenData);

            $to = getenv('EMAIL_TO_TEST');
            if (empty($to)) {
                throw new \Exception('La variable de entorno EMAIL_TO_TEST no está configurada.');
            }

            $proveedorNombre = esc($cotizacion['RazonSocial'] ?? 'Proveedor'); // Usamos la info de la cotización

            $subject = 'Nueva Orden de Compra MBSP - Folio: ' . $solicitud['No_Folio'];
            $message = '
                <p>Estimado Proveedor: ' . $proveedorNombre . '</p>
                <p>Le contactamos de parte de MBSP RENTAS S.A. DE C.V. para hacerle llegar nuestra orden de compra.</p>
                <p>Adjunto a este correo encontrará el documento PDF con los detalles.</p>
                <p>Quedamos a la espera de su confirmación.</p>
                <br>
                <p>Saludos cordiales,</p>
                <p><strong>Departamento de Compras</strong></p>
                <p>MBSP RENTAS S.A. DE C.V.</p>
            ';

            $pdf = new GenerarPDF();
            $pdfPath = $pdf->generarYGuardarOrden($idSolicitud);
            if (empty($pdfPath)) {
                throw new \Exception('No se pudo generar o guardar el PDF de la Orden de Compra.');
            }

            $option = [
                'attachments' => [$pdfPath],
                'fromName' => 'MBSP RENTAS S.A. DE C.V.'
            ];

            $updateResult = $solicitudModel->update($idSolicitud, ['Estado' => $nuevoEstadoSolicitud]);

            if ($updateResult === false) {
                $errors = $solicitudModel->errors();
                $errorMessage = $errors ? implode(', ', $errors) : 'La actualización del estado de la solicitud falló.';
                throw new \Exception($errorMessage);
            }

            $mail = new MBSMail();
            $mail->send_email($to, $subject, $message, $option);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Orden Creada, estado actualizado y correo enviado.',
                'nuevoEstado' => $nuevoEstadoSolicitud,
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

        $solicitudModel = new \App\Models\SolicitudModel();
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

}