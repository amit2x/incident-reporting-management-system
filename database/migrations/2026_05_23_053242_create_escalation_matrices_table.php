<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('escalation_matrices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('incident_categories')->cascadeOnDelete();
            $table->integer('level');
            $table->integer('timeout_minutes');
            $table->foreignId('escalate_to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('escalate_to_department_id')->constrained('departments')->cascadeOnDelete();
            $table->boolean('notify_via_email')->default(true);
            $table->boolean('notify_via_push')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'category_id', 'level']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('escalation_matrices');
    }
};
