<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('authorized_by')->nullable()->after('user_id');
            $table->timestamp('authorized_at')->nullable()->after('authorized_by');
            $table->text('authorization_notes')->nullable()->after('authorized_at');
        });
        Schema::table('needs', function (Blueprint $table) {
            $table->unsignedBigInteger('authorized_by')->nullable()->after('user_id');
            $table->timestamp('authorized_at')->nullable()->after('authorized_by');
            $table->text('authorization_notes')->nullable()->after('authorized_at');
        });
    }
    public function down(): void {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['authorized_by', 'authorized_at', 'authorization_notes']);
        });
        Schema::table('needs', function (Blueprint $table) {
            $table->dropColumn(['authorized_by', 'authorized_at', 'authorization_notes']);
        });
    }
};
