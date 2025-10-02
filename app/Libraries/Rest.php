<?php
namespace App\Libraries;
use App\Models\CotizacionModel;
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
use App\Libraries\SolicitudTipo;

use CodeIgniter\Database\BaseBuilder;
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

        $results = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre')
            ->whereNotIn('Solicitud.Estado', $excluded_statuses)
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $results ?: [];
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

        $results = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->where('Solicitud.ID_Dpto', $id)
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $results ?: [];
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
        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Proveedor.RazonSocial as RazonSocialNombre',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }
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

        // También obtiene datos de cotización si existen
        $cotizacionModel = new CotizacionModel();
        $cotizacion = $cotizacionModel
            ->select('Cotizacion.*, Proveedor.RazonSocial as ProveedorNombre')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('ID_Solicitud', $id)
            ->first();

        if ($cotizacion) {
            $solicitud['cotizacion'] = $cotizacion;
        }

        return $solicitud ? $solicitud : [];
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
        $result = [];

        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $usuarioModel = new UsuariosModel();
        $dptoModel = new DepartamentosModel();
        $proveedorModel = new ProveedorModel();

        // Obtenemos todas las cotizaciones
        $cotizaciones = $cotizacionModel->findAll();

        foreach ($cotizaciones as $cotizacion) {
            // Buscar solicitud ligada
            $solicitud = $solicitudModel->find($cotizacion['ID_Solicitud']);

            if (
                !$solicitud ||
                ($solicitud['Estado'] ?? '') === 'En revision' ||
                ($solicitud['Estado'] ?? '') === 'Aprobada' ||
                ($solicitud['Estado'] ?? '') === 'Rechazada'
            ) {
                continue;
            }

            // Buscar usuario y departamento ligados a la solicitud
            $usuario = $usuarioModel->find($solicitud['ID_Usuario']);
            $departamento = $dptoModel->find($solicitud['ID_Dpto']);

            // Buscar proveedor ligado a la cotización
            $proveedor = $proveedorModel->find($cotizacion['ID_Proveedor']);

            // Armar el resultado con el mismo formato que necesitas
            $result[] = [
                'ID' => $cotizacion['ID_Cotizacion'],
                'ID_Solicitud' => $solicitud['ID_Solicitud'],
                'Folio' => $solicitud['No_Folio'] ?? '',
                'Usuario' => $usuario['Nombre'] ?? '',
                'Departamento' => $departamento['Nombre'] ?? '',
                'Proveedor' => $proveedor['RazonSocial'] ?? '',
                'Monto' =>
                    $solicitud['IVA'] === true ? $cotizacion['Total'] * 1.16 : $cotizacion['Total'],
                'Estado' => $solicitud['Estado'] ?? '',
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
            // esto nos asegura de no mostrar las solicitudes del propio jefe
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
        $sql = 'SELECT * FROM "Producto" WHERE "Nombre" ILIKE ?' . ($limit > 0 ? ' LIMIT ?' : '');
        $params = [$name . '%'];
        if ($limit > 0) {
            $params[] = $limit;
            $query = $this->db->query($sql, $params);
            return $query->getResultArray();
        }
        return [];
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
     * Obtiene un proveedor por su ID.
     *
     * @param int $id El ID del proveedor.
     * @return array|null El proveedor encontrado o null si no se encuentra.
     */
    public function getProveedorById(int $id): ?array
    {
        $proveedorModel = new ProveedorModel();
        $result = $proveedorModel->find($id);
        return $result ?: null;
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
        } else {
            return null;
        }
    }
    //endregion

    //region misceláneos
    public static function ShowDebug($data)
    {
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
     * Obtiene los datos de una solicitud de pago para generar un PDF.
     *
     * @param int $id El ID de la solicitud.
     * @return array|null Un array con los datos de la solicitud de pago o null si no se encuentra.
     */
    public function getSolicitudPago(int $id): ?array
    {
         /*
            -------------Datos------------- 
            Razón Social 
            Titulo:Requisición de pago 
            Metodo:Transferencia,Cheque,Efectivo 
            Fecha de solicitud 
            Departamento 
            Proyecto 
            Prooveedor 
            Fecha de pago 
            Importe Total 
            --------------Datos Tabla--------- 
            |No.|No. Factura|Importe|Descripcion de pago| 
            ----------------------------------
        */
        $usuarioModel = new UsuariosModel();
        $razonSocialModel = new RazonSocialModel();
        $solicitudModel = new SolicitudModel();

        $solicitud = $solicitudModel->find($id);
        $usuario = $usuarioModel->find($solicitud['ID_Usuario']);
        $razonSocial = $razonSocialModel->find($usuario['ID_RazonSocial']);


        $solicitud['UsuarioNombre'] = $usuario['Nombre'];
        $solicitud['RazonSocialNombre'] = $razonSocial['Nombre'];

    

        if (!$solicitud) {
            return null;
        }

        return $solicitud ?: [];

    }
    //endregion
}
