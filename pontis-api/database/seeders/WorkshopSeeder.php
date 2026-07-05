<?php

namespace Database\Seeders;

use App\Models\Workshop;
use Illuminate\Database\Seeder;

class WorkshopSeeder extends Seeder
{
    /**
     * Cinco talleres reales hardcodeados (fuente: crawler/base local) para un
     * dataset de test estable e independiente del crawler. Distribución:
     * 2 CABA, 1 Provincia de Buenos Aires, 1 Salta, 1 Chubut. Las provincias
     * vacías del crawler (Buenos Aires/Chubut) se corrigen según la ciudad/zona.
     * Ver openspec: improve-test-seeders.
     */
    public function run(): void
    {
        $workshops = [
            [
                'zone_number' => 1,
                'zone_name' => 'Logias de Ciudad Autónoma de Buenos Aires',
                'name' => 'UNION DEL PLATA',
                'number' => 1,
                'work_day' => 'Lunes',
                'work_frequency' => '1ro 3ro 5to',
                'address' => 'TTE. GRAL. J. D. PERON 1242',
                'city' => 'Ciudad Autónoma de Buenos Aires',
                'province' => 'Ciudad Autónoma de Buenos Aires',
                'country' => 'Argentina',
            ],
            [
                'zone_number' => 1,
                'zone_name' => 'Logias de Ciudad Autónoma de Buenos Aires',
                'name' => 'CONFRATERNIDAD ARGENTINA',
                'number' => 2,
                'work_day' => 'Jueves',
                'work_frequency' => '1ro 3ro',
                'address' => 'TTE. GRAL. J. D. PERON 1242',
                'city' => 'Ciudad Autónoma de Buenos Aires',
                'province' => 'Ciudad Autónoma de Buenos Aires',
                'country' => 'Argentina',
            ],
            [
                'zone_number' => 8,
                'zone_name' => 'Logias de Buenos Aires / La Pampa',
                'name' => 'LA PLATA',
                'number' => 80,
                'work_day' => 'Miércoles',
                'work_frequency' => '2do 4to',
                'address' => 'CALLE 49 No 731 E/9 Y 10',
                'city' => 'La Plata',
                'province' => 'Buenos Aires',
                'country' => 'Argentina',
            ],
            [
                'zone_number' => 12,
                'zone_name' => 'Logias de Salta / Tucumán / Jujuy',
                'name' => "DR. MARCELO O' CONNOR",
                'number' => 702,
                'work_day' => 'Lunes',
                'work_frequency' => '1ro 3ro',
                'address' => 'Calle Mitre No 1555',
                'city' => 'Salta',
                'province' => 'Salta',
                'country' => 'Argentina',
            ],
            [
                'zone_number' => 15,
                'zone_name' => 'Logias de las Provincias Patagónicas',
                'name' => 'TRIANGULO LUZ DEL ARA 679',
                'number' => 1222,
                'work_day' => 'Miércoles',
                'work_frequency' => '2do 4to',
                'address' => null,
                'city' => 'Rawson',
                'province' => 'Chubut',
                'country' => 'Argentina',
            ],
        ];

        foreach ($workshops as $workshop) {
            Workshop::updateOrCreate(['number' => $workshop['number']], $workshop);
        }
    }
}
