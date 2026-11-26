<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterEmployerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Đăng nhập sai quá nhiều lần. Thử lại sau '.RateLimiter::availableIn($throttleKey).' giây.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tài khoản của bạn đã bị khóa.']);
        }

        $request->session()->regenerate();

        // Only follow the "intended" URL if it belongs to the user's own area; otherwise go to their dashboard.
        $intended = $request->session()->pull('url.intended');
        if ($intended && $this->urlAllowedFor($user, $intended)) {
            return redirect($intended);
        }

        return redirect($user->homeUrl());
    }

    public function showRegister(Request $request): View
    {
        if ($request->query('role') === UserRole::Employer->value) {
            return view('auth.register-employer', [
                'businessModels' => config('recruitment.employer.business_models'),
                'companySizes' => config('recruitment.employer.company_sizes'),
                'industries' => config('recruitment.job_categories'),
            ]);
        }

        return view('auth.register');
    }

    /**
     * Candidate sign-up. Employers use the dedicated form handled by registerEmployer().
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'name' => 'họ tên',
            'password' => 'mật khẩu',
        ]);

        $user = $this->createUser($data, UserRole::Candidate);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->homeUrl())->with('status', 'Đăng ký thành công!');
    }

    public function registerEmployer(RegisterEmployerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $logoPath = $request->file('logo')->store('company-logos', 'public');

        try {
            $user = DB::transaction(function () use ($data, $logoPath): User {
                $user = $this->createUser($data, UserRole::Employer);
                // New companies start as "pending" until an admin verifies them.
                $user->company()->create([
                    'name' => $data['company_name'],
                    'size' => $data['company_size'] ?? null,
                    ...Arr::only($data, ['phone', 'business_model', 'industry', 'website', 'description']),
                    'logo_path' => $logoPath,
                ]);

                return $user;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($logoPath);

            throw $exception;
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->homeUrl())
            ->with('status', 'Đăng ký thành công! Công ty của bạn đang chờ Quản trị viên xác thực, thường trong vòng 1 ngày làm việc.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    private function createUser(array $data, UserRole $role): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        // Set outside mass assignment on purpose.
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function urlAllowedFor(User $user, string $url): bool
    {
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        foreach (UserRole::cases() as $role) {
            $prefix = '/'.$role->value;
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $user->role === $role;
            }
        }

        return true; // public pages
    }
}
