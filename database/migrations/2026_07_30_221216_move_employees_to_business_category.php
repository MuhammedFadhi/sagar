<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('business_category_id')->nullable()->after('joining_date')
                ->constrained('business_categories')->onDelete('cascade');
        });

        DB::table('employees')->orderBy('id')->each(function ($employee) {
            $branch = DB::table('branches')->find($employee->branch_id);
            if ($branch) {
                DB::table('employees')->where('id', $employee->id)
                    ->update(['business_category_id' => $branch->business_category_id]);
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::dropIfExists('branches');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_category_id')->constrained('business_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('joining_date')
                ->constrained('branches')->onDelete('cascade');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['business_category_id']);
            $table->dropColumn('business_category_id');
        });
    }
};
