<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('modality', 30)->default('both'); // presencial, remoto, both
            $table->string('location')->nullable();
            $table->text('availability')->nullable();
            $table->text('conditions')->nullable();
            $table->string('visibility', 30)->default('private'); // private, workshop, my_workshops, registered, anonymous, public
            $table->string('status', 20)->default('draft'); // draft, active, paused, hidden, disabled
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'visibility']);
        });
    }
    public function down(): void { Schema::dropIfExists('services'); }
};
