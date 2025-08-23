<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;

class Api extends ResourceController
{
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
}