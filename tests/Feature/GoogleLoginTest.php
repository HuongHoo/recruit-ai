<?php

namespace Tests\Feature;

use App\Enums\SocialProvider;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\SocialLoginAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
    }

    public function test_redirects_to_google(): void
    {
        $this->get('/auth/google')->assertRedirectContains('accounts.google.com');
    }

    public function test_google_always_shows_account_chooser_and_consent_screen(): void
    {
        $this->get('/auth/google')->assertRedirectContains('prompt=select_account+consent');
    }

    public function test_login_alert_is_emailed_to_the_google_address(): void
    {
        Notification::fake();
        $this->fakeGoogleUser('google-7', 'that@gmail.com');

        $this->get('/auth/google/callback')
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'that@gmail.com'));

        Notification::assertSentOnDemand(
            SocialLoginAlert::class,
            fn (SocialLoginAlert $alert, array $channels, object $notifiable) => $notifiable->routes['mail'] === ['that@gmail.com' => 'Google User']
                && $alert->isNewUser
                && str_contains((string) $alert->toMail($notifiable)->render(), 'that@gmail.com'),
        );
    }

    public function test_failed_alert_email_does_not_block_login(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'invalid.invalid', 'mail.mailers.smtp.port' => 1]);
        $this->fakeGoogleUser('google-8', 'offline@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertAuthenticated();
    }

    public function test_state_mismatch_explains_how_to_retry(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unknown_provider_returns_404(): void
    {
        $this->get('/auth/myspace')->assertNotFound();
        $this->get('/auth/myspace/callback')->assertNotFound();
    }

    public function test_unconfigured_provider_redirects_back_to_login(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google')->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_cancelled_login_redirects_back_to_login(): void
    {
        $this->get('/auth/google/callback?error=access_denied')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_new_google_user_becomes_candidate(): void
    {
        $this->fakeGoogleUser('google-1', 'moi@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user = User::firstWhere('email', 'moi@gmail.com');
        $this->assertSame(UserRole::Candidate, $user->role);
        $this->assertNull($user->password);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => SocialProvider::Google->value,
            'provider_user_id' => 'google-1',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_account_is_linked_by_email(): void
    {
        $employer = User::factory()->employer()->create(['email' => 'hr@congty.vn']);
        $this->fakeGoogleUser('google-2', 'hr@congty.vn');

        $this->get('/auth/google/callback')->assertRedirect('/employer/dashboard');

        $this->assertSame('google-2', $employer->socialAccounts()->sole()->provider_user_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_linked_account_is_found_even_if_email_changed(): void
    {
        $user = User::factory()->create(['email' => 'cu@gmail.com']);
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => 'google-5']);
        $this->fakeGoogleUser('google-5', 'moi-doi@gmail.com');

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_email_linked_to_another_google_account_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'trung@gmail.com']);
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => 'google-old']);
        $this->fakeGoogleUser('google-new', 'trung@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_unverified_google_email_is_refused(): void
    {
        User::factory()->create(['email' => 'nan-nhan@gmail.com']);
        $this->fakeGoogleUser('google-3', 'nan-nhan@gmail.com', emailVerified: false);

        $this->get('/auth/google/callback')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_locked_account_cannot_login_with_google(): void
    {
        User::factory()->inactive()->create(['email' => 'khoa@gmail.com']);
        $this->fakeGoogleUser('google-4', 'khoa@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_deleting_user_removes_social_accounts(): void
    {
        $user = User::factory()->create();
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => 'google-6']);

        $user->delete();

        $this->assertDatabaseCount('social_accounts', 0);
    }

    private function fakeGoogleUser(string $id, string $email, bool $emailVerified = true): void
    {
        $googleUser = (new SocialiteUser)
            ->setRaw(['email_verified' => $emailVerified])
            ->map(['id' => $id, 'email' => $email, 'name' => 'Google User', 'avatar' => 'https://example.com/a.png']);

        Socialite::fake('google', $googleUser);
    }
}
