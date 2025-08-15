<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductoModel;

class Api extends ResourceController
{
    public function search()
    {
        $query = $this->request->getVar('query');
        $type = $this->request->getVar('type');
    

        if (empty($query)) {
            return $this->fail('La consulta no puede estar vacía.', 400);
        }

        $model = new ProductoModel();
        $results = [];

        if ($type === 'Código' || $type === 'Codigo' || $type === 'codigo') {
            $results = $model->where('"Codigo" ILIKE ' . '\'%'. $query .'%\'')
            ->findAll(10);
        } elseif ($type === 'Producto' || $type === 'producto') {
            $results = $model->where('"Nombre" ILIKE ' . '\'%'. $query .'%\'')
                ->findAll(10);
        }
        $formattedResults = [];
        foreach ($results as $item) {
            $formattedResults[] = [
                'codigo' => $item['Codigo'],
                'nombre' => $item['Nombre'],
                'nombre_o_codigo' => $item['Codigo'] ?? $item['Nombre'] 
            ];
            print_r($item);
        }
        
        //print_r($model->getLastQuery());
        return $this->respond($formattedResults);
    }
}