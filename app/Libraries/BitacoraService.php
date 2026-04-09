<?php

namespace App\Libraries;

use App\Models\BitacoraModel;
use Config\Services;

class BitacoraService
{
    private static $instance = null;
    private $logs = [];
    private $userContext = null;

    private function __construct() {
        $this->loadUserContext();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadUserContext()
    {
        $session = Services::session();
        if ($session->get('isLoggedIn')) {
            $this->userContext = [
                'usuario_id'      => $session->get('id'),
                'nombre_usuario'  => $session->get('nombre_usuario'),
                'departamento_id' => $session->get('id_departamento_usuario'),
                // Intentar obtener otros valores si se agregaron a la sesión después
                'complejo_id'     => $session->get('id_complejo'),
                'razon_social_id' => $session->get('id_razon_social'),
            ];
        }
    }

    /**
     * Registra una entrada en la bitácora (acumulada en memoria)
     */
    public function registrar(array $data)
    {
        $session = Services::session();
        $request = Services::request();

        // Si no hay contexto cargado y hay sesión, reintentar carga (por si logueó en esta petición)
        if ($this->userContext === null && $session->get('isLoggedIn')) {
            $this->loadUserContext();
        }

        $entry = [
            'usuario_id'         => $this->userContext['usuario_id'] ?? 0,
            'nombre_usuario'     => $this->userContext['nombre_usuario'] ?? 'SISTEMA',
            'departamento_id'    => $data['departamento_id'] ?? ($this->userContext['departamento_id'] ?? null),
            'complejo_id'        => $data['complejo_id'] ?? ($this->userContext['complejo_id'] ?? null),
            'razon_social_id'    => $data['razon_social_id'] ?? ($this->userContext['razon_social_id'] ?? null),
            'tipo_accion'        => $data['tipo_accion'] ?? 'ACCION_DESCONOCIDA',
            'clasificacion'      => $data['clasificacion'] ?? 'General',
            'usuario_autoriza_id'=> $data['usuario_autoriza_id'] ?? null,
            'modulo'             => $data['modulo'] ?? 'GENERAL',
            'solicitud_id'       => $data['solicitud_id'] ?? null,
            'orden_compra_id'    => $data['orden_compra_id'] ?? null,
            'cotizacion_id'      => $data['cotizacion_id'] ?? null,
            'ip_address'         => $request->getIPAddress(),
            'valores_antiguos'   => isset($data['valores_antiguos']) ? json_encode($data['valores_antiguos']) : null,
            'valores_nuevos'     => isset($data['valores_nuevos']) ? json_encode($data['valores_nuevos']) : null,
            'estado'             => $data['estado'] ?? 'exito',
        ];

        $this->logs[] = $entry;
    }

    /**
     * Guarda todos los registros acumulados en la base de datos
     */
    public function persistir()
    {
        if (empty($this->logs)) {
            return;
        }

        try {
            $model = new BitacoraModel();
            $model->insertBatch($this->logs);
        } catch (\Exception $e) {
            log_message('error', '[BitacoraService] Error al persistir logs: ' . $e->getMessage());
        } finally {
            $this->logs = [];
        }
    }
}
