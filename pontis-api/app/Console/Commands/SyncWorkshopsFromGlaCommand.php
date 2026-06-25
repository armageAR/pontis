<?php

namespace App\Console\Commands;

use App\Models\Workshop;
use App\Services\GlaSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncWorkshopsFromGlaCommand extends Command
{
    protected $signature = 'workshops:sync-from-gla {--dry-run : Preview changes without saving}';

    protected $description = 'Sync workshops from the public GLA website';

    public function __construct(private GlaSyncService $sync)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching source page...');

        try {
            if ($this->option('dry-run')) {
                $diff = $this->sync->preview();

                $this->info('New workshops: ' . count($diff['new']));
                $this->table(
                    ['Zona', 'Nro', 'Nombre', 'Día', 'Ciudad'],
                    collect($diff['new'])->map(fn (array $w) => [
                        $w['zone_number'],
                        $w['number'],
                        Str::limit($w['name'], 30),
                        $w['work_day'] ?? '—',
                        $w['city'] ?? '—',
                    ])->toArray()
                );

                $this->info('Modified workshops: ' . count($diff['modified']));
                foreach ($diff['modified'] as $m) {
                    $this->line("  Nro {$m['number']} — {$m['name']}");
                    foreach ($m['changes'] as $field => $change) {
                        $this->line("    {$field}: {$change['from']} → {$change['to']}");
                    }
                }

                $this->info('To disable: ' . count($diff['disabled']));
                foreach ($diff['disabled'] as $d) {
                    $this->line("  Nro {$d['number']} — {$d['name']}");
                }

                return self::SUCCESS;
            }

            $parsed = $this->sync->fetch();

            if (empty($parsed)) {
                $this->warn('No workshops parsed.');
                return self::FAILURE;
            }

            $syncedNumbers = [];

            foreach ($parsed as $data) {
                $syncedNumbers[] = $data['number'];
                Workshop::updateOrCreate(['number' => $data['number']], $data);
            }

            $disabled = Workshop::whereNotIn('number', $syncedNumbers)
                ->whereNotNull('source_url')
                ->where('status', 'active')
                ->update(['status' => 'disabled']);

            $this->info('Synced ' . count($parsed) . " workshops. Disabled {$disabled} stale entries.");

            return self::SUCCESS;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
