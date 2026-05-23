<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kpi_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 50); // daily, weekly, monthly
            $table->date('report_date');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            // KPIs
            $table->integer('total_incidents')->default(0);
            $table->integer('open_incidents')->default(0);
            $table->integer('resolved_incidents')->default(0);
            $table->integer('closed_incidents')->default(0);
            $table->integer('escalated_incidents')->default(0);
            $table->integer('sla_breaches')->default(0);
            $table->decimal('avg_response_time_minutes', 10, 2)->default(0);
            $table->decimal('avg_resolution_time_minutes', 10, 2)->default(0);
            $table->decimal('sla_compliance_percentage', 5, 2)->default(0);

            $table->json('severity_distribution')->nullable();
            $table->json('category_distribution')->nullable();
            $table->json('hourly_distribution')->nullable();
            $table->json('additional_metrics')->nullable();

            $table->timestamps();

            $table->unique(['report_type', 'report_date', 'department_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_reports');
    }
};
