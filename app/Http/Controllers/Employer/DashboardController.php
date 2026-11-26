<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $applications = Application::query()->whereRelation('jobPost', 'user_id', $user->id);
        $weekAgo = now()->subDays(7);

        $byStatus = (clone $applications)->toBase()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Applications per day for the last 14 days (zero-filled).
        $daily = (clone $applications)->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->pluck('created_at')
            ->countBy(fn ($date) => Carbon::parse($date)->toDateString());
        $trend = collect(range(13, 0))->map(function (int $daysAgo) use ($daily) {
            $day = now()->subDays($daysAgo);

            return ['label' => $day->format('d/m'), 'total' => $daily[$day->toDateString()] ?? 0];
        });

        $company = $user->company;
        $jobCount = $user->jobPosts()->count();
        $applicationCount = $byStatus->sum();

        return view('employer.dashboard', [
            'company' => $company,
            'visibleJobCount' => $user->jobPosts()->visible()->count(),
            'jobCount' => $jobCount,
            'applicationCount' => $applicationCount,
            'newThisWeek' => (clone $applications)->where('created_at', '>=', $weekAgo)->count(),
            'unreadCount' => (int) ($byStatus[ApplicationStatus::Submitted->value] ?? 0),
            'interviewCount' => (int) ($byStatus[ApplicationStatus::Interview->value] ?? 0),
            'passedCount' => (int) ($byStatus[ApplicationStatus::Passed->value] ?? 0),
            'pipeline' => collect(ApplicationStatus::cases())->map(fn (ApplicationStatus $status) => [
                'status' => $status,
                'total' => (int) ($byStatus[$status->value] ?? 0),
            ]),
            'trend' => $trend,
            'topJobs' => $user->jobPosts()
                ->withCount(['applications', 'applications as new_applications_count' => fn ($q) => $q->where('status', ApplicationStatus::Submitted)])
                ->orderByDesc('applications_count')->latest()->limit(5)->get(),
            'recentApplications' => (clone $applications)->with(['candidate', 'jobPost'])->latest()->limit(6)->get(),
            'checklist' => [
                ['Tạo hồ sơ công ty', $company !== null, route('employer.company.edit')],
                ['Được xác thực doanh nghiệp', $company?->status === CompanyStatus::Verified, route('employer.company.edit')],
                ['Đăng tin tuyển dụng đầu tiên', $jobCount > 0, route('employer.jobs.create')],
                ['Nhận hồ sơ ứng viên đầu tiên', $applicationCount > 0, route('employer.applications.index')],
                ['Mời ứng viên phỏng vấn', ($byStatus[ApplicationStatus::Interview->value] ?? 0) + ($byStatus[ApplicationStatus::Passed->value] ?? 0) > 0, route('employer.applications.index')],
            ],
        ]);
    }
}
