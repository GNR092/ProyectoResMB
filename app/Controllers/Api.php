<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use CodeIgniter\RESTful\ResourceController;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;
use App\Libraries\SolicitudTipo;
use App\Libraries\Status;

class Api extends ResourceController
{
    protected $format = 'json';
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }

    //region productos
    /**
     * Busca productos por consulta y tipo.
     *
     * @return \CodeIgniter\HTTP\Response El resultado de la búsqueda en formato JSON.
     */
    public function search()
    {
        $query = $this->request->getVar('query'); // LA busqueda
        $type = $this->request->getVar('type'); // El tipo de busqueda, puede ser 'Código' o 'Producto'
        // Ejmplo de consulta: /api/product/search?query=123&type=Código
        if (empty($query)) {
            return $this->fail('La consulta no puede estar vacía.', HttpStatus::BAD_REQUEST); // Retorna un error si la consulta está vacía.
        }
        $results = $this->api->getProductsByQuery($query, $type); // Obtiene los productos de la API.

        //print_r($producto->getLastQuery());
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }

    /**
     * Obtiene todos los productos.
     *
     * @return \CodeIgniter\HTTP\Response Todos los productos en formato JSON.
     */
    public function allProducts()
    {
        $results = $this->api->getAllProducts(); // Obtiene todos los productos de la API.
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }

    /**
     * Obtiene un producto por su ID.
     *
     * @param int|null $id El ID del producto.
     * @return \CodeIgniter\HTTP\Response El producto encontrado o un error 404 si no se encuentra.
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

    //region departamentos
    /**
     * Obtiene todos los departamentos.
     *
     * @return \CodeIgniter\HTTP\Response Todos los departamentos en formato JSON.
     */
    public function getDepartments()
    {
        $results = $this->api->getAllDepartments(); // Obtiene todos los departamentos de la API.
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }
    //endregion

    //region historial
    /**
     * Obtiene todo el historial de solicitudes.
     *
     * @return \CodeIgniter\HTTP\Response El historial de solicitudes en formato JSON.
     */
    public function getHistorial()
    {
        $result = $this->api->getAllSolicitud(); // Obtiene todas las solicitudes de la API.
        return $this->respond($result, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }

    /**
     * Obtiene el historial de solicitudes por departamento.
     *
     * @param int $id El ID del departamento.
     * @return \CodeIgniter\HTTP\Response El historial de solicitudes del departamento en formato JSON.
     */
    public function getHistorialByDepartment($id)
    {
        $results = $this->api->getSolicitudByDepartment($id); // Obtiene las solicitudes por departamento de la API.
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }

    /**
     * Obtiene los detalles de una solicitud específica.
     *
     * @param int|null $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response Los detalles de la solicitud o un error si no se encuentra.
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
     * Obtiene los detalles de una cotizacion específica.
     *
     * @param int|null $id El ID de la cotizacion.
     * @return \CodeIgniter\HTTP\Response Los detalles de la cotizacion o un error si no se encuentra.
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

    /**
     * Obtiene todas las solicitudes cotizadas.
     *
     * @return \CodeIgniter\HTTP\Response Las solicitudes cotizadas en formato JSON.
     */
    public function getSolicitudesCotizadas()
    {
        $results = $this->api->getSolicitudesCotizadas(); // Obtiene las solicitudes cotizadas de la API.
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }

    /**
     * Obtiene todas las solicitudes en revisión.
     *
     * @return \CodeIgniter\HTTP\Response Las solicitudes en revisión en formato JSON.
     */
    public function getSolicitudesEnRevision()
    {
        $results = $this->api->getSolicitudesEnRevision(); // Obtiene las solicitudes en revisión de la API.
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }

    /**
     * Crea una nueva cotización para una solicitud.
     *
     * @return \CodeIgniter\HTTP\Response El resultado de la operación.
     */
    public function crearCotizacion()
    {
        $json = $this->request->getJSON();

        if (!isset($json->ID_Solicitud) || !isset($json->ID_Proveedor)) {
            return $this->failValidationErrors('Se requiere ID de solicitud y de proveedor.');
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $idProveedor = (int) $json->ID_Proveedor;

        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel(); // Instancia del modelo de Solicitud.
        $details = $this->api->getSolicitudWithProducts($idSolicitud); // Obtiene los detalles de la solicitud con sus productos.

        // Check if solicitud exists and is in correct state
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

        // Calcular total
        $total = 0;
        if ($solicitud['Tipo'] != SolicitudTipo::Servicios) {
            if (!empty($details['productos'])) {
                foreach ($details['productos'] as $p) {
                    $cantidad = (float) $p['Cantidad'];
                    $importe = (float) $p['Importe'];
                    $total += $cantidad * $importe;
                }
            }
        } else {
            if (!empty($details['productos'])) {
                foreach ($details['productos'] as $p) {
                    $importe = (float) $p['Importe'];
                    $total += $importe;
                }
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Insert into Cotizacion table
            $cotizacionData = [
                'ID_Solicitud' => $idSolicitud,
                'ID_Proveedor' => $idProveedor,
                'Total' => $total,
            ];
            $cotizacionModel->insert($cotizacionData);

            // 2. Update Solicitud status
            $solicitudModel->update($idSolicitud, ['Estado' => 'Cotizando']);

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
     *
     * @return \CodeIgniter\HTTP\Response El resultado de la operación.
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

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        if ($solicitud['Estado'] !== 'Cotizando') {
            return $this->fail(
                'La solicitud no está en estado "Cotizado".',
                HttpStatus::BAD_REQUEST,
            );
        }

        try {
            $this->api->updateSolicitudById($idSolicitud, ['Estado' => 'En revision']);
            $files = $this->request->getFiles();
            $folder = FPath::FCOTIZACION . $solicitud['Fecha'];
            $this->api->CreateFolder($folder);
            $tmp = [];
            $count = 0;
            if ($files) {
                foreach ($files as $fileGroup) {
                    foreach ($fileGroup as $file) {
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
                }
                $cfls['Cotizacion_Files'] = implode(',', $tmp);
                $this->api->updateCotizacionById($idCotizacion, $cfls);
                //return Rest::ShowDebug($cfls);
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
     *
     * @return \CodeIgniter\HTTP\Response El resultado de la operación.
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
            $dataToUpdate = [
                'Estado' => $nuevoEstado,
                'ComentariosAdmin' => $comentarios,
            ];
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

    /**
     * Obtiene las solicitudes pendientes de aprobación para el departamento del jefe actual.
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
     * Permite a un jefe de departamento aprobar o rechazar una solicitud de un empleado.
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

        // Verificaciones de seguridad
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
            $nuevoEstado = $accion === Status::Aprobar ? Status::En_espera : Status::Dept_Rechazada; //Cambiar rechazada para solo verlo en el historial del departamento
            $solicitudModel->update($idSolicitud, [
                'Estado' => $nuevoEstado,
                'ComentariosAdmin' => $comentarios,
            ]);

            return $this->respondUpdated([
                'success' => $accion === Status::Aprobar ? true : false,
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
    //endregion
    //region proveedores
    /**
     * Obtiene todos los proveedores con solo ID y Nombre.
     *
     * @return \CodeIgniter\HTTP\Response Los proveedores encontrados en formato JSON.
     */
    public function getAllProviders()
    {
        $results = $this->api->getAllProveedorName(); // Obtiene todos los proveedores de la API.
        return $this->respond($results, HttpStatus::OK); // Responde con los resultados y un estado OK.
    }
    //endregion

    //region Solicitudes
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
}