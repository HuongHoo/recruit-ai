<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\JobPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_stats_for_own_jobs_only(): void
    {
        $company = Company::factory()->verified()->create(['name' => 'Công ty Bảng Điều Khiển']);
        $job = JobPost::factory()->create(['user_id' => $company->user_id, 'title' => 'Kế toán tổng hợp']);
        Application::factory()->count(2)->create(['job_post_id' => $job->id]);
        Application::factory()->create(['job_post_id' => $job->id, 'status' => ApplicationStatus::Interview]);
        Application::factory()->count(4)->create(); // other employers

        $this->actingAs($company->owner)->get(route('employer.dashboard'))
            ->assertOk()
            ->assertSee('Công ty Bảng Điều Khiển')
            ->assertSee('Kế toán tổng hợp')
            ->assertSee('Phễu tuyển dụng')
            ->assertViewHas('applicationCount', 3)
            ->assertViewHas('interviewCount', 1)
            ->assertViewHas('newThisWeek', 3);
    }

    public function test_settings_hub_links_to_company_info(): void
    {
        $company = Company::factory()->verified()->create(['name' => 'Công ty Cài Đặt']);

        $this->actingAs($company->owner)->get(route('employer.settings'))
            ->assertOk()
            ->assertSee('Công ty Cài Đặt')
            ->assertSee('Thông tin công ty')
            ->assertSee('Thiết lập quy trình tuyển dụng')
            ->assertSee('href="'.route('employer.company.edit').'"', false);
    }

    public function test_company_info_saves_contact_email_and_address(): void
    {
        $company = Company::factory()->verified()->create();
        $owner = $company->owner;

        $this->actingAs($owner)->get(route('employer.company.edit'))
            ->assertOk()->assertSee('Quản lý thông tin công ty')->assertSee('Thông tin liên hệ');

        $this->put(route('employer.company.update'), [
            'name' => $company->name,
            'business_model' => $company->business_model,
            'website' => $company->website,
            'phone' => $company->phone,
            'contact_email' => 'tuyendung@congty.vn',
            'address' => '12 Lê Lợi, Quận 1, TP. Hồ Chí Minh',
        ])->assertSessionHasNoErrors();

        $company->refresh();
        $this->assertSame('tuyendung@congty.vn', $company->contact_email);
        $this->assertSame('12 Lê Lợi, Quận 1, TP. Hồ Chí Minh', $company->address);
    }

    public function test_kpi_page_counts_activity_in_selected_week(): void
    {
        $company = Company::factory()->verified()->create();
        $job = JobPost::factory()->create(['user_id' => $company->user_id]);
        Application::factory()->count(2)->create(['job_post_id' => $job->id]);
        Application::factory()->status(ApplicationStatus::Passed)->create(['job_post_id' => $job->id]);
        Application::factory()->create(['job_post_id' => $job->id, 'created_at' => now()->subWeeks(3)]);

        $response = $this->actingAs($company->owner)->get(route('employer.kpi'))
            ->assertOk()->assertSee('Tổng đội')->assertSee('Nhận phỏng vấn');

        $team = $response->viewData('team');
        $this->assertSame(1, $team['jobs']);
        $this->assertSame(3, $team['applications']);
        $this->assertSame(1, $team['passed']);

        $this->get(route('employer.kpi', ['period' => 'month', 'month' => now()->subYear()->format('Y-m')]))
            ->assertOk()->assertViewHas('team', fn ($t) => $t['applications'] === 0);

        $this->get(route('employer.kpi', ['period' => 'week', 'week' => now()->format('o-\WW')]))->assertOk();
    }
}
