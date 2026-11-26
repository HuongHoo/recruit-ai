<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the candidate when the employer moves their application to a decisive status.
 */
class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->jobPost;
        $status = $this->application->status;

        $message = (new MailMessage)
            ->subject("[{$status->label()}] {$job->title} — {$job->company_name}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Hồ sơ ứng tuyển vị trí **{$job->title}** tại **{$job->company_name}** đã được cập nhật: **{$status->label()}**.");

        $message = match ($status) {
            ApplicationStatus::Interview => $message->line('Nhà tuyển dụng muốn mời bạn phỏng vấn và sẽ sớm liên hệ để hẹn lịch.'),
            ApplicationStatus::Passed => $message->line('Chúc mừng! Bạn đã vượt qua vòng tuyển chọn.'),
            ApplicationStatus::Rejected => $message->line('Rất tiếc hồ sơ chưa phù hợp lần này. Đừng nản lòng — còn rất nhiều cơ hội khác đang chờ bạn.'),
            default => $message,
        };

        return $message->action('Xem đơn ứng tuyển', route('candidate.applications.index'));
    }

    /**
     * Statuses that trigger an email to the candidate.
     */
    public static function shouldNotify(ApplicationStatus $status): bool
    {
        return in_array($status, [ApplicationStatus::Interview, ApplicationStatus::Passed, ApplicationStatus::Rejected], true);
    }
}
