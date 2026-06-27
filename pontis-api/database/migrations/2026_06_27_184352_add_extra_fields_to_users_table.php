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
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_url')->nullable()->after('bio');
            $table->string('linkedin')->nullable()->after('photo_url');
            $table->string('website')->nullable()->after('linkedin');
            $table->string('facebook')->nullable()->after('website');
            $table->string('instagram')->nullable()->after('facebook');
            $table->text('availability_notes')->nullable()->after('instagram');
            $table->text('admin_notes')->nullable()->after('availability_notes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['photo_url','linkedin','website','facebook','instagram','availability_notes','admin_notes']);
        });
    }
};
