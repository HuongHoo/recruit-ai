<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\JobPost;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployerJobPostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validJobData(array $overrides = []): array
    {
        return [
            'title' => 'Lập trình viên PHP',
            'company_name' => 'Công ty Demo',
            'category' => 'IT - Phần mềm',
            'location' => 'Hà Nội',
            'job_type' => 'full_time',
            'salary_min' => 15,
            'salary_max' => 25,
            'description' => 'Phát triển ứng dụng Laravel.',
            'deadline' => now()->addMonth()->toDateString(),
            'status' => 'open',
            ...$overrides,
        ];
    }

    public function test_employer_can_create_update_and_delete_job_post(): void
    {
        $employer = User::factory()->employer()->create();

        $this->actingAs($employer)->get(route('employer.jobs.create'))->assertOk();
        $this->actingAs($employer)->post(route('employer.jobs.store'), $this->validJobData())->assertRedirect();

        $job = $employer->jobPosts()->sole();
        $this->assertSame('15 - 25 triệu', $job->salaryLabel());

        $this->actingAs($employer)->put(route('employer.jobs.update', $job), $this->validJobData(['title' => 'Senior PHP', 'status' => 'closed']))
            ->assertRedirect(route('employer.jobs.index'));
        $this->assertSame('Senior PHP', $job->fresh()->title);
        $this->assertFalse($job->fresh()->isVisible());

        $this->actingAs($employer)->delete(route('employer.jobs.destroy', $job));
        $this->assertModelMissing($job);
    }

    public function test_job_post_validation(): void
    {
        $employer = User::factory()->employer()->create();

        $this->actingAs($employer)
            ->post(route('employer.jobs.store'), $this->validJobData(['salary_min' => 30, 'salary_max' => 10, 'category' => 'Không tồn tại']))
            ->assertSessionHasErrors(['salary_max', 'category']);
    }

    public function test_employer_cannot_manage_other_employers_jobs_and_candidates_are_blocked(): void
    {
        $job = JobPost::factory()->create();
        $otherEmployer = User::factory()->employer()->create();

        $this->actingAs($otherEmployer)->get(route('employer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($otherEmployer)->get(route('employer.jobs.show', $job))->assertForbidden();
        $this->actingAs($otherEmployer)->delete(route('employer.jobs.destroy', $job))->assertForbidden();
        $this->actingAs(User::factory()->candidate()->create())->get(route('employer.jobs.index'))->assertForbidden();
    }

    public function test_opening_application_marks_it_viewed_and_shows_cv(): void
    {
        $application = Application::factory()->create();
        $employer = $application->jobPost->employer;

        $this->actingAs($employer)->get(route('employer.applications.show', $application))->assertOk()->assertSee($application->candidate->name);
        $this->assertSame(ApplicationStatus::Viewed, $application->fresh()->status);

        $this->actingAs($employer)->get(route('employer.applications.cv', $application))->assertOk()->assertSee('Quay lại hồ sơ ứng tuyển');
    }

    public function test_status_change_notifies_candidate_for_decisive_statuses(): void
    {
        Notification::fake();
        $application = Application::factory()->create();
        $employer = $application->jobPost->employer;

        $this->actingAs($employer)->patch(route('employer.applications.update', $application), ['status' => 'viewed']);
        Notification::assertNothingSent();

        $this->actingAs($employer)->patch(route('employer.applications.update', $application), ['status' => 'interview']);
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        Notification::assertSentTo($application->candidate, ApplicationStatusChanged::class);

        $this->actingAs($employer)->patch(route('employer.applications.update', $application), ['status' => 'submitted'])
            ->assertSessionHasErrors('status');
    }

    public function test_other_employer_cannot_view_or_update_application(): void
    {
        $application = Application::factory()->create();
        $otherEmployer = User::factory()->employer()->create();

        $this->actingAs($otherEmployer)->get(route('employer.applications.show', $application))->assertForbidden();
        $this->actingAs($otherEmployer)->get(route('employer.applications.cv', $application))->assertForbidden();
        $this->actingAs($otherEmployer)->patch(route('employer.applications.update', $application), ['status' => 'passed'])->assertForbidden();
    }
}
