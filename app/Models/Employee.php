<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'full_name', 'arabic_name', 'profile_photo', 'gender',
        'date_of_birth', 'nationality', 'mobile_number', 'email', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'employee_number',
        'joining_date', 'branch_id', 'department', 'designation', 'salary',
        'shift', 'employment_status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'salary' => 'decimal:2',
    ];

    protected $appends = [
        'working_service_days',
        'paused_days',
        'ticket_eligibility_days_left',
        'ticket_eligibility_date',
        'ticket_status'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    // Dynamic attributes for Flight Ticket Eligibility (Phase 7)
    public function getPausedDaysAttribute()
    {
        // Calculate total days from approved leaves that pause countdown: Emergency, Medical, Unpaid
        $pausingLeaves = $this->leaves()
            ->where('status', 'Approved')
            ->whereIn('leave_type', ['Emergency Leave', 'Medical Leave', 'Unpaid Leave'])
            ->get();

        $totalPausedDays = 0;
        foreach ($pausingLeaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            
            // If the leave ends in the future, we only pause up to today
            $today = Carbon::today();
            if ($end->isAfter($today)) {
                if ($start->isBefore($today)) {
                    $totalPausedDays += $start->diffInDays($today) + 1;
                }
            } else {
                $totalPausedDays += $start->diffInDays($end) + 1;
            }
        }

        return $totalPausedDays;
    }

    public function getWorkingServiceDaysAttribute()
    {
        $joining = Carbon::parse($this->joining_date);
        $today = Carbon::today();
        
        if ($joining->isAfter($today)) {
            return 0;
        }

        $totalCalendarDays = $joining->diffInDays($today);
        $pausedDays = $this->paused_days;

        $workedDays = $totalCalendarDays - $pausedDays;
        return $workedDays < 0 ? 0 : $workedDays;
    }

    public function getTicketEligibilityDaysLeftAttribute()
    {
        $requiredDays = 730; // 2 years
        $workedDays = $this->working_service_days;
        $left = $requiredDays - $workedDays;
        return $left < 0 ? 0 : $left;
    }

    public function getTicketEligibilityDateAttribute()
    {
        $joining = Carbon::parse($this->joining_date);
        $pausedDays = $this->paused_days;
        
        // Target date is joining date + 730 days + paused days
        return $joining->copy()->addDays(730)->addDays($pausedDays);
    }

    public function getTicketStatusAttribute()
    {
        $daysLeft = $this->ticket_eligibility_days_left;
        $eligibilityDate = $this->ticket_eligibility_date;
        $today = Carbon::today();

        if ($daysLeft == 0) {
            return 'Eligible';
        } elseif ($eligibilityDate->isBefore($today)) {
            // Overdue if eligibility date passed but days left is somehow not zero (e.g. status changes)
            return 'Overdue';
        } elseif ($daysLeft <= 30) {
            return 'Eligible in 30 Days';
        } elseif ($daysLeft <= 60) {
            return 'Eligible in 60 Days';
        } else {
            return 'Upcoming';
        }
    }
}
