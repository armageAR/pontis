<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // The status column is already a string — no enum change needed.
        // This migration documents the accepted values for the relationship:
        // pending, active, correction_requested, rejected, inactive, suspended, ended, historical
        // No DDL change required for PostgreSQL string columns.
    }
    public function down(): void {}
};
