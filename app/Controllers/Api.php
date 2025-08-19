<?php

namespace App\Controllers;

use App\Models\UsuariosModel;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductoModel;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;

class Api extends ResourceController
{
    //region productos
    public function search()
    {
        $query = $this->request->getVar('query'); // LA busqueda
        $type = $this->request->getVar('type'); // El tipo de busqueda, puede ser 'Código' o 'Producto'
        // Ejmplo de consulta: /api/search?query=123&type=Código
        if (empty($query)) {
            return $this->fail('La consulta no puede estar vacía.', HttpStatus::BAD_REQUEST);
        }

        $producto = new ProductoModel();
        $results = [];

        if ($type === 'Código' || $type === 'Codigo' || $type === 'codigo') {
            $results = $producto->where('"Codigo" ILIKE ' . '\'%' . $query . '%\'')->findAll(10);
        } elseif ($type === 'Producto' || $type === 'producto') {
            $results = $producto->where('"Nombre" ILIKE ' . '\'%' . $query . '%\'')->findAll(10);
        }
        $formattedResults = [];
        foreach ($results as $item) {
            $formattedResults[] = [
                'codigo' => $item['Codigo'],
                'nombre' => $item['Nombre'],
                'nombre_o_codigo' => $item['Codigo'] ?? $item['Nombre'],
            ];
            //print_r($item);
        }
        //print_r($producto->getLastQuery());
        return $this->respond($formattedResults, HttpStatus::OK);
    }
    //endregion
}