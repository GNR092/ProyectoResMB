<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableApiToken extends Migration
{
    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->forge->addField([
            'ID_Token' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('ID_Token');
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->createTable('User_Tokens');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->forge->dropTable('User_Tokens');
    }
}
