<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\CvMatchAnalysis;
use App\Models\JobPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerApplicantPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_pool_filters_by_search_job_and_status_and_shows_ai_score(): void
    {
        $company = Company::factory()->verified()->create();
        $jobA = JobPost::factory()->create(['user_id' => $company->user_id, 'title' => 'Lập trình viên PHP']);
        $jobB = JobPost::factory()->create(['user_id' => $company->user_id, 'title' => 'Nhân viên kinh doanh']);

        $an = Application::factory()->create(['job_post_id' => $jobA->id]);
        $an->candidate->update(['name' => 'Nguyễn Thị An']);
        $binh = Application::factory()->status(ApplicationStatus::Interview)->create(['job_post_id' => $jobB->id]);
        $binh->candidate->update(['name' => 'Trần Văn Bình']);
        $outsider = Application::factory()->create();
        $outsider->candidate->update(['name' => 'Người Ngoài Công Ty']);

        CvMatchAnalysis::query()->create([
            'user_id' => $an->user_id, 'job_post_id' => $jobA->id, 'cv_id' => $an->cv_id, 'score' => 83,
            'headline' => 'x', 'summary' => 'x', 'criteria' => [], 'requirements' => [], 'source_hash' => 'h', 'model' => 'm',
        ]);

        $owner = $company->owner;

        $this->actingAs($owner)->get(route('employer.applications.index'))
            ->assertOk()->assertSee('Nguyễn Thị An')->assertSee('Trần Văn Bình')
            ->assertDontSee('Người Ngoài Công Ty')->assertSee('83%');

        $this->get(route('employer.applications.index', ['q' => 'Bình']))
            ->assertSee('Trần Văn Bình')->assertDontSee('Nguyễn Thị An');

        $this->get(route('employer.applications.index', ['job' => $jobA->id]))
            ->assertSee('Nguyễn Thị An')->assertDontSee('Trần Văn Bình');

        $this->get(route('employer.applications.index', ['status' => 'interview']))
            ->assertSee('Trần Văn Bình')->assertDontSee('Nguyễn Thị An');

        $an->candidate->profile()->update(['headline' => 'Laravel Developer', 'skills' => ['PHP', 'MySQL']]);

        $this->get(route('employer.applications.index', ['must' => ['laravel', 'mysql']]))
            ->assertSee('Nguyễn Thị An')->assertDontSee('Trần Văn Bình');

        $this->get(route('employer.applications.index', ['any' => ['mysql', 'Bình']]))
            ->assertSee('Nguyễn Thị An')->assertSee('Trần Văn Bình');

        $this->get(route('employer.applications.index', ['not' => ['laravel']]))
            ->assertSee('Trần Văn Bình')->assertDontSee('Nguyễn Thị An');
    }
}
