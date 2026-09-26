<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the initial superadmin account.
 *
 * Credentials come from the SUPERADMIN_* environment variables (see
 * config/pontis.php). There is no hardcoded password: when SUPERADMIN_PASSWORD
 * is not set, a random one is generated and printed once, so a public
 * deployment can never end up with a well-known default.
 *
 * Existing accounts are never modified, so this is safe to run on every deploy.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = $this->setting('SUPERADMIN_EMAIL', 'email') ?: 'admin@example.com';

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Superadmin {$email} already exists, leaving it untouched.");

            return;
        }

        $password = $this->setting('SUPERADMIN_PASSWORD', 'password');
        $generated = $password === null || $password === '';

        if ($generated) {
            $password = Str::password(20, symbols: false);
        }

        User::create([
            'name' => $this->setting('SUPERADMIN_NAME', 'name') ?: 'Super Admin',
            'email' => $email,
            'password' => $password,
            'role' => 'superadmin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Superadmin created: {$email}");

        if ($generated) {
            $this->command?->warn("Generated password: {$password}");
            $this->command?->warn('Store it now — it is not shown again. Set SUPERADMIN_PASSWORD to choose your own.');
        }
    }

    /**
     * Read a setting from the real environment first, then from config.
     *
     * A cached config file is baked at build time, so a variable provided by the
     * host at run time would otherwise be ignored; conversely, when the config
     * is cached the .env file is never loaded, so env() alone is not enough
     * either. Checking both covers every combination.
     */
    private function setting(string $envKey, string $configKey): ?string
    {
        $value = env($envKey);

        if ($value === null || $value === '') {
            $value = config("pontis.superadmin.{$configKey}");
        }

        return $value === null ? null : (string) $value;
    }
}
