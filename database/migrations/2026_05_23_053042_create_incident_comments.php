<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incident_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('incident_comments')->cascadeOnDelete();
            $table->text('content');
            $table->json('mentions')->nullable(); // User IDs mentioned in comment
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false); // Internal notes
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['incident_id', 'created_at']);
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('incident_comments');
    }
};
