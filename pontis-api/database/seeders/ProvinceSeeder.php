<?php
namespace Database\Seeders;
use App\Models\Province;
use Illuminate\Database\Seeder;
class ProvinceSeeder extends Seeder {
    public function run(): void {
        $provinces = [
            ['name' => 'Buenos Aires', 'code' => 'BA'],
            ['name' => 'Catamarca', 'code' => 'CT'],
            ['name' => 'Chaco', 'code' => 'CH'],
            ['name' => 'Chubut', 'code' => 'CB'],
            ['name' => 'Ciudad Autónoma de Buenos Aires', 'code' => 'CABA'],
            ['name' => 'Córdoba', 'code' => 'CO'],
            ['name' => 'Corrientes', 'code' => 'CR'],
            ['name' => 'Entre Ríos', 'code' => 'ER'],
            ['name' => 'Formosa', 'code' => 'FO'],
            ['name' => 'Jujuy', 'code' => 'JU'],
            ['name' => 'La Pampa', 'code' => 'LP'],
            ['name' => 'La Rioja', 'code' => 'LR'],
            ['name' => 'Mendoza', 'code' => 'MZ'],
            ['name' => 'Misiones', 'code' => 'MI'],
            ['name' => 'Neuquén', 'code' => 'NQ'],
            ['name' => 'Río Negro', 'code' => 'RN'],
            ['name' => 'Salta', 'code' => 'SA'],
            ['name' => 'San Juan', 'code' => 'SJ'],
            ['name' => 'San Luis', 'code' => 'SL'],
            ['name' => 'Santa Cruz', 'code' => 'SC'],
            ['name' => 'Santa Fe', 'code' => 'SF'],
            ['name' => 'Santiago del Estero', 'code' => 'SE'],
            ['name' => 'Tierra del Fuego', 'code' => 'TF'],
            ['name' => 'Tucumán', 'code' => 'TU'],
        ];
        foreach ($provinces as $p) {
            Province::firstOrCreate(['name' => $p['name']], $p);
        }
    }
}
