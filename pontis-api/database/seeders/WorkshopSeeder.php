<?php

namespace Database\Seeders;

use App\Models\Workshop;
use Illuminate\Database\Seeder;

class WorkshopSeeder extends Seeder
{
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
                'city' => 'CABA',
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
                'city' => 'CABA',
                'province' => 'Ciudad Autónoma de Buenos Aires',
                'country' => 'Argentina',
            ],
            [
                'zone_number' => 1,
                'zone_name' => 'Logias de Ciudad Autónoma de Buenos Aires',
                'name' => 'UNITAS',
                'number' => 387,
                'work_day' => 'Miércoles',
                'work_frequency' => '2do 4to',
                'address' => 'TTE. GRAL. J. D. PERON 1242',
                'city' => 'CABA',
                'province' => 'Ciudad Autónoma de Buenos Aires',
                'country' => 'Argentina',
                'language' => 'alemán',
            ],
        ];

        foreach ($workshops as $workshop) {
            Workshop::firstOrCreate(['number' => $workshop['number']], $workshop);
        }
    }
}
