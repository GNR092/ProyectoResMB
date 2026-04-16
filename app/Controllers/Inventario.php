<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

// Carga de Modelos
use App\Models\IngresosModel;
use App\Models\DetalleIngresoModel;
use App\Models\ProductoModel;
use App\Models\HistorialProductosModel;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;

class Inventario extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Carga la vista principal de recepción manual
     */
    public function index()
    {
        // Si necesitas pasar datos iniciales a la vista, hazlo aquí.
        // Por ahora cargamos la vista limpia.
        return view('inventario/recepcion_manual');
    }

    /**
     * API: Devuelve lista de proveedores para el <select>
     * Ruta sugerida: api/proveedores/all
     */
    public function getProveedores()
    {
        $model = new ProveedorModel();
        // Ajusta los campos según tu tabla real (ej. ID_Proveedor, RazonSocial)
        $data = $model->select('ID_Proveedor, RazonSocial, RFC')->findAll();
        return $this->respond($data);
    }

    /**
     * API: Devuelve lista de productos para el buscador
     * Ruta sugerida: api/product/all (Si ya la tienes en otro lado, omite esto)
     */
    public function getProductos()
    {
        $model = new ProductoModel();
        $data = $model->select('ID_Producto, Codigo, Nombre, Existencia')->findAll();
        return $this->respond($data);
    }

    /**
     * PROCESO PRINCIPAL: Guardar el ingreso manual
     * Recibe JSON con cabecera y detalles.
     */
    public function guardarIngresoManual()
    {
        $json = $this->request->getJSON(true);

        if (!$json) {
            return $this->fail('No se recibieron datos JSON válidos.');
        }

        $cabeceraData = $json['cabecera'];
        $detallesData = $json['detalles'];

        // Obtener ID de usuario de la sesión (Ajusta según tu sistema de auth)
        $usuarioID = session()->get('ID_Usuario') ?? 1;

        $ingresosModel  = new IngresosModel();
        $detalleModel   = new DetalleIngresoModel();
        $productoModel  = new ProductoModel();
        $historialModel = new HistorialProductosModel();

        try {
            // 1. INICIAR TRANSACCIÓN (Todo o nada)
            $this->db->transException(true)->transStart();

            // A. Insertar en tabla INGRESOS (Cabecera)
            $nuevoIngresoId = $ingresosModel->insert([
                'ID_Proveedor'     => $cabeceraData['id_proveedor'],
                'ID_Usuario'       => $usuarioID,
                'UUID'             => $cabeceraData['uuid'],
                'RFC_Receptor'     => $cabeceraData['rfc_receptor'],
                'FechaEmision'     => $cabeceraData['fecha_emision'],
                'NombreArchivoXML' => 'Carga Manual' // Indicador de que no hubo XML físico
            ]);

            if (!$nuevoIngresoId) {
                throw new \Exception('No se pudo crear el registro de ingreso.');
            }

            // B. Procesar cada producto en DETALLEINGRESO
            foreach ($detallesData as $item) {
                $idProducto = $item['id_producto'];
                $cantidad   = $item['cantidad'];

                // Insertar detalle
                $detalleModel->insert([
                    'ID_Ingreso'        => $nuevoIngresoId,
                    'ID_Producto'       => $idProducto,
                    'CantidadOriginal'  => $cantidad,
                    'CantidadIngresada' => $cantidad // Factor 1:1 en manual
                ]);

                // C. Actualizar Stock en tabla PRODUCTO
                $productoActual = $productoModel->find($idProducto);

                if (!$productoActual) {
                    throw new \Exception("El producto con ID $idProducto no existe.");
                }

                $existenciaAnt = $productoActual['Existencia'];
                $existenciaNew = $existenciaAnt + $cantidad;

                $productoModel->update($idProducto, [
                    'Existencia' => $existenciaNew
                ]);

                // D. Registrar en HISTORIALPRODUCTOS
                $historialModel->insert([
                    'ID_Usuario'    => $usuarioID,
                    'ID_Producto'   => $idProducto,
                    'CodigoAnt'     => $productoActual['Codigo'],
                    'NombreAnt'     => $productoActual['Nombre'],
                    'ExistenciaAnt' => $existenciaAnt,
                    'CodigoNew'     => $productoActual['Codigo'],
                    'NombreNew'     => $productoActual['Nombre'],
                    'ExistenciaNew' => $existenciaNew,
                    'Razon'         => "Ingreso Manual (Ref: " . $cabeceraData['uuid'] . ")"
                ]);
            }

            // Confirmar Transacción
            $this->db->transComplete();

            return $this->respond(['success' => true, 'message' => 'Ingreso registrado correctamente.']);

        } catch (\Exception $e) {
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
            }
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'FALLO_INGRESO_INVENTARIO',
                'modulo'         => 'Almacen',
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            return $this->failServerError('Error en el proceso: ' . $e->getMessage());
        }
    }

    /**
     * API: Devuelve la lista de tus empresas/complejos (Razón Social)
     */
    public function getReceptores()
    {
        $model = new RazonSocialModel();

        // Seleccionamos 'RFC' (para guardar/validar) y 'Nombre' (para mostrar en el select)
        // Omitimos 'Ubicacion' ya que no la necesitamos en el dropdown
        $data = $model->select('RFC, Nombre')->findAll();

        return $this->respond($data);
    }

    /**
     * API: Crea un producto nuevo desde el modal de recepción
     * Ruta sugerida: post('inventario/crearProductoRapido')
     */
    public function crearProductoRapido()
    {
        $json = $this->request->getJSON(true);
        $productoModel = new \App\Models\ProductoModel();

        if (empty($json['Nombre'])) {
            return $this->fail('El nombre del producto es obligatorio.');
        }

        // 1. Generamos un código temporal aleatorio para que no choque con 'is_unique'
        //    mientras obtenemos el ID real.
        $tempCode = 'TEMP-' . uniqid();

        $data = [
            'Codigo'     => $tempCode,
            'Nombre'     => $json['Nombre'],
            'Existencia' => 0
        ];

        try {
            // 2. Insertamos para obtener el ID
            $id = $productoModel->insert($data);

            if ($id) {
                // 3. ACTUALIZACIÓN AUTOMÁTICA:
                // Ahora que tenemos el ID, actualizamos el campo Codigo para que sea igual al ID.
                // (Puedes agregarle un prefijo si gustas, ej: 'P-' . $id)
                $nuevoCodigo = (string)$id;

                $productoModel->update($id, ['Codigo' => $nuevoCodigo]);

                // 4. Devolvemos el producto con el código final
                return $this->respond([
                    'success' => true,
                    'producto' => [
                        'ID_Producto' => $id,
                        'Codigo' => $nuevoCodigo, // El código ahora es el ID
                        'Nombre' => $data['Nombre'],
                        'Existencia' => 0
                    ]
                ]);
            } else {
                return $this->fail('No se pudo guardar el producto en BD.');
            }
        } catch (\Exception $e) {
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'FALLO_CREAR_PRODUCTO_RAPIDO',
                'modulo'         => 'Almacen',
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            return $this->failServerError($e->getMessage());
        }
    }
}