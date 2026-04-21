<?php

namespace App\Traits;

use CodeIgniter\Events\Events;

trait AuditTrait
{
    protected $tempOldData = [];

    /**
     * Captura los datos actuales antes de una actualización
     */
    protected function captureOldData(array $data)
    {
        if (isset($data['id'])) {
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
            foreach ($ids as $id) {
                $row = $this->find($id);
                if ($row) {
                    $this->tempOldData[$id] = $row;
                }
            }
        }
        return $data;
    }

    /**
     * Registra la auditoría después de una actualización
     */
    protected function auditUpdate(array $data)
    {
        if (!$data['result']) return $data;

        $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
        foreach ($ids as $id) {
            $old = $this->tempOldData[$id] ?? [];
            $newInput = $data['data'];

            $changedOld = [];
            $changedNew = [];

            foreach ($newInput as $key => $value) {
                if (array_key_exists($key, $old)) {
                    // Comparación flexible para tipos de datos de BD
                    if ($old[$key] != $value) {
                        $changedOld[$key] = $old[$key];
                        $changedNew[$key] = $value;
                    }
                }
            }

            if (!empty($changedNew)) {
                // Incluir campos de identificación (si existen en el modelo) para dar contexto al registro
                if (isset($this->auditIdentifyingFields) && is_array($this->auditIdentifyingFields)) {
                    foreach ($this->auditIdentifyingFields as $idField) {
                        if (isset($old[$idField])) {
                            $changedNew[$idField] = $old[$idField];
                            $changedOld[$idField] = $old[$idField];
                        }
                    }
                }

                Events::trigger('auditoria', [
                    'tipo_accion' => 'ACTUALIZAR',
                    'clasificacion' => $this->auditClasificacion ?? 'Operaciones',
                    'modulo'      => $this->table,
                    'valores_antiguos' => $changedOld,
                    'valores_nuevos'   => $changedNew,
                    // Intentar extraer IDs de negocio si están en el registro
                    'solicitud_id'     => $old['ID_Solicitud'] ?? ($newInput['ID_Solicitud'] ?? null),
                    'orden_compra_id'  => $old['ID_OrdenCompra'] ?? ($newInput['ID_OrdenCompra'] ?? null),
                    'cotizacion_id'    => $old['ID_Cotizacion'] ?? ($newInput['ID_Cotizacion'] ?? null),
                    'estado'      => 'exito'
                ]);
            }
        }
        
        // Limpiar temporales
        $this->tempOldData = [];
        return $data;
    }

    /**
     * Registra la auditoría después de una inserción
     */
    protected function auditInsert(array $data)
    {
        if (!$data['result']) return $data;

        Events::trigger('auditoria', [
            'tipo_accion' => 'INSERTAR',
            'clasificacion' => $this->auditClasificacion ?? 'Operaciones',
            'modulo'      => $this->table,
            'valores_nuevos' => $data['data'],
            'solicitud_id'     => $data['data']['ID_Solicitud'] ?? null,
            'orden_compra_id'  => $data['data']['ID_OrdenCompra'] ?? null,
            'cotizacion_id'    => $data['data']['ID_Cotizacion'] ?? null,
            'estado'      => 'exito'
        ]);

        return $data;
    }

    /**
     * Registra la auditoría después de una eliminación
     */
    protected function auditDelete(array $data)
    {
        if (!$data['result']) return $data;

        Events::trigger('auditoria', [
            'tipo_accion' => 'ELIMINAR',
            'clasificacion' => $this->auditClasificacion ?? 'Operaciones',
            'modulo'      => $this->table,
            'valores_antiguos' => ['id' => $data['id']],
            'estado'      => 'exito'
        ]);

        return $data;
    }
}
