@extends('partials.layouts.master')

@section('title', 'Saudi HR Portal')

@section('css')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    /* ===== TOAST NOTIFICATIONS ===== */
    #toast-container {
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        display: flex; flex-direction: column; gap: 10px;
        pointer-events: none;
    }
    .toast-item {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 18px; border-radius: 10px; min-width: 280px; max-width: 380px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        backdrop-filter: blur(10px); pointer-events: all;
        animation: slideInRight 0.3s cubic-bezier(0.175,0.885,0.32,1.275) forwards;
        font-size: 14px; font-weight: 500;
    }
    .toast-item.toast-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
    .toast-item.toast-error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    .toast-item.toast-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
    .toast-item.toast-info    { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
    .toast-item.toast-fadeout { animation: slideOutRight 0.3s ease-in forwards; }
    @keyframes slideInRight { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(110%); opacity: 0; } }

    /* ===== CONFIRM DIALOG ===== */
    .confirm-overlay {
        position: fixed; inset: 0; z-index: 9998;
        background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center;
        animation: fadeIn 0.2s ease;
    }
    .confirm-box {
        background: #fff; border-radius: 16px; padding: 32px 28px; max-width: 380px; width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        animation: popIn 0.25s cubic-bezier(0.175,0.885,0.32,1.275);
    }
    @keyframes popIn { from { transform: scale(0.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* ===== GENERAL CARDS ===== */
    .stat-card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
    .alert-indicator { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    
    /* ===== ROLE BADGES ===== */
    .role-admin { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .role-user  { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
    .sidebar-badge { font-size: 10px; vertical-align: top; }

    /* ===== EMPLOYEE AVATAR ===== */
    .emp-avatar-img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
    .emp-avatar-lg  { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0; }
    .emp-avatar-placeholder { display: flex; align-items: center; justify-content: center; font-weight: 700; }

    /* ===== PAGINATION ===== */
    .pagination-btn { cursor: pointer; user-select: none; }

    /* ===== TABLE IMPROVEMENTS ===== */
    .table > :not(caption) > * > * { padding: 10px 12px; }
    .table-hover > tbody > tr:hover { background-color: rgba(99,102,241,0.04); }

    /* ===== LOADING SKELETON ===== */
    .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 6px; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* ===== TABS IN MODALS ===== */
    .modal-tab-btn { border: none; background: none; padding: 8px 16px; color: #64748b; border-bottom: 2px solid transparent; font-weight: 500; font-size: 13px; cursor: pointer; }
    .modal-tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; }

    /* ===== DOC CARD ===== */
    .doc-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; transition: box-shadow 0.2s; }
    .doc-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
</style>
@endsection

@section('content')
<div class="w-100" x-init="init()">

    <!-- ===== TOAST NOTIFICATION CONTAINER ===== -->
    <div id="toast-container">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast-item" :class="'toast-' + toast.type + (toast.fading ? ' toast-fadeout' : '')">
                <i class="bi" :class="toast.type === 'success' ? 'bi-check-circle-fill' : (toast.type === 'error' ? 'bi-x-circle-fill' : (toast.type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill'))"></i>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    <!-- ===== CUSTOM CONFIRM DIALOG ===== -->
    <div class="confirm-overlay" x-show="confirmDialog.show">
        <div class="confirm-box text-center">
            <div class="mb-3" style="font-size: 3rem; line-height:1;" x-text="confirmDialog.icon || '⚠️'"></div>
            <h5 class="fw-bold text-dark mb-1" x-text="confirmDialog.title || 'Are you sure?'"></h5>
            <p class="text-muted fs-13 mb-4" x-text="confirmDialog.message"></p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-secondary btn-sm px-4" @click="confirmDialog.show = false; if(confirmDialog.onCancel) confirmDialog.onCancel()">Cancel</button>
                <button class="btn btn-sm px-4" :class="confirmDialog.btnClass || 'btn-danger'" @click="confirmDialog.show = false; if(confirmDialog.onConfirm) confirmDialog.onConfirm()" x-text="confirmDialog.btnText || 'Delete'"></button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- UNAUTHENTICATED STATE: LOGIN PANEL -->
    <!-- ============================================================== -->
    <template x-if="!loggedIn">
        <div class="position-fixed top-0 start-0 w-100 h-100 z-3 bg-white overflow-auto">
            <img src="assets/images/auth/login_bg.jpg" alt="Auth Background"
                class="auth-bg light w-full h-full opacity-60 position-absolute top-0">
            <img src="assets/images/auth/auth_bg_dark.jpg" alt="Auth Background" class="auth-bg d-none dark">
            <div class="container position-relative">
                <div class="row justify-content-center align-items-center min-vh-100 py-10">
                    <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                        <div class="card mx-xxl-8 shadow-lg">
                            <div class="card-body py-12 px-8">
                                <img src="assets/images/logo-dark.png" alt="Logo Dark" height="30"
                                    class="mb-4 mx-auto d-block">
                                <h6 class="mb-4 fw-medium text-center">Saudi HR & Employee Management System</h6>
                                
                                <!-- Alert message -->
                                <div x-show="loginForm.errorMessage" 
                                     x-transition 
                                     class="alert alert-danger p-2 fs-13 text-center" 
                                     x-text="loginForm.errorMessage">
                                </div>

                                <form @submit.prevent="login">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label for="username" class="form-label text-dark">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="username"
                                                placeholder="Enter your email" x-model="loginForm.email" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="password" class="form-label text-dark">Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input :type="loginForm.showPassword ? 'text' : 'password'" class="form-control" id="password"
                                                    placeholder="Enter your password" x-model="loginForm.password" required>
                                                <button type="button" class="btn btn-light border" @click="loginForm.showPassword = !loginForm.showPassword" tabindex="-1">
                                                    <i class="bi" :class="loginForm.showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="rememberMe">
                                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                                </div>
                                                <div class="form-text">
                                                    <a href="javascript:void(0)"
                                                        class="link link-primary text-muted text-decoration-underline">Forgot password?</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-8">
                                            <button type="submit" class="btn btn-primary w-full mb-4" :disabled="loginForm.loading">
                                                <span x-show="!loginForm.loading">Sign In <i class="bi bi-box-arrow-in-right ms-1 fs-16"></i></span>
                                                <span x-show="loginForm.loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                <span x-show="loginForm.loading">Authenticating...</span>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mb-0 fw-semibold position-relative text-center fs-12">Don't have an account? <a
                                            href="javascript:void(0)" class="text-decoration-underline text-primary">Contact Administrator</a>
                                    </p>
                                </form>
                                <div class="mt-4 pt-2 border-top text-center text-muted fs-12">
                                    <span>Developer Access: <strong>admin@hr.sa</strong> / <strong>password</strong></span>
                                </div>
                            </div>
                        </div>
                        <p class="position-relative text-center fs-12 mb-0">© 2026 Saudi HR. Crafted with ❤️</p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- ============================================================== -->
    <!-- AUTHENTICATED STATE: DASHBOARD CONTENTS -->
    <!-- ============================================================== -->
    <template x-if="loggedIn">
        <div class="row">
            <div class="col-12">
                
                <!-- SECTION 1: DASHBOARD OVERVIEW -->
                <div x-show="page === 'dashboard'" x-transition>
                    
                    <!-- Dashboard Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">Overview Dashboard</h4>
                            <p class="text-muted fs-13 mb-0">Welcome back, <span class="fw-semibold text-primary" x-text="userName"></span>!</p>
                        </div>
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex align-items-center gap-2">
                            <i class="bi bi-clock text-primary fs-5"></i>
                            <div>
                                <div class="fs-12 text-muted fw-medium text-uppercase" x-text="new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' })"></div>
                                <div class="fw-bold text-dark fs-14" x-text="currentTime"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xxl-3 col-sm-6 mb-3">
                            <div class="card stat-card bg-light-subtle h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <span class="text-muted fs-13 d-block mb-1">Total Employees</span>
                                            <h3 class="fw-bold mb-0 text-dark" x-text="stats.employees.total">0</h3>
                                        </div>
                                        <div class="h-45px w-45px rounded-pill bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-5">
                                            <i class="bi bi-people"></i>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary w-100 fs-12" @click="page = 'employees'; setTimeout(() => openAddEmployee(), 300)">
                                        <i class="bi bi-plus-circle me-1"></i> Add Employee
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-sm-6 mb-3">
                            <div class="card stat-card bg-light-subtle h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <span class="text-muted fs-13 d-block mb-1">Active Staff</span>
                                            <h3 class="fw-bold mb-0 text-success" x-text="stats.employees.active">0</h3>
                                        </div>
                                        <div class="h-45px w-45px rounded-pill bg-success-subtle text-success d-flex align-items-center justify-content-center fs-5">
                                            <i class="bi bi-person-check"></i>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-success w-100 fs-12" @click="page = 'employees'; empFilterStatus = 'Active'; loadEmployees()">
                                        <i class="bi bi-search me-1"></i> View Active
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-sm-6 mb-3">
                            <div class="card stat-card bg-light-subtle h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <span class="text-muted fs-13 d-block mb-1">On Approved Leave</span>
                                            <h3 class="fw-bold mb-0 text-warning" x-text="stats.employees.on_leave">0</h3>
                                        </div>
                                        <div class="h-45px w-45px rounded-pill bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-5">
                                            <i class="bi bi-calendar-range"></i>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-warning w-100 fs-12" @click="page = 'leaves'">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Manage Leaves
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-sm-6 mb-3">
                            <div class="card stat-card bg-light-subtle h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <span class="text-muted fs-13 d-block mb-1">Business Categories</span>
                                            <h3 class="fw-bold mb-0 text-info" x-text="stats.employees.categories">0</h3>
                                        </div>
                                        <div class="h-45px w-45px rounded-pill bg-info-subtle text-info d-flex align-items-center justify-content-center fs-5">
                                            <i class="bi bi-building"></i>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-info w-100 fs-12" @click="page = 'branches'">
                                        <i class="bi bi-building-add me-1"></i> View Categories
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expiry & Eligibility Alerts Widgets -->
                    <div class="row">
                        <!-- Expiry Trackers -->
                        <div class="col-xl-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                                    <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Critical Expiry Alerts</h5>
                                    <span class="badge bg-danger-subtle text-danger" x-text="stats.expiries.total + ' Alerts'">0 Alerts</span>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-card-text text-primary fs-5 me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">Iqama Expiry Alerts</h6>
                                                    <small class="text-muted">Mandatory Saudi Identification</small>
                                                </div>
                                            </div>
                                            <span class="badge rounded-pill fs-12 px-3" :class="stats.expiries.iqama > 0 ? 'bg-danger' : 'bg-success'" x-text="stats.expiries.iqama + ' Action'">0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-journal-album text-info fs-5 me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">Passport Expiry Alerts</h6>
                                                    <small class="text-muted">International Travel Document</small>
                                                </div>
                                            </div>
                                            <span class="badge rounded-pill fs-12 px-3" :class="stats.expiries.passport > 0 ? 'bg-danger' : 'bg-success'" x-text="stats.expiries.passport + ' Action'">0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-heart-pulse text-success fs-5 me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">Health Insurance Alerts</h6>
                                                    <small class="text-muted">Bupa / Tawuniya Policies</small>
                                                </div>
                                            </div>
                                            <span class="badge rounded-pill fs-12 px-3" :class="stats.expiries.insurance > 0 ? 'bg-danger' : 'bg-success'" x-text="stats.expiries.insurance + ' Action'">0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-shield-check text-warning fs-5 me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">Baladiya Card Alerts</h6>
                                                    <small class="text-muted">Municipal Health & Safety Permits</small>
                                                </div>
                                            </div>
                                            <span class="badge rounded-pill fs-12 px-3" :class="stats.expiries.baladiya > 0 ? 'bg-warning text-dark' : 'bg-success'" x-text="stats.expiries.baladiya + ' Action'">0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-car-front text-secondary fs-5 me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">Saudi Driving License Alerts</h6>
                                                    <small class="text-muted">Delivery & Logistics Staff</small>
                                                </div>
                                            </div>
                                            <span class="badge rounded-pill fs-12 px-3" :class="stats.expiries.driving > 0 ? 'bg-warning text-dark' : 'bg-success'" x-text="stats.expiries.driving + ' Action'">0</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Eligibility Widgets -->
                        <div class="col-xl-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                                    <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-airplane text-success me-2"></i>Flight Ticket Eligibility</h5>
                                    <span class="badge bg-success-subtle text-success">Policy: 2 Years Service</span>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <div class="p-3 border rounded text-center">
                                                <h2 class="fw-bold text-success mb-1" x-text="stats.tickets.eligible_now">0</h2>
                                                <span class="text-muted fs-13">Eligible This Month</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 border rounded text-center">
                                                <h2 class="fw-bold text-warning mb-1" x-text="stats.tickets.overdue">0</h2>
                                                <span class="text-muted fs-13">Overdue Employees</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 border rounded text-center">
                                                <h2 class="fw-bold text-primary mb-1" x-text="stats.tickets.eligible_30">0</h2>
                                                <span class="text-muted fs-13">Eligible in Next 30 Days</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 border rounded text-center">
                                                <h2 class="fw-bold text-info mb-1" x-text="stats.tickets.eligible_60">0</h2>
                                                <span class="text-muted fs-13">Eligible in Next 60 Days</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-3 bg-light rounded d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-pause-circle-fill text-warning fs-4 me-2"></i>
                                            <div>
                                                <span class="d-block fw-semibold text-dark fs-13">Delayed due to Leaves</span>
                                                <small class="text-muted fs-11">Countdown paused by Medical/Emergency leave</small>
                                            </div>
                                        </div>
                                        <h4 class="fw-bold mb-0 text-dark" x-text="stats.tickets.delayed">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 8: Dashboard Charts -->
                    <div class="row">
                        <div class="col-xl-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Employees by Business Type</h5>
                                </div>
                                <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px;">
                                    <canvas id="businessChart" style="max-height:200px;"></canvas>
                                    <p class="text-muted fs-13 m-0" id="businessChartEmpty" style="display:none;">No business category data available yet.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-globe me-2 text-info"></i>Employees by Nationality</h5>
                                </div>
                                <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px;">
                                    <canvas id="nationalityChart" style="max-height:200px;"></canvas>
                                    <p class="text-muted fs-13 m-0" id="nationalityChartEmpty" style="display:none;">No nationality data available yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: MY PROFILE (Phase 1) -->
                <div x-show="page === 'profile'" x-transition>
                    <div class="card col-md-8 mx-auto">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold">Profile Management</h5>
                        </div>
                        <div class="card-body p-4">
                            <div x-show="profileForm.successMessage" class="alert alert-success p-2 text-center" x-text="profileForm.successMessage"></div>
                            <div x-show="profileForm.errorMessage" class="alert alert-danger p-2 text-center" x-text="profileForm.errorMessage"></div>

                            <form @submit.prevent="updateProfile" enctype="multipart/form-data">
                                <div class="d-flex align-items-center gap-4 mb-4 pb-2">
                                    <div class="position-relative">
                                        <img :src="userAvatar || 'assets/images/avatar/avatar-10.jpg'" 
                                             class="rounded-circle border" 
                                             style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-dark fw-semibold">Upload Profile Picture</h6>
                                        <p class="text-muted fs-12 mb-2">PNG, JPG or JPEG. Max 2MB.</p>
                                        <input type="file" 
                                               class="form-control form-control-sm" 
                                               accept="image/*"
                                               @change="handleAvatarChange">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-6">
                                        <label class="form-label text-dark fw-semibold fs-13">Full Name</label>
                                        <input type="text" class="form-control" x-model="profileForm.name" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-dark fw-semibold fs-13">Email Address</label>
                                        <input type="email" class="form-control" x-model="profileForm.email" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-sm-6">
                                        <label class="form-label text-dark fw-semibold fs-13">New Password (Optional)</label>
                                        <input type="password" class="form-control" placeholder="Leave empty to keep current" x-model="profileForm.password">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-dark fw-semibold fs-13">Confirm New Password</label>
                                        <input type="password" class="form-control" placeholder="Confirm new password" x-model="profileForm.password_confirmation">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" :disabled="profileForm.loading">
                                    <span x-show="!profileForm.loading">Save Changes</span>
                                    <span x-show="profileForm.loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    <span x-show="profileForm.loading">Updating...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: COMPANY SETTINGS (Phase 1 & 12) -->
                <div x-show="page === 'settings'" x-transition>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-gear me-2 text-primary"></i>Company Settings & Master Data</h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- Tabs -->
                            <ul class="nav nav-tabs border-bottom px-4 pt-3" id="settingsTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#settingsCompany" id="tab-company">Company</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#settingsDesigs" id="tab-desigs" @click="loadMasterData()">Designations</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#settingsLeaves" id="tab-leaves" @click="loadMasterData()">Leave Types</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#settingsNat" id="tab-nat" @click="loadMasterData()">Nationalities</a>
                                </li>
                            </ul>
                            <div class="tab-content p-4">
                                <!-- Company Tab -->
                                <div class="tab-pane fade show active" id="settingsCompany">
                                    <div x-show="settingsForm.successMessage" class="alert alert-success p-2 text-center" x-text="settingsForm.successMessage"></div>
                                    <div x-show="settingsForm.errorMessage" class="alert alert-danger p-2 text-center" x-text="settingsForm.errorMessage"></div>
                                    <form @submit.prevent="updateSettings" class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label text-dark fw-semibold fs-13">Company Name</label>
                                            <input type="text" class="form-control" x-model="settingsForm.company_name" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label text-dark fw-semibold fs-13">Flight Ticket Eligibility Service (Months)</label>
                                            <input type="number" class="form-control" x-model="settingsForm.flight_ticket_policy_months" required>
                                            <small class="text-muted">Standard policy requires 24 months (2 years) of working service.</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary" :disabled="settingsForm.loading">
                                            <span x-show="!settingsForm.loading">Save Settings</span>
                                            <span x-show="settingsForm.loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            <span x-show="settingsForm.loading">Updating...</span>
                                        </button>
                                    </form>
                                </div>

                                <!-- Designations Tab -->
                                <div class="tab-pane fade" id="settingsDesigs">
                                    <h6 class="fw-semibold text-dark mb-3">Manage Designations / Job Titles</h6>
                                    <p class="text-muted fs-13">These designations will be available in the Employee Management form.</p>
                                    <div x-show="masterSaveSuccess" class="alert alert-success p-2 fs-13" x-text="masterSaveSuccess"></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <template x-for="(des, idx) in masterData.designations" :key="idx">
                                            <span class="badge bg-success-subtle text-success px-3 py-2 fs-13 d-flex align-items-center gap-2">
                                                <span x-text="des"></span>
                                                <button type="button" class="btn-close btn-close-sm p-0" style="font-size:10px;" @click="masterData.designations.splice(idx,1)"></button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="input-group mb-3" style="max-width:400px;">
                                        <input type="text" class="form-control form-control-sm" placeholder="Add designation..." x-model="newDesigName" @keydown.enter.prevent="if(newDesigName.trim()){ masterData.designations.push(newDesigName.trim()); newDesigName=''; }">
                                        <button class="btn btn-sm btn-outline-success" @click="if(newDesigName.trim()){ masterData.designations.push(newDesigName.trim()); newDesigName=''; }"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <button class="btn btn-success btn-sm" @click="saveMasterDataField('designations')">Save Designations</button>
                                </div>

                                <!-- Leave Types Tab -->
                                <div class="tab-pane fade" id="settingsLeaves">
                                    <h6 class="fw-semibold text-dark mb-3">Manage Leave Types</h6>
                                    <p class="text-muted fs-13">These leave types will be available in the Leave Management form.</p>
                                    <div x-show="masterSaveSuccess" class="alert alert-success p-2 fs-13" x-text="masterSaveSuccess"></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <template x-for="(lt, idx) in masterData.leave_types" :key="idx">
                                            <span class="badge bg-warning-subtle text-warning px-3 py-2 fs-13 d-flex align-items-center gap-2">
                                                <span x-text="lt"></span>
                                                <button type="button" class="btn-close btn-close-sm p-0" style="font-size:10px;" @click="masterData.leave_types.splice(idx,1)"></button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="input-group mb-3" style="max-width:400px;">
                                        <input type="text" class="form-control form-control-sm" placeholder="Add leave type..." x-model="newLeaveTypeName" @keydown.enter.prevent="if(newLeaveTypeName.trim()){ masterData.leave_types.push(newLeaveTypeName.trim()); newLeaveTypeName=''; }">
                                        <button class="btn btn-sm btn-outline-warning" @click="if(newLeaveTypeName.trim()){ masterData.leave_types.push(newLeaveTypeName.trim()); newLeaveTypeName=''; }"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <button class="btn btn-warning btn-sm" @click="saveMasterDataField('leave_types')">Save Leave Types</button>
                                </div>

                                <!-- Nationalities Tab -->
                                <div class="tab-pane fade" id="settingsNat">
                                    <h6 class="fw-semibold text-dark mb-3">Manage Nationalities</h6>
                                    <p class="text-muted fs-13">These nationalities will be available in the Employee Management form.</p>
                                    <div x-show="masterSaveSuccess" class="alert alert-success p-2 fs-13" x-text="masterSaveSuccess"></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <template x-for="(nat, idx) in masterData.nationalities" :key="idx">
                                            <span class="badge bg-info-subtle text-info px-3 py-2 fs-13 d-flex align-items-center gap-2">
                                                <span x-text="nat"></span>
                                                <button type="button" class="btn-close btn-close-sm p-0" style="font-size:10px;" @click="masterData.nationalities.splice(idx,1)"></button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="input-group mb-3" style="max-width:400px;">
                                        <input type="text" class="form-control form-control-sm" placeholder="Add nationality..." x-model="newNatName" @keydown.enter.prevent="if(newNatName.trim()){ masterData.nationalities.push(newNatName.trim()); newNatName=''; }">
                                        <button class="btn btn-sm btn-outline-info" @click="if(newNatName.trim()){ masterData.nationalities.push(newNatName.trim()); newNatName=''; }"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <button class="btn btn-info btn-sm text-white" @click="saveMasterDataField('nationalities')">Save Nationalities</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: ACTIVITY LOGS (Phase 1) -->
                <div x-show="page === 'logs'" x-transition>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold">System Audit & Activity Logs</h5>
                            <button class="btn btn-sm btn-outline-secondary" @click="loadLogs()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">User</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>IP Address</th>
                                            <th class="pe-4">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="log in logs" :key="log.id">
                                            <tr>
                                                <td class="ps-4 text-dark fw-semibold" x-text="log.user_name"></td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary px-2 py-1 text-capitalize" x-text="log.action"></span>
                                                </td>
                                                <td class="text-muted" x-text="log.description"></td>
                                                <td class="fs-13" x-text="log.ip_address"></td>
                                                <td class="pe-4 text-muted fs-12" :title="log.time" x-text="log.time_diff"></td>
                                            </tr>
                                        </template>
                                        <template x-if="logs.length === 0">
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No logs recorded yet.</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 5: BUSINESS CATEGORY MANAGEMENT (Phase 2) -->
                <div x-show="page === 'branches'" x-transition>
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                                    <h5 class="card-title mb-0 text-dark fw-semibold">Business Categories</h5>
                                    <button x-show="userRole === 'admin'" class="btn btn-sm btn-primary" @click="categoryModalForm.name = ''; categoryModalForm.errorMessage = ''; showCategoryModal = true;">
                                        <i class="bi bi-plus-lg me-1"></i>Add
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <template x-for="cat in categories" :key="cat.id">
                                            <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold text-dark" x-text="cat.name"></span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge rounded-pill bg-light text-dark border" x-text="(cat.employees_count || 0) + ' Employees'">0</span>
                                                    <button x-show="userRole === 'admin'" class="btn btn-sm text-danger p-0 border-0 bg-transparent" @click.stop="deleteCategory(cat.id)">
                                                        <i class="bi bi-trash fs-14"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="categories.length === 0">
                                            <div class="text-center py-5">
                                                <i class="bi bi-building fs-1 text-muted mb-3 d-block"></i>
                                                <h6>No Business Categories Defined</h6>
                                                <p class="text-muted fs-13">Create a category to start assigning employees to it.</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 7: EMPLOYEE MANAGEMENT (Phase 3) -->
                <div x-show="page === 'employees'" x-transition>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Employee Management</h5>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <input type="text" class="form-control form-control-sm" style="width:200px;" placeholder="Search name, ID, mobile..." x-model="empSearch" @input.debounce.400ms="loadEmployees()">
                                <select class="form-select form-select-sm" style="width:130px;" x-model="empFilterStatus" @change="loadEmployees()">
                                    <option value="">All Status</option>
                                    <option>Active</option>
                                    <option>On Leave</option>
                                    <option>Terminated</option>
                                    <option>Resigned</option>
                                </select>
                                <select class="form-select form-select-sm" style="width:150px;" x-model="empFilterCategory" @change="loadEmployees()">
                                    <option value="">All Categories</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                                <button x-show="userRole === 'admin'" class="btn btn-sm btn-primary" @click="openAddEmployee()">
                                    <i class="bi bi-plus-lg me-1"></i>Add Employee
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee</th>
                                            <th>Employee ID</th>
                                            <th>Business Category</th>
                                            <th>Designation</th>
                                            <th>Nationality</th>
                                            <th>Status</th>
                                            <th>Ticket</th>
                                            <th class="pe-4 text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="empLoading">
                                            <template x-for="i in [1,2,3,4,5]" :key="i">
                                                <tr>
                                                    <td class="ps-4"><div class="skeleton" style="height:20px;width:160px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:80px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:100px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:90px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:70px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:60px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:60px;"></div></td>
                                                    <td><div class="skeleton" style="height:20px;width:90px;"></div></td>
                                                </tr>
                                            </template>
                                        </template>
                                        <template x-if="!empLoading">
                                        <template x-for="emp in paginatedEmployees" :key="emp.id">
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <template x-if="emp.profile_photo">
                                                            <img :src="emp.profile_photo" class="emp-avatar-img" :alt="emp.full_name">
                                                        </template>
                                                        <template x-if="!emp.profile_photo">
                                                            <div class="h-35px w-35px rounded-circle bg-primary-subtle text-primary emp-avatar-placeholder fs-13" x-text="emp.full_name.charAt(0)"></div>
                                                        </template>
                                                        <div>
                                                            <div class="fw-semibold text-dark" x-text="emp.full_name"></div>
                                                            <div class="text-muted fs-12" x-text="emp.arabic_name || ''"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-medium" x-text="emp.employee_id"></div>
                                                </td>
                                                <td>
                                                    <div x-text="emp.business_category || '-'"></div>
                                                </td>
                                                <td>
                                                    <div x-text="emp.designation"></div>
                                                </td>
                                                <td x-text="emp.nationality"></td>
                                                <td>
                                                    <span class="badge px-2 py-1"
                                                          :class="emp.employment_status === 'Active' ? 'bg-success-subtle text-success' : (emp.employment_status === 'On Leave' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger')"
                                                          x-text="emp.employment_status"></span>
                                                </td>
                                                <td>
                                                    <span class="badge px-2 py-1 fs-11"
                                                          :class="emp.ticket_status === 'Eligible' ? 'bg-success-subtle text-success' : (emp.ticket_status === 'Overdue' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info')"
                                                          x-text="emp.ticket_status"></span>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <button class="btn btn-sm btn-primary px-2 py-1 me-1" @click="viewEmployee(emp.id)" title="View Profile">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <template x-if="userRole === 'admin'">
                                                        <span>
                                                            <button class="btn btn-sm btn-warning px-2 py-1 me-1" @click="openEditEmployee(emp)" title="Edit Employee">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger px-2 py-1" @click="confirmDeleteEmployee(emp)" title="Delete Employee">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </span>
                                                    </template>

                                                </td>
                                            </tr>
                                        </template>
                                        </template>
                                        <template x-if="!empLoading && employees.length === 0">
                                            <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-people fs-2 d-block mb-2 text-muted"></i>No employees found. Try adjusting your filters or add a new employee.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top" x-show="employees.length > empPerPage">
                                <div class="text-muted fs-13">
                                    Showing <strong x-text="empPageStart + 1"></strong>–<strong x-text="Math.min(empPageStart + empPerPage, employees.length)"></strong> of <strong x-text="employees.length"></strong> employees
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary pagination-btn" :disabled="empPage === 1" @click="empPage--"><i class="bi bi-chevron-left"></i></button>
                                    <template x-for="pg in empTotalPages" :key="pg">
                                        <button class="btn btn-sm pagination-btn" :class="empPage === pg ? 'btn-primary' : 'btn-outline-secondary'" @click="empPage = pg" x-text="pg"></button>
                                    </template>
                                    <button class="btn btn-sm btn-outline-secondary pagination-btn" :disabled="empPage === empTotalPages" @click="empPage++"><i class="bi bi-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 8: DOCUMENT EXPIRY MANAGEMENT (Phase 5) -->
                <div x-show="page === 'expiries'" x-transition>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold">Document Expiry Tracker</h5>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <span class="text-muted fs-13 me-2 align-self-center">Show expiring within:</span>
                                <template x-for="d in [7, 15, 30, 60, 90]" :key="d">
                                    <button class="btn btn-sm"
                                            :class="expiryDaysFilter === d ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="expiryDaysFilter = d; loadExpiries()" x-text="d + ' days'"></button>
                                </template>
                                
                                <select class="form-select form-select-sm ms-2" style="width:150px;" x-model="expiryTypeFilter">
                                    <option value="">All Doc Types</option>
                                    <option value="Iqama">Iqama</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Health Insurance">Health Insurance</option>
                                    <option value="Baladiya Card">Baladiya Card</option>
                                    <option value="Driving License">Driving License</option>
                                </select>

                                <button class="btn btn-sm btn-light border ms-1" @click="loadExpiries(); showToast('Statuses refreshed!', 'success')" title="Refresh Statuses">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                                
                                <div class="dropdown ms-1">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-download me-1"></i>Export
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item fs-13" href="javascript:void(0)" @click="window.print()"><i class="bi bi-printer me-2"></i>Print Report</a></li>
                                        <li><a class="dropdown-item fs-13" href="javascript:void(0)" @click="showToast('Exporting to CSV...', 'info')"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Export CSV</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee</th>
                                            <th>Document Type</th>
                                            <th>Document No.</th>
                                            <th>Business Category</th>
                                            <th>Expiry Date</th>
                                            <th>Days Left</th>
                                            <th class="pe-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="doc in expiringDocs.filter(d => !expiryTypeFilter || d.type === expiryTypeFilter)" :key="doc.id">
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold text-dark" x-text="doc.employee_name"></div>
                                                </td>
                                                <td><span class="badge bg-primary-subtle text-primary" x-text="doc.type"></span></td>
                                                <td x-text="doc.document_number"></td>
                                                <td x-text="doc.business_category"></td>
                                                <td x-text="doc.expiry_date"></td>
                                                <td>
                                                    <span :class="doc.days_left < 0 ? 'text-danger fw-bold' : (doc.days_left <= 15 ? 'text-danger fw-semibold' : (doc.days_left <= 30 ? 'text-warning fw-semibold' : 'text-dark'))">
                                                        <span x-show="doc.days_left < 0" x-text="Math.abs(doc.days_left) + ' days overdue'"></span>
                                                        <span x-show="doc.days_left >= 0" x-text="doc.days_left + ' days left'"></span>
                                                    </span>
                                                </td>
                                                <td class="pe-4">
                                                    <span class="badge px-2 py-1"
                                                          :class="doc.status === 'Expired' ? 'bg-danger-subtle text-danger' : (doc.status === 'Expiring Soon' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success')"
                                                          x-text="doc.status"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="expiringDocs.length === 0">
                                            <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>No documents expiring within the selected threshold.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 9: LEAVE MANAGEMENT (Phase 6) -->
                <div x-show="page === 'leaves'" x-transition>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold">Leave Management</h5>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <select class="form-select form-select-sm" style="width:140px;" x-model="leaveFilterStatus" @change="loadLeaves()">
                                    <option value="">All Status</option>
                                    <option>Pending</option>
                                    <option>Approved</option>
                                    <option>Rejected</option>
                                </select>
                                <select class="form-select form-select-sm" style="width:180px;" x-model="leaveFilterType" @change="loadLeaves()">
                                    <option value="">All Leave Types</option>
                                    <template x-for="t in masterData.leave_types" :key="t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                                <button x-show="userRole === 'admin'" class="btn btn-sm btn-primary" @click="openAddLeave()">
                                    <i class="bi bi-plus-lg me-1"></i>Add Leave
                                </button>
                            </div>
                        </div>
                        
                        <!-- Leave Statistics Bar -->
                        <div class="bg-light-subtle border-bottom px-4 py-3 d-flex align-items-center gap-4 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded px-2 py-1 fs-12 fw-semibold">Pending</div>
                                <span class="fw-bold fs-5 text-dark" x-text="leaveStats.pending">0</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success rounded px-2 py-1 fs-12 fw-semibold">Approved</div>
                                <span class="fw-bold fs-5 text-dark" x-text="leaveStats.approved">0</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger-subtle text-danger rounded px-2 py-1 fs-12 fw-semibold">Rejected</div>
                                <span class="fw-bold fs-5 text-dark" x-text="leaveStats.rejected">0</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <div class="bg-dark text-white rounded px-2 py-1 fs-12 fw-semibold">Total Approved Days</div>
                                <span class="fw-bold fs-5 text-dark" x-text="leaveStats.total_days">0</span>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee</th>
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Dates</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th class="pe-4 text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="leave in leaves" :key="leave.id">
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold text-dark" x-text="leave.employee_name"></div>
                                                    <div class="text-muted fs-12" x-text="leave.business_category"></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary" x-text="leave.leave_type"></span>
                                                    <span x-show="leave.pauses_ticket" class="badge bg-warning-subtle text-warning ms-1 fs-10" title="Pauses flight ticket countdown">⏸ Pauses Ticket</span>
                                                </td>
                                                <td><span class="fw-semibold" x-text="leave.duration_days + ' days'"></span></td>
                                                <td>
                                                    <div class="fs-13" x-text="leave.start_date"></div>
                                                    <div class="text-muted fs-12" x-text="'→ ' + leave.end_date"></div>
                                                </td>
                                                <td class="text-muted fs-13" x-text="leave.reason || '-'" style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></td>
                                                <td>
                                                    <span class="badge px-2 py-1"
                                                          :class="leave.status === 'Approved' ? 'bg-success-subtle text-success' : (leave.status === 'Rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning')"
                                                          x-text="leave.status"></span>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <template x-if="userRole === 'admin'">
                                                        <div class="d-flex gap-1 justify-content-end">
                                                            <template x-if="leave.status === 'Pending'">
                                                                <span>
                                                                    <button class="btn btn-sm btn-success py-1 px-2" @click="updateLeaveStatus(leave.id, 'Approved')" title="Approve">
                                                                        <i class="bi bi-check-lg"></i>
                                                                    </button>
                                                                    <button class="btn btn-sm btn-danger py-1 px-2" @click="updateLeaveStatus(leave.id, 'Rejected')" title="Reject">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                </span>
                                                            </template>
                                                            <button class="btn btn-sm btn-warning py-1 px-2" @click="openEditLeave(leave)" title="Edit Leave">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger py-1 px-2" @click="confirmDeleteLeave(leave)" title="Delete Leave">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="leaves.length === 0">
                                            <tr><td colspan="7" class="text-center py-5 text-muted">No leave records found.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 10: FLIGHT TICKET ELIGIBILITY (Phase 7) -->
                <div x-show="page === 'tickets'" x-transition>
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-success h-100">
                                <div class="card-body text-center py-3">
                                    <div class="h-50px w-50px rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fs-4 mx-auto mb-2"><i class="bi bi-airplane-fill"></i></div>
                                    <h4 class="fw-bold text-success mb-0" x-text="ticketStats.eligible"></h4>
                                    <small class="text-muted">Eligible Now</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-warning h-100">
                                <div class="card-body text-center py-3">
                                    <div class="h-50px w-50px rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-4 mx-auto mb-2"><i class="bi bi-hourglass-split"></i></div>
                                    <h4 class="fw-bold text-warning mb-0" x-text="ticketStats.eligible_30"></h4>
                                    <small class="text-muted">Eligible in 30 Days</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-info h-100">
                                <div class="card-body text-center py-3">
                                    <div class="h-50px w-50px rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fs-4 mx-auto mb-2"><i class="bi bi-pause-circle"></i></div>
                                    <h4 class="fw-bold text-info mb-0" x-text="ticketStats.delayed"></h4>
                                    <small class="text-muted">Delayed by Leave</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-danger h-100">
                                <div class="card-body text-center py-3">
                                    <div class="h-50px w-50px rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fs-4 mx-auto mb-2"><i class="bi bi-exclamation-triangle"></i></div>
                                    <h4 class="fw-bold text-danger mb-0" x-text="ticketStats.upcoming"></h4>
                                    <small class="text-muted">Still Working</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="card-title mb-0 fw-semibold text-dark">Flight Ticket Eligibility — 2-Year Service Tracker</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee</th>
                                            <th>Nationality</th>
                                            <th>Business Category</th>
                                            <th>Joined</th>
                                            <th>Actual Service Days</th>
                                            <th>Paused Days (Leaves)</th>
                                            <th>Eligible On</th>
                                            <th class="pe-4">Ticket Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="emp in ticketEmployees" :key="emp.id">
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold text-dark" x-text="emp.full_name"></div>
                                                    <div class="text-muted fs-12" x-text="emp.employee_id"></div>
                                                </td>
                                                <td x-text="emp.nationality"></td>
                                                <td x-text="emp.business_category"></td>
                                                <td x-text="emp.joining_date"></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress" style="width: 80px; height: 6px;">
                                                            <div class="progress-bar bg-primary" :style="'width:' + Math.min(100, (emp.working_service_days / 730) * 100) + '%'"></div>
                                                        </div>
                                                        <span class="fs-12 fw-semibold" x-text="emp.working_service_days + '/730'"></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span :class="emp.paused_days > 0 ? 'text-warning fw-semibold' : 'text-muted'" x-text="emp.paused_days + ' days'"></span>
                                                </td>
                                                <td x-text="emp.ticket_eligibility_date"></td>
                                                <td class="pe-4">
                                                    <span class="badge px-2 py-1"
                                                          :class="emp.ticket_status === 'Eligible' ? 'bg-success-subtle text-success' : (emp.ticket_status === 'Overdue' ? 'bg-danger-subtle text-danger' : (emp.ticket_status === 'Eligible in 30 Days' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary'))"
                                                          x-text="emp.ticket_status"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="ticketEmployees.length === 0">
                                            <tr><td colspan="8" class="text-center py-5 text-muted">No employee data available.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 11: REPORTS (Phase 10) -->
                <div x-show="page === 'reports'" x-transition>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-people fs-1 text-primary mb-3 d-block"></i>
                                    <h5 class="fw-bold text-dark">Employee Master List</h5>
                                    <p class="text-muted fs-13">Download complete employee roster with all details.</p>
                                    <a href="/api/export/employees" class="btn btn-primary btn-sm me-2" target="_blank">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-file-earmark-text fs-1 text-warning mb-3 d-block"></i>
                                    <h5 class="fw-bold text-dark">Document Expiry Report</h5>
                                    <p class="text-muted fs-13">All expiring or expired documents across all employees.</p>
                                    <a href="/api/export/expiries" class="btn btn-warning btn-sm me-2 text-white" target="_blank">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-calendar-check fs-1 text-success mb-3 d-block"></i>
                                    <h5 class="fw-bold text-dark">Leave Summary Report</h5>
                                    <p class="text-muted fs-13">Leave history across all employees with approval details.</p>
                                    <a href="/api/export/leaves" class="btn btn-success btn-sm me-2" target="_blank">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-airplane fs-1 text-info mb-3 d-block"></i>
                                    <h5 class="fw-bold text-dark">Flight Ticket Eligibility</h5>
                                    <p class="text-muted fs-13">Track which employees have reached 2-year service milestone.</p>
                                    <a href="/api/export/tickets" class="btn btn-info btn-sm me-2 text-white" target="_blank">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SECTION 12: USER MANAGEMENT (Phase 9) -->
                <div x-show="page === 'users'" x-transition>
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
                            <h5 class="card-title mb-0 text-dark fw-semibold"><i class="bi bi-person-gear me-2 text-primary"></i>User Management</h5>
                            <button class="btn btn-sm btn-primary" @click="openAddUser()">
                                <i class="bi bi-plus-lg me-1"></i>Add User
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                            <th class="pe-4 text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="usr in sysUsers" :key="usr.id">
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="h-35px w-35px rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-13" x-text="usr.name.charAt(0)"></div>
                                                        <span class="fw-semibold text-dark" x-text="usr.name"></span>
                                                    </div>
                                                </td>
                                                <td x-text="usr.email"></td>
                                                <td>
                                                    <span class="badge px-2 py-1 rounded" :class="usr.role === 'admin' ? 'role-admin' : 'role-user'" x-text="usr.role.charAt(0).toUpperCase() + usr.role.slice(1)"></span>
                                                </td>
                                                <td x-text="usr.created_at"></td>
                                                <td class="pe-4 text-end">
                                                    <button class="btn btn-sm btn-light border px-2 py-1 me-1" @click="openEditUser(usr)"><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-sm btn-light border px-2 py-1 text-danger" @click="deleteUser(usr.id)" :disabled="usr.id == currentUserId"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="sysUsers.length === 0">
                                            <tr><td colspan="5" class="text-center py-5 text-muted">No users found.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 13: NOTIFICATIONS & ALERTS (Phase 11) -->
                <div x-show="page === 'notifications'" x-transition>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <div class="card border-danger h-100">
                                <div class="card-body d-flex align-items-center gap-3 py-3">
                                    <div class="h-50px w-50px rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fs-4"><i class="bi bi-file-earmark-x"></i></div>
                                    <div>
                                        <h4 class="fw-bold text-danger mb-0" x-text="notifAlerts.total_doc_alerts || 0"></h4>
                                        <small class="text-muted">Document Expiry Alerts</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-warning h-100">
                                <div class="card-body d-flex align-items-center gap-3 py-3">
                                    <div class="h-50px w-50px rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-4"><i class="bi bi-airplane"></i></div>
                                    <div>
                                        <h4 class="fw-bold text-warning mb-0" x-text="notifAlerts.total_ticket_alerts || 0"></h4>
                                        <small class="text-muted">Ticket Eligibility Alerts</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Expiry Alerts Table -->
                    <div class="card mb-4">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="card-title mb-0 fw-semibold text-dark"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Document Expiry Alerts (Next 90 Days)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee</th>
                                            <th>Document</th>
                                            <th>Document No.</th>
                                            <th>Expiry Date</th>
                                            <th>Days Left</th>
                                            <th class="pe-4">Urgency</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="alert in (notifAlerts.document_alerts || [])" :key="alert.id">
                                            <tr :class="alert.urgency === 'expired' ? 'table-danger' : (alert.urgency === 'critical' ? 'table-warning' : '')">
                                                <td class="ps-4">
                                                    <div class="fw-semibold text-dark" x-text="alert.employee_name"></div>
                                                </td>
                                                <td><span class="badge bg-primary-subtle text-primary" x-text="alert.document_type"></span></td>
                                                <td x-text="alert.document_number"></td>
                                                <td x-text="alert.expiry_date"></td>
                                                <td>
                                                    <span class="fw-semibold"
                                                          :class="alert.days_left < 0 ? 'text-danger' : (alert.days_left <= 7 ? 'text-danger' : (alert.days_left <= 30 ? 'text-warning' : 'text-muted'))">
                                                        <span x-show="alert.days_left < 0" x-text="Math.abs(alert.days_left) + ' days OVERDUE'"></span>
                                                        <span x-show="alert.days_left >= 0" x-text="alert.days_left + ' days left'"></span>
                                                    </span>
                                                </td>
                                                <td class="pe-4">
                                                    <span class="badge px-2 py-1"
                                                          :class="alert.urgency === 'expired' ? 'bg-danger text-white' : (alert.urgency === 'critical' ? 'bg-danger-subtle text-danger' : (alert.urgency === 'warning' ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info'))"
                                                          x-text="alert.urgency.charAt(0).toUpperCase() + alert.urgency.slice(1)"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="!(notifAlerts.document_alerts && notifAlerts.document_alerts.length > 0)">
                                            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>No document expiry alerts. All clear!</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Eligibility Alerts -->
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="card-title mb-0 fw-semibold text-dark"><i class="bi bi-airplane-fill text-success me-2"></i>Flight Ticket Eligibility Alerts</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee</th>
                                            <th>Employee ID</th>
                                            <th>Ticket Status</th>
                                            <th class="pe-4">Eligibility Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="ta in (notifAlerts.ticket_alerts || [])" :key="ta.id">
                                            <tr>
                                                <td class="ps-4 fw-semibold text-dark" x-text="ta.full_name"></td>
                                                <td x-text="ta.employee_id"></td>
                                                <td>
                                                    <span class="badge px-2 py-1"
                                                          :class="ta.ticket_status === 'Eligible' ? 'bg-success-subtle text-success' : (ta.ticket_status === 'Overdue' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning')"
                                                          x-text="ta.ticket_status"></span>
                                                </td>
                                                <td class="pe-4" x-text="ta.eligibility_date"></td>
                                            </tr>
                                        </template>
                                        <template x-if="!(notifAlerts.ticket_alerts && notifAlerts.ticket_alerts.length > 0)">
                                            <tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>No ticket eligibility alerts.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Add Category Modal -->
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;" x-show="showCategoryModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark">Add Business Category</h5>
                        <button type="button" class="btn-close" @click="showCategoryModal = false"></button>
                    </div>
                    <form @submit.prevent="submitCategory">
                        <div class="modal-body p-4">
                            <div x-show="categoryModalForm.errorMessage" class="alert alert-danger p-2 fs-13 text-center" x-text="categoryModalForm.errorMessage"></div>
                            <div class="mb-3">
                                <label class="form-label text-dark">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="e.g. Restaurant, Boofiya, Cafe" x-model="categoryModalForm.name" required>
                            </div>
                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-secondary btn-sm" @click="showCategoryModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="categoryModalForm.loading">
                                <span x-show="!categoryModalForm.loading">Create Category</span>
                                <span x-show="categoryModalForm.loading" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add/Edit Employee Modal -->
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;" x-show="showEmployeeModal">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark" x-text="employeeModalForm.id ? 'Edit Employee' : 'Add New Employee'">Add Employee</h5>
                        <button type="button" class="btn-close" @click="showEmployeeModal = false"></button>
                    </div>

                    <!-- Wizard step indicator: create-flow only -->
                    <div class="px-4 pt-3 d-flex gap-1 border-bottom" x-show="!employeeModalForm.id">
                        <span class="modal-tab-btn" :class="wizardStep === 1 ? 'active' : ''">1. Personal Info</span>
                        <span class="modal-tab-btn" :class="wizardStep === 2 ? 'active' : ''">2. Employment & Access</span>
                        <span class="modal-tab-btn" :class="wizardStep === 3 ? 'active' : ''">3. Documents</span>
                    </div>

                    <form @submit.prevent="submitEmployee" enctype="multipart/form-data" class="d-flex flex-column overflow-hidden">
                        <div class="modal-body p-4" style="flex: 1 1 auto; min-height: 0;">
                            <div x-show="employeeModalForm.errorMessage" class="alert alert-danger p-2 fs-13" x-text="employeeModalForm.errorMessage"></div>

                            <!-- STEP 1: Personal Information -->
                            <div x-show="employeeModalForm.id || wizardStep === 1">
                                <h6 class="text-muted fw-semibold mb-3 border-bottom pb-2">Personal Information</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.full_name" :required="employeeModalForm.id">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Arabic Name</label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.arabic_name" dir="rtl">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="employeeModalForm.gender" :required="employeeModalForm.id">
                                            <option value="" disabled>Select</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" x-model="employeeModalForm.date_of_birth" :required="employeeModalForm.id">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="employeeModalForm.nationality" :required="employeeModalForm.id">
                                            <option value="" disabled>Select Nationality</option>
                                            <template x-for="n in masterData.nationalities" :key="n">
                                                <option :value="n" x-text="n"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.mobile_number" :required="employeeModalForm.id">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" x-model="employeeModalForm.email">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.address">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Emergency Contact Name</label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.emergency_contact_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Emergency Contact Phone</label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.emergency_contact_phone">
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 2: Employment Information + optional system login -->
                            <div x-show="employeeModalForm.id || wizardStep === 2">
                                <h6 class="text-muted fw-semibold mb-3 border-bottom pb-2">Employment Information</h6>
                                <div class="row g-3">
                                    <div class="col-md-4" x-show="employeeModalForm.id">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" x-model="employeeModalForm.employee_id" disabled>
                                    </div>
                                    <div class="col-md-4" x-show="!employeeModalForm.id">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" value="Auto-generated on save" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Joining Date</label>
                                        <input type="date" class="form-control" x-model="employeeModalForm.joining_date">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Business Category <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="employeeModalForm.business_category_id" :required="employeeModalForm.id">
                                            <option value="" disabled>Select Business Category</option>
                                            <template x-for="cat in categories" :key="cat.id">
                                                <option :value="cat.id" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="employeeModalForm.designation" :required="employeeModalForm.id">
                                            <option value="" disabled>Select Designation</option>
                                            <template x-if="masterData.designations.length > 0">
                                                <template x-for="d in masterData.designations" :key="d">
                                                    <option :value="d" x-text="d"></option>
                                                </template>
                                            </template>
                                            <template x-if="masterData.designations.length === 0">
                                                <option :value="employeeModalForm.designation" x-text="employeeModalForm.designation || 'Loading...'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Salary (SAR) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" x-model="employeeModalForm.salary" min="0" :required="employeeModalForm.id">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Shift <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="employeeModalForm.shift" :required="employeeModalForm.id">
                                            <option value="" disabled>Select</option>
                                            <option>Morning</option>
                                            <option>Evening</option>
                                            <option>Night</option>
                                            <option>Split</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Employment Status <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="employeeModalForm.employment_status" :required="employeeModalForm.id">
                                            <option>Active</option>
                                            <option>On Leave</option>
                                            <option>Terminated</option>
                                            <option>Resigned</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" class="form-control" accept="image/*" @change="employeeModalForm.photoFile = $event.target.files[0]">
                                    </div>
                                </div>

                                <template x-if="!employeeModalForm.id">
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="createLoginCheck" x-model="employeeModalForm.create_login">
                                            <label class="form-check-label fw-semibold text-dark" for="createLoginCheck">Grant system access (create a login for this employee)</label>
                                        </div>
                                        <div class="row g-3 mt-1" x-show="employeeModalForm.create_login">
                                            <div class="col-md-6">
                                                <label class="form-label">Login Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" x-model="employeeModalForm.login_email">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Login Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" x-model="employeeModalForm.login_password" placeholder="Min. 6 characters">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- STEP 3: Documents (create-flow only) -->
                            <template x-if="!employeeModalForm.id">
                                <div x-show="wizardStep === 3">
                                    <h6 class="text-muted fw-semibold mb-3 border-bottom pb-2">Documents <span class="fw-normal fs-12">(optional — can also be added later)</span></h6>
                                    <template x-for="docType in documentTypeList" :key="docType.key">
                                        <div class="border rounded p-3 mb-3">
                                            <h6 class="fw-semibold text-dark mb-3" x-text="docType.label"></h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Document Number</label>
                                                    <input type="text" class="form-control" x-model="employeeModalForm.documents[docType.key].document_number">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Place of Issue</label>
                                                    <input type="text" class="form-control" x-model="employeeModalForm.documents[docType.key].place_of_issue">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Issue Date</label>
                                                    <input type="date" class="form-control" x-model="employeeModalForm.documents[docType.key].issue_date">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Expiry Date</label>
                                                    <input type="date" class="form-control" x-model="employeeModalForm.documents[docType.key].expiry_date">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">File</label>
                                                    <input type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @change="employeeModalForm.documents[docType.key].file = $event.target.files[0]">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="modal-footer border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-light btn-sm" x-show="!employeeModalForm.id && wizardStep > 1" @click="wizardStep--">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </button>
                            <div class="d-flex gap-2 ms-auto">
                                <button type="button" class="btn btn-secondary btn-sm" @click="showEmployeeModal = false">Cancel</button>
                                <button type="button" class="btn btn-primary btn-sm" x-show="!employeeModalForm.id && wizardStep < 3" @click="goToNextWizardStep()">
                                    Next
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm" x-show="employeeModalForm.id || wizardStep === 3" :disabled="employeeModalForm.loading">
                                    <span x-show="!employeeModalForm.loading" x-text="employeeModalForm.id ? 'Save Changes' : 'Add Employee'"></span>
                                    <span x-show="employeeModalForm.loading" class="spinner-border spinner-border-sm" role="status"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Employee View Modal (Detail/Profile) - UPGRADED -->
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;" x-show="showViewEmployeeModal">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <template x-if="viewEmpData && viewEmpData.profile_photo">
                                <img :src="viewEmpData.profile_photo" class="emp-avatar-lg" :alt="viewEmpData ? viewEmpData.full_name : ''">
                            </template>
                            <template x-if="viewEmpData && !viewEmpData.profile_photo">
                                <div class="h-55px w-55px rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fs-3 fw-bold" x-text="viewEmpData.full_name.charAt(0)"></div>
                            </template>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0" x-text="viewEmpData ? viewEmpData.full_name : 'Employee Profile'"></h5>
                                <div class="d-flex gap-2 align-items-center mt-1">
                                    <span class="badge" :class="viewEmpData && viewEmpData.employment_status === 'Active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'" x-text="viewEmpData ? viewEmpData.employment_status : ''"></span>
                                    <span class="text-muted fs-13" x-text="viewEmpData ? viewEmpData.employee_id : ''"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" @click="showViewEmployeeModal = false"></button>
                    </div>
                    <div class="modal-body p-0" x-show="viewEmpData">
                        <template x-if="viewEmpData">
                            <div>
                                <!-- Tab Navigation -->
                                <div class="border-bottom px-4 d-flex gap-1">
                                    <button class="modal-tab-btn" :class="viewEmpTab === 'info' ? 'active' : ''" @click="viewEmpTab = 'info'"><i class="bi bi-person me-1"></i>Profile</button>
                                    <button class="modal-tab-btn" :class="viewEmpTab === 'docs' ? 'active' : ''" @click="viewEmpTab = 'docs'">
                                        <i class="bi bi-file-earmark-text me-1"></i>Documents
                                        <span class="badge bg-primary-subtle text-primary ms-1" x-text="viewEmpData.documents.length"></span>
                                    </button>
                                    <button class="modal-tab-btn" :class="viewEmpTab === 'leaves' ? 'active' : ''" @click="viewEmpTab = 'leaves'">
                                        <i class="bi bi-calendar-check me-1"></i>Leaves
                                        <span class="badge bg-warning-subtle text-warning ms-1" x-text="viewEmpData.leaves.length"></span>
                                    </button>
                                    <button class="modal-tab-btn" :class="viewEmpTab === 'ticket' ? 'active' : ''" @click="viewEmpTab = 'ticket'"><i class="bi bi-airplane me-1"></i>Ticket</button>
                                </div>

                                <!-- TAB: Profile Info -->
                                <div class="p-4" x-show="viewEmpTab === 'info'">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <h6 class="text-muted fw-semibold mb-3 border-bottom pb-2"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Personal Information</h6>
                                            <table class="table table-sm fs-13">
                                                <tr><td class="text-muted" style="width:40%">Full Name</td><td class="fw-semibold" x-text="viewEmpData.full_name"></td></tr>
                                                <tr><td class="text-muted">Arabic Name</td><td dir="rtl" x-text="viewEmpData.arabic_name || '—'"></td></tr>
                                                <tr><td class="text-muted">Gender</td><td x-text="viewEmpData.gender"></td></tr>
                                                <tr><td class="text-muted">Date of Birth</td><td x-text="viewEmpData.date_of_birth || '—'"></td></tr>
                                                <tr><td class="text-muted">Nationality</td><td x-text="viewEmpData.nationality"></td></tr>
                                                <tr><td class="text-muted">Mobile</td><td x-text="viewEmpData.mobile_number"></td></tr>
                                                <tr><td class="text-muted">Email</td><td x-text="viewEmpData.email || '—'"></td></tr>
                                                <tr><td class="text-muted">Address</td><td x-text="viewEmpData.address || '—'"></td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted fw-semibold mb-3 border-bottom pb-2"><i class="bi bi-briefcase-fill me-2 text-success"></i>Employment Information</h6>
                                            <table class="table table-sm fs-13">
                                                <tr><td class="text-muted" style="width:40%">Employee ID</td><td class="fw-semibold" x-text="viewEmpData.employee_id"></td></tr>
                                                <tr><td class="text-muted">Business Category</td><td x-text="viewEmpData.business_category"></td></tr>
                                                <tr><td class="text-muted">Designation</td><td x-text="viewEmpData.designation"></td></tr>
                                                <tr><td class="text-muted">Joining Date</td><td x-text="viewEmpData.joining_date || 'Not set'"></td></tr>
                                                <tr><td class="text-muted">System Login</td><td x-text="viewEmpData.has_login ? viewEmpData.login_email : 'No login access'"></td></tr>
                                                <tr><td class="text-muted">Shift</td><td x-text="viewEmpData.shift"></td></tr>
                                                <tr><td class="text-muted">Salary</td><td class="fw-semibold text-success" x-text="'SAR ' + Number(viewEmpData.salary).toLocaleString()"></td></tr>
                                            </table>
                                            <h6 class="text-muted fw-semibold mb-3 border-bottom pb-2 mt-2"><i class="bi bi-telephone-fill me-2 text-warning"></i>Emergency Contact</h6>
                                            <table class="table table-sm fs-13">
                                                <tr><td class="text-muted" style="width:40%">Name</td><td x-text="viewEmpData.emergency_contact_name || '—'"></td></tr>
                                                <tr><td class="text-muted">Phone</td><td x-text="viewEmpData.emergency_contact_phone || '—'"></td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- TAB: Documents -->
                                <div class="p-4" x-show="viewEmpTab === 'docs'">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Employee Documents</h6>
                                        <button x-show="userRole === 'admin'" class="btn btn-sm btn-primary" @click="openAddDocument(viewEmpData.id)">
                                            <i class="bi bi-plus-lg me-1"></i>Add Document
                                        </button>
                                    </div>
                                    <div class="row g-3">
                                        <template x-for="doc in viewEmpData.documents" :key="doc.id">
                                            <div class="col-md-6">
                                                <div class="doc-card">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <span class="badge bg-primary-subtle text-primary" x-text="doc.type"></span>
                                                        <div class="d-flex gap-1 align-items-center">
                                                            <span class="badge fs-10" :class="doc.status === 'Expired' ? 'bg-danger-subtle text-danger' : (doc.status === 'Expiring Soon' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success')" x-text="doc.status"></span>
                                                            <template x-if="userRole === 'admin'">
                                                                <span>
                                                                    <button class="btn btn-xs btn-sm p-0 px-1 text-warning" @click="openEditDocument(doc, viewEmpData.id)" title="Edit"><i class="bi bi-pencil-fill fs-12"></i></button>
                                                                    <button class="btn btn-xs btn-sm p-0 px-1 text-danger" @click="confirmDeleteDocument(doc)" title="Delete"><i class="bi bi-trash-fill fs-12"></i></button>
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    <div class="fs-12 text-muted">No: <span class="text-dark fw-medium" x-text="doc.document_number"></span></div>
                                                    <div class="fs-12 text-muted" x-show="doc.place_of_issue">Issued at: <span class="text-dark" x-text="doc.place_of_issue"></span></div>
                                                    <div class="fs-12 text-muted" x-show="doc.issue_date">Issue Date: <span class="text-dark" x-text="doc.issue_date"></span></div>
                                                    <div class="fs-12 text-muted" x-show="doc.expiry_date">Expires: <span :class="doc.status === 'Expired' ? 'text-danger fw-semibold' : 'text-dark'" x-text="doc.expiry_date"></span></div>
                                                    <div class="mt-2" x-show="doc.file_path">
                                                        <a :href="doc.file_path" target="_blank" class="btn btn-xs btn-sm btn-outline-secondary py-0 fs-11"><i class="bi bi-file-earmark-arrow-down me-1"></i>View File</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="viewEmpData.documents.length === 0">
                                            <div class="col-12 text-center py-4 text-muted">
                                                <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>No documents added yet.
                                                <br><button x-show="userRole === 'admin'" class="btn btn-sm btn-primary mt-2" @click="openAddDocument(viewEmpData.id)"><i class="bi bi-plus-lg me-1"></i>Add First Document</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- TAB: Leaves -->
                                <div class="p-4" x-show="viewEmpTab === 'leaves'">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-check me-2 text-warning"></i>Leave History</h6>
                                        <button x-show="userRole === 'admin'" class="btn btn-sm btn-primary" @click="openAddLeave(); leaveModalForm.employee_id = viewEmpData.id">
                                            <i class="bi bi-plus-lg me-1"></i>Add Leave
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm fs-13 align-middle">
                                            <thead class="table-light">
                                                <tr><th>Type</th><th>Duration</th><th>Dates</th><th>Reason</th><th>Status</th><th x-show="userRole === 'admin'" class="text-end">Actions</th></tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="lv in viewEmpData.leaves" :key="lv.id">
                                                    <tr>
                                                        <td><span class="badge bg-light text-dark border" x-text="lv.leave_type"></span></td>
                                                        <td x-text="lv.duration_days + ' days'"></td>
                                                        <td x-text="lv.start_date + ' → ' + lv.end_date"></td>
                                                        <td class="text-muted" x-text="lv.reason || '—'" style="max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></td>
                                                        <td><span class="badge" :class="lv.status === 'Approved' ? 'bg-success-subtle text-success' : (lv.status === 'Rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning')" x-text="lv.status"></span></td>
                                                        <td x-show="userRole === 'admin'" class="text-end">
                                                            <button class="btn btn-xs btn-outline-primary me-1" @click="openEditLeave(lv)"><i class="bi bi-pencil"></i></button>
                                                            <button class="btn btn-xs btn-outline-danger" @click="confirmDeleteLeave(lv)"><i class="bi bi-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <template x-if="viewEmpData.leaves.length === 0">
                                                    <tr><td colspan="6" class="text-muted text-center py-3"><i class="bi bi-calendar-x me-1"></i>No leave records.</td></tr>
                                                </template>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- TAB: Flight Ticket -->
                                <div class="p-4" x-show="viewEmpTab === 'ticket'">
                                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-airplane-fill me-2 text-info"></i>Flight Ticket Eligibility (2-Year Policy)</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <div class="card border text-center p-3">
                                                <div class="text-muted fs-12 mb-1">Status</div>
                                                <span class="badge px-3 py-2 fs-13" :class="viewEmpData.ticket_status === 'Eligible' ? 'bg-success-subtle text-success' : (viewEmpData.ticket_status === 'Overdue' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info')" x-text="viewEmpData.ticket_status"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border text-center p-3">
                                                <div class="text-muted fs-12 mb-1">Service Days</div>
                                                <div class="fw-bold fs-5" x-text="viewEmpData.working_service_days + '/730'"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border text-center p-3">
                                                <div class="text-muted fs-12 mb-1">Paused Days</div>
                                                <div class="fw-bold fs-5 text-warning" x-text="viewEmpData.paused_days + ' days'"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border text-center p-3">
                                                <div class="text-muted fs-12 mb-1">Eligible On</div>
                                                <div class="fw-bold fs-13" x-text="viewEmpData.ticket_eligibility_date || '—'"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-1 d-flex justify-content-between">
                                        <small class="text-muted">Service Progress</small>
                                        <small class="fw-semibold" x-text="Math.min(100, Math.round((viewEmpData.working_service_days / 730) * 100)) + '%'"></small>
                                    </div>
                                    <div class="progress mb-3" style="height: 12px; border-radius: 8px;">
                                        <div class="progress-bar" :class="viewEmpData.ticket_status === 'Eligible' ? 'bg-success' : 'bg-primary'" :style="'width:' + Math.min(100, (viewEmpData.working_service_days / 730) * 100) + '%'"></div>
                                    </div>
                                    <small class="text-muted">Joining Date: <strong x-text="viewEmpData.joining_date"></strong></small>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer border-top">
                        <template x-if="userRole === 'admin' && viewEmpData">
                            <button class="btn btn-warning btn-sm me-auto" @click="openEditEmployee(viewEmpData); showViewEmployeeModal = false;">
                                <i class="bi bi-pencil-fill me-1"></i>Edit Employee
                            </button>
                        </template>
                        <button type="button" class="btn btn-secondary btn-sm" @click="showViewEmployeeModal = false">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Document Modal -->
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5); z-index: 1055;" x-show="showDocumentModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark" x-text="docModalForm.id ? 'Edit Document' : 'Add Document'"></h5>
                        <button type="button" class="btn-close" @click="showDocumentModal = false"></button>
                    </div>
                    <form @submit.prevent="submitDocument" enctype="multipart/form-data">
                        <div class="modal-body p-4">
                            <div x-show="docModalForm.errorMessage" class="alert alert-danger p-2 fs-13" x-text="docModalForm.errorMessage"></div>
                            <div class="mb-3">
                                <label class="form-label">Document Type <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="docModalForm.type" required>
                                    <option value="" disabled>Select Type</option>
                                    <option>Iqama (Residence Permit)</option>
                                    <option>Passport</option>
                                    <option>Health Insurance</option>
                                    <option>Baladiya (Municipality License)</option>
                                    <option>Driving License</option>
                                    <option>Work Permit</option>
                                    <option>Exit/Re-entry Visa</option>
                                    <option>GOSI Registration</option>
                                    <option>Labor Contract</option>
                                    <option>Medical Fitness Certificate</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Document Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="docModalForm.document_number" placeholder="e.g. 2345678901" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Place of Issue</label>
                                    <input type="text" class="form-control" x-model="docModalForm.place_of_issue" placeholder="e.g. Riyadh">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Issue Date</label>
                                    <input type="date" class="form-control" x-model="docModalForm.issue_date">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" x-model="docModalForm.expiry_date">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Document File (Optional)</label>
                                <input type="file" class="form-control" @change="docModalForm.docFile = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">PDF, JPG, PNG — max 5MB</small>
                            </div>
                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-secondary btn-sm" @click="showDocumentModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="docModalForm.loading">
                                <span x-show="!docModalForm.loading" x-text="docModalForm.id ? 'Save Changes' : 'Add Document'"></span>
                                <span x-show="docModalForm.loading" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add / Edit Leave Modal -->
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;" x-show="showLeaveModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark" x-text="leaveModalForm.id ? 'Edit Leave Record' : 'Add Leave Record'"></h5>
                        <button type="button" class="btn-close" @click="showLeaveModal = false"></button>
                    </div>
                    <form @submit.prevent="submitLeave">
                        <div class="modal-body p-4">
                            <div x-show="leaveModalForm.errorMessage" class="alert alert-danger p-2 fs-13" x-text="leaveModalForm.errorMessage"></div>
                            <div class="mb-3">
                                <label class="form-label">Employee <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="leaveModalForm.employee_id" required>
                                    <option value="" disabled>Select Employee</option>
                                    <template x-for="emp in employees" :key="emp.id">
                                        <option :value="emp.id" x-text="emp.full_name + ' (' + emp.employee_id + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="leaveModalForm.leave_type" required>
                                    <option value="" disabled>Select Type</option>
                                    <template x-if="masterData.leave_types.length > 0">
                                        <template x-for="lt in masterData.leave_types" :key="lt">
                                            <option :value="lt" x-text="lt"></option>
                                        </template>
                                    </template>
                                    <template x-if="masterData.leave_types.length === 0">
                                        <template x-for="lt in ['Annual Leave','Emergency Leave','Medical Leave','Unpaid Leave','Casual Leave','Hajj Leave','Maternity Leave']" :key="lt">
                                            <option :value="lt" x-text="lt"></option>
                                        </template>
                                    </template>
                                </select>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" x-model="leaveModalForm.start_date" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" x-model="leaveModalForm.end_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="leaveModalForm.status" required>
                                    <option>Pending</option>
                                    <option>Approved</option>
                                    <option>Rejected</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <textarea class="form-control" x-model="leaveModalForm.reason" rows="2"></textarea>
                            </div>
                            <div x-show="['Emergency Leave','Medical Leave','Unpaid Leave'].includes(leaveModalForm.leave_type)" class="alert alert-warning py-2 fs-12">
                                <i class="bi bi-info-circle me-1"></i> This leave type will <strong>pause</strong> the flight ticket eligibility countdown.
                            </div>
                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-secondary btn-sm" @click="showLeaveModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="leaveModalForm.loading">
                                <span x-show="!leaveModalForm.loading">Save Leave</span>
                                <span x-show="leaveModalForm.loading" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Modal (Phase 9) -->
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;" x-show="showUserModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark" x-text="userModalForm.id ? 'Edit User' : 'Add New User'"></h5>
                        <button type="button" class="btn-close" @click="showUserModal = false"></button>
                    </div>
                    <form @submit.prevent="submitUser">
                        <div class="modal-body p-4">
                            <div x-show="userModalForm.errorMessage" class="alert alert-danger p-2 fs-13" x-text="userModalForm.errorMessage"></div>
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="userModalForm.name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" x-model="userModalForm.email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="userModalForm.role" required>
                                    <option value="user">User (Read-Only)</option>
                                    <option value="admin">Admin (Full Access)</option>
                                </select>
                                <small class="text-muted fs-12 mt-1 d-block">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Admin</strong>: Full CRUD access. <strong>User</strong>: Read-only access to all modules.
                                </small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password <span x-show="!userModalForm.id" class="text-danger">*</span> <span x-show="userModalForm.id" class="text-muted fs-12">(leave blank to keep current)</span></label>
                                <input type="password" class="form-control" x-model="userModalForm.password" :required="!userModalForm.id" placeholder="Min. 6 characters">
                            </div>
                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-secondary btn-sm" @click="showUserModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="userModalForm.loading">
                                <span x-show="!userModalForm.loading" x-text="userModalForm.id ? 'Save Changes' : 'Create User'"></span>
                                <span x-show="userModalForm.loading" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

            </div>
        </div>
    </template>

</div>
@endsection

@section('js')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('spaApp', () => ({
            // Global App State
            loggedIn: {!! Auth::check() ? 'true' : 'false' !!},
            page: '{{ $page ?? "dashboard" }}',
            userName: {!! json_encode(Auth::user()->name ?? '') !!},
            userEmail: {!! json_encode(Auth::user()->email ?? '') !!},
            userRole: {!! json_encode(Auth::user()->role ?? 'user') !!},
            userAvatar: {!! json_encode(Auth::user()->profile_photo ?? '') !!},
            currentTime: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),

            // Toast Notification System
            toasts: [],
            showToast(message, type = 'success') {
                const id = Date.now();
                this.toasts.push({ id, message, type, fading: false });
                setTimeout(() => {
                    const t = this.toasts.find(t => t.id === id);
                    if (t) t.fading = true;
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 350);
                }, 3500);
            },

            // Custom Confirm Dialog
            confirmDialog: { show: false, title: '', message: '', icon: '⚠️', btnText: 'Delete', btnClass: 'btn-danger', onConfirm: null, onCancel: null },
            showConfirm(options) {
                this.confirmDialog = { show: true, title: options.title || 'Are you sure?', message: options.message || '', icon: options.icon || '⚠️', btnText: options.btnText || 'Delete', btnClass: options.btnClass || 'btn-danger', onConfirm: options.onConfirm || null, onCancel: options.onCancel || null };
            },
            
            // Stats & Logs Lists
            stats: {!! json_encode($stats ?? ['employees' => ['total' => 0, 'active' => 0, 'on_leave' => 0, 'categories' => 0], 'expiries' => ['iqama' => 0, 'passport' => 0, 'insurance' => 0, 'baladiya' => 0, 'driving' => 0, 'total' => 0], 'tickets' => ['eligible_now' => 0, 'eligible_30' => 0, 'eligible_60' => 0, 'overdue' => 0, 'delayed' => 0]]) !!},
            logs: {!! json_encode($logs ?? []) !!},
            categories: {!! json_encode($categories ?? []) !!},
            showCategoryModal: false,
            categoryModalForm: { name: '', errorMessage: '', loading: false },

            // Form Data
            loginForm: { email: '', password: '', showPassword: false, errorMessage: '', loading: false },
            profileForm: { name: '', email: '', password: '', password_confirmation: '', photoFile: null, successMessage: '', errorMessage: '', loading: false },
            settingsForm: { company_name: '', flight_ticket_policy_months: 24, successMessage: '', errorMessage: '', loading: false },

            // Phase 3: Employee Management State
            employees: {!! json_encode($employees ?? []) !!},
            empSearch: '',
            empFilterStatus: '',
            empFilterCategory: '',
            empLoading: false,
            empPage: 1,
            empPerPage: 15,
            showEmployeeModal: false,
            showViewEmployeeModal: false,
            viewEmpData: null,
            viewEmpTab: 'info',
            wizardStep: 1,
            documentTypeList: [
                { key: 'iqama', label: 'Iqama Details' },
                { key: 'passport', label: 'Passport Details' },
                { key: 'insurance', label: 'Health Insurance' },
                { key: 'baladiya', label: 'Baladiya Card' },
                { key: 'driving', label: 'Saudi Driving License' },
            ],
            emptyDocumentsForm() {
                return {
                    iqama: { type: 'Iqama Details', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    passport: { type: 'Passport Details', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    insurance: { type: 'Health Insurance', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    baladiya: { type: 'Baladiya Card', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    driving: { type: 'Saudi Driving License', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                };
            },
            employeeModalForm: {
                id: null, employee_id: '', full_name: '', arabic_name: '',
                gender: 'Male', date_of_birth: '', nationality: '', mobile_number: '', email: '',
                address: '', emergency_contact_name: '', emergency_contact_phone: '',
                joining_date: '', business_category_id: '', designation: '',
                salary: '', shift: 'Morning', employment_status: 'Active',
                create_login: false, login_email: '', login_password: '',
                documents: {
                    iqama: { type: 'Iqama Details', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    passport: { type: 'Passport Details', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    insurance: { type: 'Health Insurance', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    baladiya: { type: 'Baladiya Card', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                    driving: { type: 'Saudi Driving License', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', file: null },
                },
                photoFile: null, errorMessage: '', loading: false
            },

            // Computed pagination
            get empPageStart() { return (this.empPage - 1) * this.empPerPage; },
            get paginatedEmployees() { return this.employees.slice(this.empPageStart, this.empPageStart + this.empPerPage); },
            get empTotalPages() { return Math.max(1, Math.ceil(this.employees.length / this.empPerPage)); },

            // Phase 4: Document Management State
            showDocumentModal: false,
            docModalForm: { id: null, employee_id: null, type: '', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', docFile: null, errorMessage: '', loading: false },

            // Phase 5: Expiry Management State
            expiringDocs: {!! json_encode($expiringDocs ?? []) !!},
            expiryDaysFilter: 90,
            expiryTypeFilter: '',

            // Phase 6: Leave Management State
            leaves: {!! json_encode($leaves ?? []) !!},
            leaveFilterStatus: '',
            leaveFilterType: '',
            showLeaveModal: false,
            leaveModalForm: {
                id: null, employee_id: '', leave_type: '', start_date: '', end_date: '',
                status: 'Pending', reason: '', errorMessage: '', loading: false
            },
            get leaveStats() {
                const stats = { pending: 0, approved: 0, rejected: 0, total_days: 0 };
                this.leaves.forEach(l => {
                    if (l.status === 'Pending') stats.pending++;
                    if (l.status === 'Approved') { stats.approved++; stats.total_days += parseInt(l.duration_days) || 0; }
                    if (l.status === 'Rejected') stats.rejected++;
                });
                return stats;
            },

            // Phase 7: Flight Ticket State
            ticketEmployees: {!! json_encode($ticketEmployees ?? []) !!},
            ticketStats: {!! json_encode($ticketStats ?? ['eligible' => 0, 'eligible_30' => 0, 'delayed' => 0, 'upcoming' => 0]) !!},

            // Phase 9: User Management State
            sysUsers: {!! json_encode($sysUsers ?? []) !!},
            currentUserId: {!! json_encode(Auth::user()->id ?? null) !!},
            showUserModal: false,
            userModalForm: { id: null, name: '', email: '', role: 'user', password: '', errorMessage: '', loading: false },

            // Phase 11: Notifications State
            notifAlerts: {!! json_encode($notifAlerts ?? ['document_alerts' => [], 'ticket_alerts' => [], 'total_doc_alerts' => 0, 'total_ticket_alerts' => 0]) !!},

            // Phase 12: Master Data State
            masterData: {!! json_encode($masterData ?? ['designations' => [], 'leave_types' => [], 'nationalities' => []]) !!},
            masterSaveSuccess: '',
            newDesigName: '',
            newLeaveTypeName: '',
            newNatName: '',

            // Dynamic view title helper
            get pageTitle() {
                if (this.page === 'dashboard') return 'HR System Dashboard';
                if (this.page === 'branches') return 'Business Categories';
                if (this.page === 'profile') return 'My Profile Management';
                if (this.page === 'settings') return 'Company Master Settings';
                if (this.page === 'logs') return 'System Audit Logs';
                
                // Capitalize other page routes
                return this.page.charAt(0).toUpperCase() + this.page.slice(1) + ' Management';
            },

            // SPA Initialization
            init() {
                console.log('Alpine init. STATS IS:', JSON.stringify(this.stats));
                // Initial profile form hydration
                if (this.loggedIn) {
                    this.profileForm.name = this.userName;
                    this.profileForm.email = this.userEmail;
                }

                // Global watcher on page change to load relevant data (fallback)
                this.$watch('page', (value) => {
                    this.loadPageData(value);
                });

                // Real-time clock interval
                setInterval(() => {
                    this.currentTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }, 1000);
            },
            
            // Helper to load data based on the current page route
            loadPageData(value) {
                if (value === 'dashboard') this.loadStats();
                if (value === 'branches') this.loadCategories();
                if (value === 'employees') { this.loadCategories(); this.loadEmployees(); }
                if (value === 'expiries') this.loadExpiries();
                if (value === 'leaves') { this.loadEmployees(); this.loadLeaves(); }
                if (value === 'tickets') this.loadTicketEligibility();
                if (value === 'users') this.loadUsers();
                if (value === 'notifications') this.loadNotificationAlerts();
                if (value === 'logs') this.loadLogs();
                if (value === 'settings') this.loadSettings();
            },



            // Auth: Log in
            async login() {
                this.loginForm.loading = true;
                this.loginForm.errorMessage = '';

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const response = await fetch('/api/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        },
                        body: JSON.stringify({
                            email: this.loginForm.email,
                            password: this.loginForm.password
                        })
                    });

                    if (response.status === 419) {
                        this.loginForm.errorMessage = 'Session expired. Reloading page for a fresh CSRF token...';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                        return;
                    }

                    const data = await response.json();
                    if (data.success) {
                        this.loggedIn = true;
                        this.userName = data.user.name;
                        this.userEmail = data.user.email;
                        this.userRole = data.user.role;
                        this.userAvatar = data.user.profile_photo;
                        this.currentUserId = data.user.id;
                        
                        this.profileForm.name = data.user.name;
                        this.profileForm.email = data.user.email;

                        this.loginForm.email = '';
                        this.loginForm.password = '';
                        
                        this.page = 'dashboard';
                        this.loadStats();
                    } else {
                        this.loginForm.errorMessage = data.message || 'Authentication failed.';
                    }
                } catch (e) {
                    this.loginForm.errorMessage = 'Network error. Please try again.';
                } finally {
                    this.loginForm.loading = false;
                }
            },

            // Auth: Log out
            async logout() {
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        }
                    });
                } catch (e) {
                    console.log('Error during logout request', e);
                } finally {
                    this.loggedIn = false;
                    this.page = 'dashboard';
                    this.userName = '';
                    this.userEmail = '';
                    this.userRole = 'user';
                    this.userAvatar = '';
                }
            },

            // Load dashboard stats
            async loadStats() {
                try {
                    const res = await fetch('/api/dashboard-stats');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.stats = data.stats;
                            // Phase 8: Render charts after data loads
                            this.$nextTick(() => this.renderDashboardCharts(data.stats));
                        }
                    }
                } catch (e) {
                    console.error('Error fetching statistics', e);
                }
            },

            // Load audit logs
            async loadLogs() {
                try {
                    const res = await fetch('/api/activity-logs');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.logs = data.logs;
                        }
                    }
                } catch (e) {
                    console.error('Error fetching audit logs', e);
                }
            },

            // Load company settings
            async loadSettings() {
                try {
                    const res = await fetch('/api/settings');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.settingsForm.company_name = data.settings.company_name;
                            this.settingsForm.flight_ticket_policy_months = data.settings.flight_ticket_policy_months;
                        }
                    }
                } catch (e) {
                    console.error('Error loading settings', e);
                }
            },

            // Handle file input changes for avatar upload
            handleAvatarChange(event) {
                this.profileForm.photoFile = event.target.files[0];
            },

            // Update Profile settings
            async updateProfile() {
                this.profileForm.loading = true;
                this.profileForm.successMessage = '';
                this.profileForm.errorMessage = '';

                try {
                    const formData = new FormData();
                    formData.append('name', this.profileForm.name);
                    formData.append('email', this.profileForm.email);
                    
                    if (this.profileForm.password) {
                        formData.append('password', this.profileForm.password);
                        formData.append('password_confirmation', this.profileForm.password_confirmation);
                    }
                    if (this.profileForm.photoFile) {
                        formData.append('profile_photo_file', this.profileForm.photoFile);
                    }

                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch('/api/profile/update', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        },
                        body: formData
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.profileForm.successMessage = data.message;
                        this.userName = data.user.name;
                        this.userEmail = data.user.email;
                        this.userAvatar = data.user.profile_photo;
                        
                        this.profileForm.password = '';
                        this.profileForm.password_confirmation = '';
                        this.profileForm.photoFile = null;
                    } else {
                        // Gather validation errors
                        if (data.errors) {
                            this.profileForm.errorMessage = Object.values(data.errors).flat().join(', ');
                        } else {
                            this.profileForm.errorMessage = data.message || 'Failed to update profile.';
                        }
                    }
                } catch (e) {
                    this.profileForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.profileForm.loading = false;
                }
            },

            // Update Company Settings
            async updateSettings() {
                this.settingsForm.loading = true;
                this.settingsForm.successMessage = '';
                this.settingsForm.errorMessage = '';

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch('/api/settings/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        },
                        body: JSON.stringify({
                            company_name: this.settingsForm.company_name,
                            flight_ticket_policy_months: this.settingsForm.flight_ticket_policy_months
                        })
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.settingsForm.successMessage = data.message;
                    } else {
                        this.settingsForm.errorMessage = data.message || 'Failed to update settings.';
                    }
                } catch (e) {
                    this.settingsForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.settingsForm.loading = false;
                }
            },

            // Load Categories (Phase 2)
            async loadCategories() {
                try {
                    const res = await fetch('/api/categories');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.categories = data.categories;
                        }
                    }
                } catch (e) {
                    console.error('Error loading categories', e);
                }
            },

            // Submit Category (Phase 2)
            async submitCategory() {
                this.categoryModalForm.loading = true;
                this.categoryModalForm.errorMessage = '';

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch('/api/categories', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        },
                        body: JSON.stringify({ name: this.categoryModalForm.name })
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.categoryModalForm.name = '';
                        this.showCategoryModal = false;
                        this.loadCategories();
                        this.loadStats();
                    } else {
                        this.categoryModalForm.errorMessage = data.message || 'Failed to create category.';
                    }
                } catch (e) {
                    this.categoryModalForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.categoryModalForm.loading = false;
                }
            },

            // Delete Category (Phase 2)
            async deleteCategory(id) {
                if (!confirm('Are you sure you want to delete this category? All employees assigned to it will also be deleted!')) return;

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch(`/api/categories/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.loadCategories();
                        this.loadStats();
                    } else {
                        alert(data.message || 'Failed to delete category.');
                    }
                } catch (e) {
                    console.error('Error deleting category', e);
                }
            },

            // ========== PHASE 3: EMPLOYEE MANAGEMENT ==========

            async loadEmployees() {
                this.empLoading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.empSearch) params.set('search', this.empSearch);
                    if (this.empFilterStatus) params.set('status', this.empFilterStatus);
                    if (this.empFilterCategory) params.set('business_category_id', this.empFilterCategory);

                    const res = await fetch('/api/employees?' + params.toString());
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.employees = data.employees;
                            // reset to page 1 on new filter
                            if (this.empPage > this.empTotalPages) this.empPage = 1;
                        }
                    }
                } catch (e) {
                    console.error('Error loading employees', e);
                } finally {
                    this.empLoading = false;
                }
            },

            openAddEmployee() {
                this.wizardStep = 1;
                this.employeeModalForm = {
                    id: null, employee_id: '', full_name: '', arabic_name: '',
                    gender: 'Male', date_of_birth: '', nationality: '', mobile_number: '', email: '',
                    address: '', emergency_contact_name: '', emergency_contact_phone: '',
                    joining_date: '', business_category_id: '', designation: '',
                    salary: '', shift: 'Morning', employment_status: 'Active',
                    create_login: false, login_email: '', login_password: '',
                    documents: this.emptyDocumentsForm(),
                    photoFile: null, errorMessage: '', loading: false
                };
                this.showEmployeeModal = true;
            },

            openEditEmployee(emp) {
                this.wizardStep = 1;
                this.employeeModalForm = {
                    ...emp,
                    create_login: false, login_email: '', login_password: '',
                    documents: this.emptyDocumentsForm(),
                    photoFile: null, errorMessage: '', loading: false
                };
                this.showEmployeeModal = true;
            },

            async viewEmployee(id) {
                try {
                    const res = await fetch(`/api/employees/${id}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.viewEmpData = data.employee;
                            this.showViewEmployeeModal = true;
                        }
                    }
                } catch (e) {
                    console.error('Error loading employee details', e);
                }
            },

            // Manual per-step validation for the create wizard (native `required` is
            // deliberately off for wizard fields — a required field hidden on a step
            // the user has moved past makes the browser silently refuse to submit).
            validateWizardStep(step) {
                this.employeeModalForm.errorMessage = '';
                const f = this.employeeModalForm;

                if (step === 1) {
                    if (!f.full_name || !f.gender || !f.date_of_birth || !f.nationality || !f.mobile_number) {
                        this.employeeModalForm.errorMessage = 'Please fill in all required fields (marked with *) before continuing.';
                        return false;
                    }
                }
                if (step === 2) {
                    if (!f.business_category_id || !f.designation || !f.salary || !f.shift || !f.employment_status) {
                        this.employeeModalForm.errorMessage = 'Please fill in all required fields (marked with *) before continuing.';
                        return false;
                    }
                    if (f.create_login && (!f.login_email || !f.login_password)) {
                        this.employeeModalForm.errorMessage = 'Please provide a login email and password, or uncheck "Grant system access".';
                        return false;
                    }
                }
                return true;
            },

            goToNextWizardStep() {
                if (!this.validateWizardStep(this.wizardStep)) return;
                this.wizardStep++;
            },

            async submitEmployee() {
                // Wizard navigation on the create flow: advance a step instead of submitting
                if (!this.employeeModalForm.id && this.wizardStep < 3) {
                    if (!this.validateWizardStep(this.wizardStep)) return;
                    this.wizardStep++;
                    return;
                }

                // Defensive re-check in case step 3 was reached without going through the Next guard
                if (!this.employeeModalForm.id && (!this.validateWizardStep(1) || !this.validateWizardStep(2))) {
                    return;
                }

                this.employeeModalForm.loading = true;
                this.employeeModalForm.errorMessage = '';

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const formData = new FormData();

                    const fields = [
                        'full_name','arabic_name','gender','date_of_birth',
                        'nationality','mobile_number','email','address','emergency_contact_name',
                        'emergency_contact_phone','joining_date','business_category_id','designation',
                        'salary','shift','employment_status'
                    ];
                    fields.forEach(f => { if (this.employeeModalForm[f] !== null && this.employeeModalForm[f] !== undefined) formData.append(f, this.employeeModalForm[f]); });
                    if (this.employeeModalForm.photoFile) formData.append('profile_photo_file', this.employeeModalForm.photoFile);

                    if (!this.employeeModalForm.id && this.employeeModalForm.create_login) {
                        formData.append('create_login', '1');
                        formData.append('login_email', this.employeeModalForm.login_email);
                        formData.append('login_password', this.employeeModalForm.login_password);
                    }

                    const url = this.employeeModalForm.id ? `/api/employees/${this.employeeModalForm.id}` : '/api/employees';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' },
                        body: formData
                    });

                    const data = await res.json();
                    if (data.success) {
                        if (!this.employeeModalForm.id && data.employee && data.employee.id) {
                            await this.uploadWizardDocuments(data.employee.id);
                        }
                        this.showEmployeeModal = false;
                        this.showToast(data.message, 'success');
                        this.loadEmployees();
                        this.loadStats();
                    } else {
                        if (data.errors) {
                            this.employeeModalForm.errorMessage = Object.values(data.errors).flat().join('. ');
                        } else {
                            this.employeeModalForm.errorMessage = data.message || 'Failed to save employee.';
                        }
                        this.showToast('Failed to save employee details.', 'error');
                    }
                } catch (e) {
                    this.employeeModalForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.employeeModalForm.loading = false;
                }
            },

            // Upload any filled-in document sections from the Add Employee wizard
            async uploadWizardDocuments(employeeId) {
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                for (const key of Object.keys(this.employeeModalForm.documents)) {
                    const doc = this.employeeModalForm.documents[key];
                    if (!doc.document_number) continue; // section left blank, skip

                    const fd = new FormData();
                    fd.append('employee_id', employeeId);
                    fd.append('type', doc.type);
                    fd.append('document_number', doc.document_number);
                    if (doc.place_of_issue) fd.append('place_of_issue', doc.place_of_issue);
                    if (doc.issue_date) fd.append('issue_date', doc.issue_date);
                    if (doc.expiry_date) fd.append('expiry_date', doc.expiry_date);
                    if (doc.file) fd.append('document_file', doc.file);

                    try {
                        await fetch('/api/documents', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' },
                            body: fd
                        });
                    } catch (e) {
                        console.error('Error uploading document', key, e);
                    }
                }
            },

            confirmDeleteEmployee(emp) {
                this.showConfirm({
                    title: 'Delete Employee',
                    message: `Are you sure you want to delete ${emp.full_name}? All their documents and leaves will also be permanently deleted.`,
                    icon: '🗑️',
                    onConfirm: () => this.deleteEmployee(emp.id)
                });
            },

            async deleteEmployee(id) {
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch(`/api/employees/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' }
                    });
                    const data = await res.json();
                    if (data.success) { 
                        this.showToast('Employee deleted successfully.', 'success');
                        this.loadEmployees(); 
                        this.loadStats(); 
                    } else {
                        this.showToast(data.message || 'Failed to delete employee.', 'error');
                    }
                } catch (e) { console.error(e); }
            },

            // ========== PHASE 4: DOCUMENT MANAGEMENT ==========

            openAddDocument(empId) {
                this.docModalForm = { id: null, employee_id: empId, type: '', document_number: '', place_of_issue: '', issue_date: '', expiry_date: '', docFile: null, errorMessage: '', loading: false };
                this.showDocumentModal = true;
            },

            openEditDocument(doc, empId) {
                this.docModalForm = { id: doc.id, employee_id: empId, type: doc.type, document_number: doc.document_number, place_of_issue: doc.place_of_issue || '', issue_date: doc.issue_date || '', expiry_date: doc.expiry_date || '', docFile: null, errorMessage: '', loading: false };
                this.showDocumentModal = true;
            },

            async submitDocument() {
                this.docModalForm.loading = true;
                this.docModalForm.errorMessage = '';

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const formData = new FormData();
                    formData.append('employee_id', this.docModalForm.employee_id);
                    formData.append('type', this.docModalForm.type);
                    formData.append('document_number', this.docModalForm.document_number);
                    if (this.docModalForm.place_of_issue) formData.append('place_of_issue', this.docModalForm.place_of_issue);
                    if (this.docModalForm.issue_date) formData.append('issue_date', this.docModalForm.issue_date);
                    if (this.docModalForm.expiry_date) formData.append('expiry_date', this.docModalForm.expiry_date);
                    if (this.docModalForm.docFile) formData.append('document_file', this.docModalForm.docFile);

                    const url = this.docModalForm.id ? `/api/documents/${this.docModalForm.id}` : '/api/documents';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' },
                        body: formData
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.showDocumentModal = false;
                        this.showToast(data.message, 'success');
                        this.viewEmployee(this.docModalForm.employee_id); // refresh profile view
                        this.loadStats();
                    } else {
                        if (data.errors) {
                            this.docModalForm.errorMessage = Object.values(data.errors).flat().join('. ');
                        } else {
                            this.docModalForm.errorMessage = data.message || 'Failed to save document.';
                        }
                    }
                } catch (e) {
                    this.docModalForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.docModalForm.loading = false;
                }
            },

            confirmDeleteDocument(doc) {
                this.showConfirm({
                    title: 'Delete Document',
                    message: `Are you sure you want to delete ${doc.type} (${doc.document_number})?`,
                    icon: '🗑️',
                    onConfirm: () => this.deleteDocument(doc.id, doc.employee_id)
                });
            },

            async deleteDocument(id, empId) {
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch(`/api/documents/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' }
                    });
                    const data = await res.json();
                    if (data.success) { 
                        this.showToast('Document deleted successfully.', 'success');
                        this.viewEmployee(empId);
                        this.loadStats();
                    } else {
                        this.showToast(data.message || 'Failed to delete.', 'error');
                    }
                } catch (e) { console.error(e); }
            },

            // ========== PHASE 5: DOCUMENT EXPIRY MANAGEMENT ==========

            async loadExpiries() {
                try {
                    const res = await fetch(`/api/expiring-documents?days=${this.expiryDaysFilter}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) this.expiringDocs = data.documents;
                    }
                } catch (e) {
                    console.error('Error loading expiring documents', e);
                }
            },

            // ========== PHASE 6: LEAVE MANAGEMENT ==========

            async loadLeaves() {
                try {
                    const params = new URLSearchParams();
                    if (this.leaveFilterStatus) params.set('status', this.leaveFilterStatus);
                    if (this.leaveFilterType) params.set('leave_type', this.leaveFilterType);

                    const res = await fetch('/api/leaves?' + params.toString());
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) this.leaves = data.leaves;
                    }
                } catch (e) {
                    console.error('Error loading leaves', e);
                }
            },

            openAddLeave() {
                this.leaveModalForm = {
                    employee_id: '', leave_type: '', start_date: '', end_date: '',
                    status: 'Pending', reason: '', errorMessage: '', loading: false
                };
                this.showLeaveModal = true;
            },

            openEditLeave(leave) {
                this.leaveModalForm = {
                    id: leave.id, employee_id: leave.employee_id, leave_type: leave.leave_type,
                    start_date: leave.start_date, end_date: leave.end_date,
                    status: leave.status, reason: leave.reason || '', errorMessage: '', loading: false
                };
                this.showLeaveModal = true;
            },

            async submitLeave() {
                this.leaveModalForm.loading = true;
                this.leaveModalForm.errorMessage = '';

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const url = this.leaveModalForm.id ? `/api/leaves/${this.leaveModalForm.id}` : '/api/leaves';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        },
                        body: JSON.stringify({
                            employee_id: this.leaveModalForm.employee_id,
                            leave_type: this.leaveModalForm.leave_type,
                            start_date: this.leaveModalForm.start_date,
                            end_date: this.leaveModalForm.end_date,
                            status: this.leaveModalForm.status,
                            reason: this.leaveModalForm.reason
                        })
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.showLeaveModal = false;
                        this.showToast(data.message, 'success');
                        this.loadLeaves();
                        // If view modal is open, refresh it
                        if (this.showViewEmployeeModal && this.viewEmpData && this.viewEmpData.id === parseInt(this.leaveModalForm.employee_id)) {
                            this.viewEmployee(this.viewEmpData.id);
                        }
                    } else {
                        if (data.errors) this.leaveModalForm.errorMessage = Object.values(data.errors).flat().join('. ');
                        else this.leaveModalForm.errorMessage = data.message || 'Failed to save leave.';
                    }
                } catch (e) {
                    this.leaveModalForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.leaveModalForm.loading = false;
                }
            },

            async updateLeaveStatus(id, status) {
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch(`/api/leaves/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : ''
                        },
                        body: JSON.stringify({ status })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showToast(data.message, 'success');
                        this.loadLeaves();
                        if (this.showViewEmployeeModal && this.viewEmpData) this.viewEmployee(this.viewEmpData.id);
                    } else {
                        this.showToast(data.message || 'Failed to update status.', 'error');
                    }
                } catch (e) { console.error(e); }
            },

            confirmDeleteLeave(leave) {
                this.showConfirm({
                    title: 'Delete Leave Record',
                    message: `Are you sure you want to delete this ${leave.leave_type} record?`,
                    icon: '🗑️',
                    onConfirm: () => this.deleteLeave(leave.id)
                });
            },

            async deleteLeave(id) {
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch(`/api/leaves/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showToast('Leave record deleted.', 'success');
                        this.loadLeaves();
                        if (this.showViewEmployeeModal && this.viewEmpData) this.viewEmployee(this.viewEmpData.id);
                    } else {
                        this.showToast(data.message || 'Failed to delete.', 'error');
                    }
                } catch (e) { console.error(e); }
            },

            // ========== PHASE 7: FLIGHT TICKET ELIGIBILITY ==========

            async loadTicketEligibility() {
                try {
                    const res = await fetch('/api/ticket-eligibility');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.ticketEmployees = data.employees;
                            this.ticketStats = {
                                eligible: data.employees.filter(e => e.ticket_status === 'Eligible').length,
                                eligible_30: data.employees.filter(e => e.ticket_status === 'Eligible in 30 Days').length,
                                delayed: data.employees.filter(e => e.paused_days > 0 && e.ticket_status !== 'Eligible').length,
                                upcoming: data.employees.filter(e => e.ticket_status === 'Upcoming').length,
                            };
                        }
                    }
                } catch (e) {
                    console.error('Error loading ticket eligibility', e);
                }
            },

            // ========== PHASE 8: DASHBOARD CHARTS ==========
            // Called after loadStats() to draw Chart.js charts

            _charts: {},

            renderDashboardCharts(stats) {
                if (typeof Chart === 'undefined') return;

                const palette = ['#4361ee','#7209b7','#f72585','#4cc9f0','#3a0ca3','#560bad','#480ca8','#f77f00','#d62828','#023e8a'];
                const getCtx  = id => { const el = document.getElementById(id); return el ? el.getContext('2d') : null; };
                const empty   = id => { const el = document.getElementById(id + 'Empty'); if (el) el.style.display = 'block'; };

                // Destroy old charts
                Object.values(this._charts).forEach(c => c && c.destroy && c.destroy());
                this._charts = {};

                const chartData = stats.charts || {};

                // Business type (category) chart
                if (chartData.by_business && chartData.by_business.labels && chartData.by_business.labels.length > 0) {
                    const ctx = getCtx('businessChart');
                    if (ctx) {
                        this._charts.business = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: chartData.by_business.labels,
                                datasets: [{ data: chartData.by_business.data, backgroundColor: palette, hoverOffset: 6 }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'right' } } }
                        });
                    }
                } else { empty('businessChart'); }

                // Nationality chart
                if (chartData.by_nationality && chartData.by_nationality.labels && chartData.by_nationality.labels.length > 0) {
                    const ctx = getCtx('nationalityChart');
                    if (ctx) {
                        this._charts.nationality = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: chartData.by_nationality.labels,
                                datasets: [{ data: chartData.by_nationality.data, backgroundColor: palette, hoverOffset: 6 }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'right' } } }
                        });
                    }
                } else { empty('nationalityChart'); }
            },

            // ========== PHASE 9: USER MANAGEMENT ==========

            async loadUsers() {
                try {
                    const res = await fetch('/api/users');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) this.sysUsers = data.users;
                    }
                } catch (e) { console.error('Error loading users', e); }
            },

            openAddUser() {
                this.userModalForm = { id: null, name: '', email: '', role: 'user', password: '', errorMessage: '', loading: false };
                this.showUserModal = true;
            },

            openEditUser(usr) {
                this.userModalForm = { ...usr, password: '', errorMessage: '', loading: false };
                this.showUserModal = true;
            },

            async submitUser() {
                this.userModalForm.loading = true;
                this.userModalForm.errorMessage = '';
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const url = this.userModalForm.id ? `/api/users/${this.userModalForm.id}` : '/api/users';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' },
                        body: JSON.stringify({ name: this.userModalForm.name, email: this.userModalForm.email, role: this.userModalForm.role, password: this.userModalForm.password })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showUserModal = false;
                        this.loadUsers();
                    } else {
                        this.userModalForm.errorMessage = data.errors ? Object.values(data.errors).flat().join('. ') : (data.message || 'Failed to save user.');
                    }
                } catch (e) {
                    this.userModalForm.errorMessage = 'Network error occurred.';
                } finally {
                    this.userModalForm.loading = false;
                }
            },

            async deleteUser(id) {
                if (!confirm('Delete this user account? This cannot be undone.')) return;
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch(`/api/users/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' } });
                    const data = await res.json();
                    if (data.success) this.loadUsers();
                    else alert(data.message || 'Failed to delete.');
                } catch (e) { console.error(e); }
            },

            // ========== PHASE 11: NOTIFICATION ALERTS ==========

            async loadNotificationAlerts() {
                try {
                    const res = await fetch('/api/notifications/alerts');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) this.notifAlerts = data;
                    }
                } catch (e) { console.error('Error loading notification alerts', e); }
            },

            // Phase 12: Master Data Methods
            async loadMasterData() {
                try {
                    const res = await fetch('/api/master-data');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success) {
                            this.masterData.designations = data.data.designations || [];
                            this.masterData.leave_types = data.data.leave_types || [];
                            this.masterData.nationalities = data.data.nationalities || [];
                        }
                    }
                } catch (e) { console.error('Error loading master data', e); }
            },
            
            async saveMasterDataField(field) {
                this.settingsForm.loading = true;
                this.masterSaveSuccess = '';
                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const res = await fetch('/api/master-data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '' },
                        body: JSON.stringify({ field: field, values: this.masterData[field] })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.masterSaveSuccess = data.message;
                        setTimeout(() => { this.masterSaveSuccess = ''; }, 3000);
                    } else {
                        alert(data.message || 'Error saving data.');
                    }
                } catch (e) {
                    console.error('Error saving master data', e);
                    alert('An error occurred.');
                } finally {
                    this.settingsForm.loading = false;
                }
            }
        }));
    });
</script>
@endsection
