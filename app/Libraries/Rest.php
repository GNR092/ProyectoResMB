<?php
namespace App\Libraries;
use App\Models\CotizacionModel;
use App\Models\CuentasModel;
use App\Models\DepartamentosModel;
use App\Models\DetalleModel;
use App\Models\OrdenCompraModel;
use App\Models\PagoModel;
use App\Models\PlacesModel;
use App\Models\ProductoModel;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\TokenModel;
use App\Models\UsuariosModel;
use App\Libraries\HttpStatus;
use App\Libraries\ImageProcessor;
use App\Libraries\SolicitudTipo;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Clase Rest
 *
 * Proporciona métodos para interactuar con la base de datos y realizar operaciones relacionadas con usuarios, productos, proveedores, departamentos y otros.
 */
class Rest
{
    protected $db;
    /**
     * Constructor de la clase Rest.
     * Inicializa la conexión a la base de datos.
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    //region Tokens
    /**
     * Genera un nuevo token API para un usuario.
     *
     * @param int $userid El ID del usuario para el cual se generará el token.
     * @return bool True si el token se generó y guardó correctamente, false en caso contrario.
     */
    public function generateUserToken(int $userid): bool
    {
        $tokenmodel = new TokenModel();
        $tokenhash = $this->generatetoken($userid);

        if (!$tokenhash) {
            return false;
        }

        $usertoken = [
            'ID_Usuario' => $userid,
            'token' => $tokenhash,
        ];

        return $tokenmodel->insert($usertoken) !== false;
    }

