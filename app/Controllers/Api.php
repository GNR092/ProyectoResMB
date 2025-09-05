<?php

namespace App\Controllers;

use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use CodeIgniter\RESTful\ResourceController;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;

class Api extends ResourceController
{
    protected $format = 'json';
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }

    //region productos
    public function search()
    {
        $query = $this->request->getVar('query'); // LA busqueda
        $type = $this->request->getVar('type'); // El tipo de busqueda, puede ser 'Código' o 'Producto'
        // Ejmplo de consulta: /api/product/search?query=123&type=Código
        if (empty($query)) {
            return $this->fail('La consulta no puede estar vacía.', HttpStatus::BAD_REQUEST);
        }
        $results = $this->api->getProductsByQuery($query, $type);

        //print_r($producto->getLastQuery());
        return $this->respond($results, HttpStatus::OK);
    }
    public function allProducts()
    {
        $results = $this->api->getAllProducts();
        return $this->respond($results, HttpStatus::OK);
    }
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
    public function getDepartments()
    {
        $results = $this->api->getAllDepartments();
        return $this->respond($results, HttpStatus::OK);
    }
    //endregion
    //region historial
    public function getHistorial()
    {
        $result = $this->api->getAllSolicitud();
        return $this->respond($result, HttpStatus::OK);
    }
    public function getHistorialByDepartment($id)
    {
        $results = $this->api->getSolicitudByDepartment($id);
        return $this->respond($results, HttpStatus::OK);
    }
    public function getSolicitudDetails($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationErrors('Se requiere un ID de solicitud numérico.');
        }

        $details = $this->api->getSolicitudWithProducts((int)$id);

        if (empty($details)) {
            return $this->failNotFound('No se encontraron detalles para la solicitud con ID: ' . $id);
        }

        return $this->respond($details);
    }

    public function crearCotizacion()
    {
        $json = $this->request->getJSON();

        if (!isset($json->ID_SolicitudProd) || !isset($json->ID_Proveedor)) {
            return $this->failValidationErrors('Se requiere ID de solicitud y de proveedor.');
        }

        $idSolicitud = (int) $json->ID_SolicitudProd;
        $idProveedor = (int) $json->ID_Proveedor;

        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();

        // Check if solicitud exists and is in correct state
        $solicitud = $solicitudModel->find($idSolicitud);
        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== 'En espera') {
            return $this->fail('La solicitud ya no está en estado "En espera".', HttpStatus::BAD_REQUEST);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Insert into Cotizacion table
            $cotizacionData = [
                'ID_SolicitudProd' => $idSolicitud,
                'ID_Proveedor'     => $idProveedor,
                'Total'            => 0, // Initial total, can be updated later
            ];
            $cotizacionModel->insert($cotizacionData);

            // 2. Update Solicitud status
            $solicitudModel->update($idSolicitud, ['Estado' => 'Cotizando']);

            $db->transComplete();

            return $this->respondCreated(['success' => true, 'message' => 'Cotización creada y solicitud actualizada.']);
        } catch (\Exception $e) {
            log_message('error', '[crearCotizacion] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado al crear la cotización.');
        }
    }
    //endregion

    //region proveedores
    public function getAllProviders()
    {
        $results = $this->api->getAllProveedorName();
        return $this->respond($results, HttpStatus::OK);
    }
    //endregion
}