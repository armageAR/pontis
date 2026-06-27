<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('urgency', 20)->nullable(); // low, medium, high
            $table->string('visibility', 30)->default('private');
            $table->string('status', 30)->default('draft'); // draft, open, searching, with_matches, contact_requested, linked, closed, cancelled
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('needs'); }
};
