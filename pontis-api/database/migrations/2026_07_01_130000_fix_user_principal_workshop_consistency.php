<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige inconsistencias de Taller principal antes de exponer la gestión
     * desde el perfil: cada usuario debe tener a lo sumo un `is_principal`
     * activo, y al menos uno si tiene membresías activas. Además, ninguna
     * membresía no activa debe quedar marcada como principal.
     */
    public function up(): void
    {
        $userIds = DB::table('user_workshop')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            // Membresías no activas nunca deben ser principal.
            DB::table('user_workshop')
                ->where('user_id', $userId)
                ->where('status', '!=', 'active')
                ->where('is_principal', true)
                ->update(['is_principal' => false]);

            $activePrincipalIds = DB::table('user_workshop')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('is_principal', true)
                ->orderBy('id')
                ->pluck('id');

            if ($activePrincipalIds->count() > 1) {
                // Conservar el primero, desmarcar el resto.
                DB::table('user_workshop')
                    ->whereIn('id', $activePrincipalIds->slice(1)->values()->all())
                    ->update(['is_principal' => false]);
            } elseif ($activePrincipalIds->isEmpty()) {
                // Sin principal: marcar la primera membresía activa, si existe.
                $firstActiveId = DB::table('user_workshop')
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->value('id');

                if ($firstActiveId) {
                    DB::table('user_workshop')->where('id', $firstActiveId)->update(['is_principal' => true]);
                }
            }
        }
    }

    public function down(): void
    {
        // Corrección de datos: no reversible.
    }
};
