<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incident_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video', 'document', 'audio']);
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->string('thumbnail_path', 500)->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // EXIF data, duration, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['incident_id', 'media_type']);
            $table->index('uploaded_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('incident_media');
    }
};
