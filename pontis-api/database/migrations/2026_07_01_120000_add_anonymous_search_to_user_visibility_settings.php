<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preferencia independiente: aparecer en búsquedas sin revelar identidad.
        // Solo tiene sentido para el bloque "identity", pero se guarda en la
        // misma fila para mantener audiencia y flag colocados.
        Schema::table('user_visibility_settings', function (Blueprint $table) {
            $table->boolean('anonymous_search')->default(false)->after('visibility');
        });

        // Compatibilidad de datos: los perfiles que tenían Identidad en
        // "anonymous" pasan a tener el flag activo y una audiencia conservadora
        // (workshop) para no ampliar la exposición de identidad.
        DB::table('user_visibility_settings')
            ->where('block', 'identity')
            ->where('visibility', 'anonymous')
            ->update([
                'anonymous_search' => true,
                'visibility'       => 'workshop',
            ]);
    }

    public function down(): void
    {
        Schema::table('user_visibility_settings', function (Blueprint $table) {
            $table->dropColumn('anonymous_search');
        });
    }
};
