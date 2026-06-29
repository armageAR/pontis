<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE services ALTER COLUMN status TYPE VARCHAR(30)');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE services MODIFY status VARCHAR(30) NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE services ALTER COLUMN status TYPE VARCHAR(20)');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE services MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");
        }
    }
};
