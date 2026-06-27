<?php
namespace Database\Seeders;
use App\Models\Province;
use App\Models\Locality;
use Illuminate\Database\Seeder;
class LocalitySeeder extends Seeder {
    public function run(): void {
        $data = [
            'Buenos Aires' => ['La Plata','Mar del Plata','Bahía Blanca','Tandil','Quilmes','Lomas de Zamora','Lanús','General San Martín','Vicente López','San Isidro','Morón','Tres de Febrero','Almirante Brown','Florencio Varela','Berazategui','Avellaneda'],
            'Catamarca' => ['San Fernando del Valle de Catamarca','Andalgalá','Belén','Santa María','Tinogasta'],
            'Chaco' => ['Resistencia','Presidencia Roque Sáenz Peña','Villa Ángela','Barranqueras','Fontana'],
            'Chubut' => ['Rawson','Comodoro Rivadavia','Puerto Madryn','Trelew','Esquel'],
            'Ciudad Autónoma de Buenos Aires' => ['Buenos Aires'],
            'Córdoba' => ['Córdoba','Villa María','Río Cuarto','San Francisco','Alta Gracia','Villa Carlos Paz','Cosquín','Jesús María','Bell Ville'],
            'Corrientes' => ['Corrientes','Goya','Paso de los Libres','Curuzú Cuatiá','Mercedes'],
            'Entre Ríos' => ['Paraná','Concordia','Gualeguaychú','Concepción del Uruguay','Colón','Victoria'],
            'Formosa' => ['Formosa','Clorinda','Pirané','El Colorado'],
            'Jujuy' => ['San Salvador de Jujuy','Palpalá','San Pedro','Libertador General San Martín','Humahuaca'],
            'La Pampa' => ['Santa Rosa','General Pico','Toay','Realicó'],
            'La Rioja' => ['La Rioja','Chilecito','Aimogasta'],
            'Mendoza' => ['Mendoza','San Rafael','Godoy Cruz','Guaymallén','Luján de Cuyo','Maipú','Las Heras','Rivadavia'],
            'Misiones' => ['Posadas','Oberá','Eldorado','Puerto Iguazú','Apóstoles'],
            'Neuquén' => ['Neuquén','San Martín de los Andes','Zapala','Cutral Có','Plottier','Centenario'],
            'Río Negro' => ['Viedma','Bariloche','General Roca','Cipolletti','Allen','Catriel'],
            'Salta' => ['Salta','San Ramón de la Nueva Orán','Tartagal','Metán','Cafayate','Rosario de la Frontera'],
            'San Juan' => ['San Juan','Rawson','Chimbas','Rivadavia','Santa Lucía','Pocito'],
            'San Luis' => ['San Luis','Villa Mercedes','Merlo','Juana Koslay'],
            'Santa Cruz' => ['Río Gallegos','Caleta Olivia','Pico Truncado','Puerto Deseado','Las Heras'],
            'Santa Fe' => ['Santa Fe','Rosario','Rafaela','Venado Tuerto','Santo Tomé','Reconquista','Villa Gobernador Gálvez'],
            'Santiago del Estero' => ['Santiago del Estero','La Banda','Termas de Río Hondo','Añatuya'],
            'Tierra del Fuego' => ['Ushuaia','Río Grande','Tolhuin'],
            'Tucumán' => ['San Miguel de Tucumán','Tafí Viejo','Yerba Buena','Banda del Río Salí','Aguilares','Concepción'],
        ];
        foreach ($data as $provinceName => $localities) {
            $province = Province::where('name', $provinceName)->first();
            if (!$province) continue;
            foreach ($localities as $name) {
                Locality::firstOrCreate(['province_id' => $province->id, 'name' => $name]);
            }
        }
    }
}
