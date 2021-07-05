<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Mail\RegisteredUserMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;

class SendRegisteredUserNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $tries = 3;
    // public $backoff = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->user->email_verified_at) {       // Delaying jobs if some conditions fails
            $admins = User::where('is_admin', 1)->get();
            foreach ($admins as $admin) {
                Mail::to($admin)->send(new RegisteredUserMail($this->user));
            }
        } else {    // repeating the job a bit later if the condition fails now
            if ($this->attempts() < 2) {
                $this->release(60);
            } else {
                $this->release(120);
            }
        }
    }

    // public function failed(\Throwable $exception)
    // {
        // Log::info("Failed" . \get_class($exception) . ' - ' . $exception->getMessage());
        // $admins = User::where('is_admin', 1)->get();
        // foreach ($admins as $admin) {
        //     Mail::to($admin)->send(info("Failed" . \get_class($exception) . ' - ' . $exception->getMessage()));
        // }
    // }
}
