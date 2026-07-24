<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmployeeDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExpiryReminderMail;
use Illuminate\Support\Facades\Log;

class SendExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-expiry-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated email reminders for expiring employee documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Expiry Reminders Check...');
        
        $thresholds = [90, 60, 30, 15, 7, 1];
        $today = Carbon::today();
        
        $documents = EmployeeDocument::with('employee')->whereNotNull('expiry_date')->get();
        $admins = User::where('role', 'admin')->get();
        
        if ($admins->isEmpty()) {
            $this->warn('No admin users found to send emails to.');
            return;
        }

        $emailsSent = 0;

        foreach ($documents as $doc) {
            $expiryDate = Carbon::parse($doc->expiry_date);
            $daysLeft = (int) $today->diffInDays($expiryDate, false);
            
            if (in_array($daysLeft, $thresholds, true)) {
                $this->info("Document {$doc->type} (No. {$doc->document_number}) for {$doc->employee->full_name} is expiring in {$daysLeft} days.");
                
                foreach ($admins as $admin) {
                    try {
                        Mail::to($admin->email)->send(new ExpiryReminderMail($doc, $daysLeft));
                        $emailsSent++;
                    } catch (\Exception $e) {
                        Log::error("Failed to send expiry reminder email to {$admin->email}: " . $e->getMessage());
                        $this->error("Failed to send to {$admin->email}");
                    }
                }
            }
        }
        
        $this->info("Expiry Reminders Check Completed. Total emails sent: {$emailsSent}.");
    }
}
