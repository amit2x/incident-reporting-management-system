<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {

            $table->id();

            $table->string('platform')->default('x');

            $table->text('content');

            $table->string('author')->nullable();

            $table->string('keyword')->nullable();

            $table->string('post_url')->nullable();

            $table->timestamp('posted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
