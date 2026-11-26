<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employers may only publish or edit job posts once an admin has verified their company.
 */
class EnsureCompanyIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasVerifiedCompany()) {
            return redirect()->route('employer.company.edit')
                ->withErrors(['company' => 'Công ty của bạn cần được Quản trị viên xác thực trước khi đăng tin tuyển dụng.']);
        }

        return $next($request);
    }
}
