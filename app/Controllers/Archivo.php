<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;
use App\Models\DepartamentosModel;

class Archivo extends BaseController
{
    public function index()
    {
        return view('formulario_subida');
    }

    public function subir()
    {
        /*$archivo = $this->request->getFile('archivo');

        if (!$archivo->isValid()) {
            return redirect()->back()->with('error', 'El archivo no es válido');
        }

        // Nombre aleatorio para evitar conflictos
        $nuevoNombre = $archivo->getRandomName();

        // Mover a writable/uploads/
        if ($archivo->move(WRITEPATH . 'uploads', $nuevoNombre)) {
            return redirect()->back()->with('mensaje', 'Archivo subido correctamente como: ' . $nuevoNombre);
        } else {
            return redirect()->back()->with('error', 'No se pudo mover el archivo');
        }*/

        $post = $this->request->getPost();
        $codigos = $post['codigo'];
        $productos = $post['producto'];
        $cantidades = $post['cantidad'];
        $importes = $post['importe'];
        $usuario = new UsuarioModel()->find(session('id'));

        $datosParaInsertar = [];
        for ($i = 0; $i < count($codigos); $i++) {
            // Se puede calcular el costo aquí o en el frontend
            $costo = $cantidades[$i] * $importes[$i];

            $datosParaInsertar[] = [
                'ID_Solicitud' => 0,
                'usuario' => $usuario['Nombre'],
                'codigo' => $codigos[$i],
                'nombre_producto' => $productos[$i],
                'cantidad' => $cantidades[$i],
                'importe' => $importes[$i],
                'costo' => $costo,
            ];
        }

        $solicitud = new SolicitudModel();
        
        $departamentos = new DepartamentosModel();
        $jsondata = json_encode($datosParaInsertar);


        if (true) {
            // Pruebas
            return $this->response->setJSON([
                'success' => true,
                'message' => $jsondata,
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

       
    }
}
