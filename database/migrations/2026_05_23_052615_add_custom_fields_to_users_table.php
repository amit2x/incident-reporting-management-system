<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('employee_id', 50)->unique()->nullable()->after('phone');
            $table->string('avatar', 255)->nullable()->after('employee_id');
            $table->foreignId('department_id')->nullable()->after('avatar')->constrained()->nullOnDelete();
            $table->string('designation', 100)->nullable()->after('department_id');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('designation');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->json('preferences')->nullable();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'employee_id', 'avatar',
                'department_id', 'designation', 'status',
                'last_login_at', 'last_login_ip', 'preferences'
            ]);
        });
    }
};
