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
use App\Models\TokenModel;
use App\Models\UsuariosModel;
use App\Libraries\HttpStatus;


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
     * Genera un nuevo token API.
     *
     * @return bool True si el token se generó correctamente, false en caso contrario.
     * @param int $userid El ID del usuario para el cual se generará el token
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

    public function generatetoken(int $userid): string
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userid);

        if (!$user) {
            return "";
        }

        $tokenmodel = new TokenModel();

        $tokenmodel->where('ID_Usuario', $userid)->delete();

        do {
            $tokenhash = bin2hex(random_bytes(32));
        } while ($tokenmodel->where('token', $tokenhash)->first());

        return $tokenhash;
    }

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
     * Borra un token API.
     *
     * @return bool true su el token se eliminó correctamente, false en caso contrario.
     * @param int $userId El ID del usuario cuyo token se eliminará
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
     * @return array Los tokens encontrados
     */
    private function getTokens(): array
    {
        $tokenModel = new TokenModel();
        $tokens = $tokenModel->findall();
        return $tokens;
    }
    //endregion

    //region cotizacione
    public function getCotizacionById(int $id): ?array
    {
        $cotizacionModel = new CotizacionModel();
        $result = $cotizacionModel->find($id);
        return $result ?: null;
    }
    public function getCotizaciones(): array
    {
        $cotizacionModel = new CotizacionModel();
        $results = $cotizacionModel->findAll();
        return $results ?: [];
    }
    //endregion

    //region solicitudes
    public function getAllSolicitud()
    {
        $solicitudModel = new SolicitudModel();

        $results = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->orderBy('Solicitud.ID_SolicitudProd', 'DESC')
            ->findAll();

        return $results ?: [];
    }
    public function getSolicitudByDepartment(int $id)
    {
        $solicitudModel = new SolicitudModel();

        $results = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->where('Solicitud.ID_Dpto', $id)
            ->orderBy('Solicitud.ID_SolicitudProd', 'DESC')
            ->findAll();

        return $results ?: [];
    }

    public function getSolicitudWithProducts(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel
            ->select('Solicitud.*, Usuarios.Nombre as UsuarioNombre, Departamentos.Nombre as DepartamentoNombre, Proveedor.Nombre as RazonSocialNombre')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }

        $solicitudProductModel = new SolicitudProductModel();
        $productos = $solicitudProductModel
            ->where('ID_SolicitudProd', $id)
            ->findAll();

        $solicitud['productos'] = $productos;

        return $solicitud;
    }

    //endregion

    //region Usuarios
    /**
     * Obtiene un usuario por su ID.
     *
     * @param int $id El ID del usuario
     * @return array|null El usuario encontrado o null si no se encuentra
     */
    public function getUserById(int $id): ?array
    {
        $usuariosModel = new UsuariosModel();
        $result = $usuariosModel->find($id);
        return $result ?: null;
    }
    /**
     * Obtiene un usuario por su nombre.
     *
     * @param string $name El nombre del usuario
     * @return array|null El usuario encontrado o null si no se encuentra
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
        * @param string $email El correo electrónico del usuario
        * @return array|null El usuario encontrado o null si no se encuentra
        */
    public function getUserByEmail(string $email): ?array
    {
        $usuariosModel = new UsuariosModel();
        $result = $usuariosModel->where('Correo', $email)->first();
        return $result ?: null;
    }
    /**
     * Obtiene usuarios por departamento.
     *
     * @param int $departmentId El ID del departamento
     * @return array Los usuarios encontrados
     */
    public function getUsersByDepartament(int $departmentId): array
    {
        $usuariosModel = new UsuariosModel();
        $results = $usuariosModel->where('ID_Dpto', $departmentId)->findAll();
        return $results ?: [];
    }
    /**
     * Obtiene todos los usuarios.
     *
     * @return array Los usuarios encontrados
     */
    public function getAllUsers(): array
    {
        $usuariosModel = new UsuariosModel();
        $results = $usuariosModel->findAll();
        return $results ?: [];
    }
    /**
     * Agrega un nuevo usuario.
     *
     * @param array $data Los datos del usuario a agregar
     * @return bool True si el usuario se agregó correctamente, false en caso contrario.
     */
    public function addUser(array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->insert($data) !== false;
    }
    /**
     * Actualiza un usuario por su ID.
     *
     * @param int $id El ID del usuario a actualizar
     * @param array $data Los datos a actualizar
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
     * @param int $id El ID del usuario a eliminar
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
     * Obtiene productos por consulta y tipo.
     *
     * @param string $query La consulta de búsqueda
     * @param string $type El tipo de búsqueda ('Código' o 'Producto')
     * @return array Los productos encontrados
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
     * @param int $id El ID del producto
     * @return array|null El producto encontrado o null si no se encuentra
     */
    public function getProductById(int $id): ?array
    {
        $producto = new ProductoModel();
        $result = $producto->find($id);
        return $result ?: null;
    }
    /**
     * Obtiene productos por código.
     *
     * @param string $code El código del producto
     * @param int $limit El número máximo de resultados a devolver
     * @return array Los productos encontrados
     */
    public function getProductsByCode(string $code, int $limit = 0): array
    {
        $producto = new ProductoModel();
        $results = $producto->like('Codigo', $code, 'both', null, true)->findAll($limit);
        return $results;
    }
    /**
     * Obtiene productos por nombre.
     *
     * @param string $name El nombre del producto
     * @param int $limit El número máximo de resultados a devolver
     * @return array Los productos encontrados
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
     * Registra un nuevo producto.
     *
     * @param array $data Los datos del producto a registrar
     * @return bool True si el producto se registró correctamente, false en caso contrario.
     */
    public function registrarProductoArray(array $data): bool
    {
        $producto = new ProductoModel();
        return $producto->insert($data) !== false;
    }
    /**
     * Registra un nuevo producto.
     *
     * @param string $codigo El código del producto
     * @param string $nombre El nombre del producto
     * @param int $existencia La existencia del producto
     * @return bool True si el producto se registró correctamente, false en caso contrario.
     */
    public function registrarProducto($codigo , $nombre, $existencia): bool
    {
        $producto = new ProductoModel();
        $data = [
            'Codigo'     => $codigo,
            'Nombre'     => $nombre,
            'Existencia' => $existencia,
        ];
        return $producto->insert($data) !== false;
    }
    
    /**
     *  Elimina un producto por su ID.
     * * @param int $id
     * @return bool|\CodeIgniter\Database\BaseResult true si el producto se eliminó correctamente, false en caso contrario.
     */
    public function eliminarProductoById(int $id): bool
    {
        $producto = new ProductoModel();
        return $producto->delete($id);
    }
    /**
     * Actualiza un producto por su ID.
     *
     * @param int $id El ID del producto a actualizar
     * @param array $data Los datos a actualizar
     * @return bool True si el producto se actualizó correctamente, false en caso contrario.
     */
    public function actualizarProducto(int $id, array $data): bool
    {
        $producto = new ProductoModel();
        return $producto->update($id, $data);
    }
     /**
     * Obtiene todos los productos.
     *
     * @return array Los productos encontrados
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
     * @param int $id El ID del proveedor
     * @return array|null El proveedor encontrado o null si no se encuentra
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
     * @return array Los proveedores encontrados
     */
    public function getAllProveedores(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->findAll();
        return $results ?: [];
    }
    /**
     * Obtiene todos los proveedores con solo ID y Nombre.
     *
     * @return array Los proveedores encontrados
     */
    public function getAllProveedorName(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->select('ID_Proveedor, Nombre')->findAll();
        return $results;
    }
    //endregion

    //region departamentos
    /**
     * Obtiene todos los departamentos.
     *
     * @return array Los departamentos encontrados
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
     * Obtiene un departamento por su ID.
     *
     * @param int $id El ID del departamento
     * @param bool $long Si se debe devolver el nombre completo o solo el nombre corto
     * @return string|null El departamento encontrado o null si no se encuentra
     *
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
    //endregion
}
