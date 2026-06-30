<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->boolean('is_principal')->default(false)->after('workshop_id');
        });

        // Backfill: para cada usuario, marcar como principal su membresía más
        // antigua (preferentemente activa). Aproxima el taller de registro.
        $userIds = DB::table('user_workshop')->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $principal = DB::table('user_workshop')
                ->where('user_id', $userId)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->first();
            if ($principal) {
                DB::table('user_workshop')->where('id', $principal->id)->update(['is_principal' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->dropColumn('is_principal');
        });
    }
};
