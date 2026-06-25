<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->after('workshop_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
