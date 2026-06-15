<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIDCatalogoProdToSolicitudItems extends Migration
{
    public function up()
    {
        $fields = [
            'ID_CatalogoProd' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Solicitud'
            ],
        ];

        if (!$this->db->fieldExists('ID_CatalogoProd', 'Solicitud_Producto')) {
            $this->forge->addColumn('Solicitud_Producto', $fields);
        }
        if (!$this->db->fieldExists('ID_CatalogoProd', 'Solicitud_Servicios')) {
            $this->forge->addColumn('Solicitud_Servicios', $fields);
        }

        // Foreign keys are better added via direct query for existing tables in some environments or using forge if supported.
        // CI4 forge doesn't have a direct addForeignKey for existing tables in all DBs easily without dropping/recreating.
        // But we can try to use direct SQL which is safer for this project's mix of MySQL/PostgreSQL.
        
        $dbType = $this->db->getPlatform();
        
        if (strtolower($dbType) === 'postgre') {
            try { $this->db->query('ALTER TABLE "Solicitud_Producto" ADD CONSTRAINT "fk_sol_prod_catalogo" FOREIGN KEY ("ID_CatalogoProd") REFERENCES "Catalogo_Productos"("ID_CatalogoProd") ON DELETE SET NULL ON UPDATE CASCADE'); } catch (\Exception $e) {}
            try { $this->db->query('ALTER TABLE "Solicitud_Servicios" ADD CONSTRAINT "fk_sol_serv_catalogo" FOREIGN KEY ("ID_CatalogoProd") REFERENCES "Catalogo_Productos"("ID_CatalogoProd") ON DELETE SET NULL ON UPDATE CASCADE'); } catch (\Exception $e) {}
        } else {
            try { $this->db->query('ALTER TABLE Solicitud_Producto ADD CONSTRAINT fk_sol_prod_catalogo FOREIGN KEY (ID_CatalogoProd) REFERENCES Catalogo_Productos(ID_CatalogoProd) ON DELETE SET NULL ON UPDATE CASCADE'); } catch (\Exception $e) {}
            try { $this->db->query('ALTER TABLE Solicitud_Servicios ADD CONSTRAINT fk_sol_serv_catalogo FOREIGN KEY (ID_CatalogoProd) REFERENCES Catalogo_Productos(ID_CatalogoProd) ON DELETE SET NULL ON UPDATE CASCADE'); } catch (\Exception $e) {}
        }
    }

    public function down()
    {
        $dbType = $this->db->getPlatform();
        if (strtolower($dbType) === 'postgre') {
            $this->db->query('ALTER TABLE "Solicitud_Producto" DROP CONSTRAINT IF EXISTS "fk_sol_prod_catalogo"');
            $this->db->query('ALTER TABLE "Solicitud_Servicios" DROP CONSTRAINT IF EXISTS "fk_sol_serv_catalogo"');
        } else {
            $this->db->query('ALTER TABLE Solicitud_Producto DROP FOREIGN KEY fk_sol_prod_catalogo');
            $this->db->query('ALTER TABLE Solicitud_Servicios DROP FOREIGN KEY fk_sol_serv_catalogo');
        }

        $this->forge->dropColumn('Solicitud_Producto', 'ID_CatalogoProd');
        $this->forge->dropColumn('Solicitud_Servicios', 'ID_CatalogoProd');
    }
}
