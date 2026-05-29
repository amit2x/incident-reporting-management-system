<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escalations', function (Blueprint $table) {
            // Already has 'response' and 'status' columns from earlier
            if (!Schema::hasColumn('escalations', 'responded_by')) {
                $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('escalations', 'response_type')) {
                $table->string('response_type', 50)->nullable()->comment('accepted, rejected, returned, reassigned');
            }
        });
    }

    public function down(): void
    {
        Schema::table('escalations', function (Blueprint $table) {
            $table->dropForeign(['responded_by']);
            $table->dropColumn(['responded_by', 'response_type']);
        });
    }
};
