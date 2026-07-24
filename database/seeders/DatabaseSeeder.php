<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BusinessCategory;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Leave;
use App\Models\CompanySetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles & Users
        $admin = User::create([
            'name' => 'HR Administrator',
            'email' => 'admin@hr.sa',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Portal User',
            'email' => 'user@hr.sa',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // 2. Seed Business Categories
        $restaurantCat = BusinessCategory::create(['name' => 'Restaurant']);
        $boofiyaCat = BusinessCategory::create(['name' => 'Boofiya']);
        $cafeCat = BusinessCategory::create(['name' => 'Cafe']);

        // 3. Seed Branches
        $branches = [
            'Restaurant' => ['Restaurant 1', 'Restaurant 2', 'Restaurant 3', 'Restaurant 4'],
            'Boofiya' => ['Boofiya 1'],
            'Cafe' => ['Cafe 1'],
        ];

        $restaurantBranches = [];
        foreach ($branches['Restaurant'] as $name) {
            $restaurantBranches[] = Branch::create([
                'business_category_id' => $restaurantCat->id,
                'name' => $name,
                'code' => 'BR-' . strtoupper(substr($name, 0, 3)) . rand(10, 99)
            ]);
        }

        $boofiyaBranches = [];
        foreach ($branches['Boofiya'] as $name) {
            $boofiyaBranches[] = Branch::create([
                'business_category_id' => $boofiyaCat->id,
                'name' => $name,
                'code' => 'BR-' . strtoupper(substr($name, 0, 3)) . rand(10, 99)
            ]);
        }

        $cafeBranches = [];
        foreach ($branches['Cafe'] as $name) {
            $cafeBranches[] = Branch::create([
                'business_category_id' => $cafeCat->id,
                'name' => $name,
                'code' => 'BR-' . strtoupper(substr($name, 0, 3)) . rand(10, 99)
            ]);
        }

        // 4. Seed Company Settings
        CompanySetting::create(['key' => 'company_name', 'value' => 'Saudi Premium Hospitality Group']);
        CompanySetting::create(['key' => 'flight_ticket_policy_months', 'value' => '24']); // 2 years
        CompanySetting::create(['key' => 'reminder_days', 'value' => '90,60,30,15,7,1,0']);

        // 5. Seed Mock Employees
        // Today is July 6, 2026.
        // Employee 1: Ahmad Al-Harbi (Saudi Arabia, Restaurant 1, Joined Jan 10, 2024 - worked > 2 years, eligible for ticket)
        $emp1 = Employee::create([
            'employee_id' => 'EMP-001',
            'full_name' => 'Ahmad Al-Harbi',
            'arabic_name' => 'أحمد الحربي',
            'profile_photo' => null,
            'gender' => 'Male',
            'date_of_birth' => '1990-05-15',
            'nationality' => 'Saudi Arabia',
            'mobile_number' => '+966501234567',
            'email' => 'ahmad.harbi@example.com',
            'address' => 'Olaya Road, Riyadh, KSA',
            'emergency_contact_name' => 'Khalid Al-Harbi',
            'emergency_contact_phone' => '+966509876543',
            'employee_number' => 'E10001',
            'joining_date' => '2024-01-10',
            'branch_id' => $restaurantBranches[0]->id,
            'department' => 'Kitchen',
            'designation' => 'Head Chef',
            'salary' => 8500.00,
            'shift' => 'Morning',
            'employment_status' => 'Active',
        ]);

        // Employee 1 Documents
        EmployeeDocument::create([
            'employee_id' => $emp1->id,
            'type' => 'Iqama Details',
            'document_number' => '1029384756',
            'place_of_issue' => 'Riyadh',
            'issue_date' => '2024-01-10',
            'expiry_date' => '2027-01-10', // Valid
        ]);
        
        EmployeeDocument::create([
            'employee_id' => $emp1->id,
            'type' => 'Passport Details',
            'document_number' => 'P982374',
            'place_of_issue' => 'Riyadh',
            'issue_date' => '2020-05-10',
            'expiry_date' => '2030-05-10', // Valid
        ]);

        EmployeeDocument::create([
            'employee_id' => $emp1->id,
            'type' => 'Baladiya Card',
            'document_number' => 'B22394',
            'place_of_issue' => 'Riyadh Municipality',
            'issue_date' => '2025-08-10',
            'expiry_date' => '2026-08-10', // Expiring in ~35 days! (Expiring soon)
        ]);

        EmployeeDocument::create([
            'employee_id' => $emp1->id,
            'type' => 'Health Insurance',
            'document_number' => 'POL-9872',
            'place_of_issue' => 'Bupa Arabia',
            'issue_date' => '2025-07-01',
            'expiry_date' => '2026-07-01', // Already expired! (July 6, 2026 is today)
            'additional_metadata' => [
                'insurance_company' => 'Bupa Arabia',
                'policy_number' => 'POL-9872'
            ]
        ]);

        // Employee 2: Muhammad Khan (Pakistan, Cafe 1, Joined May 1, 2024, had a Medical Leave that paused countdown)
        $emp2 = Employee::create([
            'employee_id' => 'EMP-002',
            'full_name' => 'Muhammad Khan',
            'arabic_name' => 'محمد خان',
            'profile_photo' => null,
            'gender' => 'Male',
            'date_of_birth' => '1993-08-20',
            'nationality' => 'Pakistan',
            'mobile_number' => '+966559998888',
            'email' => 'muhammad.khan@example.com',
            'address' => 'Takhassusi Street, Riyadh, KSA',
            'emergency_contact_name' => 'Yasmin Khan',
            'emergency_contact_phone' => '+923001234567',
            'employee_number' => 'E10002',
            'joining_date' => '2024-05-01', // Worked service around 797 calendar days. Subtracting 30 days leave = 767. Eligible!
            'branch_id' => $cafeBranches[0]->id,
            'department' => 'Service',
            'designation' => 'Senior Barista',
            'salary' => 4200.00,
            'shift' => 'Evening',
            'employment_status' => 'Active',
        ]);

        // Employee 2 Documents
        EmployeeDocument::create([
            'employee_id' => $emp2->id,
            'type' => 'Iqama Details',
            'document_number' => '2039485761',
            'place_of_issue' => 'Riyadh',
            'issue_date' => '2024-05-01',
            'expiry_date' => '2026-07-20', // Expiring in 14 days! (Expiring soon)
        ]);

        EmployeeDocument::create([
            'employee_id' => $emp2->id,
            'type' => 'Passport Details',
            'document_number' => 'P12984',
            'place_of_issue' => 'Karachi',
            'issue_date' => '2019-01-01',
            'expiry_date' => '2029-01-01', // Valid
        ]);

        // Employee 2 Pausing Leave
        Leave::create([
            'employee_id' => $emp2->id,
            'leave_type' => 'Medical Leave',
            'start_date' => '2025-10-10',
            'end_date' => '2025-11-09', // 31 days. Pauses flight ticket eligibility countdown!
            'status' => 'Approved',
            'reason' => 'Recovery from surgery',
            'approved_by' => $admin->id
        ]);

        // Employee 3: Sarah Smith (UK, Cafe 1, Joined July 10, 2025 - joined < 1 year ago)
        $emp3 = Employee::create([
            'employee_id' => 'EMP-003',
            'full_name' => 'Sarah Smith',
            'arabic_name' => 'سارة سميث',
            'profile_photo' => null,
            'gender' => 'Female',
            'date_of_birth' => '1995-11-12',
            'nationality' => 'United Kingdom',
            'mobile_number' => '+966522223333',
            'email' => 'sarah.smith@example.com',
            'address' => 'King Abdullah District, Riyadh, KSA',
            'emergency_contact_name' => 'John Smith',
            'emergency_contact_phone' => '+447712345678',
            'employee_number' => 'E10003',
            'joining_date' => '2025-07-10', // 1 year away from ticket eligibility (around 361 days left)
            'branch_id' => $cafeBranches[0]->id,
            'department' => 'Management',
            'designation' => 'Store Manager',
            'salary' => 12000.00,
            'shift' => 'Morning',
            'employment_status' => 'Active',
        ]);

        // Employee 3 Documents
        EmployeeDocument::create([
            'employee_id' => $emp3->id,
            'type' => 'Iqama Details',
            'document_number' => '1092837465',
            'place_of_issue' => 'Jeddah',
            'issue_date' => '2025-07-10',
            'expiry_date' => '2026-07-12', // Expiring in 6 days! (Expiring soon)
        ]);

        EmployeeDocument::create([
            'employee_id' => $emp3->id,
            'type' => 'Passport Details',
            'document_number' => 'P55421',
            'place_of_issue' => 'London',
            'issue_date' => '2021-02-15',
            'expiry_date' => '2031-02-15',
        ]);

        EmployeeDocument::create([
            'employee_id' => $emp3->id,
            'type' => 'Saudi Driving License',
            'document_number' => 'DL-99221',
            'place_of_issue' => 'Riyadh Traffic Dept',
            'issue_date' => '2025-08-01',
            'expiry_date' => '2030-08-01', // Valid
        ]);
    }
}
