<?php

// database/migrations/xxxx_xx_xx_create_airports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('code', 10)->unique();
            $table->string('icao_code', 4)->unique()->nullable();
            $table->string('iata_code', 3)->unique()->nullable();
            $table->string('name', 191);
            $table->text('description')->nullable();

            // Location Details
            $table->string('address')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('timezone', 50)->default('Asia/Kolkata');

            // Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Contact Information
            $table->string('contact_email', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('website', 255)->nullable();

            // Branding & Settings
            $table->string('logo')->nullable();
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->json('settings')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('city');
            $table->index('country');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
