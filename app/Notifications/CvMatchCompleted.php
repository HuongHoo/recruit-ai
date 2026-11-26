<?php

namespace App\Notifications;

use App\Models\CvMatchAnalysis;
use App\Models\JobPost;
use Illuminate\Notifications\Notification;

/**
 * In-app (bell) notification sent when the AI finishes comparing a CV with a job.
 */
class CvMatchCompleted extends Notification
{
    public function __construct(private CvMatchAnalysis $analysis, private JobPost $job) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{icon: string, title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'target',
            'title' => 'Đã có kết quả so khớp CV',
            'message' => "CV của bạn phù hợp {$this->analysis->score}% với vị trí {$this->job->title} tại {$this->job->company_name}.",
            'url' => route('candidate.cv-match.show', [$this->job, 'cv_id' => $this->analysis->cv_id]),
        ];
    }
}
