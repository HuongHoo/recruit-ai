<?php

namespace Database\Seeders;

use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\Post;
use App\Models\User;
use Database\Factories\JobPostFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Admin accounts cannot self-register, so they are created here.
     * Demo password for all accounts: password
     */
    public function run(): void
    {
        $accounts = [
            ['Quản trị viên', 'admin@example.com', UserRole::Admin],
            ['Nhà tuyển dụng Demo', 'employer@example.com', UserRole::Employer],
            ['Ứng viên Demo', 'candidate@example.com', UserRole::Candidate],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->password = 'password';
            $user->role = $role;
            $user->is_active = true;
            $user->save();
        }

        if (! JobPost::query()->exists()) {
            $this->seedJobPosts();
        }

        if (! Post::query()->exists()) {
            $this->seedPosts(User::where('email', 'admin@example.com')->firstOrFail());
        }

        $this->call(CommunitySeeder::class);
    }

    /**
     * Sample events and articles authored by the admin account (shown in "Nội dung nổi bật").
     */
    private function seedPosts(User $admin): void
    {
        $posts = [
            [PostType::Event, 'Ngày hội việc làm sinh viên 2026', 'Ngày hội việc làm', 'Hơn 60 doanh nghiệp tham gia tuyển dụng trực tiếp, phỏng vấn tại chỗ và tư vấn CV miễn phí cho sinh viên năm cuối.', 'Hà Nội', 10, true],
            [PostType::Event, 'Workshop: Viết CV chuẩn ATS trong 30 phút', 'Đồng hành với trường đại học', 'Chuyên gia tuyển dụng hướng dẫn cách trình bày CV để vượt qua hệ thống lọc hồ sơ tự động.', 'Trực tuyến', 3, false],
            [PostType::Event, 'Tuần lễ tuyển dụng ngành IT - Phần mềm', 'Tuyển dụng ngành', 'Kết nối ứng viên với các công ty phần mềm đang mở hàng trăm vị trí từ thực tập đến trưởng nhóm.', 'TP. Hồ Chí Minh', 20, false],
            [PostType::Article, '7 lỗi thường gặp khiến CV bị loại ngay từ vòng đầu', 'Cẩm nang nghề nghiệp', 'Lỗi chính tả, email thiếu chuyên nghiệp hay CV quá dài là những lý do phổ biến khiến nhà tuyển dụng bỏ qua hồ sơ của bạn.', null, null, true],
            [PostType::Article, 'Cách trả lời câu hỏi "Điểm yếu của bạn là gì?"', 'Kỹ năng phỏng vấn', 'Gợi ý cấu trúc câu trả lời trung thực mà vẫn cho thấy bạn đang chủ động cải thiện bản thân.', null, null, false],
            [PostType::Article, 'Đàm phán lương lần đầu: chuẩn bị những gì?', 'Lương thưởng', 'Tìm hiểu mặt bằng lương, xác định mức mong muốn và cách trình bày để buổi đàm phán diễn ra suôn sẻ.', null, null, false],
        ];

        foreach ($posts as $i => [$type, $title, $category, $excerpt, $location, $daysAhead, $featured]) {
            $post = new Post([
                'type' => $type,
                'title' => $title,
                'category' => $category,
                'excerpt' => $excerpt,
                'content' => $excerpt."\n\nNội dung chi tiết sẽ được quản trị viên cập nhật. Theo dõi trang để không bỏ lỡ thông tin mới nhất.",
                'event_date' => $daysAhead ? today()->addDays($daysAhead) : null,
                'event_location' => $location,
                'is_featured' => $featured,
                'published_at' => now()->subDays($i + 1),
            ]);
            $post->user_id = $admin->id;
            $post->save();
        }
    }

    /**
     * One employer account per sample company (the demo employer owns the first one),
     * then every job template is posted by one of them, newest first.
     */
    private function seedJobPosts(): void
    {
        $employers = collect(JobPostFactory::COMPANIES)->map(function (string $company, int $index): User {
            $user = User::firstOrNew(['email' => $index === 0 ? 'employer@example.com' : 'ntd'.($index + 1).'@example.com']);
            $user->name = $index === 0 ? 'Nhà tuyển dụng Demo' : "Phòng Nhân sự - {$company}";
            $user->password = 'password';
            $user->role = UserRole::Employer;
            $user->is_active = true;
            $user->save();

            $this->seedVerifiedCompany($user, $company, $index);

            return $user;
        });

        // One employer whose company is still waiting for admin verification, to demo the review flow.
        $pending = User::firstOrNew(['email' => 'ntd-cho-duyet@example.com']);
        $pending->name = 'Nhà tuyển dụng chờ duyệt';
        $pending->password = 'password';
        $pending->role = UserRole::Employer;
        $pending->is_active = true;
        $pending->save();
        Company::factory()->for($pending, 'owner')->create([
            'name' => 'Công ty TNHH Khởi Nghiệp Mới',
            'logo_path' => $this->makeLogo('Công ty TNHH Khởi Nghiệp Mới', 99),
        ]);

        JobPost::factory()->eachTemplate()
            ->sequence(fn (Sequence $sequence): array => [
                'user_id' => $employers[$sequence->index % $employers->count()]->id,
                'company_name' => JobPostFactory::COMPANIES[$sequence->index % $employers->count()],
                'created_at' => now()->subHours($sequence->index * 7),
            ])
            ->create();
    }

    private function seedVerifiedCompany(User $user, string $name, int $index): void
    {
        if ($user->company()->exists()) {
            return;
        }

        Company::factory()->verified()->for($user, 'owner')->create([
            'name' => $name,
            'logo_path' => $this->makeLogo($name, $index),
        ]);
    }

    /**
     * Writes a simple initial-letter SVG logo to the public disk and returns its path.
     */
    private function makeLogo(string $name, int $index): string
    {
        $colors = ['#059669', '#2563eb', '#7c3aed', '#dc2626', '#ea580c', '#0f172a'];
        $initial = e(mb_strtoupper(mb_substr(preg_replace('/^(Công ty|CÔNG TY)\s+(TNHH|CP|Cổ phần)?\s*/u', '', $name), 0, 1)));
        $path = "company-logos/seed-{$index}.svg";

        Storage::disk('public')->put($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" rx="20" fill="'
            .$colors[$index % count($colors)].'"/><text x="48" y="62" font-family="Arial" font-size="44" font-weight="700" fill="#fff" text-anchor="middle">'
            .$initial.'</text></svg>');

        return $path;
    }
}
