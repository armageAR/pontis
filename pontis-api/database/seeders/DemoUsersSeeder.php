<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    private const WORKSHOP_NUMBERS = [469, 730, 1, 2];

    private const USERS_PER_WORKSHOP = 15;

    public function run(): void
    {
        $workshops = Workshop::whereIn('number', self::WORKSHOP_NUMBERS)->get()->keyBy('number');

        $missing = collect(self::WORKSHOP_NUMBERS)->reject(fn ($n) => $workshops->has($n));
        if ($missing->isNotEmpty()) {
            $this->command->warn('Logias no encontradas con números: '.$missing->join(', '));
        }

        foreach ($workshops as $workshop) {
            $this->command->info("Creando usuarios para logia #{$workshop->number} — {$workshop->name}");

            $users = User::factory()->count(self::USERS_PER_WORKSHOP)->create();

            // First user of each workshop gets admin role in the pivot
            foreach ($users as $index => $user) {
                $role = $index === 0 ? 'admin' : 'member';
                $workshop->users()->attach($user->id, ['role' => $role]);
            }
        }

        $this->command->info('Demo users seeded: '.($workshops->count() * self::USERS_PER_WORKSHOP).' usuarios en '.$workshops->count().' logias.');
    }
}
