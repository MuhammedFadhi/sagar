<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'type', 'document_number', 'place_of_issue',
        'issue_date', 'expiry_date', 'file_path', 'additional_metadata'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'additional_metadata' => 'array',
    ];

    protected $appends = [
        'days_until_expiry',
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }

        $expiry = Carbon::parse($this->expiry_date);
        $today = Carbon::today();

        if ($today->isAfter($expiry)) {
            // Already expired, return negative days
            return -$today->diffInDays($expiry);
        }

        return $today->diffInDays($expiry);
    }

    public function getStatusAttribute()
    {
        if (!$this->expiry_date) {
            return 'Valid';
        }

        $days = $this->days_until_expiry;

        if ($days < 0) {
            return 'Expired';
        } elseif ($days <= 90) {
            return 'Expiring Soon';
        } else {
            return 'Valid';
        }
    }
}
