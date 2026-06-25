<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('role');
            $table->boolean('requested_by_user')->default(false)->after('status');
            $table->timestamp('user_seen_at')->nullable()->after('requested_by_user');
        });
    }

    public function down(): void
    {
        Schema::table('user_workshop', function (Blueprint $table) {
            $table->dropColumn(['status', 'requested_by_user', 'user_seen_at']);
        });
    }
};
