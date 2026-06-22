<?php

namespace App\Console\Commands;

use App\Models\Workshop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class SyncWorkshopsFromGlaCommand extends Command
{
    protected $signature = 'workshops:sync-from-gla {--dry-run : Preview changes without saving}';

    protected $description = 'Sync workshops from the public GLA website';

    private const SOURCE_URL = 'https://www.masoneria-argentina.org.ar/sobre-la-gla/logias-de-la-obediencia/';

    public function handle(): int
    {
        $this->info('Fetching source page...');

        $response = Http::timeout(20)
            ->retry(2, 1000)
            ->get(self::SOURCE_URL);

        if (! $response->successful()) {
            $this->error('Could not fetch source page (HTTP ' . $response->status() . ').');
            return self::FAILURE;
        }

        $body = $response->body();
        $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $body);

        $crawler = new Crawler($body);
        $parsed = $this->parseFromDom($crawler);

        if (empty($parsed)) {
            $this->warn('No workshops parsed.');
            return self::FAILURE;
        }

        $this->info("Parsed workshops: " . count($parsed));

        if ($this->option('dry-run')) {
            $this->table(
                ['Zona', 'Nro', 'Nombre', 'Día', 'Frecuencia', 'Ciudad'],
                collect($parsed)->map(fn (array $w) => [
                    $w['zone_number'],
                    $w['number'],
                    Str::limit($w['name'], 30),
                    $w['work_day'],
                    $w['work_frequency'],
                    $w['city'] ?? '—',
                ])->toArray()
            );

            return self::SUCCESS;
        }

        $syncedNumbers = [];

        foreach ($parsed as $data) {
            $syncedNumbers[] = $data['number'];

            Workshop::updateOrCreate(
                ['number' => $data['number']],
                $data
            );
        }

        $disabled = Workshop::whereNotIn('number', $syncedNumbers)
            ->where('source_url', self::SOURCE_URL)
            ->update(['status' => 'disabled']);

        $this->info("Workshops synced. Disabled {$disabled} stale entries.");

        return self::SUCCESS;
    }

    private function parseFromDom(Crawler $crawler): array
    {
        $zones = [];

        $crawler->filter('.elementor-tab-desktop-title')->each(function (Crawler $node) use (&$zones) {
            $tab = $node->attr('data-tab');
            $text = trim($node->text());

            if ($tab && preg_match('/^Zona\s+(\d+)\s+-\s+(.+)$/iu', $text, $m)) {
                $zones[$tab] = [
                    'zone_number' => (int) $m[1],
                    'zone_name' => trim($m[2]),
                ];
            }
        });

        $parsed = [];

        $crawler->filter('.elementor-tab-content')->each(function (Crawler $panel) use ($zones, &$parsed) {
            $tab = $panel->attr('data-tab');
            $zone = $zones[$tab] ?? ['zone_number' => null, 'zone_name' => null];

            $panel->filter('td a')->each(function (Crawler $link) use ($zone, &$parsed) {
                $text = trim($link->text());
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

                if (! $text) {
                    return;
                }

                $workshop = $this->parseWorkshopText($text, $zone['zone_number'], $zone['zone_name']);

                if ($workshop) {
                    $parsed[] = $workshop;
                }
            });
        });

        return $parsed;
    }

    private function sanitizeUtf8(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function parseWorkshopText(string $text, ?int $zoneNumber, ?string $zoneName): ?array
    {
        $clean = $this->sanitizeUtf8(trim(preg_replace('/^•\s*/u', '', $text)));

        $sinInfoPattern = '/^(?<name>.+?)\s+Nro\s+(?<number>\d+)\s+[–\-]\s+Trabaja\s+Sin\s+Informaci[oó]n\s+en\s+(?<address>.+)$/iu';

        if (preg_match($sinInfoPattern, $clean, $m)) {
            $m['day'] = null;
            $m['frequency'] = null;
        } else {
            $pattern = '/^(?<name>.+?)\s+Nro\s+(?<number>\d+)\s+[–\-]\s+Trabaja\s+(?<day>Lunes|Martes|Miércoles|Miercoles|Jueves|Viernes|Sábado|Sabado|Domingo)\s+(?<frequency>.+?)\s+en\s+(?<address>.+)$/iu';

            if (! preg_match($pattern, $clean, $m)) {
                $this->warn("Could not parse: {$clean}");
                return null;
            }
        }

        $day = isset($m['day']) ? trim($m['day']) : null;
        $frequency = isset($m['frequency']) ? trim($m['frequency']) : null;

        $address = trim($m['address']);
        $language = null;

        if (preg_match('/\(en\s+(?<lang>[^)]+)\)/iu', $address, $langMatch)) {
            $language = trim($langMatch['lang']);
            $address = trim(preg_replace('/\(en\s+[^)]+\)/iu', '', $address));
        }

        [$address, $city, $province] = $this->parseAddress($address);

        return array_map(
            fn ($v) => is_string($v) ? $this->sanitizeUtf8($v) : $v,
            [
                'zone_number' => $zoneNumber,
                'zone_name' => $zoneName,
                'name' => trim($m['name']),
                'number' => (int) $m['number'],
                'work_day' => ($day && $day !== '') ? $this->normalizeDay($day) : null,
                'work_frequency' => ($frequency && $frequency !== '') ? $frequency : null,
                'address' => $address,
                'city' => $city,
                'province' => $province,
                'country' => 'Argentina',
                'language' => $language,
                'status' => 'active',
                'source_url' => self::SOURCE_URL,
                'last_synced_at' => now(),
                'raw_source_text' => $clean,
            ]
        );
    }

    private function parseAddress(string $address): array
    {
        $address = trim($address, " \t\n\r\0\x0B–-");

        if (preg_match('/\s+CABA$/iu', $address)) {
            return [
                trim(preg_replace('/\s+CABA$/iu', '', $address)),
                'CABA',
                'Ciudad Autónoma de Buenos Aires',
            ];
        }

        return [$address, null, null];
    }

    private function normalizeDay(string $day): string
    {
        return match (Str::lower($day)) {
            'miercoles' => 'Miércoles',
            'sabado' => 'Sábado',
            default => Str::ucfirst(Str::lower($day)),
        };
    }
}
