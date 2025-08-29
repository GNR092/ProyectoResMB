<?php

namespace App\Controllers;

use App\Models\SolicitudProductModel;
use App\Models\SolicitudModel;
use App\Libraries\Status;
use App\Libraries\HttpStatus;
use App\Libraries\Rest;

class Archivo extends BaseController
{
    protected $api;
    public function __construct()
    {
        $this->api = new Rest();
    }
    public function index()
    {
        return view('formulario_subida');
    }

    public function subir()
    {
        $post = $this->request->getPost();
        $codigos = $post['codigo'];
        $producto = $post['producto'];
        $cantidades = $post['cantidad'];
        $importes = $post['importe'];

        $user = $this->api->getUserById(session('id'));
        $razon = $this->api->getProveedorById($post['razon_social']);
        $fecha = $post['fecha'];

        $datosSolicitud = [
            'ID_Usuario' => $user['ID_Usuario'],
            'ID_Dpto' => $user['ID_Dpto'],
            'ID_Proveedor' => $razon['ID_Proveedor'],
            'IVA' => isset($post['iva']) ? true : false,
            'Fecha' => $fecha,
            'Estado' => Status::En_espera,
            'No_Folio' => null,
        ];

        $datosProductos = [];

        for ($i = 0; $i < count($codigos); $i++) {
            $datosProductos[] = [
                'Codigo' => $codigos[$i],
                'Nombre' => $producto[$i],
                'Cantidad' => $cantidades[$i],
                'Importe' => $importes[$i],
            ];
        }

        try {
            $solicitud = new SolicitudModel();
            $solicitud->insert($datosSolicitud);
            $solicitudId = $solicitud->insertID();
            $solicitud->update($solicitudId, [
                'No_Folio' => 'mbsp-' . $solicitudId,
            ]);

            $solicitudProduct = new SolicitudProductModel();

            foreach ($datosProductos as $solproducto) {
                $solproducto['ID_SolicitudProd'] = $solicitudId;
                $solicitudProduct->insert($solproducto);
            }

            $adjunto = $this->request->getFile('archivo');
            if ($adjunto && $adjunto->isValid()) {
                $nuevoNombre = 'solicitud_' . $solicitudId . '_' . $adjunto->getRandomName();
                $folder = WRITEPATH . 'uploads/solicitud/' . $fecha;
                $this->api->CreateFolder($folder);
                $adjunto->move($folder, $nuevoNombre);
            }
            return $this->response
                ->setStatusCode(HttpStatus::OK)
                ->setJSON([
                    'success' => true,
                    'message' => 'Solicitud registrada correctamente ✔',
                ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $e->getMessage(),
            ]);
        }
    }
}