<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\ProductoModel;
use Faker\Factory;

class ProductosSeeder extends Seeder
{
    public function run()
    {
        $faker = Factory::create('es_ES'); 
        $model = new ProductoModel();

        $num_productos = 100;

        for ($i = 1; $i < $num_productos; $i++) {
            $data = [
                'Codigo' => $faker->unique()->numberBetween(1000, 99999),
                'Nombre' => $faker->unique()->words(2, true),
                'Existencia' => $faker->numberBetween(0, 500),
            ];
            $model->insert($data);
        }

        echo 'Se han insertado ' . $num_productos . ' productos de prueba en la base de datos.\n'; 
    }
}