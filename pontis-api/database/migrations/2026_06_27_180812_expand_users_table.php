<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('dni', 20)->nullable()->after('last_name');
            $table->string('masonic_id', 50)->nullable()->after('dni');
            $table->date('birth_date')->nullable()->after('masonic_id');
            $table->date('initiation_date')->nullable()->after('birth_date');
            $table->string('masonic_status', 30)->nullable()->default('active')->after('initiation_date');
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('alternative_email')->nullable();
            $table->string('contact_preference', 50)->nullable();
            $table->string('country', 100)->nullable()->default('Argentina');
            $table->string('province', 100)->nullable();
            $table->string('locality', 100)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('profession')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->text('profession_description')->nullable();
            $table->text('bio')->nullable();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name','dni','masonic_id','birth_date','initiation_date','masonic_status',
                'phone','whatsapp','alternative_email','contact_preference',
                'country','province','locality','neighborhood','address',
                'profession','occupation','company','profession_description','bio']);
        });
    }
};
