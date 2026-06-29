<?php

namespace App\Services;

use App\Models\Workshop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class GlaSyncService
{
    public const SOURCE_URL = 'https://www.masoneria-argentina.org.ar/sobre-la-gla/logias-de-la-obediencia/';

    private const TRACKED_FIELDS = [
        'name', 'zone_number', 'zone_name', 'work_day', 'work_frequency',
        'address', 'city', 'province', 'language',
    ];

    public function fetch(): array
    {
        $response = Http::timeout(30)->retry(2, 1500)->get(self::SOURCE_URL);

        if (! $response->successful()) {
            throw new \RuntimeException('No se pudo obtener la página de GLA (HTTP ' . $response->status() . ').');
        }

        $body = mb_convert_encoding($response->body(), 'UTF-8', 'UTF-8');
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $body);

        return $this->parseFromDom(new Crawler($body));
    }

    public function preview(): array
    {
        $parsed = $this->fetch();
        $parsedByNumber = collect($parsed)->keyBy('number');

        $existing = Workshop::whereNotNull('source_url')->get()->keyBy('number');

        $new = [];
        $modified = [];

        foreach ($parsed as $data) {
            $number = $data['number'];

            if (! $existing->has($number)) {
                $new[] = $data;
            } else {
                $workshop = $existing->get($number);
                $changes = $this->diffFields($workshop, $data);

                if (! empty($changes)) {
                    $modified[] = [
                        'id'      => $workshop->id,
                        'number'  => $workshop->number,
                        'name'    => $workshop->name,
                        'changes' => $changes,
                    ];
                }
            }
        }

        $parsedNumbers = array_column($parsed, 'number');

        $disabled = Workshop::whereNotNull('source_url')
            ->whereNotIn('number', $parsedNumbers)
            ->where('status', 'active')
            ->get(['id', 'number', 'name', 'city'])
            ->values()
            ->toArray();

        return compact('new', 'modified', 'disabled');
    }

    public function applyNew(array $numbers): int
    {
        $parsed = $this->fetch();
        $byNumber = collect($parsed)->keyBy('number');
        $count = 0;

        foreach ($numbers as $number) {
            if ($byNumber->has($number)) {
                Workshop::updateOrCreate(['number' => $number], $byNumber->get($number));
                $count++;
            }
        }

        return $count;
    }

    public function applyModified(array $ids): int
    {
        $parsed = $this->fetch();
        $byNumber = collect($parsed)->keyBy('number');
        $count = 0;

        foreach (Workshop::whereIn('id', $ids)->get() as $workshop) {
            if ($byNumber->has($workshop->number)) {
                $workshop->update($byNumber->get($workshop->number));
                $count++;
            }
        }

        return $count;
    }

    public function applyDisabled(array $ids): int
    {
        return Workshop::whereIn('id', $ids)->update(['status' => 'disabled']);
    }

    private function diffFields(Workshop $workshop, array $incoming): array
    {
        $changes = [];

        foreach (self::TRACKED_FIELDS as $field) {
            $current = $workshop->$field;
            $next = $incoming[$field] ?? null;

            $a = is_null($current) ? '' : (string) $current;
            $b = is_null($next) ? '' : (string) $next;

            if ($a !== $b) {
                $changes[$field] = ['from' => $current, 'to' => $next];
            }
        }

        return $changes;
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
                    'zone_name'   => trim($m[2]),
                ];
            }
        });

        $parsed = [];

        $crawler->filter('.elementor-tab-content')->each(function (Crawler $panel) use ($zones, &$parsed) {
            $tab  = $panel->attr('data-tab');
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

    private function parseWorkshopText(string $text, ?int $zoneNumber, ?string $zoneName): ?array
    {
        $clean = $this->sanitize(trim(preg_replace('/^•\s*/u', '', $text)));

        $sinInfoPattern = '/^(?<name>.+?)\s+Nro\s+(?<number>\d+)\s+[–\-]\s+Trabaja\s+Sin\s+Informaci[oó]n\s+en\s+(?<address>.+)$/iu';

        if (preg_match($sinInfoPattern, $clean, $m)) {
            $m['day']       = null;
            $m['frequency'] = null;
        } else {
            $pattern = '/^(?<name>.+?)\s+Nro\s+(?<number>\d+)\s+[–\-]\s+Trabaja\s+(?<day>Lunes|Martes|Miércoles|Miercoles|Jueves|Viernes|Sábado|Sabado|Domingo)\s+(?<frequency>.+?)\s+en\s+(?<address>.+)$/iu';

            if (! preg_match($pattern, $clean, $m)) {
                return null;
            }
        }

        $address  = trim($m['address']);
        $language = null;

        if (preg_match('/\(en\s+(?<lang>[^)]+)\)/iu', $address, $langMatch)) {
            $language = trim($langMatch['lang']);
            $address  = trim(preg_replace('/\(en\s+[^)]+\)/iu', '', $address));
        }

        [$address, $city, $province] = $this->parseAddress($address);

        $day       = isset($m['day']) ? trim($m['day']) : null;
        $frequency = isset($m['frequency']) ? trim($m['frequency']) : null;

        return array_map(
            fn ($v) => is_string($v) ? $this->sanitize($v) : $v,
            [
                'zone_number'    => $zoneNumber,
                'zone_name'      => $zoneName,
                'name'           => trim($m['name']),
                'number'         => (int) $m['number'],
                'work_day'       => ($day && $day !== '') ? $this->normalizeDay($day) : null,
                'work_frequency' => ($frequency && $frequency !== '') ? $frequency : null,
                'address'        => $address,
                'city'           => $city,
                'province'       => $province,
                'country'        => 'Argentina',
                'language'       => $language,
                'status'         => 'active',
                'source_url'     => self::SOURCE_URL,
                'last_synced_at' => now(),
                'raw_source_text' => $clean,
            ]
        );
    }

    private function parseAddress(string $raw): array
    {
        $raw = preg_replace('/^[\s\-–]+|[\s\-–]+$/u', '', $raw ?? '');

        if ($raw === '' || $raw === null) {
            return [null, null, null];
        }

        // "CIUDAD AUTONOMA DE BUENOS AIRES" (full form)
        if (preg_match('/CIUDAD\s+AUT[OÓ]NOMA\s+DE\s+BUENOS\s+AIRES/iu', $raw)) {
            $street = trim(preg_replace('/CIUDAD\s+AUT[OÓ]NOMA\s+DE\s+BUENOS\s+AIRES/iu', '', $raw), " ,–-\t");
            return [$street !== '' ? $street : null, 'CABA', 'Ciudad Autónoma de Buenos Aires'];
        }

        // "CABA" shorthand
        if (preg_match('/\bCABA\b/iu', $raw)) {
            $street = trim(preg_replace('/\bCABA\b/iu', '', $raw), " ,–-\t");
            return [$street !== '' ? $street : null, 'CABA', 'Ciudad Autónoma de Buenos Aires'];
        }

        // Comma-separated: everything after the last comma is the city
        // e.g. "JUNIN N° 2147 Villa Maipú, SAN MARTIN"
        if (str_contains($raw, ',')) {
            $parts  = array_map('trim', explode(',', $raw));
            $city   = array_pop($parts);
            $street = implode(', ', $parts);
            return [trim($street) !== '' ? trim($street) : null, $city !== '' ? $city : null, null];
        }

        // General pattern: [STREET NAME] [NUMBER] [CITY]
        // Greedy match finds the LAST number token — everything after it is the city.
        // Handles N°622, Nº622, plain numbers, and suffixes like "50-".
        if (preg_match('/^(.*\b\d+\S*)\s+(\pL.+)$/u', $raw, $m)) {
            return [trim($m[1]) !== '' ? trim($m[1]) : null, trim($m[2]) !== '' ? trim($m[2]) : null, null];
        }

        // No number found — the whole string is the city (e.g. "TIGRE", "Olivos")
        return [null, $raw, null];
    }

    private function normalizeDay(string $day): string
    {
        return match (Str::lower($day)) {
            'miercoles' => 'Miércoles',
            'sabado'    => 'Sábado',
            default     => Str::ucfirst(Str::lower($day)),
        };
    }

    private function sanitize(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}
