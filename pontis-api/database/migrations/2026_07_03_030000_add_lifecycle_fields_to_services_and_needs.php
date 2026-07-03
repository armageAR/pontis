<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('published_at');
            $table->index('expires_at');
        });

        Schema::table('needs', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('published_at');
            $table->index('expires_at');
        });

        // Normalize legacy statuses to the lifecycle: draft, active, suspended, expired.
        DB::table('services')
            ->whereIn('status', ['paused', 'hidden', 'disabled', 'closed', 'cancelled'])
            ->update(['status' => 'suspended']);
        DB::table('services')
            ->whereIn('status', ['pending_authorization', 'requires_correction', 'rejected'])
            ->update(['status' => 'draft']);

        DB::table('needs')
            ->whereIn('status', ['open', 'searching', 'with_matches', 'contact_requested', 'linked'])
            ->update(['status' => 'active']);
        DB::table('needs')
            ->whereIn('status', ['closed', 'cancelled'])
            ->update(['status' => 'suspended']);
        DB::table('needs')
            ->whereIn('status', ['pending_authorization', 'requires_correction', 'rejected'])
            ->update(['status' => 'draft']);

        // Backfill dates for records that are active but have no validity window yet.
        // Publication date mirrors creation; expiration defaults to the 90-day maximum.
        foreach (['services', 'needs'] as $table) {
            DB::table($table)
                ->where('status', 'active')
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('created_at')]);
            DB::table($table)
                ->where('status', 'active')
                ->whereNull('expires_at')
                ->update(['expires_at' => Carbon::now()->addDays(90)]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['published_at', 'expires_at']);
        });

        Schema::table('needs', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['published_at', 'expires_at']);
        });
    }
};
