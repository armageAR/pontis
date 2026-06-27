<?php
namespace Database\Seeders;
use App\Models\Position;
use Illuminate\Database\Seeder;
class PositionSeeder extends Seeder {
    public function run(): void {
        $positions = [
            'Venerable Maestro','Primer Vigilante','Segundo Vigilante',
            'Orador','Secretario','Tesorero','Maestro de Ceremonias',
            'Hospitalero','Experto','Primer Diácono','Segundo Diácono',
            'Primer Guardatemplo','Segundo Guardatemplo','Arquitecto Revisor',
            'Bibliotecario','Orador Adjunto','Secretario Adjunto',
        ];
        foreach ($positions as $name) {
            Position::firstOrCreate(['name' => $name]);
        }
    }
}
