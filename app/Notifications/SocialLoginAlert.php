<?php

namespace App\Notifications;

use App\Enums\SocialProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Emails the user's real (provider-verified) address whenever they sign in with a social provider, so an
 * unexpected sign-in is noticed. Sent synchronously: the alert should arrive right away, not when a queue
 * worker happens to run.
 */
class SocialLoginAlert extends Notification
{
    public function __construct(
        public string $userName,
        public SocialProvider $provider,
        public string $providerEmail,
        public Carbon $loggedInAt,
        public ?string $ipAddress,
        public ?string $userAgent,
        public bool $isNewUser,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name');
        $provider = $this->provider->label();

        $message = (new MailMessage)
            ->subject($this->isNewUser
                ? "Chào mừng bạn đến với {$app}"
                : "Cảnh báo bảo mật: đăng nhập mới vào {$app}")
            ->greeting("Xin chào {$this->userName},");

        if ($this->isNewUser) {
            $message->line("Tài khoản {$app} của bạn vừa được tạo bằng tài khoản {$provider} **{$this->providerEmail}**.");
        } else {
            $message->line("Tài khoản {$app} của bạn vừa được đăng nhập bằng tài khoản {$provider} **{$this->providerEmail}**.");
        }

        return $message
            ->line('**Thời gian:** '.$this->loggedInAt->timezone(config('app.timezone'))->format('H:i d/m/Y'))
            ->line('**Địa chỉ IP:** '.($this->ipAddress ?? 'Không xác định'))
            ->line('**Thiết bị:** '.($this->userAgent ?? 'Không xác định'))
            ->action("Mở {$app}", url('/'))
            ->line("Nếu đây không phải bạn, hãy đổi mật khẩu tài khoản {$provider} và liên hệ quản trị viên ngay.");
    }
}
