<?php

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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->dropColumn('employee_number');
            $table->dropColumn('department');
            $table->foreignId('user_id')->nullable()->after('business_category_id')
                ->constrained('users')->onDelete('set null');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->date('joining_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('joining_date')->nullable(false)->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('department')->after('business_category_id');
            $table->string('employee_number')->unique()->after('employee_id');
        });
    }
};
