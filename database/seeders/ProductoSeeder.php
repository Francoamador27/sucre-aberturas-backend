<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datos = [
            array(
                'nombre' => "Iman",
                'precio' => 1000,
                'imagen' => "iman",
                'categoria_id' => 1,
                'disponible' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ),
        ];
        DB::table('productos')->insert($datos);
    }
}
