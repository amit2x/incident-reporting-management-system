<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incident_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->default('#6B7280');
            $table->foreignId('parent_id')->nullable()->constrained('incident_categories')->nullOnDelete();
            $table->integer('default_priority')->default(2); // 1=Low, 2=Medium, 3=High, 4=Critical
            $table->integer('sla_minutes')->default(120);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('required_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('incident_categories');
    }
};
