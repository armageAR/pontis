<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requestee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('need_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->text('response_message')->nullable();
            $table->string('status', 30)->default('pending'); // pending, accepted, rejected, cancelled, expired, closed
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['requestee_id', 'status']);
            $table->index(['requester_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('contact_requests'); }
};
