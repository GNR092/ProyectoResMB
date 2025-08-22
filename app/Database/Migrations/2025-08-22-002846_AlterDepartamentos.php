<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterDepartamentos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Departamentos', [
            'ID_Place' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true, 
                'after'      => 'ID_Dpto',
            ],
        ]);
        $firstPlace = $this->db->table('Places')->orderBy('ID_Place', 'ASC')->get()->getRow();
        if ($firstPlace) {
            $this->db->table('Departamentos')->update(['ID_Place' => $firstPlace->ID_Place]);
        }

        $this->forge->modifyColumn('Departamentos', [
            'ID_Place' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query('ALTER TABLE "Departamentos" ADD CONSTRAINT "departamentos_id_place_foreign" FOREIGN KEY ("ID_Place") REFERENCES "Places" ("ID_Place") ON DELETE CASCADE ON UPDATE CASCADE');
        }

        $data = [
            ['Nombre' => 'Administración', 'ID_Place' => 2],
            ['Nombre' => 'Atn. A residentes', 'ID_Place' => 2],
            ['Nombre' => 'Hotel', 'ID_Place' => 2],
            ['Nombre' => 'Hood corner', 'ID_Place' => 2],
            ['Nombre' => 'Ama de llaves', 'ID_Place' => 2],
            ['Nombre' => 'Mantenimiento', 'ID_Place' => 2],
            ['Nombre' => 'Operación de proyectos (complejos ab1 ab2 tmzn nte)', 'ID_Place' => 2],
            ['Nombre' => 'Recursos humanos', 'ID_Place' => 2],
            ['Nombre' => 'Rentas', 'ID_Place' => 2],
            ['Nombre' => 'Seguridad', 'ID_Place' => 2],
            ['Nombre' => 'Sistemas', 'ID_Place' => 2],
            ['Nombre' => 'Transportes', 'ID_Place' => 2]


        ];

        $this->db->table('Departamentos')->insertBatch($data);
    }

    public function down()
    {
        $departmentNames = [
            'Administración',
            'Atn. A residentes',
            'Hotel',
            'Hood corner', 
            'Ama de llaves',
            'Mantenimiento',
            'Operación de proyectos (complejos ab1 ab2 tmzn nte)',
            'Recursos humanos',
            'Rentas',
            'Seguridad',
            'Sistemas', 
            'Transportes', 
        ];
        $this->db->table('Departamentos')
            ->whereIn('Nombre', $departmentNames)
            ->where('ID_Place', 2)
            ->delete();
        // Revertir los cambios: primero se elimina la llave foránea y luego la columna.
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query('ALTER TABLE "Departamentos" DROP CONSTRAINT IF EXISTS "departamentos_id_place_foreign"');
        }
        $this->forge->dropColumn('Departamentos', 'ID_Place');
        
    }
}
