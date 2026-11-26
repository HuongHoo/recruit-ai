<?php

use App\Http\Middleware\EnsureCompanyIsVerified;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a tunnel/reverse proxy (ngrok, hosting load balancer) HTTPS is terminated upstream; trust its
        // X-Forwarded-* headers so generated URLs (incl. the Google OAuth callback) use the public https host.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'company.verified' => EnsureCompanyIsVerified::class,
        ]);

        // Unauthenticated -> login page; already logged-in visiting guest pages -> own dashboard.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->homeUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The upload exceeded PHP's post_max_size. This fires before the session starts (so no flash
        // messages): send the person back to the page they came from with a flag the form displays.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tổng dung lượng tải lên quá lớn (tối đa '.ini_get('post_max_size').').'], 413);
            }

            $back = (string) $request->headers->get('referer');
            $sameSite = $back !== '' && parse_url($back, PHP_URL_HOST) === $request->getHost();

            return $sameSite
                ? redirect()->to($back.(str_contains($back, '?') ? '&' : '?').'upload_too_large=1')
                : null;
        });
    })->create();
