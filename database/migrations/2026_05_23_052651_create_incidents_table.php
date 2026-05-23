<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_id', 20)->unique(); // Auto-generated like INC-2026-0001
            $table->string('title', 255);
            $table->text('description');
            $table->foreignId('category_id')->constrained('incident_categories')->cascadeOnDelete();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'open', 'acknowledged', 'in_progress', 'escalated',
                'resolved', 'closed', 'rejected'
            ])->default('open');

            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('location', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamp('sla_due_at')->nullable();
            $table->integer('sla_breach_count')->default(0);

            $table->text('resolution_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_anonymous')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['status', 'severity', 'priority']);
            $table->index(['department_id', 'assigned_to']);
            $table->index(['created_at', 'status']);
            $table->index('incident_id');
            $table->fullText(['title', 'description']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('incidents');
    }
};
