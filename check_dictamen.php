<?php
// Script para consultar solicitudes en dictamen y sus partidas
require 'vendor/autoload.php';

// Intentar cargar el entorno de CI4 mínimamente para usar la DB
try {
    $db = \Config\Database::connect();
    
    $builder = $db->table('Solicitud s');
    $builder->select('s.ID_Solicitud, s.No_Folio, s.Estado, d.Nombre as Departamento');
    $builder->join('Departamentos d', 'd.ID_Dpto = s.ID_Dpto', 'left');
    $builder->where('s.Estado', 'En revision');
    $solicitudes = $builder->get()->getResultArray();

    if (empty($solicitudes)) {
        echo "No hay solicitudes con estado 'En revision' actualmente.\n";
        exit;
    }

    echo "=== Solicitudes en Dictamen ('En revision') ===\n";
    foreach ($solicitudes as $sol) {
        echo "ID: {$sol['ID_Solicitud']} | Folio: {$sol['No_Folio']} | Depto: {$sol['Departamento']}\n";
        
        // Consultar productos y sus partidas
        $pBuilder = $db->table('Solicitud_Producto sp');
        $pBuilder->select('sp.Nombre as Producto, gp.Nombre as Partida');
        $pBuilder->join('GrupoPresupuestal gp', 'gp.ID_GrupoPresupuestal = sp.ID_GrupoPresupuestal', 'left');
        $pBuilder->where('sp.ID_Solicitud', $sol['ID_Solicitud']);
        $productos = $pBuilder->get()->getResultArray();

        if (!empty($productos)) {
            echo "  Partidas:\n";
            foreach ($productos as $p) {
                echo "    - " . ($p['Producto'] ?? 'Sin nombre') . ": " . ($p['Partida'] ?? 'SIN PARTIDA ASIGNADA') . "\n";
            }
        } else {
            // Revisar si son servicios
            $sBuilder = $db->table('Solicitud_Servicio ss');
            $sBuilder->select('ss.Nombre as Servicio');
            $sBuilder->where('ss.ID_Solicitud', $sol['ID_Solicitud']);
            $servicios = $sBuilder->get()->getResultArray();
            if (!empty($servicios)) {
                echo "  Servicios (No suelen tener partida asignada en esta tabla):\n";
                foreach ($servicios as $ser) {
                    echo "    - {$ser['Servicio']}\n";
                }
            } else {
                echo "  No se encontraron productos o servicios asociados.\n";
            }
        }
        echo "--------------------------------------------------\n";
    }
} catch (\Exception $e) {
    // Si falla el bootstrap de CI4, intentaremos leer la config de DB manualmente
    echo "Error conectando con CI4: " . $e->getMessage() . "\n";
}
