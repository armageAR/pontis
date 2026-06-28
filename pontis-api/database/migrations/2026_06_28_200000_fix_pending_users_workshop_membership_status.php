<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Users registered before the verifying/pending state model fix were attached
        // to workshops with status='active' (the column default). For any user whose
        // account status is 'pending' or 'verifying', their workshop membership must
        // reflect that they are awaiting approval — not already active.
        DB::statement("
            UPDATE user_workshop
            SET status = 'pending', requested_by_user = true
            WHERE user_id IN (
                SELECT id FROM users WHERE status IN ('pending', 'verifying')
            )
            AND status = 'active'
        ");
    }

    public function down(): void
    {
        // Not reversible — we cannot know which rows were changed without a separate audit table.
    }
};
