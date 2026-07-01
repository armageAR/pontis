<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_visibility_settings')
            ->where('visibility', 'talleres_seleccionados')
            ->update(['visibility' => 'my_workshops']);

        DB::table('services')
            ->where('visibility', 'talleres_seleccionados')
            ->update(['visibility' => 'my_workshops']);

        DB::table('needs')
            ->where('visibility', 'talleres_seleccionados')
            ->update(['visibility' => 'my_workshops']);
    }

    public function down(): void
    {
        // Irreversible without knowing which records originally used the old option.
    }
};
