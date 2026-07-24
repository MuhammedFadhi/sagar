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
        // 1. Business Categories
        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 2. Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_category_id')->constrained('business_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        // 3. Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique(); // Custom unique ID (e.g., EMP-1001)
            $table->string('full_name');
            $table->string('arabic_name')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('nationality');
            $table->string('mobile_number');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            // Employment details
            $table->string('employee_number')->unique();
            $table->date('joining_date');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('department');
            $table->string('designation');
            $table->decimal('salary', 10, 2);
            $table->string('shift'); // Morning, Evening, etc.
            $table->string('employment_status')->default('Active'); // Active, Terminated, Resigned, On Leave
            
            $table->timestamps();
        });

        // 4. Employee Documents
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // iqama, passport, health_insurance, baladiya_card, driving_license, contract, medical, visa, other
            $table->string('document_number');
            $table->string('place_of_issue')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->json('additional_metadata')->nullable(); // insurance_company, policy_number, municipality, etc.
            $table->timestamps();
        });

        // 5. Leaves
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('leave_type'); // annual, emergency, medical, unpaid, casual, hajj, maternity
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 6. Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // 7. Company Settings
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('business_categories');
    }
};
