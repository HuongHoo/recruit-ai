<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\Post;
use App\Models\User;
use App\Support\CvSampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public landing page. All job, category and company figures come from the database.
 */
class HomeController extends Controller
{
    /** Icons (Heroicons paths) per job category; unknown categories fall back to a briefcase. */
    private const CATEGORY_ICONS = [
        'IT - Phần mềm' => 'M8 9l-3 3 3 3m8-6 3 3-3 3M14 4l-4 16',
        'Kinh doanh / Bán hàng' => 'M4 20V10m6 10V4m6 16v-7m4 7H2',
        'Marketing / PR' => 'M11 5 6 9H2v6h4l5 4V5Zm8.07-.07a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07',
        'Kế toán / Tài chính' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
        'Nhân sự / Hành chính' => 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m11-12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'Thiết kế / Sáng tạo' => 'M12 19l7-7 3 3-7 7-3-3ZM18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5ZM2 2l7.6 7.6',
        'Chăm sóc khách hàng' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10Z',
        'Sản xuất / Kỹ thuật' => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z',
        'Giáo dục / Đào tạo' => 'M12 14 3 9l9-5 9 5-9 5Zm0 0v6m-5-8.5V16c0 1.5 2.2 3 5 3s5-1.5 5-3v-4.5',
    ];

    private const BRIEFCASE_ICON = 'M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2';

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $visibleJobs = fn () => JobPost::query()->visible();

        $jobCountByCategory = $visibleJobs()->toBase()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');

        // Verified companies ranked by how many visible jobs they have, with their two newest jobs.
        $companies = Company::query()
            ->where('status', CompanyStatus::Verified)
            ->withCount(['jobPosts as open_jobs_count' => fn ($q) => $q->visible()])
            ->with(['jobPosts' => fn ($q) => $q->visible()->latest()->limit(2)])
            ->orderByDesc('open_jobs_count')->latest('verified_at')
            ->limit(6)
            ->get();

        return view('welcome', [
            'locations' => ['Tất cả', 'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương'],
            'keywords' => ['Kinh doanh', 'Marketing', 'Kế toán', 'Laravel', 'Nhân sự', 'Thiết kế', 'Chăm sóc khách hàng', 'Tiếng Anh'],
            'stats' => [
                [$visibleJobs()->count(), 'Việc làm đang tuyển'],
                [$visibleJobs()->distinct()->count('company_name'), 'Doanh nghiệp'],
                [User::query()->where('role', UserRole::Candidate)->count(), 'Ứng viên'],
                [count($jobCountByCategory), 'Ngành nghề'],
            ],
            'featuredJobs' => $visibleJobs()
                ->orderByRaw("case when promotion_tier = 'promax' and promoted_until > ? then 1 else 0 end desc", [now()])
                ->latest()->limit(6)->get(),
            'jobColors' => ['bg-blue-600', 'bg-violet-600', 'bg-rose-600', 'bg-amber-500', 'bg-emerald-600', 'bg-cyan-600'],
            'savedJobIds' => $user?->isCandidate() ? $user->savedJobs()->pluck('job_posts.id')->all() : null,
            'categories' => collect(config('recruitment.job_categories'))
                ->reject(fn (string $name): bool => $name === 'Khác')
                ->map(fn (string $name): array => [
                    'name' => $name,
                    'jobs' => (int) ($jobCountByCategory[$name] ?? 0),
                    'icon' => self::CATEGORY_ICONS[$name] ?? self::BRIEFCASE_ICON,
                    'url' => route('jobs.index', ['category' => $name]),
                ])
                ->sortByDesc('jobs')->take(8)->values(),
            'companies' => $companies,
            'cvTemplates' => config('recruitment.cv.templates'),
            ...CvSampleData::make(),
            'featuredEvents' => Post::query()->published()->where('type', PostType::Event)->highlighted()->limit(3)->get(),
            'featuredArticles' => Post::query()->published()->where('type', PostType::Article)->highlighted()->limit(3)->get(),
            'faqs' => [
                ['q' => 'Tôi có phải trả phí khi tạo tài khoản ứng viên không?', 'a' => 'Không. Ứng viên được tạo tài khoản, xây dựng hồ sơ và ứng tuyển hoàn toàn miễn phí.'],
                ['q' => 'Làm sao để theo dõi trạng thái hồ sơ đã ứng tuyển?', 'a' => 'Sau khi đăng nhập, vào mục "Việc đã ứng tuyển" trong trang cá nhân để xem nhà tuyển dụng đã xem, mời phỏng vấn hay từ chối hồ sơ.'],
                ['q' => 'Nhà tuyển dụng đăng tin như thế nào?', 'a' => 'Đăng ký tài khoản Nhà tuyển dụng rồi chọn "Đăng tin mới". Tin sẽ hiển thị ngay cho ứng viên tìm kiếm.'],
                ['q' => 'Thông tin cá nhân của tôi có được bảo mật không?', 'a' => 'Có. Hồ sơ chỉ được chia sẻ với nhà tuyển dụng khi bạn chủ động ứng tuyển.'],
            ],
        ]);
    }
}
