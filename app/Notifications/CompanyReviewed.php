<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the employer the result of the admin's company verification.
 */
class CompanyReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Company $company) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->greeting("Xin chào {$notifiable->name},");

        if ($this->company->isVerified()) {
            return $message
                ->subject("Công ty {$this->company->name} đã được xác thực")
                ->line("Chúc mừng! Công ty **{$this->company->name}** đã được xác thực. Bạn có thể bắt đầu đăng tin tuyển dụng ngay bây giờ.")
                ->action('Đăng tin tuyển dụng', route('employer.jobs.create'));
        }

        return $message
            ->subject("Hồ sơ công ty {$this->company->name} chưa được xác thực")
            ->line("Rất tiếc, hồ sơ công ty **{$this->company->name}** chưa được xác thực vì lý do sau:")
            ->line("> {$this->company->rejection_reason}")
            ->line('Vui lòng cập nhật lại thông tin, hồ sơ sẽ tự động được gửi duyệt lại.')
            ->action('Cập nhật hồ sơ công ty', route('employer.company.edit'));
    }
}
