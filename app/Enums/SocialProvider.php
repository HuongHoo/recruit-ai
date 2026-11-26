<?php

namespace App\Enums;

use Laravel\Socialite\AbstractUser;

/**
 * OAuth providers users may sign in with. Adding a provider: add a case, its config in config/services.php,
 * and its e-mail verification rule below.
 */
enum SocialProvider: string
{
    case Google = 'google';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
        };
    }

    public function isConfigured(): bool
    {
        return filled(config("services.{$this->value}.client_id"))
            && filled(config("services.{$this->value}.client_secret"));
    }

    /**
     * Extra parameters for the authorization redirect. Google silently reuses the signed-in account and skips
     * the consent screen once the app has been approved, so we force both the account chooser and the
     * "Google sẽ cho phép ... truy cập vào thông tin này" confirmation on every sign-in.
     *
     * @return array<string, string>
     */
    public function redirectParameters(): array
    {
        return match ($this) {
            self::Google => ['prompt' => 'select_account consent'],
        };
    }

    /**
     * Accounts are linked by e-mail, so only addresses the provider itself has verified may be trusted;
     * otherwise someone could take over an account by claiming its e-mail.
     */
    public function hasVerifiedEmail(AbstractUser $user): bool
    {
        if (blank($user->getEmail())) {
            return false;
        }

        return match ($this) {
            self::Google => (bool) ($user->getRaw()['email_verified'] ?? false),
        };
    }
}
