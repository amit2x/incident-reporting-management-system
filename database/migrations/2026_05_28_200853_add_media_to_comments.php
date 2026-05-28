<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_comments', function (Blueprint $table) {
            // Already has attachments column (JSON), ensure it exists
            if (!Schema::hasColumn('incident_comments', 'attachments')) {
                $table->json('attachments')->nullable()->after('mentions');
            }
            // Already has mentions column (JSON), ensure it exists
            if (!Schema::hasColumn('incident_comments', 'mentions')) {
                $table->json('mentions')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incident_comments', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'mentions']);
        });
    }
};
