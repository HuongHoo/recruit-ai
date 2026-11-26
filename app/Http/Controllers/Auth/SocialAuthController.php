<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SocialProvider;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Notifications\SocialLoginAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

/**
 * "Sign in with Google" (and any other SocialProvider). New users become candidates; employers must register
 * with company details, but an existing employer/admin may still sign in once the e-mail matches their account.
 */
class SocialAuthController extends Controller
{
    public function redirect(SocialProvider $provider): RedirectResponse
    {
        if (! $provider->isConfigured()) {
            return $this->failed("Đăng nhập {$provider->label()} chưa được cấu hình.");
        }

        return Socialite::driver($provider->value)->with($provider->redirectParameters())->redirect();
    }

    public function callback(Request $request, SocialProvider $provider): RedirectResponse
    {
        if (! $provider->isConfigured()) {
            return $this->failed("Đăng nhập {$provider->label()} chưa được cấu hình.");
        }

        if ($request->has('error')) {
            return $this->failed("Bạn đã hủy đăng nhập bằng {$provider->label()}.");
        }

        try {
            $socialUser = Socialite::driver($provider->value)->user();
        } catch (InvalidStateException) {
            // The session that started the login is gone: usually the site was opened on a different host
            // (127.0.0.1 vs localhost) than the callback URL, or the login tab sat open past the session lifetime.
            Log::warning('Social login state mismatch', ['provider' => $provider->value, 'host' => $request->getHost()]);

            return $this->failed('Phiên đăng nhập đã hết hạn hoặc không khớp. Hãy mở trang bằng địa chỉ '
                .config('app.url').' rồi thử lại.');
        } catch (Throwable $exception) {
            Log::warning('Social login failed', [
                'provider' => $provider->value,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->failed("Không thể đăng nhập bằng {$provider->label()}. Vui lòng thử lại.");
        }

        if (! $provider->hasVerifiedEmail($socialUser)) {
            return $this->failed("Email {$provider->label()} của bạn chưa được xác minh.");
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $socialUser->getId())
            ->first();

        $user = $account?->user ?? User::query()->where('email', $socialUser->getEmail())->first();

        if ($user === null) {
            $user = new User([
                'name' => $socialUser->getName() ?: $socialUser->getEmail(),
                'email' => $socialUser->getEmail(),
            ]);
            $user->role = UserRole::Candidate;
            $user->is_active = true;
        }

        if (! $user->is_active) {
            return $this->failed('Tài khoản của bạn đã bị khóa.');
        }

        // The e-mail matched, but this user is already linked to a different account at the same provider.
        if ($account === null && $user->exists && $user->socialAccounts()->where('provider', $provider)->exists()) {
            return $this->failed("Email này đã được liên kết với một tài khoản {$provider->label()} khác.");
        }

        $isNewUser = ! $user->exists;

        DB::transaction(function () use ($user, $account, $provider, $socialUser): void {
            $user->avatar_url ??= $socialUser->getAvatar();
            $user->email_verified_at ??= now();
            $user->save();

            if ($account === null) {
                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'provider_user_id' => $socialUser->getId(),
                ]);
            }
        });

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $this->sendLoginAlert($request, $user, $provider, $socialUser->getEmail(), $isNewUser);

        $status = $isNewUser
            ? 'Chào mừng bạn đến với '.config('app.name').'! '
            : '';

        return redirect($user->homeUrl())->with(
            'status',
            $status."Bạn đã đăng nhập bằng {$provider->label()}: {$socialUser->getEmail()}. "
                .'Thông báo đăng nhập đã được gửi tới email này.',
        );
    }

    /**
     * A mail outage must never block signing in, so delivery failures are only logged.
     */
    private function sendLoginAlert(Request $request, User $user, SocialProvider $provider, string $email, bool $isNewUser): void
    {
        try {
            Notification::route('mail', [$email => $user->name])->notify(new SocialLoginAlert(
                userName: $user->name,
                provider: $provider,
                providerEmail: $email,
                loggedInAt: now(),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                isNewUser: $isNewUser,
            ));

            Log::info('Social login alert sent', ['user_id' => $user->id, 'email' => $email]);
        } catch (Throwable $exception) {
            Log::error('Social login alert could not be sent', ['user_id' => $user->id, 'message' => $exception->getMessage()]);
        }
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
