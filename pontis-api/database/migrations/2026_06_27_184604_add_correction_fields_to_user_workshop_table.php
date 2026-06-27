<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->text('correction_notes')->nullable()->after('user_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->dropColumn('correction_notes');
        });
    }
};
