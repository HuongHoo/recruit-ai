<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Cv;
use App\Models\JobAlert;
use App\Models\JobPost;
use App\Models\User;
use App\Notifications\NewJobsForAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CandidateJobApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_search_shows_only_visible_jobs_matching_filters(): void
    {
        JobPost::factory()->create(['title' => 'Lập trình viên Laravel', 'location' => 'Hà Nội']);
        JobPost::factory()->create(['title' => 'Laravel Developer HCM', 'location' => 'Hồ Chí Minh']);
        JobPost::factory()->closed()->create(['title' => 'Laravel tin đã đóng']);
        JobPost::factory()->expired()->create(['title' => 'Laravel tin hết hạn']);

        $this->get(route('jobs.index', ['keyword' => 'Laravel', 'location' => 'Hà Nội']))
            ->assertOk()
            ->assertSee('Lập trình viên Laravel')
            ->assertDontSee('Laravel Developer HCM')
            ->assertDontSee('Laravel tin đã đóng')
            ->assertDontSee('Laravel tin hết hạn');
    }

    public function test_hidden_job_detail_returns_404_for_candidates(): void
    {
        $job = JobPost::factory()->closed()->create();

        $this->get(route('jobs.show', $job))->assertNotFound();
        $this->actingAs($job->employer)->get(route('jobs.show', $job))->assertOk();
    }

    public function test_candidate_can_save_and_unsave_a_job(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)->post(route('candidate.saved-jobs.store', $job))->assertRedirect();
        $this->actingAs($candidate)->post(route('candidate.saved-jobs.store', $job));
        $this->assertSame(1, $candidate->savedJobs()->count());

        $this->actingAs($candidate)->get(route('candidate.saved-jobs.index'))->assertOk()->assertSee($job->title);

        $this->actingAs($candidate)->delete(route('candidate.saved-jobs.destroy', $job));
        $this->assertSame(0, $candidate->savedJobs()->count());
    }

    public function test_candidate_can_apply_with_own_cv_only_once(): void
    {
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create(['title' => 'CV Backend']);
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)
            ->post(route('candidate.applications.store', $job), ['cv_id' => $cv->id, 'cover_letter' => 'Xin chào'])
            ->assertRedirect(route('candidate.applications.index'));

        $application = $candidate->applications()->sole();
        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertSame('CV Backend', $application->cv_title);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.store', $job), ['cv_id' => $cv->id])
            ->assertSessionHasErrors('cv_id');
        $this->assertSame(1, Application::count());
    }

    public function test_candidate_cannot_apply_with_someone_elses_cv_or_to_closed_job(): void
    {
        $candidate = User::factory()->candidate()->create();
        $foreignCv = Cv::factory()->create();
        $ownCv = Cv::factory()->for($candidate)->create();

        $this->actingAs($candidate)
            ->post(route('candidate.applications.store', JobPost::factory()->create()), ['cv_id' => $foreignCv->id])
            ->assertSessionHasErrors('cv_id');

        $this->actingAs($candidate)
            ->post(route('candidate.applications.store', JobPost::factory()->closed()->create()), ['cv_id' => $ownCv->id])
            ->assertForbidden();

        $this->assertSame(0, Application::count());
    }

    public function test_candidate_sees_application_statuses_and_can_filter(): void
    {
        $candidate = User::factory()->candidate()->create();
        $interviewJob = JobPost::factory()->create(['title' => 'Vị trí phỏng vấn']);
        $rejectedJob = JobPost::factory()->create(['title' => 'Vị trí bị loại']);
        Application::factory()->for($interviewJob)->for(Cv::factory()->for($candidate))->status(ApplicationStatus::Interview)->create();
        Application::factory()->for($rejectedJob)->for(Cv::factory()->for($candidate))->status(ApplicationStatus::Rejected)->create();

        $this->actingAs($candidate)->get(route('candidate.applications.index'))
            ->assertOk()->assertSee('Vị trí phỏng vấn')->assertSee('Vị trí bị loại');

        $this->actingAs($candidate)->get(route('candidate.applications.index', ['status' => 'interview']))
            ->assertOk()->assertSee('Vị trí phỏng vấn')->assertDontSee('Vị trí bị loại');
    }

    public function test_candidate_can_manage_job_alerts(): void
    {
        $candidate = User::factory()->candidate()->create();

        $this->actingAs($candidate)->post(route('candidate.job-alerts.store'), ['keyword' => 'PHP', 'location' => 'Hà Nội'])
            ->assertRedirect(route('candidate.job-alerts.index'));
        $alert = $candidate->jobAlerts()->sole();
        $this->assertTrue($alert->is_active);

        $this->actingAs($candidate)->patch(route('candidate.job-alerts.update', $alert));
        $this->assertFalse($alert->fresh()->is_active);

        $this->actingAs(User::factory()->candidate()->create())->delete(route('candidate.job-alerts.destroy', $alert))->assertForbidden();

        $this->actingAs($candidate)->delete(route('candidate.job-alerts.destroy', $alert));
        $this->assertModelMissing($alert);
    }

    public function test_job_alert_command_emails_only_new_matching_jobs(): void
    {
        Notification::fake();
        $this->travelTo(now()->subDay());
        $alert = JobAlert::factory()->create(['keyword' => 'Laravel']);
        $inactiveAlert = JobAlert::factory()->inactive()->create();
        JobPost::factory()->create(['title' => 'Laravel cũ']);
        $this->travelBack();

        JobPost::factory()->create(['title' => 'Laravel mới']);
        JobPost::factory()->create(['title' => 'Kế toán mới']);

        $this->artisan('job-alerts:send')->assertSuccessful();

        Notification::assertSentTo($alert->user, NewJobsForAlert::class, fn (NewJobsForAlert $n) => $n->jobs->pluck('title')->all() === ['Laravel mới']);
        Notification::assertNotSentTo($inactiveAlert->user, NewJobsForAlert::class);
        $this->assertNotNull($alert->fresh()->last_sent_at);

        $this->artisan('job-alerts:send');
        Notification::assertSentToTimes($alert->user, NewJobsForAlert::class, 1);
    }
}
