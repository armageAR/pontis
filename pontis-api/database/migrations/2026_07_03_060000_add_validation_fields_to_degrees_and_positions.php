<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_degrees', function (Blueprint $table) {
            $table->string('validation_status', 30)->default('validated')->after('notes');
            $table->foreignId('validator_id')->nullable()->after('validation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validator_id');
            $table->text('validation_notes')->nullable()->after('validated_at');
        });

        Schema::table('user_positions', function (Blueprint $table) {
            $table->string('validation_status', 30)->default('validated')->after('notes');
            $table->foreignId('validator_id')->nullable()->after('validation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validator_id');
            $table->text('validation_notes')->nullable()->after('validated_at');
        });

        DB::table('user_degrees')->update(['validation_status' => 'validated', 'validated_at' => DB::raw('created_at')]);
        DB::table('user_positions')->update(['validation_status' => 'validated', 'validated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('user_degrees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validator_id');
            $table->dropColumn(['validation_status', 'validated_at', 'validation_notes']);
        });
        Schema::table('user_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validator_id');
            $table->dropColumn(['validation_status', 'validated_at', 'validation_notes']);
        });
    }
};
