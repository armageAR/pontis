<?php

namespace Tests\Feature;

use App\Models\Workshop;
use App\Services\GlaSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers parsing and diffing of the public directory page.
 *
 * The page is faked, so these never touch the network. Each entry mirrors the
 * real markup: one free-text line per workshop inside a <td><a>, grouped in
 * Elementor tab panels that carry the zone.
 */
class GlaSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fakePage(array $lines): void
    {
        $items = collect($lines)
            ->map(fn (string $line) => '<tr><td>• <a href="#">'.$line.'</a></td></tr>')
            ->implode('');

        $html = <<<HTML
            <html><head><meta charset="UTF-8"></head><body>
            <div class="elementor-tab-desktop-title" data-tab="1">Zona 1 - Logias de Ciudad Autónoma de Buenos Aires</div>
            <div class="elementor-tab-content" data-tab="1"><table>{$items}</table></div>
            </body></html>
            HTML;

        Http::fake([GlaSyncService::SOURCE_URL => Http::response($html)]);
    }

    private function parseOne(string $line): array
    {
        $this->fakePage([$line]);

        $parsed = app(GlaSyncService::class)->fetch();

        $this->assertCount(1, $parsed, 'The line was not parsed at all.');

        return $parsed[0];
    }

    // ── Parsing ──────────────────────────────────────────────────────────────

    public function test_it_parses_name_number_schedule_and_zone(): void
    {
        $w = $this->parseOne('UNION DEL PLATA Nro 1 – Trabaja Lunes 1ro 3ro 5to en TTE. GRAL. J. D. PERON 1242 CABA');

        $this->assertSame('UNION DEL PLATA', $w['name']);
        $this->assertSame(1, $w['number']);
        $this->assertSame('Lunes', $w['work_day']);
        $this->assertSame('1ro 3ro 5to', $w['work_frequency']);
        $this->assertSame(1, $w['zone_number']);
        $this->assertSame('Logias de Ciudad Autónoma de Buenos Aires', $w['zone_name']);
        $this->assertSame('TTE. GRAL. J. D. PERON 1242', $w['address']);
    }

    /**
     * The page spells the capital several ways; all of them must fold into the
     * single form the stored directory uses.
     */
    public function test_it_normalizes_every_spelling_of_the_capital(): void
    {
        $spellings = [
            'CABA',
            'CIUDAD AUTONOMA DE BUENOS AIRES',
            'CIUDAD AUTÓNOMA DE BUENOS AIRES',
            'CAPITAL FEDERAL',
            'C.A.B.A.',
        ];

        foreach ($spellings as $spelling) {
            $w = $this->parseOne("LEALTAD Nro 6 – Trabaja Viernes 1ro en Av. Boedo N°1115 {$spelling}");

            $this->assertSame('CABA', $w['city'], "Failed for spelling: {$spelling}");
            $this->assertSame('Ciudad Autónoma de Buenos Aires', $w['province']);
            $this->assertSame('Av. Boedo N°1115', $w['address']);
        }
    }

    public function test_it_splits_the_capital_off_an_address_with_no_street_number(): void
    {
        $w = $this->parseOne('HABANA Nro 777 – Trabaja Jueves Todos en Habana y Joaquín V. Gonzalez CABA');

        $this->assertSame('Habana y Joaquín V. Gonzalez', $w['address']);
        $this->assertSame('CABA', $w['city']);
    }

    public function test_it_splits_a_city_that_trails_the_street_number(): void
    {
        $w = $this->parseOne('EGALITE Nro 20 – Trabaja Lunes 2do en AVENIDA DEL LIBERTADOR 3120 La Lucila');

        $this->assertSame('AVENIDA DEL LIBERTADOR 3120', $w['address']);
        $this->assertSame('La Lucila', $w['city']);
        // Province does not follow from an arbitrary city, so it stays unknown.
        $this->assertArrayNotHasKey('province', $w);
    }

    public function test_it_tolerates_punctuation_after_the_street_number(): void
    {
        $w = $this->parseOne('ARRIBENOS Nro 693 – Trabaja Lunes 1ro en Arribeños 3619. CABA');

        $this->assertSame('Arribeños 3619.', $w['address']);
        $this->assertSame('CABA', $w['city']);
    }

    /**
     * Regression test: parseAddress() used to trim an en dash with a byte-wise
     * trim(), which also ate the trailing 0x93 byte of a final "Ó" and left a
     * dangling 0xc3 that degraded to "?" — "ITUZAINGÓ" became "ITUZAING?".
     */
    public function test_it_preserves_accented_uppercase_at_the_end_of_a_value(): void
    {
        $w = $this->parseOne('LUMEN Nro 200 – Trabaja Martes 1ro 3ro en Tabaré N°2974 ITUZAINGÓ');

        $this->assertSame('ITUZAINGÓ', $w['city']);
        $this->assertSame('Tabaré N°2974', $w['address']);
        $this->assertTrue(mb_check_encoding($w['city'], 'UTF-8'));
        $this->assertStringNotContainsString('?', $w['city']);
    }

    /**
     * When there is no boundary between street and city, guessing would corrupt
     * data. The keys must be absent so nothing is overwritten downstream.
     */
    public function test_it_reports_no_city_when_the_address_has_no_boundary(): void
    {
        $w = $this->parseOne('TIGRE Nro 531 – Trabaja Lunes Todos en TIGRE');

        $this->assertArrayNotHasKey('city', $w);
        $this->assertArrayNotHasKey('province', $w);
    }

    public function test_it_does_not_mistake_a_trailing_address_fragment_for_a_city(): void
    {
        $w = $this->parseOne('AYACUCHO Nro 89 – Trabaja Lunes 1ro en Sarmiento y 25 de Mayo');

        $this->assertArrayNotHasKey('city', $w);
        $this->assertSame('Sarmiento y 25 de Mayo', $w['address']);
    }

    public function test_it_parses_entries_without_a_schedule(): void
    {
        $w = $this->parseOne('SIN DATOS Nro 999 – Trabaja Sin Información en Av. Siempreviva 742 CABA');

        // The page states the absence explicitly, so null is what the source says.
        $this->assertNull($w['work_day']);
        $this->assertNull($w['work_frequency']);
        $this->assertSame('Av. Siempreviva 742', $w['address']);
    }

    // ── Diffing ──────────────────────────────────────────────────────────────

    /**
     * The core of issue #4: a field the page does not report must not be
     * proposed as a change, or applying the sync wipes curated data.
     */
    public function test_preview_does_not_propose_clearing_a_city_the_page_does_not_report(): void
    {
        Workshop::factory()->create([
            'number' => 531,
            'name' => 'TIGRE',
            'address' => 'Alsina 1234',
            'city' => 'TIGRE',
            'province' => 'Buenos Aires',
            'source_url' => GlaSyncService::SOURCE_URL,
            'work_day' => 'Lunes',
            'work_frequency' => 'Todos',
            'zone_number' => 1,
            'zone_name' => 'Logias de Ciudad Autónoma de Buenos Aires',
        ]);

        $this->fakePage(['TIGRE Nro 531 – Trabaja Lunes Todos en TIGRE']);

        $diff = app(GlaSyncService::class)->preview();

        $this->assertCount(1, $diff['modified']);
        $changes = $diff['modified'][0]['changes'];

        $this->assertArrayNotHasKey('city', $changes, 'The stored city must not be proposed for clearing.');
        $this->assertArrayNotHasKey('province', $changes);
        // The address genuinely differs and should still be reported.
        $this->assertArrayHasKey('address', $changes);
    }

    public function test_preview_reports_a_genuine_change_and_ignores_unchanged_entries(): void
    {
        Workshop::factory()->create([
            'number' => 115,
            'name' => 'GALILEO GALILEI',
            'address' => 'Av. Pavón 3816',
            'city' => 'CABA',
            'province' => 'Ciudad Autónoma de Buenos Aires',
            'source_url' => GlaSyncService::SOURCE_URL,
            'work_day' => 'Lunes',
            'work_frequency' => '1ro',
            'zone_number' => 1,
            'zone_name' => 'Logias de Ciudad Autónoma de Buenos Aires',
        ]);

        $this->fakePage(['GALILEO GALILEI Nro 115 – Trabaja Lunes 1ro en Av. Pavón 3918 CABA']);

        $diff = app(GlaSyncService::class)->preview();

        $this->assertCount(1, $diff['modified']);
        $this->assertSame(
            ['from' => 'Av. Pavón 3816', 'to' => 'Av. Pavón 3918'],
            $diff['modified'][0]['changes']['address']
        );
        $this->assertCount(1, $diff['modified'][0]['changes'], 'Only the address changed.');
    }

    public function test_applying_a_modification_does_not_null_out_unreported_fields(): void
    {
        $workshop = Workshop::factory()->create([
            'number' => 531,
            'address' => 'Alsina 1234',
            'city' => 'TIGRE',
            'province' => 'Buenos Aires',
            'language' => 'Español',
            'source_url' => GlaSyncService::SOURCE_URL,
        ]);

        $this->fakePage(['TIGRE Nro 531 – Trabaja Lunes Todos en TIGRE']);

        app(GlaSyncService::class)->applyModified([$workshop->id]);

        $workshop->refresh();

        $this->assertSame('TIGRE', $workshop->city);
        $this->assertSame('Buenos Aires', $workshop->province);
        $this->assertSame('Español', $workshop->language);
        $this->assertSame('TIGRE', $workshop->address);
    }

    public function test_it_detects_new_and_delisted_workshops(): void
    {
        Workshop::factory()->create([
            'number' => 700,
            'name' => 'YA NO FIGURA',
            'status' => 'active',
            'source_url' => GlaSyncService::SOURCE_URL,
        ]);

        $this->fakePage(['NUEVA LOGIA Nro 1254 – Trabaja Sábado 2do en Rivadavia 100 CABA']);

        $diff = app(GlaSyncService::class)->preview();

        $this->assertCount(1, $diff['new']);
        $this->assertSame(1254, $diff['new'][0]['number']);
        $this->assertCount(1, $diff['disabled']);
        $this->assertSame(700, $diff['disabled'][0]['number']);
    }
}
