<?php

namespace App\Console\Commands;

use App\Models\JobAlert;
use App\Notifications\NewJobsForAlert;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('job-alerts:send')]
#[Description('Email candidates the new jobs matching their active job alerts')]
class SendJobAlerts extends Command
{
    /** Maximum jobs listed in one email. */
    private const MAX_JOBS_PER_EMAIL = 10;

    public function handle(): int
    {
        $sent = 0;

        JobAlert::query()->where('is_active', true)->with('user')->chunkById(200, function ($alerts) use (&$sent): void {
            foreach ($alerts as $alert) {
                $jobs = $alert->matchingJobs()->limit(self::MAX_JOBS_PER_EMAIL)->get();

                if ($jobs->isEmpty()) {
                    continue;
                }

                $alert->user->notify(new NewJobsForAlert($alert, $jobs));
                $alert->forceFill(['last_sent_at' => now()])->save();
                $sent++;
            }
        });

        $this->info("Đã gửi {$sent} email thông báo việc làm.");

        return self::SUCCESS;
    }
}
