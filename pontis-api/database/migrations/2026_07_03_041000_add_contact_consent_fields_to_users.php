<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->json('contact_default_shared_fields')->nullable()->after('contact_preference');
            $table->json('contact_preferred_channels')->nullable()->after('contact_default_shared_fields');
            $table->json('contact_allowed_sources')->nullable()->after('contact_preferred_channels');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'contact_default_shared_fields',
                'contact_preferred_channels',
                'contact_allowed_sources',
            ]);
        });
    }
};
