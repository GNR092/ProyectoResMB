<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBitacoraTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fecha_hora' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'usuario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'nombre_usuario' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'departamento_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'complejo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'razon_social_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'tipo_accion' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'clasificacion' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'usuario_autoriza_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'modulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'solicitud_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'orden_compra_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'cotizacion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => false,
            ],
            'valores_antiguos' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'valores_nuevos' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'estado' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('fecha_hora');
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('solicitud_id');
        $this->forge->addKey('orden_compra_id');
        $this->forge->addKey('clasificacion');
        $this->forge->createTable('bitacora');
    }

    public function down()
    {
        $this->forge->dropTable('bitacora');
    }
}
