<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// SPA entry page
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/{page}', [DashboardController::class, 'index'])
    ->where('page', 'dashboard|branches|employees|expiries|leaves|tickets|reports|users|notifications|settings|logs|profile')
    ->name('page');

// Auth endpoints
Route::post('/api/login', [DashboardController::class, 'login']);
Route::post('/api/logout', [DashboardController::class, 'logout'])->middleware('auth');

// Protected API routes
Route::middleware('auth')->group(function () {
    Route::get('/api/user', [DashboardController::class, 'getCurrentUser']);
    Route::post('/api/profile/update', [DashboardController::class, 'updateProfile']);
    Route::get('/api/activity-logs', [DashboardController::class, 'getActivityLogs']);
    Route::get('/api/settings', [DashboardController::class, 'getSettings']);
    Route::post('/api/settings/update', [DashboardController::class, 'updateSettings']);
    Route::get('/api/dashboard-stats', [DashboardController::class, 'getStats']);

    // Phase 2 Category & Branch APIs
    Route::get('/api/categories', [DashboardController::class, 'getCategories']);
    Route::post('/api/categories', [DashboardController::class, 'storeCategory']);
    Route::delete('/api/categories/{id}', [DashboardController::class, 'deleteCategory']);
    Route::post('/api/branches', [DashboardController::class, 'storeBranch']);
    Route::post('/api/branches/{id}', [DashboardController::class, 'updateBranch']);
    Route::delete('/api/branches/{id}', [DashboardController::class, 'deleteBranch']);

    // Phase 3: Employee Management APIs
    Route::get('/api/employees', [DashboardController::class, 'getEmployees']);
    Route::get('/api/employees/{id}', [DashboardController::class, 'getEmployee']);
    Route::post('/api/employees', [DashboardController::class, 'storeEmployee']);
    Route::post('/api/employees/{id}', [DashboardController::class, 'updateEmployee']);
    Route::delete('/api/employees/{id}', [DashboardController::class, 'deleteEmployee']);

    // Phase 4: Employee Documents APIs
    Route::post('/api/documents', [DashboardController::class, 'storeDocument']);
    Route::post('/api/documents/{id}', [DashboardController::class, 'updateDocument']);
    Route::delete('/api/documents/{id}', [DashboardController::class, 'deleteDocument']);

    // Phase 5: Document Expiry Management
    Route::get('/api/expiring-documents', [DashboardController::class, 'getExpiringDocuments']);

    // Phase 6: Leave Management
    Route::get('/api/leaves', [DashboardController::class, 'getLeaves']);
    Route::post('/api/leaves', [DashboardController::class, 'storeLeave']);
    Route::post('/api/leaves/{id}', [DashboardController::class, 'updateLeave']);
    Route::post('/api/leaves/{id}/status', [DashboardController::class, 'updateLeaveStatus']);
    Route::delete('/api/leaves/{id}', [DashboardController::class, 'deleteLeave']);

    // Phase 7: Flight Ticket Eligibility
    Route::get('/api/ticket-eligibility', [DashboardController::class, 'getTicketEligibility']);

    // Phase 9: User Management (Admin only)
    Route::get('/api/users', [DashboardController::class, 'getUsers']);
    Route::post('/api/users', [DashboardController::class, 'storeUser']);
    Route::post('/api/users/{id}', [DashboardController::class, 'updateUser']);
    Route::delete('/api/users/{id}', [DashboardController::class, 'deleteUser']);

    // Phase 11: Notifications — get alerts summary
    Route::get('/api/notifications/alerts', [DashboardController::class, 'getNotificationAlerts']);

    // Phase 12: Master Data (departments, designations, leave types)
    Route::get('/api/master-data', [DashboardController::class, 'getMasterData']);
    Route::post('/api/master-data', [DashboardController::class, 'saveMasterData']);
    // Phase 10: Reports (Exports)
    Route::get('/api/export/employees', [DashboardController::class, 'exportEmployees']);
    Route::get('/api/export/expiries', [DashboardController::class, 'exportExpiries']);
    Route::get('/api/export/leaves', [DashboardController::class, 'exportLeaves']);
    Route::get('/api/export/tickets', [DashboardController::class, 'exportTickets']);
});

// Catch-all route redirects back to SPA home
Route::fallback(function () {
    return redirect()->route('home');
});