    /**
     * Crea un hash de token único para un usuario.
     *
     * Elimina cualquier token existente del usuario antes de crear uno nuevo.
     *
     * @param int $userid El ID del usuario.
     * @return string El hash del token generado, o una cadena vacía si el usuario no existe.
     */
    public function generatetoken(int $userid): string
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userid);

        if (!$user) {
            return '';
        }

        $tokenmodel = new TokenModel();

        $tokenmodel->where('ID_Usuario', $userid)->delete();

        do {
            $tokenhash = bin2hex(random_bytes(32));
        } while ($tokenmodel->where('token', $tokenhash)->first());

        return $tokenhash;
    }

    /**
     * Actualiza el token de un usuario.
     *
     * @param int $userid El ID del usuario.
     * @param string|null $token El nuevo token a guardar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function updateToken(int $userid, ?string $token): bool
    {
        $tokenModel = new TokenModel();
        $tokenData = $tokenModel->where('ID_Usuario', $userid)->first();

        if (!$tokenData) {
            return false;
        }

        $dataToUpdate = [
            'token' => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $tokenModel->update($tokenData['ID_Token'], $dataToUpdate);
    }
    /**
     * Borra un token API de un usuario.
     *
     * @param int $userId El ID del usuario cuyo token se eliminará.
     * @return bool True si el token se eliminó correctamente, false en caso contrario.
     */
    public function deleteToken(int $userId): bool
    {
        $tokenModel = new TokenModel();
        $tokenData = $tokenModel->where('ID_Usuario', $userId)->first();

        if (!$tokenData) {
            return false;
        }

        if ($tokenModel->delete($tokenData['ID_Token'])) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Obtiene todos los tokens de la base de datos.
     * @return array Un array con todos los tokens.
     */
    private function getTokens(): array
    {
        $tokenModel = new TokenModel();
        $tokens = $tokenModel->findall();
        return $tokens;
    }
    //endregion

    //region cotizacione
    /**
     * Obtiene una cotización por su ID.
     *
     * @param int $id El ID de la cotización.
     * @return array|null Los datos de la cotización o null si no se encuentra.
     */
    public function getCotizacionById(int $id): ?array
    {
        $cotizacionModel = new CotizacionModel();
        $result = $cotizacionModel->find($id);
        return $result ?: null;
    }

    /**
     * Obtiene una cotización por el ID de la solicitud.
     *
     * @param int $solicitudId El ID de la solicitud.
     * @return array|null La cotización encontrada o null si no se encuentra.
     */
    public function getCotizacionBySolicitudID(int $solicitudId): ?array
    {
        $cotizacionModel = new CotizacionModel();
        return $cotizacionModel->where('ID_Solicitud', $solicitudId)->first();
    }
    /**
     * Obtiene todas las cotizaciones.
     *
     * @return array Un array con todas las cotizaciones.
     */
    public function getCotizaciones(): array
    {
        $cotizacionModel = new CotizacionModel();
        $results = $cotizacionModel->findAll();
        return $results ?: [];
    }

    /**
     * Actualiza una cotización por su ID.
     *
     * @param int|null $id El ID de la cotización a actualizar.
     * @param array|null $row Los datos a actualizar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function updateCotizacionById(?int $id, ?array $row): bool
    {
        if ($id === null || $row === null) {
            return false;
        }
        $cotizacionModel = new CotizacionModel();
        return $cotizacionModel->update($id, $row);
    }
    //endregion

    //region solicitudes
    /**
     * Obtiene todas las solicitudes, excluyendo ciertos estados.
     *
     * @return array Un array de solicitudes con el nombre del departamento.
     */
    public function getAllSolicitud()
    {
        $excluded_statuses = [Status::Dept_Rechazada, Status::Aprobacion_pendiente];
        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();

        $solicitudes = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre')
            ->whereNotIn('Solicitud.Estado', $excluded_statuses)
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left') // <-- Añadir este join
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        if (empty($solicitudes)) {
            return [];
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        $cotizaciones = $cotizacionModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();

        $cotizacionIds = array_column($cotizaciones, 'ID_Cotizacion');

        $ordenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel->whereIn('ID_Cotizacion', $cotizacionIds)->findAll();
        }

        $cotizacionesMap = [];
        foreach ($cotizaciones as $cot) {
            if (!isset($cotizacionesMap[$cot['ID_Solicitud']])) {
                $cotizacionesMap[$cot['ID_Solicitud']] = $cot;
            }
        }

        $ordenesMap = [];
        foreach ($ordenes as $orden) {
            $ordenesMap[$orden['ID_Cotizacion']] = $orden;
        }

        foreach ($solicitudes as &$solicitud) {
            if ($solicitud['Estado'] === Status::Aprobada) {
                if (isset($cotizacionesMap[$solicitud['ID_Solicitud']])) {
                    $cotizacion = $cotizacionesMap[$solicitud['ID_Solicitud']];
                    if (isset($ordenesMap[$cotizacion['ID_Cotizacion']])) {
                        $orden = $ordenesMap[$cotizacion['ID_Cotizacion']];
                        if (!empty($orden['Estado'])) {
                            $solicitud['Estado'] = $orden['Estado'];
                        }
                    }
                }
            }
        }

        return $solicitudes;
    }
    /**
     * Obtiene todas las solicitudes de un departamento específico.
     *
     * @param int $id El ID del departamento.
     * @return array Un array de solicitudes para el departamento dado.
     */
    public function getSolicitudByDepartment(int $id)
    {
        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();

        $solicitudes = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->where('Solicitud.ID_Dpto', $id)
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();
        log_message('debug', print_r($solicitudes[0] ?? [], true));
        if (empty($solicitudes)) {
            return [];
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        $cotizaciones = $cotizacionModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();

        $cotizacionIds = array_column($cotizaciones, 'ID_Cotizacion');

        $ordenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel->whereIn('ID_Cotizacion', $cotizacionIds)->findAll();
        }

        $cotizacionesMap = [];
        foreach ($cotizaciones as $cot) {
            if (!isset($cotizacionesMap[$cot['ID_Solicitud']])) {
                $cotizacionesMap[$cot['ID_Solicitud']] = $cot;
            }
        }

        $ordenesMap = [];
        foreach ($ordenes as $orden) {
            $ordenesMap[$orden['ID_Cotizacion']] = $orden;
        }

        foreach ($solicitudes as &$solicitud) {
            if ($solicitud['Estado'] === Status::Aprobada) {
                if (isset($cotizacionesMap[$solicitud['ID_Solicitud']])) {
                    $cotizacion = $cotizacionesMap[$solicitud['ID_Solicitud']];
                    if (isset($ordenesMap[$cotizacion['ID_Cotizacion']])) {
                        $orden = $ordenesMap[$cotizacion['ID_Cotizacion']];
                        if (!empty($orden['Estado'])) {
                            $solicitud['Estado'] = $orden['Estado'];
                        }
                    }
                }
            }
        }

        return $solicitudes;
    }

    /**
     * Obtiene una solicitud por su ID.
     *
     * @param int $id El ID de la solicitud.
     * @return array|null La solicitud encontrada o null si no se encuentra.
     */
    public function getSolicitudById(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        return $solicitudModel->find($id) ?: null;
    }

    /**
     * Actualiza una solicitud por su ID.
     *
     * @param int|null $id El ID de la solicitud a actualizar.
     * @param array|null $row Los datos a actualizar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function updateSolicitudById(?int $id, ?array $row): bool
    {
        if ($id === null || $row === null) {
            return false;
        }
        $solicitudModel = new SolicitudModel();
        return $solicitudModel->update($id, $row);
    }

    /**
     * Obtiene una solicitud específica con todos sus productos y detalles asociados.
     *
     * Realiza un control de acceso opcional para restringir los resultados a un usuario
     * o departamento específico.
     *
     * @param int      $id         El ID de la solicitud a obtener.
     * @return array|null Un array con los datos de la solicitud y sus productos,
     *                    o null si la solicitud no se encuentra o el acceso es denegado.
     */
    public function getSolicitudWithProducts(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Proveedor.RazonSocial as RazonSocialNombre',
                'Razon_Social.Nombre as Complejo',
                'Places.Nombre_Corto as ID_Place',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }
        if (!empty($solicitud['ID_Cuenta'])) {
            $cuentasModel = new CuentasModel();
            $solicitud['cuenta_details'] = $cuentasModel->find($solicitud['ID_Cuenta']);
        }

        $solicitud['ComplejoRFC'] = $razonSocialModel->find($solicitud['ID_RazonSocial'])['RFC'];
        $productos = [];

        if (
            $solicitud['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitud['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel->where('ID_Solicitud', $id)->findAll();
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $productos = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
        }

        $solicitud['productos'] = $productos;

        $cotizacionModel = new CotizacionModel();
        $cotizaciones = $cotizacionModel
            ->select('Cotizacion.*, Proveedor.RazonSocial as ProveedorNombre')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('ID_Solicitud', $id)
            ->findAll();

        if (!empty($cotizaciones)) {
            $solicitud['cotizaciones'] = $cotizaciones;
            $solicitud['cotizacion'] = $cotizaciones[0];

            $ordenCompraModel = new OrdenCompraModel();
            $orden = $ordenCompraModel
                ->where('ID_Cotizacion', $cotizaciones[0]['ID_Cotizacion'])
                ->first();

            if ($orden) {
                $solicitud['EstadoOrden'] = $orden['Estado'];
                $solicitud['OrdenCompra'] = $orden; // <--- ¡ESTA ES LA LÍNEA MÁGICA QUE FALTA!
            }
        }

        return $solicitud ? $solicitud : [];
    }

    /**
     * Obtiene una solicitud específica con todos sus servicios y detalles asociados.
     * Es similar a getSolicitudWithProducts, pero enfocado en servicios.
     *
     * @param int      $id         El ID de la solicitud a obtener.
     * @return array|null Un array con los datos de la solicitud y sus servicios,
     *                    o null si la solicitud no se encuentra.
     */
    public function getSolicitudWithServiceDetails(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $proveedorModel = new ProveedorModel();

        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Proveedor.RazonSocial as ProveedorRazonSocial',
                'Proveedor.RFC as ProveedorRFC',
                'Proveedor.Direccion as ProveedorDireccion',
                'Proveedor.MetodoPago as ProveedorMetodoPago', // Para forma de pago
                'Razon_Social.Nombre as Complejo',
                'Solicitud.Fecha_Aprobacion', // Necesario para la fecha de la factura
                'Places.Nombre_Corto as ID_Place',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }

        $solicitud['ComplejoRFC'] = $razonSocialModel->find($solicitud['ID_RazonSocial'])['RFC'];
        $solicitud['UsuarioRazon'] = $razonSocialModel->find($solicitud['ID_RazonSocial']); // Detalles completos de la razón social del complejo

        $solicitudServicioModel = new SolicitudServiciosModel();
        $servicios = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
        $solicitud['servicios'] = $servicios;

        // Si hay un proveedor asignado a la solicitud (para el servicio)
        if (!empty($solicitud['ID_Proveedor'])) {
            $solicitud['Proveedor'] = $proveedorModel->find($solicitud['ID_Proveedor']);
        }

        return $solicitud ? $solicitud : [];
    }
    /**
     * Obtiene una solicitud específica con su cotización y detalles asociados.
     *
     * @param int $id El ID de la solicitud a obtener.
     * @return array|null Un array con los datos de la solicitud, sus productos y su cotización,
     *                    o null si la solicitud no se encuentra.
     */
    public function getSolicitudWithCotizacion(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Proveedor.RazonSocial as RazonSocialNombre',
                'Razon_Social.Nombre as Complejo',
                'Solicitud.Fecha_Aprobacion',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }
        $solicitud['ID_Place'] = $placesModel->find(
            $this->getDepartmentById($solicitud['ID_Dpto'])['ID_Place'],
        )['Nombre_Corto'];
        $solicitud['ComplejoRFC'] = $razonSocialModel->find($solicitud['ID_RazonSocial'])['RFC'];
        $productos = [];

        if (
            $solicitud['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitud['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel->where('ID_Solicitud', $id)->findAll();
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $productos = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
        }

        $solicitud['productos'] = $productos;

        $cotizacionModel = new CotizacionModel();
        $cotizacion = $cotizacionModel
            ->select(
                'Cotizacion.*, Cotizacion.Cotizacion_Files, Proveedor.RazonSocial as ProveedorNombre',
            )
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('ID_Solicitud', $id)
            ->first();

        if ($cotizacion) {
            $solicitud['cotizacion'] = $cotizacion;
        }

        return $solicitud ? $solicitud : [];
    }

    /**
     * Obtiene una orden de compra específica con todos sus detalles asociados.
     *
     * @param int $id El ID de la solicitud para la que se genera la orden de compra.
     * @return array|null Un array con los datos de la orden de compra, incluyendo el EstadoOrden,
     *                    o null si la solicitud no se encuentra.
     */
    public function getOrdenCompra(int $id): ?array
    {
        log_message('debug', 'Iniciando getOrdenCompra para Solicitud ID: ' . $id);
        $solicitudModel = new SolicitudModel();
        $razonSocialModel = new RazonSocialModel();
        $cuentasModel = new CuentasModel();
        $proveedorModel = new ProveedorModel();

        // 1. Consolidate Main Solicitud Query with all necessary joins
        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'RS.Nombre as Complejo', // Alias Razon_Social to RS
                'Solicitud.TipoComentarioAdmin',
                'OC.Estado as EstadoOrden', // Alias OrdenCompra to OC
                'UsuarioCotiza.Nombre as UsuarioCotizaNombre',
                'UsuarioAutoriza.Nombre as UsuarioAutorizaNombre',
                'Places.Nombre_Corto as ID_Place',
                // Cotizacion details
                'Cotizacion.ID_Cotizacion',
                'Cotizacion.Total as CotizacionTotal',
                'Cotizacion.ID_Proveedor as CotizacionIDProveedor',
                'Prov.RazonSocial as ProveedorNombreCotizacion', // Alias Proveedor to Prov
                // OrdenCompra details (files)
                'OC.File_Factura',
                'OC.File_Comprobante',
                'OC.File_ReqPag',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Razon_Social RS', 'RS.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left') // Use alias
            // Join Cotizacion and OrdenCompra early
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('OrdenCompra OC', 'OC.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left') // Use alias
            ->join('Proveedor Prov', 'Prov.ID_Proveedor = Cotizacion.ID_Proveedor', 'left') // Use alias for Cotizacion's Provider
            // Joins for user names (if they are different from the main Solicitud user)
            ->join(
                'Usuarios UsuarioCotiza',
                'UsuarioCotiza.ID_Usuario = Cotizacion.ID_Usuario_Cotiza',
                'left'
            )
            ->join(
                'Usuarios UsuarioAutoriza',
                'UsuarioAutoriza.ID_Usuario = Solicitud.ID_Usuario_Autoriza',
                'left'
            )
            ->find($id);

        if (!$solicitud) {
            log_message('debug', 'Solicitud no encontrada para ID: ' . $id);
            return null;
        }

        // Initialize structured data for return
        $solicitudData = $solicitud; // Use the fetched array directly

        // Handle additional details from separate models
        if (!empty($solicitudData['ID_Cuenta'])) {
            $solicitudData['cuenta_details'] = $cuentasModel->find($solicitudData['ID_Cuenta']);
        }

        // Fetch supplier details only if not already joined effectively
        if (!empty($solicitudData['ID_Proveedor'])) {
            $proveedor = $proveedorModel->find($solicitudData['ID_Proveedor']);
            if ($proveedor) {
                unset($proveedor['Correo']);
                unset($proveedor['Tel_Contacto']);
            }
            $solicitudData['proveedor'] = $proveedor;
        }

        $solicitudData['ComplejoRFC'] = $razonSocialModel->find($solicitudData['ID_RazonSocial'])['RFC'];

        // Fetch products or services
        $productos = [];
        if (
            $solicitudData['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitudData['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel->where('ID_Solicitud', $id)->findAll();
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $productos = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
        }
        $solicitudData['productos'] = $productos;

        // Structure Cotizacion data
        if (!empty($solicitudData['ID_Cotizacion'])) {
            $solicitudData['cotizacion'] = [
                'ID_Cotizacion' => $solicitudData['ID_Cotizacion'],
                'Total' => $solicitudData['CotizacionTotal'],
                'ID_Proveedor' => $solicitudData['CotizacionIDProveedor'],
                'ProveedorNombre' => $solicitudData['ProveedorNombreCotizacion'],
                // Add any other Cotizacion fields you need that are not directly on Solicitud
            ];
            // Remove redundant fields from top level if they are now nested
            unset($solicitudData['CotizacionTotal']);
            unset($solicitudData['CotizacionIDProveedor']);
            unset($solicitudData['ProveedorNombreCotizacion']);
            // If EstadoOrden is primarily from OrdenCompra, it should be within OrdenCompra details or renamed at top level
        }

        // Structure OrdenCompra data
        if (!empty($solicitudData['File_Factura']) || !empty($solicitudData['File_Comprobante']) || !empty($solicitudData['File_ReqPag'])) {
            $solicitudData['OrdenCompra'] = [
                'File_Factura' => $solicitudData['File_Factura'],
                'File_Comprobante' => $solicitudData['File_Comprobante'],
                'File_ReqPag' => $solicitudData['File_ReqPag'],
                'Estado' => $solicitudData['EstadoOrden'] ?? null, // Ensure Estado is present if needed
            ];
            // Remove redundant fields from top level if they are now nested
            unset($solicitudData['File_Factura']);
            unset($solicitudData['File_Comprobante']);
            unset($solicitudData['File_ReqPag']);
            // unset($solicitudData['EstadoOrden']);
        }


        log_message('debug', 'Finalizando getOrdenCompra con éxito para Solicitud ID: ' . $id);
        // The original method returns $solicitud ?: [] at the end, so I'll follow that convention.
        return $solicitudData ?: [];
    }

    /**
     * Obtiene los datos de una orden de compra, incluyendo información de la cotización y la solicitud asociada.
     *
     * @param int $id El ID de la Orden de Compra.
     * @return array|null Un array con los datos de la orden de compra o null si no se encuentra.
     */
    public function getOrdenCompraData(int $id): ?array
    {
        $ordenCompraModel = new OrdenCompraModel();

        $result = $ordenCompraModel
            ->select(
                'OrdenCompra.ID_OrdenCompra, Cotizacion.ID_Cotizacion, Solicitud.ID_Solicitud, Solicitud.MetodoPago, OrdenCompra.Estado as EstadoOrden',
            )
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud')
            ->where('OrdenCompra.ID_OrdenCompra', $id)
            ->first();

        return $result ?: null;
    }

    /**
     * Obtiene una orden de compra por el ID de la solicitud.
     *
     * @param int $solicitudId El ID de la solicitud.
     * @return array|null La orden de compra encontrada o null si no existe.
     */
    public function getOrdenByIDSolicitud(int $solicitudId): ?array
    {
        $cotizacionModel = new CotizacionModel();
        $cotizacion = $cotizacionModel->where('ID_Solicitud', $solicitudId)->first();

        if (!$cotizacion) {
            return null;
        }

        $ordenCompraModel = new OrdenCompraModel();
        $orden = $ordenCompraModel->where('ID_Cotizacion', $cotizacion['ID_Cotizacion'])->first();

        return $orden ?: null;
    }

    /**
     * Obtiene todas las órdenes de compra con su información asociada.
     *
     * @return array Un array con todas las órdenes de compra.
     */
    public function getAllOrdenCompraData(): array
    {
        $ordenCompraModel = new OrdenCompraModel();

        $results = $ordenCompraModel
            ->select(
                'OrdenCompra.ID_OrdenCompra, Cotizacion.ID_Cotizacion, Solicitud.ID_Solicitud, Solicitud.MetodoPago, OrdenCompra.Estado as EstadoOrden',
            )
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud')
            ->findAll();

        return $results ?: [];
    }

    /**
     * Obtiene solicitudes filtrando por estado y departamento, opcionalmente excluyendo a un usuario.
     *
     * @param string $status El estado de la solicitud a buscar.
     * @param int $departmentId El ID del departamento.
     * @param int|null $excludeUserId El ID del usuario a excluir de los resultados (ej. el jefe).
     * @return array Un array con las solicitudes encontradas.
     */
    public function getSolicitudesByStatusAndDept(
        string $status,
        int $departmentId,
        ?int $excludeUserId = null,
    ): array {
        $solicitudModel = new SolicitudModel();
        $builder = $solicitudModel
            ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario')
            ->where('Solicitud.Estado', $status)
            ->where('Solicitud.ID_Dpto', $departmentId);

        if ($excludeUserId !== null) {
            $builder->where('Solicitud.ID_Usuario !=', $excludeUserId);
        }

        return $builder->orderBy('Solicitud.Fecha', 'DESC')->findAll();
    }

    //endregion

    //region Solicitudes Cotizadas
    /**
     * Obtiene un resumen de las solicitudes que ya han sido cotizadas.
     *
     * @return array Un array con los datos formateados de las solicitudes cotizadas.
     */
    public function getSolicitudesCotizadas()
    {
        $solicitudModel = new SolicitudModel();

        $solicitudes = $solicitudModel
            ->select(
                'Solicitud.ID_Solicitud as ID, Solicitud.No_Folio as Folio, Solicitud.Estado, Solicitud.IVA,
                Usuarios.Nombre as Usuario, Departamentos.Nombre as Departamento,
                Proveedor.RazonSocial as Proveedor, Cotizacion.Total as Monto, Cotizacion.ID_Cotizacion'
            )
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('Solicitud.Estado', Status::Cotizando)
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        $result = [];
        foreach ($solicitudes as $solicitud) {
            $montoFinal = (float) $solicitud['Monto'];
            if ($solicitud['IVA'] === true) {
                $montoFinal *= 1.16;
            }

            $result[] = [
                'ID' => $solicitud['ID'],
                'ID_Solicitud' => $solicitud['ID'],
                'Folio' => $solicitud['Folio'],
                'Usuario' => $solicitud['Usuario'],
                'Departamento' => $solicitud['Departamento'],
                'Proveedor' => $solicitud['Proveedor'],
                'Monto' => $montoFinal,
                'Estado' => $solicitud['Estado'],
                'ID_Cotizacion' => $solicitud['ID_Cotizacion'],
            ];
        }

        return $result;
    }

    /**
     * Obtiene las solicitudes de un departamento que están pendientes de aprobación.
     *
     * @param int $departmentId El ID del departamento.
     * @return array Un array de solicitudes pendientes.
     */
    public function getSolicitudesUsersByDepartment(int $departmentId)
    {
        $solicitudModel = new SolicitudModel();
        $results = $solicitudModel
            ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario')
            ->where('Solicitud.ID_Dpto', $departmentId)
            ->where('Solicitud.Estado', Status::Aprobacion_pendiente)

            //->where('Solicitud.ID_Usuario !=', session('id'))
            ->orderBy('Solicitud.Fecha', 'DESC')
            ->findAll();
        return $results ?: [];
    }

    /**
     * Obtiene un resumen de las solicitudes que se encuentran "En revisión".
     *
     * @return array Un array con los datos formateados de las solicitudes en revisión.
     */
    public function getSolicitudesEnRevision()
    {
        $solicitudModel = new SolicitudModel();

        $results = $solicitudModel
            ->select([
                'Solicitud.ID_Solicitud as ID',
                'Solicitud.No_Folio as Folio',
                'Usuarios.Nombre as Usuario',
                'Departamentos.Nombre as Departamento',
                'Proveedor.RazonSocial as Proveedor',
                'Cotizacion.Total as Monto',
                'Solicitud.Estado',
                'Solicitud.Fecha',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('Solicitud.Estado', 'En revision')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $results ?: [];
    }

    /**
     * Obtiene las solicitudes aprobadas que no tienen una orden de compra asociada.
     *
     * @return array Un array de solicitudes.
     */
    public function getSolicitudesSinOrdenPago(): array
    {
        $solicitudModel = new SolicitudModel();

        $results = $solicitudModel
            ->select(
                'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
            )
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('OrdenCompra', 'OrdenCompra.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left')
            ->where('Solicitud.Estado', 'Aprobada')
            ->where('OrdenCompra.ID_OrdenCompra IS NULL')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $results ?: [];
    }

    //endregion

    //region Usuarios
    /**
     * Obtiene un usuario por su ID, opcionalmente con detalles de departamento y ubicación.
     *
     * @param int $id El ID del usuario.
     * @param bool $withDetails Si es true, incluye el nombre del departamento y de la ubicación.
     * @return array|null El usuario encontrado o null si no se encuentra.
     */
    public function getUserById(int $id, bool $withDetails = false): ?array
    {
        $usuariosModel = new UsuariosModel();
        $usuarios = [];

        if ($withDetails) {
            $usuarios = $usuariosModel
                ->select(
                    'Usuarios.*, Departamentos.Nombre as departamento_nombre, Places.Nombre_Corto as place_nombre',
                )
                ->join('Departamentos', 'Departamentos.ID_Dpto = Usuarios.ID_Dpto', 'left')
                ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                ->find($id);
            return $usuarios ? $usuarios : [];
        } else {
            return $usuariosModel->find($id) ?: [];
        }
    }
    public function getSignB64ByUserID(int $id): ?string
    {
        $user = $this->getUserById($id);
        $SignaturePath = null;
        $userFolder = FPath::FUSER . $id;

        if (!$user) {
            return null;
        }
        if (!empty($user['Firma_digital'])) {
            $SignaturePath = $userFolder . DIRECTORY_SEPARATOR . $user['Firma_digital'];
            if (is_readable($SignaturePath)) {
                $signdata = file_get_contents($SignaturePath);
                $sign64 = base64_encode($signdata);
                $signtype = pathinfo($SignaturePath, PATHINFO_EXTENSION);
                $signb64 = 'data:image/' . $signtype . ';base64,' . $sign64;
                return $signb64;
            }
        }
        return null;
    }
    public function getSignByUserID(int $id)
    {
        $user = $this->getUserById($id);
        $SignaturePath = null;
        $userFolder = FPath::FUSER . $id;

        if (!$user) {
            return null;
        }
        if (!empty($user['Firma_digital'])) {
            $SignaturePath = $userFolder . DIRECTORY_SEPARATOR . $user['Firma_digital'];
            $signdata = file_get_contents($SignaturePath);
            return $signdata;
        }
        return null;
    }
    /**
     * Obtiene un usuario por su nombre.
     *
     * @param string $name El nombre del usuario.
     * @return array|null El usuario encontrado o null si no se encuentra.
     */
    public function getUserByName(string $name): ?array
    {
        $usuariosModel = new UsuariosModel();
        $result = $usuariosModel->where('Nombre', $name)->first();
        return $result ?: null;
    }
    /**
     * Obtiene un usuario por su correo electrónico.
     *
     * @param string $email El correo electrónico del usuario.
     * @return array|null El usuario encontrado o null si no se encuentra.
     */
    public function getUserByEmail(string $email): ?array
    {
        $usuariosModel = new UsuariosModel();
        $result = $usuariosModel->where('Correo', $email)->first();
        return $result ?: null;
    }
    /**
     * Obtiene todos los usuarios de un departamento específico.
     *
     * @param int $departmentId El ID del departamento.
     * @return array Los usuarios encontrados.
     */
    public function getUsersByDepartament(int $departmentId): array
    {
        $usuariosModel = new UsuariosModel();
        $results = $usuariosModel->where('ID_Dpto', $departmentId)->findAll();
        return $results ?: [];
    }
    /**
     * Obtiene todos los usuarios con detalles de su departamento y ubicación.
     *
     * @return array Los usuarios encontrados.
     */
    public function getAllUsers(): array
    {
        $usuariosModel = new UsuariosModel();
        $results = $usuariosModel
            ->select(
                'Usuarios.*, Departamentos.Nombre as departamento_nombre, Places.Nombre_Corto as place_nombre',
            )
            ->join('Departamentos', 'Departamentos.ID_Dpto = Usuarios.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->orderBy('Usuarios.Nombre', 'ASC')
            ->findAll();
        return $results ?: [];
    }
    /**
     * Agrega un nuevo usuario a la base de datos.
     *
     * @param array $data Los datos del usuario a agregar.
     * @return bool True si el usuario se agregó correctamente, false en caso contrario.
     */
    public function addUser(array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->insert($data) !== false;
    }
    /**
     * Actualiza un usuario existente por su ID.
     *
     * @param int $id El ID del usuario a actualizar.
     * @param array $data Los nuevos datos para el usuario.
     * @return bool True si el usuario se actualizó correctamente, false en caso contrario.
     */
    public function updateUser(int $id, array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->update($id, $data);
    }

    /**
     * Actualiza un usuario existente por su correo electrónico.
     *
     * @param string $email El correo electrónico del usuario a actualizar.
     * @param array $data Los nuevos datos para el usuario.
     * @return bool True si el usuario se actualizó correctamente, false en caso contrario.
     */
    public function updateUserByEmail(string $email, array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        $user = $this->getUserByEmail($email);
        if (!$user) {
            return false;
        }
        return $usuariosModel->update($user['ID_Usuario'], $data);
    }
    /**
     * Elimina un usuario por su ID.
     *
     * @param int $id El ID del usuario a eliminar.
     * @return bool True si el usuario se eliminó correctamente, false en caso contrario.
     */
    public function deleteUser(int $id): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->delete($id);
    }

    /**
     * Guarda la firma digital de un usuario.
     *
     * @param int $userId El ID del usuario.
     * @param object $file El archivo de la firma a guardar.
     * @return array Un array con el resultado de la operación.
     */
    public function save_signature(int $userId, object $file): array
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }

        $userFolder = FPath::FUSER . $userId;

        if (!empty($user['Firma_digital'])) {
            $oldSignaturePath = $userFolder . DIRECTORY_SEPARATOR . $user['Firma_digital'];
            if (file_exists($oldSignaturePath)) {
                unlink($oldSignaturePath);
            }
        }

        $baseFileName = 'signature_' . $userId . '_' . uniqid();
        $fileName = ImageProcessor::processAndSave($file, $userFolder, $baseFileName);

        if (!$fileName) {
            return ['success' => false, 'message' => 'No se pudo guardar la firma.'];
        }

        $data = ['Firma_digital' => $fileName];
        if ($usuariosModel->update($userId, $data)) {
            return ['success' => true, 'message' => 'Firma guardada correctamente.'];
        } else {
            return ['success' => false, 'message' => 'No se pudo actualizar la base de datos.'];
        }
    }
    //endregion

    //region productos
    /**
     * Obtiene productos buscando por código o por nombre.
     *
     * @param string $query La cadena de búsqueda.
     * @param string $type El tipo de búsqueda ('Código' o 'Producto').
     * @return array Los productos encontrados.
     */
    public function getProductsByQuery(string $query, string $type): array
    {
        $results = [];
        if ($type === 'Código' || $type === 'Codigo' || $type === 'codigo') {
            $results = $this->getProductsByCode($query, 10);
        } elseif ($type === 'Producto' || $type === 'producto') {
            $results = $this->getProductsByName($query, 10);
        }
        return $results;
    }
    /**
     * Obtiene un producto por su ID.
     *
     * @param int $id El ID del producto.
     * @return array|null El producto encontrado o null si no se encuentra.
     */
    public function getProductById(int $id): ?array
    {
        $producto = new ProductoModel();
        $result = $producto->find($id);
        return $result ?: null;
    }
    /**
     * Obtiene productos que coinciden con un código.
     *
     * @param string $code El código a buscar.
     * @param int $limit El número máximo de resultados a devolver.
     * @return array Los productos encontrados.
     */
    public function getProductsByCode(string $code, int $limit = 0): array
    {
        $producto = new ProductoModel();
        $results = $producto->like('Codigo', $code, 'both', null, true)->findAll($limit);
        return $results;
    }
    /**
     * Obtiene productos por nombre (búsqueda insensible a mayúsculas/minúsculas).
     *
     * @param string $name El nombre del producto a buscar.
     * @param int $limit El número máximo de resultados a devolver.
     * @return array Los productos encontrados.
     */
    public function getProductsByName(string $name, int $limit = 0): array
    {
        $builder = $this->db->table('Producto');
        $builder->like('Nombre', $name, 'after', null, true); // true for case-insensitive

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }
    /**
     * Registra un nuevo producto a partir de un array de datos.
     *
     * @param array $data Los datos del producto a registrar.
     * @return bool True si el producto se registró correctamente, false en caso contrario.
     */
    public function registrarProductoArray(array $data): bool
    {
        $producto = new ProductoModel();
        return $producto->insert($data) !== false;
    }
    /**
     * Registra un nuevo producto con sus propiedades individuales.
     *
     * @param string $codigo El código del producto.
     * @param string $nombre El nombre del producto.
     * @param int $existencia La cantidad de existencia inicial.
     * @return bool True si el producto se registró correctamente, false en caso contrario.
     */
    public function registrarProducto($codigo, $nombre, $existencia): bool
    {
        $producto = new ProductoModel();
        $data = [
            'Codigo' => $codigo,
            'Nombre' => $nombre,
            'Existencia' => $existencia,
        ];
        return $producto->insert($data) !== false;
    }

    /**
     *  Elimina un producto por su ID.
     * @param int $id El ID del producto a eliminar.
     * @return bool True si el producto se eliminó correctamente, false en caso contrario.
     */
    public function eliminarProductoById(int $id): bool
    {
        $producto = new ProductoModel();
        return $producto->delete($id);
    }
    /**
     * Actualiza un producto existente por su ID.
     *
     * @param int $id El ID del producto a actualizar.
     * @param array $data Los nuevos datos para el producto.
     * @return bool True si el producto se actualizó correctamente, false en caso contrario.
     */
    public function actualizarProducto(int $id, array $data): bool
    {
        $producto = new ProductoModel();
        return $producto->update($id, $data);
    }
    /**
     * Obtiene todos los productos de la base de datos.
     *
     * @return array Los productos encontrados.
     */
    public function getAllProducts(): array
    {
        $producto = new ProductoModel();
        $results = $producto->findAll();
        if (empty($results)) {
            return [];
        }
        return $results;
    }
    //endregion

    //region Proveedor

    /**
     * Obtiene un proveedor por su ID sin incluir datos de contacto sensibles.
     *
     * @param int $id El ID del proveedor.
     * @return array|null El proveedor encontrado o null si no se encuentra.
     */
    public function getProveedorByID(int $id): ?array
    {
        $proveedorModel = new ProveedorModel();
        $proveedor = $proveedorModel->find($id);

        if ($proveedor) {
            unset($proveedor['Correo']);
            unset($proveedor['Tel_Contacto']);
        }

        return $proveedor ?: [];
    }
    /**
     * Obtiene todos los proveedores.
     *
     * @return array Los proveedores encontrados.
     */
    public function getAllProveedores(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->findAll();
        return $results ?: [];
    }
    /**
     * Obtiene el ID y Nombre de todos los proveedores.
     *
     * @return array Un array de proveedores con solo su ID y Razón Social.
     */
    public function getAllProveedorName(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->findAll();
        return $results;
    }

    /**
     * Obtiene el ID y Razón Social de todos los proveedores.
     *
     * @return array Un array de proveedores con solo su ID y Razón Social.
     */
    public function getProveedorIdAndRazonSocial(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel
            ->select('ID_Proveedor, RazonSocial, Tel_Contacto, RFC')
            ->orderBy('RazonSocial', 'ASC')
            ->findAll();
        return $results;
    }
    //endregion

    //region departamentos
    /**
     * Obtiene todos los departamentos con el nombre corto de su ubicación.
     *
     * @return array Los departamentos encontrados.
     */
    public function getAllDepartments(): array
    {
        $departamentosModel = new DepartamentosModel();
        $results = $departamentosModel
            ->select(
                'Departamentos.ID_Dpto, Departamentos.Nombre, Departamentos.ID_Place, Places.Nombre_Corto as Place',
            )
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->findAll();

        if (empty($results)) {
            return [];
        }

        return $results;
    }
    public function getDepartmentById(int $id): ?array
    {
        $departamentosModel = new DepartamentosModel();
        $result = $departamentosModel->find($id);
        return $result ?: null;
    }
    /**
     * Obtiene el nombre de una ubicación (Place) por su ID.
     *
     * @param int $id El ID de la ubicación.
     * @param bool $long Si es true, devuelve el nombre completo; de lo contrario, el nombre corto.
     * @return string|null El nombre de la ubicación o null si no se encuentra.
     */
    public function getPlaceById(int $id, bool $long = false): ?string
    {
        $places = new PlacesModel();
        $result = $places->find($id);
        if ($result) {
            return $long ? $result['Nombre_Completo'] : $result['Nombre_Corto'];
        }

        return null;
    }
    //endregion

    //region Razón social
    public function getRazonSocialByID(int $id): ?array
    {
        $razonSocialModel = new RazonSocialModel();
        return $razonSocialModel->find($id) ?: null;
    }

    /**
     * Obtiene la razón social asociada a un usuario.
     *
     * @param int $userid El ID del usuario.
     * @return array|null La razón social encontrada o null si no se encuentra.
     */
    public function getRazonSocialByUserID(int $userid): ?array
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userid);

        if (!$user || !isset($user['ID_RazonSocial'])) {
            return null;
        }

        $razonSocialModel = new RazonSocialModel();
        return $razonSocialModel->find($user['ID_RazonSocial']) ?: null;
    }
    //endregion

    //region Limpiar Almacenamiento
    /**
     * Obtiene el contenido de un directorio dentro de 'writable/uploads'.
     *
     * @param string $relativePath La ruta relativa dentro de uploads.
     * @return array Lista de archivos y carpetas.
     * @throws \Exception Si el directorio no es válido o no se puede leer.
     */
    public function getStorageContent(string $relativePath = ''): array
    {
        $basePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;

        $cleanPath = str_replace(['../', '..\\'], '', $relativePath);
        $cleanPath = trim($cleanPath, '/\\');

        $targetPath = realpath($basePath . $cleanPath);

        if ($targetPath === false || strpos($targetPath, realpath($basePath)) !== 0) {
            $targetPath = realpath($basePath);
            $cleanPath = '';
        }

        if (!is_dir($targetPath)) {
            throw new \Exception('El directorio no existe: ' . $cleanPath);
        }

        $items = [];
        try {
            $iterator = new \DirectoryIterator($targetPath);
            $disallowedExtensions = ['html', 'htm', 'js', 'css', 'json'];

            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isDot()) {
                    continue;
                }

                if (
                    $fileinfo->isFile() &&
                    in_array(strtolower($fileinfo->getExtension()), $disallowedExtensions, true)
                ) {
                    continue;
                }

                $isDir = $fileinfo->isDir();
                $itemRelativePath = $cleanPath
                    ? $cleanPath . '/' . $fileinfo->getFilename()
                    : $fileinfo->getFilename();

                $items[] = [
                    'name' => $fileinfo->getFilename(),
                    'type' => $isDir ? 'folder' : 'file',
                    'path' => $itemRelativePath,
                    'size' => $isDir ? '-' : $this->formatBytes($fileinfo->getSize()),
                ];
            }

            usort($items, function ($a, $b) {
                if ($a['type'] === $b['type']) {
                    return strcasecmp($a['name'], $b['name']);
                }
                return $a['type'] === 'folder' ? -1 : 1;
            });
        } catch (\Exception $e) {
            throw new \Exception('Error al leer el directorio: ' . $e->getMessage());
        }

        return $items;
    }

    /**
     * Formatea bytes a una cadena legible por humanos (KB, MB, GB).
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    //endregion

    //region misceláneos
    public static function ShowDebug($data, $json = false)
    {
        if ($json) {
            return ['success' => true, 'debug' => $data];
        }
        return "<pre>Debug Info:\n" . print_r($data, true) . '</pre>';
    }
    /**
     * Crea una carpeta en la ruta especificada si no existe.
     *
     * @param string $path La ruta completa de la carpeta a crear.
     * @return bool True si la carpeta ya existe o fue creada exitosamente, false si hubo un error.
     */
    public function CreateFolder(string $path): bool
    {
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene los datos completos de una solicitud de pago para generar un PDF de requisición.
     *
     * Incluye información de usuario, departamento, proveedor, razón social, y calcula el importe total
     * y la descripción de los productos/servicios asociados a la solicitud.
     *
     * @param int $id El ID de la solicitud de la cual se obtendrán los datos de pago.
     * @return array|null Un array con todos los datos formateados para la requisición de pago, o null si la solicitud no se encuentra.
     */
    public function getSolicitudPago(int $id): ?array
    {
        log_message('debug', 'Iniciando getSolicitudPago para ID: ' . $id);
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $proveedorModel = new ProveedorModel();

        $solicitud = $solicitudModel
            ->select('Solicitud.*')
            ->select('Solicitud.ID_Proveedor as SolicitudProveedorID')
            ->select('Usuarios.Nombre as UsuarioNombre')
            ->select('Departamentos.Nombre as DepartamentoNombre')
            ->select('Proveedor.RazonSocial as ProveedorNombre')
            ->select('Proveedor.Banco as ProveedorBanco')
            ->select('Proveedor.Cuenta as ProveedorCuenta')
            ->select('Proveedor.Clabe as ProveedorClabe')

            ->select('Razon_Social.Nombre as Complejo')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            log_message('debug', 'Solicitud no encontrada para ID: ' . $id);
            return null;
        }

        if (!empty($solicitud['ID_Cuenta'])) {
            $cuentasModel = new CuentasModel();
            $solicitud['cuenta_details'] = $cuentasModel->find($solicitud['ID_Cuenta']);
        }

        log_message('debug', 'Solicitud encontrada: ' . json_encode($solicitud));

        $solicitud['ID_Place'] = $placesModel->find(
            $this->getDepartmentById($solicitud['ID_Dpto'])['ID_Place'],
        )['Nombre_Corto'];
        log_message('debug', 'ID_Place: ' . $solicitud['ID_Place']);

        $importeTotal = 0;
        $descripcionPago = '';

        if (
            $solicitud['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitud['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel->where('ID_Solicitud', $id)->findAll();
            foreach ($productos as $producto) {
                $importeTotal += $producto['Cantidad'] * $producto['Importe'];
                $descripcionPago .= $producto['Cantidad'] . ' ' . $producto['Nombre'] . ', ';
            }
            log_message('debug', 'Productos encontrados: ' . json_encode($productos));
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $servicios = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
            foreach ($servicios as $servicio) {
                $importeTotal += $servicio['Importe'];
                $descripcionPago .= $servicio['Nombre'] . ', ';
            }
            log_message('debug', 'Servicios encontrados: ' . json_encode($servicios));
        }

        $solicitud['ImporteTotal'] = $importeTotal;
        $solicitud['DescripcionPago'] = rtrim($descripcionPago, ', ');
        log_message(
            'debug',
            'ImporteTotal: ' . $importeTotal . ', DescripcionPago: ' . $descripcionPago,
        );

        $tipoPagoMap = [
            MetodoPago::Efectivo => 'Efectivo',
            MetodoPago::Credito => 'Crédito',
        ];

        $solicitud['TipoPagoTexto'] = $tipoPagoMap[$solicitud['Tipo']] ?? 'Desconocido';
        log_message('debug', 'TipoPagoTexto: ' . $solicitud['TipoPagoTexto']);

        $solicitud['Banco'] = $solicitud['ProveedorBanco'] ?? '';
        $solicitud['Cuenta'] = $solicitud['ProveedorCuenta'] ?? '';
        $solicitud['Clabe'] = $solicitud['ProveedorClabe'] ?? '';
        log_message(
            'debug',
            'Datos bancarios: ' .
                json_encode(['Banco' => $solicitud['Banco'], 'Cuenta' => $solicitud['Cuenta']]),
        );

        $solicitud['Solicita'] = $solicitud['UsuarioNombre'] ?? '';
        $solicitud['VoBo'] = 'Administracion';
        $solicitud['Autoriza'] = 'Direccion General';
        $solicitud['NotificarA'] = '';
        log_message(
            'debug',
            'Campos de firma: ' .
                json_encode(['Solicita' => $solicitud['Solicita'], 'VoBo' => $solicitud['VoBo']]),
        );

        log_message('debug', 'Finalizando getSolicitudPago con éxito.');
        log_message('debug', print_r($solicitud, true));
        return $solicitud ?: [];
    }
    
    /**
     * Obtiene solicitudes filtradas por varios criterios.
     *
     * @param string|null $fecha La fecha para filtrar (YYYY-MM-DD).
     * @param bool $porMes Si es true, filtra por mes (YYYY-MM).
     * @param string|null $estado El estado de la solicitud para filtrar.
     * @param array|null $departamentos Un array de strings "Departamento|Lugar" para filtrar.
     * @return array Un array de solicitudes filtradas.
     */
    public function getFilteredSolicitudes(?string $fecha, bool $porMes, ?string $estado, ?array $departamentos): array
    {
        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();

        $builder = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre, Usuarios.Nombre as UsuarioNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left');

        // Excluir estados por defecto si no se especifica un estado de filtro.
        // Si el estado es 'Todos los estados' (vacío), aplicamos la exclusión de getAllSolicitud.
        if (empty($estado)) {
            $excluded_statuses = [Status::Dept_Rechazada, Status::Aprobacion_pendiente];
            $builder->whereNotIn('Solicitud.Estado', $excluded_statuses);
        } elseif ($estado !== 'Todos los estados') { // Aplicar el estado específico si no es 'Todos los estados'
            $builder->where('Solicitud.Estado', $estado);
        }

        // Filtro por Fecha
        if (!empty($fecha)) {
            if ($porMes) {
                $builder->like('Solicitud.Fecha', substr($fecha, 0, 7), 'after'); // 'YYYY-MM%'
            } else {
                $builder->where('Solicitud.Fecha', $fecha);
            }
        }

        // Filtro por Departamentos
        if (!empty($departamentos)) {
            $builder->groupStart();
            foreach ($departamentos as $dptoPlace) {
                list($dpto, $place) = explode('|', $dptoPlace);
                $builder->orGroupStart();
                $builder->where('Departamentos.Nombre', $dpto);
                // Si 'Todos los lugares' (vacío) se selecciona para un departamento, no filtrar por PlaceNombre
                if (!empty($place)) {
                    $builder->where('Places.Nombre_Corto', $place);
                }
                $builder->groupEnd();
            }
            $builder->groupEnd();
        }

        $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')->findAll();

        if (empty($solicitudes)) {
            return [];
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        // Obtener cotizaciones y órdenes de compra para actualizar el estado
        $cotizaciones = $cotizacionModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();
        $cotizacionIds = array_column($cotizaciones, 'ID_Cotizacion');

        $ordenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel->whereIn('ID_Cotizacion', $cotizacionIds)->findAll();
        }

        $cotizacionesMap = [];
        foreach ($cotizaciones as $cot) {
            if (!isset($cotizacionesMap[$cot['ID_Solicitud']])) {
                $cotizacionesMap[$cot['ID_Solicitud']] = $cot;
            }
        }

        $ordenesMap = [];
        foreach ($ordenes as $orden) {
            $ordenesMap[$orden['ID_Cotizacion']] = $orden;
        }

        foreach ($solicitudes as &$solicitud) {
            if ($solicitud['Estado'] === Status::Aprobada) {
                if (isset($cotizacionesMap[$solicitud['ID_Solicitud']])) {
                    $cotizacion = $cotizacionesMap[$solicitud['ID_Solicitud']];
                    if (isset($ordenesMap[$cotizacion['ID_Cotizacion']])) {
                        $orden = $ordenesMap[$cotizacion['ID_Cotizacion']];
                        if (!empty($orden['Estado'])) {
                            $solicitud['Estado'] = $orden['Estado'];
                        }
                    }
                }
            }
        }

        return $solicitudes;
    }
    
    /**
     * Obtiene todas las órdenes de compra pendientes de pago con sus detalles.
     *
     * @return array Un array con los datos de las órdenes.
     */
    public function getPagosPendientes(): array
    {

        $ordenCompraModel = new OrdenCompraModel();

        $ordenes = $ordenCompraModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Solicitud.MetodoPago',
                'Solicitud.Fecha_Aprobacion',
                'Departamentos.Nombre as DepartamentoNombre',
                'Razon_Social.Nombre as Complejo',
                'Proveedor.RazonSocial',
                'Proveedor.Banco',
                'Proveedor.Dias_Credito',
                'Cotizacion.Total',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->where('OrdenCompra.Estado', Status::Por_Pagar)
            ->orderBy('Solicitud.Fecha_Aprobacion', 'ASC')
            ->findAll();

        // Estructurar los datos para que coincidan con lo que espera el frontend.
        $results = [];
        foreach ($ordenes as $orden) {
            $results[] = [
                'ID_Solicitud' => $orden['ID_Solicitud'],
                'No_Folio' => $orden['No_Folio'],
                'MetodoPago' => $orden['MetodoPago'],
                'Fecha_Aprobacion' => $orden['Fecha_Aprobacion'],
                'DepartamentoNombre' => $orden['DepartamentoNombre'],
                'Complejo' => $orden['Complejo'],
                'EstadoOrden' => Status::Por_Pagar, // Se asume este estado por la consulta
                'proveedor' => [
                    'RazonSocial' => $orden['RazonSocial'],
                    'Banco' => $orden['Banco'],
                    'Dias_Credito' => $orden['Dias_Credito'],
                ],
                'cotizacion' => [
                    'Total' => $orden['Total'],
                ],
            ];
        }

        return $results;
    }

    /**
     * Obtiene todas las órdenes de compra en proceso de pago con sus detalles.
     *
     * @return array Un array con los datos de las órdenes.
     */
    public function getFichasPago(): array
    {
        $ordenCompraModel = new OrdenCompraModel();

        $ordenes = $ordenCompraModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Solicitud.MetodoPago',
                'Solicitud.Fecha_Aprobacion',
                'Departamentos.Nombre as DepartamentoNombre',
                'Razon_Social.Nombre as Complejo',
                'Proveedor.RazonSocial',
                'Proveedor.Banco',
                'Proveedor.Dias_Credito',
                'Cotizacion.Total',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->where('OrdenCompra.Estado', Status::En_Proceso_Pago) // Changed status
            ->orderBy('Solicitud.Fecha_Aprobacion', 'ASC')
            ->findAll();

        $results = [];
        foreach ($ordenes as $orden) {
            $results[] = [
                'ID_Solicitud' => $orden['ID_Solicitud'],
                'No_Folio' => $orden['No_Folio'],
                'MetodoPago' => $orden['MetodoPago'],
                'Fecha_Aprobacion' => $orden['Fecha_Aprobacion'],
                'DepartamentoNombre' => $orden['DepartamentoNombre'],
                'Complejo' => $orden['Complejo'],
                'EstadoOrden' => Status::En_Proceso_Pago,
                'proveedor' => [
                    'RazonSocial' => $orden['RazonSocial'],
                    'Banco' => $orden['Banco'],
                    'Dias_Credito' => $orden['Dias_Credito'],
                ],
                'cotizacion' => [
                    'Total' => $orden['Total'],
                ],
            ];
        }

        return $results;
    }
    //endregion
}