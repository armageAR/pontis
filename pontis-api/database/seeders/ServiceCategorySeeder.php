<?php
namespace Database\Seeders;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
class ServiceCategorySeeder extends Seeder {
    public function run(): void {
        $categories = [
            'Asesoría legal','Asesoría contable y financiera','Asesoría impositiva',
            'Medicina y salud','Arquitectura y construcción','Tecnología e informática',
            'Educación y formación','Arte y cultura','Comercio y negocios',
            'Transporte y logística','Turismo y hotelería','Gastronomía',
            'Comunicación y medios','Inmobiliario','Psicología y bienestar',
            'Ingeniería','Diseño','Recursos humanos','Otros',
        ];
        foreach ($categories as $name) {
            ServiceCategory::firstOrCreate(['name' => $name]);
        }
    }
}
