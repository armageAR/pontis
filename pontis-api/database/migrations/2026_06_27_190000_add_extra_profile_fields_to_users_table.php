<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_fixed', 30)->nullable()->after('phone');
            $table->text('secondary_activities')->nullable()->after('profession_description');
            $table->text('knowledge_areas')->nullable()->after('secondary_activities');
            $table->text('certifications')->nullable()->after('knowledge_areas');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_fixed', 'secondary_activities', 'knowledge_areas', 'certifications']);
        });
    }
};
