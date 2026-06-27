<?php
namespace Database\Seeders;
use App\Models\Zone;
use Illuminate\Database\Seeder;
class ZoneSeeder extends Seeder {
    public function run(): void {
        $zones = [
            'Zona Norte', 'Zona Sur', 'Zona Este', 'Zona Oeste', 'Zona Centro',
            'Zona Metropolitana', 'Zona Patagónica', 'Zona Cuyana', 'Zona NEA', 'Zona NOA',
        ];
        foreach ($zones as $name) {
            Zone::firstOrCreate(['name' => $name]);
        }
    }
}
