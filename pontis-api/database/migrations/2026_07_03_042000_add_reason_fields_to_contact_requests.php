<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->string('reason_type', 60)->nullable()->after('shared_fields');
            $table->json('source_context')->nullable()->after('reason_type');
        });
    }

    public function down(): void {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropColumn(['reason_type', 'source_context']);
        });
    }
};
