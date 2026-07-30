<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\BusinessCategory;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Leave;
use App\Models\CompanySetting;
use App\Models\ActivityLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the main SPA index view.
     */
    public function index(Request $request, $page = 'dashboard')
    {
        $data = ['page' => $page];
        
        if (Auth::check()) {
            $data['masterData'] = json_decode(json_encode($this->getMasterData()->getData()->data ?? null), true);
            $data['stats'] = json_decode(json_encode($this->getStats()->getData()->stats ?? null), true);
            
            if ($page === 'branches') {
                $data['categories'] = $this->getCategories()->getData()->categories ?? null;
            } elseif ($page === 'employees') {
                $data['categories'] = $this->getCategories()->getData()->categories ?? null;
                $data['employees'] = $this->getEmployees($request)->getData()->employees ?? null;
            } elseif ($page === 'expiries') {
                $data['expiringDocs'] = $this->getExpiringDocuments($request)->getData()->documents ?? null;
            } elseif ($page === 'leaves') {
                $data['employees'] = $this->getEmployees($request)->getData()->employees ?? null;
                $data['leaves'] = $this->getLeaves($request)->getData()->leaves ?? null;
            } elseif ($page === 'tickets') {
                $data['ticketEmployees'] = json_decode(json_encode($this->getTicketEligibility($request)->getData()->employees ?? null), true);
                $data['ticketStats'] = json_decode(json_encode($this->getTicketEligibility($request)->getData()->stats ?? null), true);
            } elseif ($page === 'users' && Auth::user()->role === 'admin') {
                $data['sysUsers'] = $this->getUsers($request)->getData()->users ?? null;
            } elseif ($page === 'notifications' && Auth::user()->role === 'admin') {
                $data['notifAlerts'] = $this->getNotificationAlerts()->getData() ?? null;
            } elseif ($page === 'logs' && Auth::user()->role === 'admin') {
                $data['logs'] = $this->getActivityLogs()->getData()->logs ?? null;
            } elseif ($page === 'settings' && Auth::user()->role === 'admin') {
                $data['settings'] = $this->getSettings()->getData()->settings ?? null;
            }
        }

        return view('index', $data);
    }

    /**
     * AJAX Login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            ActivityLog::log('Login', 'User logged in successfully.');

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile_photo' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'The provided credentials do not match our records.',
        ], 422);
    }

    /**
     * AJAX Logout
     */
    public function logout(Request $request)
    {
        ActivityLog::log('Logout', 'User logged out.');
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Current Profile Details
     */
    public function getCurrentUser()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_photo' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
            ]
        ]);
    }

    /**
     * Update Current Profile (Phase 1)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'profile_photo_file' => 'nullable|image|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo_file')) {
            $path = $request->file('profile_photo_file')->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }

        $user->save();
        
        ActivityLog::log('Update Profile', 'Updated profile information.');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_photo' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
            ]
        ]);
    }

    /**
     * Get Settings (Phase 1 & 12)
     */
    public function getSettings()
    {
        return response()->json([
            'success' => true,
            'settings' => [
                'company_name' => CompanySetting::getVal('company_name', 'Saudi HR Portal'),
                'flight_ticket_policy_months' => CompanySetting::getVal('flight_ticket_policy_months', '24'),
                'reminder_days' => CompanySetting::getVal('reminder_days', '90,60,30,15,7,1,0'),
            ]
        ]);
    }

    /**
     * Update Settings (Phase 1 & 12)
     */
    public function updateSettings(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'flight_ticket_policy_months' => 'required|integer|min:1',
        ]);

        CompanySetting::setVal('company_name', $request->company_name);
        CompanySetting::setVal('flight_ticket_policy_months', $request->flight_ticket_policy_months);
        
        ActivityLog::log('Update Settings', 'Updated company settings.');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get Activity Logs (Phase 1)
     */
    public function getActivityLogs()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user ? $log->user->name : 'System',
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'time' => $log->created_at->format('Y-m-d H:i:s'),
                    'time_diff' => $log->created_at->diffForHumans()
                ];
            })
        ]);
    }

    /**
     * Get Dashboard Stats (Phase 8 & 5 & 7)
     */
    public function getStats()
    {
        $today = Carbon::today();
        
        // 1. Employee status counts
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('employment_status', 'Active')->count();
        $onLeaveEmployees = Employee::where('employment_status', 'On Leave')->count();
        $totalCategories = BusinessCategory::count();

        // 2. Document Expiries (Phase 5 Expiry management)
        $documents = EmployeeDocument::all();
        
        $iqamaExpiring = 0;
        $passportExpiring = 0;
        $insuranceExpiring = 0;
        $baladiyaExpiring = 0;
        $drivingExpiring = 0;

        foreach ($documents as $doc) {
            if ($doc->status === 'Expired' || $doc->status === 'Expiring Soon') {
                switch ($doc->type) {
                    case 'Iqama Details':
                        $iqamaExpiring++;
                        break;
                    case 'Passport Details':
                        $passportExpiring++;
                        break;
                    case 'Health Insurance':
                        $insuranceExpiring++;
                        break;
                    case 'Baladiya Card':
                        $baladiyaExpiring++;
                        break;
                    case 'Saudi Driving License':
                        $drivingExpiring++;
                        break;
                }
            }
        }

        // 3. Flight Ticket Eligibility
        $employees = Employee::all();
        $eligibleThisMonth = 0;
        $eligibleNext30 = 0;
        $eligibleNext60 = 0;
        $overdue = 0;
        $delayedDueToLeave = 0;

        foreach ($employees as $emp) {
            $status = $emp->ticket_status;
            $daysLeft = $emp->ticket_eligibility_days_left;

            if ($status === 'N/A') {
                continue; // no joining date set yet, exclude from eligibility counts
            }

            if ($status === 'Eligible') {
                $eligibleThisMonth++;
            } elseif ($status === 'Overdue') {
                $overdue++;
            } elseif ($daysLeft <= 30) {
                $eligibleNext30++;
            } elseif ($daysLeft <= 60) {
                $eligibleNext60++;
            }

            if ($emp->paused_days > 0 && $emp->ticket_status !== 'Eligible') {
                $delayedDueToLeave++;
            }
        }

        // 4. Charts data
        // Employees by Business Category
        $businessCounts = Employee::join('business_categories', 'employees.business_category_id', '=', 'business_categories.id')
            ->selectRaw('business_categories.name as business_name, count(employees.id) as count')
            ->groupBy('business_categories.id', 'business_name')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->business_name,
                    'count' => $item->count
                ];
            });

        // Employees by Nationality
        $nationalityCounts = Employee::selectRaw('nationality, count(*) as count')
            ->groupBy('nationality')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->nationality,
                    'count' => $item->count
                ];
            });

        return response()->json([
            'success' => true,
            'stats' => [
                'employees' => [
                    'total'      => $totalEmployees,
                    'active'     => $activeEmployees,
                    'on_leave'   => $onLeaveEmployees,
                    'categories' => $totalCategories
                ],
                'expiries' => [
                    'iqama'    => $iqamaExpiring,
                    'passport' => $passportExpiring,
                    'insurance'=> $insuranceExpiring,
                    'baladiya' => $baladiyaExpiring,
                    'driving'  => $drivingExpiring,
                    'total'    => $iqamaExpiring + $passportExpiring + $insuranceExpiring + $baladiyaExpiring + $drivingExpiring
                ],
                'tickets' => [
                    'eligible_now' => $eligibleThisMonth,
                    'eligible_30'  => $eligibleNext30,
                    'eligible_60'  => $eligibleNext60,
                    'overdue'      => $overdue,
                    'delayed'      => $delayedDueToLeave
                ],
                'charts' => [
                    'by_business'    => ['labels' => $businessCounts->pluck('label')->values()->all(), 'data' => $businessCounts->pluck('count')->values()->all()],
                    'by_nationality' => ['labels' => $nationalityCounts->pluck('label')->values()->all(), 'data' => $nationalityCounts->pluck('count')->values()->all()],
                ]
            ]
        ]);
    }

    /**
     * Get all categories with employee counts
     */
    public function getCategories()
    {
        $categories = BusinessCategory::withCount('employees')->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Store a new business category
     */
    public function storeCategory(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized action'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:business_categories,name',
        ]);

        $category = BusinessCategory::create([
            'name' => $request->name
        ]);

        ActivityLog::log('Create Category', "Created business category: {$category->name}");

        return response()->json([
            'success' => true,
            'message' => 'Business category created successfully',
            'category' => $category
        ]);
    }

    /**
     * Delete a business category
     */
    public function deleteCategory($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized action'], 403);
        }

        $category = BusinessCategory::findOrFail($id);
        $name = $category->name;
        $category->delete();

        ActivityLog::log('Delete Category', "Deleted business category: {$name}");

        return response()->json([
            'success' => true,
            'message' => 'Business category deleted successfully'
        ]);
    }

    // ========== PHASE 3: EMPLOYEE MANAGEMENT ==========

    public function getEmployees(Request $request)
    {
        $query = Employee::with(['businessCategory'])->withCount('documents', 'leaves');

        if ($request->has('business_category_id') && $request->business_category_id) {
            $query->where('business_category_id', $request->business_category_id);
        }
        if ($request->has('status') && $request->status) {
            $query->where('employment_status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhere('mobile_number', 'like', "%{$s}%");
            });
        }

        $employees = $query->orderBy('full_name')->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'employee_id' => $emp->employee_id,
                'full_name' => $emp->full_name,
                'arabic_name' => $emp->arabic_name,
                'profile_photo' => $emp->profile_photo ? asset('storage/' . $emp->profile_photo) : null,
                'gender' => $emp->gender,
                'date_of_birth' => $emp->date_of_birth?->format('Y-m-d'),
                'nationality' => $emp->nationality,
                'mobile_number' => $emp->mobile_number,
                'email' => $emp->email,
                'address' => $emp->address,
                'emergency_contact_name' => $emp->emergency_contact_name,
                'emergency_contact_phone' => $emp->emergency_contact_phone,
                'joining_date' => $emp->joining_date?->format('Y-m-d'),
                'business_category_id' => $emp->business_category_id,
                'business_category' => $emp->businessCategory ? $emp->businessCategory->name : null,
                'has_login' => (bool) $emp->user_id,
                'designation' => $emp->designation,
                'salary' => $emp->salary,
                'shift' => $emp->shift,
                'employment_status' => $emp->employment_status,
                'documents_count' => $emp->documents_count,
                'leaves_count' => $emp->leaves_count,
                'ticket_status' => $emp->ticket_status,
                'working_service_days' => $emp->working_service_days,
                'ticket_eligibility_days_left' => $emp->ticket_eligibility_days_left,
            ];
        });

        return response()->json(['success' => true, 'employees' => $employees]);
    }

    public function getEmployee($id)
    {
        $emp = Employee::with(['businessCategory', 'user', 'documents', 'leaves.approver'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $emp->id,
                'employee_id' => $emp->employee_id,
                'full_name' => $emp->full_name,
                'arabic_name' => $emp->arabic_name,
                'profile_photo' => $emp->profile_photo ? asset('storage/' . $emp->profile_photo) : null,
                'gender' => $emp->gender,
                'date_of_birth' => $emp->date_of_birth?->format('Y-m-d'),
                'nationality' => $emp->nationality,
                'mobile_number' => $emp->mobile_number,
                'email' => $emp->email,
                'address' => $emp->address,
                'emergency_contact_name' => $emp->emergency_contact_name,
                'emergency_contact_phone' => $emp->emergency_contact_phone,
                'joining_date' => $emp->joining_date?->format('Y-m-d'),
                'business_category_id' => $emp->business_category_id,
                'business_category' => $emp->businessCategory ? $emp->businessCategory->name : null,
                'has_login' => (bool) $emp->user_id,
                'login_email' => $emp->user ? $emp->user->email : null,
                'designation' => $emp->designation,
                'salary' => $emp->salary,
                'shift' => $emp->shift,
                'employment_status' => $emp->employment_status,
                'ticket_status' => $emp->ticket_status,
                'working_service_days' => $emp->working_service_days,
                'paused_days' => $emp->paused_days,
                'ticket_eligibility_days_left' => $emp->ticket_eligibility_days_left,
                'ticket_eligibility_date' => $emp->ticket_eligibility_date?->format('Y-m-d'),
                'documents' => $emp->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->type,
                        'document_number' => $doc->document_number,
                        'place_of_issue' => $doc->place_of_issue,
                        'issue_date' => $doc->issue_date?->format('Y-m-d') ?? null,
                        'expiry_date' => $doc->expiry_date?->format('Y-m-d') ?? null,
                        'file_path' => $doc->file_path ? asset('storage/' . $doc->file_path) : null,
                        'status' => $doc->status,
                        'days_until_expiry' => $doc->days_until_expiry,
                        'additional_metadata' => $doc->additional_metadata,
                    ];
                }),
                'leaves' => $emp->leaves->map(function ($leave) {
                    return [
                        'id' => $leave->id,
                        'leave_type' => $leave->leave_type,
                        'start_date' => $leave->start_date,
                        'end_date' => $leave->end_date,
                        'status' => $leave->status,
                        'reason' => $leave->reason,
                        'duration_days' => \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1,
                        'approved_by_name' => $leave->approver ? $leave->approver->name : null,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Auto-generate the next sequential Employee ID, e.g. EMP-001, EMP-002...
     */
    private function generateNextEmployeeId(): string
    {
        $lastNumber = Employee::query()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(employee_id, '-', -1) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $next = ((int) $lastNumber) + 1;
        return 'EMP-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function storeEmployee(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'full_name'       => 'required|string|max:255',
            'arabic_name'     => 'nullable|string|max:255',
            'gender'          => 'required|in:Male,Female',
            'date_of_birth'   => 'required|date',
            'nationality'     => 'required|string|max:255',
            'mobile_number'   => 'required|string|max:30',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string',
            'joining_date'    => 'nullable|date',
            'business_category_id' => 'required|exists:business_categories,id',
            'designation'     => 'required|string|max:255',
            'salary'          => 'required|numeric|min:0',
            'shift'           => 'required|string|max:50',
            'employment_status' => 'required|string|max:50',
            'profile_photo_file' => 'nullable|image|max:2048',
            'create_login'    => 'nullable|boolean',
            'login_email'     => 'required_if:create_login,1|nullable|email|unique:users,email',
            'login_password'  => 'required_if:create_login,1|nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'full_name', 'arabic_name', 'gender',
            'date_of_birth', 'nationality', 'mobile_number', 'email', 'address',
            'emergency_contact_name', 'emergency_contact_phone', 'joining_date',
            'business_category_id', 'designation', 'salary', 'shift', 'employment_status'
        ]);

        $data['employee_id'] = $this->generateNextEmployeeId();

        if ($request->hasFile('profile_photo_file')) {
            $data['profile_photo'] = $request->file('profile_photo_file')->store('employee_photos', 'public');
        }

        $employee = \DB::transaction(function () use ($request, $data) {
            if ($request->boolean('create_login')) {
                $user = User::create([
                    'name'     => $data['full_name'],
                    'email'    => $request->login_email,
                    'role'     => 'user',
                    'password' => Hash::make($request->login_password),
                ]);
                $data['user_id'] = $user->id;
            }

            return Employee::create($data);
        });

        ActivityLog::log('Create Employee', "Added new employee: {$employee->full_name} (ID: {$employee->employee_id})");

        return response()->json(['success' => true, 'message' => 'Employee added successfully', 'employee' => ['id' => $employee->id, 'employee_id' => $employee->employee_id]]);
    }

    public function updateEmployee(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name'       => 'required|string|max:255',
            'arabic_name'     => 'nullable|string|max:255',
            'gender'          => 'required|in:Male,Female',
            'date_of_birth'   => 'required|date',
            'nationality'     => 'required|string|max:255',
            'mobile_number'   => 'required|string|max:30',
            'email'           => 'nullable|email|max:255',
            'joining_date'    => 'nullable|date',
            'business_category_id' => 'required|exists:business_categories,id',
            'designation'     => 'required|string|max:255',
            'salary'          => 'required|numeric|min:0',
            'shift'           => 'required|string|max:50',
            'employment_status' => 'required|string|max:50',
            'profile_photo_file' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'full_name', 'arabic_name', 'gender',
            'date_of_birth', 'nationality', 'mobile_number', 'email', 'address',
            'emergency_contact_name', 'emergency_contact_phone', 'joining_date',
            'business_category_id', 'designation', 'salary', 'shift', 'employment_status'
        ]);

        if ($request->hasFile('profile_photo_file')) {
            $data['profile_photo'] = $request->file('profile_photo_file')->store('employee_photos', 'public');
        }

        $employee->update($data);
        ActivityLog::log('Update Employee', "Updated employee: {$employee->full_name} (ID: {$employee->employee_id})");

        return response()->json(['success' => true, 'message' => 'Employee updated successfully']);
    }

    public function deleteEmployee($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $employee = Employee::findOrFail($id);
        $name = $employee->full_name;
        $employee->delete();
        ActivityLog::log('Delete Employee', "Deleted employee: {$name}");

        return response()->json(['success' => true, 'message' => 'Employee deleted successfully']);
    }

    // ========== PHASE 4: EMPLOYEE DOCUMENTS ==========

    public function storeDocument(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required|exists:employees,id',
            'type'           => 'required|string|max:100',
            'document_number'=> 'required|string|max:100',
            'place_of_issue' => 'nullable|string|max:255',
            'issue_date'     => 'nullable|date',
            'expiry_date'    => 'nullable|date',
            'document_file'  => 'nullable|file|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['employee_id', 'type', 'document_number', 'place_of_issue', 'issue_date', 'expiry_date']);

        if ($request->hasFile('document_file')) {
            $data['file_path'] = $request->file('document_file')->store('employee_documents', 'public');
        }

        $doc = \App\Models\EmployeeDocument::create($data);
        $employee = Employee::find($request->employee_id);
        ActivityLog::log('Add Document', "Added {$request->type} document for {$employee->full_name}");

        return response()->json(['success' => true, 'message' => 'Document added successfully', 'document_id' => $doc->id]);
    }

    public function updateDocument(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $doc = \App\Models\EmployeeDocument::findOrFail($id);

        $data = $request->only(['type', 'document_number', 'place_of_issue', 'issue_date', 'expiry_date']);

        if ($request->hasFile('document_file')) {
            $data['file_path'] = $request->file('document_file')->store('employee_documents', 'public');
        }

        $doc->update($data);
        ActivityLog::log('Update Document', "Updated {$doc->type} document (ID: {$id})");

        return response()->json(['success' => true, 'message' => 'Document updated successfully']);
    }

    public function deleteDocument($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $doc = \App\Models\EmployeeDocument::findOrFail($id);
        $doc->delete();
        ActivityLog::log('Delete Document', "Deleted document (ID: {$id})");

        return response()->json(['success' => true, 'message' => 'Document deleted successfully']);
    }

    // ========== PHASE 5: DOCUMENT EXPIRY MANAGEMENT ==========

    public function getExpiringDocuments(Request $request)
    {
        $days = (int)($request->days ?? 90);
        $today = Carbon::today();
        $threshold = $today->copy()->addDays($days);

        $docs = \App\Models\EmployeeDocument::with('employee.businessCategory')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $threshold)
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($doc) use ($today) {
                $expiry = Carbon::parse($doc->expiry_date);
                $daysLeft = $today->diffInDays($expiry, false);
                return [
                    'id' => $doc->id,
                    'employee_id' => $doc->employee_id,
                    'employee_name' => $doc->employee ? $doc->employee->full_name : 'N/A',
                    'business_category' => $doc->employee && $doc->employee->businessCategory ? $doc->employee->businessCategory->name : 'N/A',
                    'type' => $doc->type,
                    'document_number' => $doc->document_number,
                    'expiry_date' => $doc->expiry_date,
                    'days_left' => $daysLeft,
                    'status' => $doc->status,
                ];
            });

        return response()->json(['success' => true, 'documents' => $docs]);
    }

    // ========== PHASE 6: LEAVE MANAGEMENT ==========

    public function getLeaves(Request $request)
    {
        $query = \App\Models\Leave::with(['employee.businessCategory', 'approver']);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->leave_type) {
            $query->where('leave_type', $request->leave_type);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get()->map(function ($leave) {
            return [
                'id' => $leave->id,
                'employee_id' => $leave->employee_id,
                'employee_name' => $leave->employee ? $leave->employee->full_name : 'N/A',
                'business_category' => $leave->employee && $leave->employee->businessCategory ? $leave->employee->businessCategory->name : 'N/A',
                'leave_type' => $leave->leave_type,
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'duration_days' => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1,
                'status' => $leave->status,
                'reason' => $leave->reason,
                'approved_by' => $leave->approver ? $leave->approver->name : null,
                'created_at' => $leave->created_at->format('Y-m-d'),
                'pauses_ticket' => in_array($leave->leave_type, ['Emergency Leave', 'Medical Leave', 'Unpaid Leave']),
            ];
        });

        return response()->json(['success' => true, 'leaves' => $leaves]);
    }

    public function storeLeave(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type'  => 'required|string|max:100',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
            'status'      => 'required|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['employee_id', 'leave_type', 'start_date', 'end_date', 'reason', 'status']);
        if ($request->status === 'Approved') {
            $data['approved_by'] = Auth::id();
        }

        $leave = \App\Models\Leave::create($data);
        $emp = Employee::find($request->employee_id);
        ActivityLog::log('Add Leave', "Added {$request->leave_type} for {$emp->full_name} ({$request->start_date} to {$request->end_date})");

        return response()->json(['success' => true, 'message' => 'Leave record added successfully']);
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $leave = \App\Models\Leave::with('employee')->findOrFail($id);
        $request->validate(['status' => 'required|in:Pending,Approved,Rejected']);

        $leave->status = $request->status;
        if ($request->status === 'Approved') {
            $leave->approved_by = Auth::id();
        }
        $leave->save();

        $empName = $leave->employee ? $leave->employee->full_name : 'Employee';
        ActivityLog::log('Leave Status', "Leave {$request->status} for {$empName}");

        return response()->json(['success' => true, 'message' => "Leave {$request->status} successfully"]);
    }

    public function updateLeave(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $leave = \App\Models\Leave::with('employee')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type'  => 'required|string|max:100',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
            'status'      => 'required|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['employee_id', 'leave_type', 'start_date', 'end_date', 'reason', 'status']);
        if ($request->status === 'Approved') {
            $data['approved_by'] = Auth::id();
        }

        $leave->update($data);
        $empName = $leave->employee ? $leave->employee->full_name : 'Employee';
        ActivityLog::log('Update Leave', "Updated leave record for {$empName} ({$request->leave_type})");

        return response()->json(['success' => true, 'message' => 'Leave record updated successfully']);
    }

    public function deleteLeave($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $leave = \App\Models\Leave::findOrFail($id);
        $leave->delete();
        ActivityLog::log('Delete Leave', "Deleted leave record (ID: {$id})");

        return response()->json(['success' => true, 'message' => 'Leave record deleted successfully']);
    }

    // ========== PHASE 7: FLIGHT TICKET ELIGIBILITY ==========

    public function getTicketEligibility()
    {
        $employees = Employee::with('businessCategory')->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'employee_id' => $emp->employee_id,
                'full_name' => $emp->full_name,
                'nationality' => $emp->nationality,
                'business_category' => $emp->businessCategory ? $emp->businessCategory->name : 'N/A',
                'joining_date' => $emp->joining_date?->format('Y-m-d'),
                'employment_status' => $emp->employment_status,
                'working_service_days' => $emp->working_service_days,
                'paused_days' => $emp->paused_days,
                'ticket_eligibility_days_left' => $emp->ticket_eligibility_days_left,
                'ticket_eligibility_date' => $emp->ticket_eligibility_date?->format('Y-m-d'),
                'ticket_status' => $emp->ticket_status,
            ];
        });

        return response()->json(['success' => true, 'employees' => $employees]);
    }

    // ========== PHASE 9: USER MANAGEMENT ==========

    public function getUsers()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $users = User::orderBy('name')->get()->map(function ($u) {
            return [
                'id'            => $u->id,
                'name'          => $u->name,
                'email'         => $u->email,
                'role'          => $u->role,
                'profile_photo' => $u->profile_photo ? asset('storage/' . $u->profile_photo) : null,
                'created_at'    => $u->created_at->format('Y-m-d'),
            ];
        });

        return response()->json(['success' => true, 'users' => $users]);
    }

    public function storeUser(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,user',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::log('Create User', "Created user: {$user->name} ({$user->role})");

        return response()->json(['success' => true, 'message' => 'User created successfully']);
    }

    public function updateUser(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'role'     => 'required|in:admin,user',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $request->role;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        ActivityLog::log('Update User', "Updated user: {$user->name}");

        return response()->json(['success' => true, 'message' => 'User updated successfully']);
    }

    public function deleteUser($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($id == Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account'], 422);
        }

        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        ActivityLog::log('Delete User', "Deleted user: {$name}");

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }

    // ========== PHASE 11: NOTIFICATION ALERTS ==========

    public function getNotificationAlerts()
    {
        $today = Carbon::today();
        $thresholds = [7, 15, 30, 60, 90];
        $alerts = [];

        foreach ($thresholds as $days) {
            $threshold = $today->copy()->addDays($days);
            $docs = \App\Models\EmployeeDocument::with('employee')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', $threshold)
                ->get();

            foreach ($docs as $doc) {
                $expiry   = Carbon::parse($doc->expiry_date);
                $daysLeft = $today->diffInDays($expiry, false);
                $alerts[] = [
                    'id'            => $doc->id,
                    'employee_name' => $doc->employee ? $doc->employee->full_name : 'N/A',
                    'document_type' => $doc->type,
                    'document_number' => $doc->document_number,
                    'expiry_date'   => $doc->expiry_date,
                    'days_left'     => $daysLeft,
                    'status'        => $doc->status,
                    'urgency'       => $daysLeft < 0 ? 'expired' : ($daysLeft <= 7 ? 'critical' : ($daysLeft <= 30 ? 'warning' : 'info')),
                ];
            }
        }

        // De-duplicate by document id
        $uniqueAlerts = collect($alerts)->unique('id')->sortBy('days_left')->values()->all();

        // Ticket eligibility alerts
        $eligibleEmployees = Employee::all()->filter(fn($e) => in_array($e->ticket_status, ['Eligible', 'Overdue', 'Eligible in 30 Days']));

        return response()->json([
            'success'            => true,
            'document_alerts'    => $uniqueAlerts,
            'ticket_alerts'      => $eligibleEmployees->map(fn($e) => [
                'id'             => $e->id,
                'full_name'      => $e->full_name,
                'employee_id'    => $e->employee_id,
                'ticket_status'  => $e->ticket_status,
                'eligibility_date' => $e->ticket_eligibility_date?->format('Y-m-d'),
            ])->values()->all(),
            'total_doc_alerts'   => count($uniqueAlerts),
            'total_ticket_alerts'=> $eligibleEmployees->count(),
        ]);
    }

    // ========== PHASE 10: REPORTS (EXPORTS) ==========

    private function exportCSV($filename, $headers, $dataCallback)
    {
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($headers, $dataCallback) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            $dataCallback($handle);
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    public function exportEmployees()
    {
        $headers = ['Employee ID', 'Full Name', 'Arabic Name', 'Gender', 'Nationality', 'Mobile', 'Email', 'Joining Date', 'Status'];
        return $this->exportCSV('employees.csv', $headers, function($handle) {
            foreach (Employee::all() as $emp) {
                fputcsv($handle, [$emp->employee_id, $emp->full_name, $emp->arabic_name, $emp->gender, $emp->nationality, $emp->mobile_number, $emp->email, $emp->joining_date, $emp->employment_status]);
            }
        });
    }

    public function exportExpiries()
    {
        $headers = ['Employee ID', 'Employee Name', 'Document Type', 'Document No.', 'Expiry Date', 'Status'];
        return $this->exportCSV('document_expiries.csv', $headers, function($handle) {
            foreach (EmployeeDocument::with('employee')->get() as $doc) {
                if ($doc->status === 'Expired' || $doc->status === 'Expiring Soon') {
                    fputcsv($handle, [$doc->employee->employee_id ?? 'N/A', $doc->employee->full_name ?? 'N/A', $doc->type, $doc->document_number, $doc->expiry_date, $doc->status]);
                }
            }
        });
    }

    public function exportLeaves()
    {
        $headers = ['Employee ID', 'Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status'];
        return $this->exportCSV('leaves.csv', $headers, function($handle) {
            foreach (\App\Models\Leave::with('employee')->get() as $leave) {
                fputcsv($handle, [$leave->employee->employee_id ?? 'N/A', $leave->employee->full_name ?? 'N/A', $leave->leave_type, $leave->start_date, $leave->end_date, $leave->days, $leave->status]);
            }
        });
    }

    public function exportTickets()
    {
        $headers = ['Employee ID', 'Employee Name', 'Joining Date', 'Paused Days', 'Eligibility Date', 'Status'];
        return $this->exportCSV('flight_tickets.csv', $headers, function($handle) {
            foreach (Employee::all() as $emp) {
                fputcsv($handle, [$emp->employee_id, $emp->full_name, $emp->joining_date, $emp->paused_days, $emp->ticket_eligibility_date ? $emp->ticket_eligibility_date->format('Y-m-d') : 'N/A', $emp->ticket_status]);
            }
        });
    }

    // ========== PHASE 12: MASTER DATA SETTINGS ==========

    public function getMasterData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'designations' => json_decode(CompanySetting::getVal('master_designations', '["Manager","Supervisor","Chef","Waiter","Cashier","Driver","Cleaner","Guard"]'), true),
                'leave_types'  => json_decode(CompanySetting::getVal('master_leave_types', '["Annual Leave","Emergency Leave","Medical Leave","Unpaid Leave","Casual Leave","Hajj Leave","Maternity Leave"]'), true),
                'nationalities'=> json_decode(CompanySetting::getVal('master_nationalities', '["Saudi","Egyptian","Pakistani","Indian","Filipino","Bangladeshi","Yemeni","Syrian","Sudanese","Nepali"]'), true),
            ]
        ]);
    }

    public function saveMasterData(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $field = $request->input('field');
        $values = $request->input('values', []);

        if (in_array($field, ['designations', 'leave_types', 'nationalities'])) {
            CompanySetting::setVal('master_' . $field, json_encode(array_filter(array_map('trim', $values))));
        }

        ActivityLog::log('Update Master Data', "Updated master data settings for {$field}.");

        return response()->json(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' saved successfully']);
    }
}
