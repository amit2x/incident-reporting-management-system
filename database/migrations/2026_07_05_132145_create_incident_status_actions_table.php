<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_status_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->onDelete('cascade');
            
            // Action type
            $table->enum('action', [
                'acknowledged', 
                'resolved', 
                'closed', 
                'rejected', 
                'reopened',
                'escalated',      // Added for consistency
                'assigned',       // Added for consistency
            ]);
            
            // Actor
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Status tracking
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            
            // Reason/Notes field - REQUIRED for most actions
            $table->text('reason')->nullable();
            
            // Additional metadata for specific actions
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamp('acted_at')->useCurrent();
            $table->timestamps();

            // Indexes for performance
            $table->index(['incident_id', 'action']);
            $table->index(['incident_id', 'acted_at']);
            $table->index(['user_id', 'action']);
            $table->index(['acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_status_actions');
    }
};