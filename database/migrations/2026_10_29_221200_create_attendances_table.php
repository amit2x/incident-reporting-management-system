<?php

// database/migrations/xxxx_xx_xx_create_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airport_id')->constrained('airports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave'])
                ->default('absent');
            $table->foreignId('marked_by')->constrained('users')->cascadeOnDelete();
            $table->enum('marked_by_role', ['self', 'nodal_officer', 'admin']);
            $table->timestamp('marked_at');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('location_verified')->default(false);
            $table->integer('distance_from_geofence')->nullable();
            $table->boolean('is_modified')->default(false);
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('modified_at')->nullable();
            $table->text('modification_reason')->nullable();
            $table->boolean('is_backfilled')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date'], 'uq_attendance_user_date');
            $table->index(['airport_id', 'attendance_date']);
            $table->index(['airport_id', 'attendance_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
