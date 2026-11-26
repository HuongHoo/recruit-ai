<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_jobs_categories_and_companies_from_the_database(): void
    {
        JobPost::factory()->create(['title' => 'Kỹ sư Cầu đường', 'company_name' => 'Công ty Xây dựng Bình Minh', 'category' => 'Sản xuất / Kỹ thuật']);
        JobPost::factory()->closed()->create(['title' => 'Tin đã đóng không hiển thị']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kỹ sư Cầu đường')
            ->assertSee('Công ty Xây dựng Bình Minh')
            ->assertSee(route('jobs.index', ['category' => 'Sản xuất / Kỹ thuật']), false)
            ->assertDontSee('Tin đã đóng không hiển thị');
    }

    public function test_top_companies_show_verified_companies_with_their_open_job_count(): void
    {
        $verified = Company::factory()->verified()->create(['name' => 'Công ty Đã Duyệt An Khang']);
        JobPost::factory()->count(3)->create(['user_id' => $verified->user_id]);
        JobPost::factory()->closed()->create(['user_id' => $verified->user_id]);
        Company::factory()->create(['name' => 'Công ty Đang Chờ Duyệt']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Công ty Đã Duyệt An Khang')
            ->assertSee('3 việc làm đang tuyển')
            ->assertDontSee('Công ty Đang Chờ Duyệt');
    }

    public function test_home_page_shows_empty_state_without_jobs(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Chưa có tin tuyển dụng nào');
    }

    public function test_seeded_demo_data_is_vietnamese(): void
    {
        $this->seed();

        $this->assertSame(17, JobPost::count());
        $this->assertSame(0, JobPost::query()->where('description', 'not like', '%-%')->count());
        $this->get(route('home'))->assertOk()->assertSee('Lập trình viên PHP / Laravel');
    }
}
