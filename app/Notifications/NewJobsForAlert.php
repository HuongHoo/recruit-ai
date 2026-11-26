<?php

namespace App\Notifications;

use App\Models\JobAlert;
use App\Models\JobPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Job alert digest: new jobs matching one of the candidate's subscriptions.
 */
class NewJobsForAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, JobPost>  $jobs
     */
    public function __construct(public JobAlert $alert, public Collection $jobs) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("{$this->jobs->count()} việc làm mới phù hợp: {$this->alert->summary()}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Có {$this->jobs->count()} việc làm mới phù hợp với thông báo **{$this->alert->summary()}** của bạn:");

        foreach ($this->jobs as $job) {
            $message->line("• [{$job->title}](".route('jobs.show', $job).") — {$job->company_name} · {$job->location} · {$job->salaryLabel()}");
        }

        return $message
            ->action('Xem tất cả việc làm', route('jobs.index', array_filter([
                'keyword' => $this->alert->keyword,
                'category' => $this->alert->category,
                'location' => $this->alert->location,
            ])))
            ->line('Bạn có thể tắt thông báo này trong mục "Thông báo việc làm".');
    }
}
