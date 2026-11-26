<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Notifications\Notification;

/**
 * In-app (bell) notification telling the candidate an interview was scheduled, changed or cancelled.
 */
class InterviewScheduled extends Notification
{
    /**
     * @param  'scheduled'|'rescheduled'|'cancelled'  $kind
     */
    public function __construct(private Interview $interview, private string $kind = 'scheduled') {}

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
        $job = $this->interview->application->jobPost;
        $when = $this->interview->scheduled_at->format('H:i').' ngày '.$this->interview->scheduled_at->format('d/m/Y');
        $where = $this->interview->type->label().($this->interview->location ? ': '.$this->interview->location : '');

        return [
            'icon' => 'calendar',
            'title' => match ($this->kind) {
                'rescheduled' => 'Lịch phỏng vấn đã thay đổi',
                'cancelled' => 'Lịch phỏng vấn đã bị hủy',
                default => 'Bạn có lịch phỏng vấn mới',
            },
            'message' => $this->kind === 'cancelled'
                ? "{$job->company_name} đã hủy buổi phỏng vấn vị trí {$job->title} lúc {$when}."
                : "{$job->company_name} mời bạn phỏng vấn vị trí {$job->title} lúc {$when} ({$where}).",
            'url' => route('candidate.applications.index'),
        ];
    }
}
