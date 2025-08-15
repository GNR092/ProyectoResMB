<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;
use App\Models\DepartamentosModel;
use App\Models\ProductoModel;
use App\Models\DetalleModel;
use App\Models\RazonSocialModel;

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
        /*------------------ Modelos ------------------ */
        $razonsocial = new RazonSocialModel()->find($post['razon_social']);
        $usuario = new UsuarioModel()->find(session('id'));
        $solicitud = new SolicitudModel();
        $departamentos = new DepartamentosModel();
        $productos = new ProductoModel();
        $detalles = new DetalleModel();
        /*------------------ Variables ------------------*/
        $fecha = $post['fecha'];
        $departamento = $post['departamento'];
        /*------------------ Productos ------------------*/
        $codigos = $post['codigo'];
        $producto = $post['producto'];
        $cantidades = $post['cantidad'];
        $importes = $post['importe'];
        $ivas = $post['iva'] ?? null;

        $datosSolicitud = [];
        $datosProductos = [];

        $datosSolicitud = [
            'ID_Usuario' => $usuario['ID_Usuario'],
            'ID_Dpto' => $departamentos->where('Nombre', $departamento)->first()['ID_Dpto'],
            'Fecha' => $fecha,
            'Estado' => 'En espera',
            'No_Folio' => null,
        ];

        for ($i = 0; $i < count($codigos); $i++) {
            // Se puede calcular el costo aquí o en el frontend
            $costo = $cantidades[$i] * $importes[$i];

            $datosProductos[] = [
                'codigo' => $codigos[$i],
                'nombre_producto' => $producto[$i],
                'cantidad' => $cantidades[$i],
                'importe' => $importes[$i],
                'costo' => $costo,
            ];
        }

        try {
            $solicitud->insert($datosSolicitud);
            $solicitudId = $solicitud->insertID();
            $solicitud->update($solicitudId, [
                'No_Folio' => 'mbsp-' . $solicitudId,
            ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Solicitud registrada correctamente',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $e->getMessage(),
            ]);
        }
    }
}