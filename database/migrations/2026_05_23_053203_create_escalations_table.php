<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escalated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('escalated_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('to_department_id')->constrained('departments')->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->text('reason');
            $table->text('response')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['incident_id', 'status']);
            $table->index('escalated_to');
        });
    }

    public function down()
    {
        Schema::dropIfExists('escalations');
    }
};
