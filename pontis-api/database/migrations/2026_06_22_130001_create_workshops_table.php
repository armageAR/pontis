<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->integer('zone_number')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('name');
            $table->integer('number')->unique();
            $table->string('work_day', 50)->nullable();
            $table->string('work_frequency', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->default('Argentina');
            $table->string('language', 100)->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('zone_number');
            $table->index('status');
            $table->index('name');
            $table->index('work_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
