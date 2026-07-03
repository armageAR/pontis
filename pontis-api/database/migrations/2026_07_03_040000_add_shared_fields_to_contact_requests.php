<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->json('shared_fields')->nullable()->after('message');
        });
    }

    public function down(): void {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropColumn('shared_fields');
        });
    }
};
