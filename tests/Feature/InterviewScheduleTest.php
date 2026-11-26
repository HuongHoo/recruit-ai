<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Interview;
use App\Models\JobPost;
use App\Models\User;
use App\Notifications\InterviewScheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InterviewScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function ownApplication(): Application
    {
        $company = Company::factory()->verified()->create();
        $job = JobPost::factory()->create(['user_id' => $company->user_id, 'title' => 'Kỹ sư QA']);

        return Application::factory()->create(['job_post_id' => $job->id]);
    }

    private function ownerOf(Application $application): User
    {
        return User::query()->findOrFail($application->jobPost->user_id);
    }

    public function test_employer_schedules_interview_and_candidate_is_notified(): void
    {
        Notification::fake();
        $application = $this->ownApplication();
        $when = now()->addDays(3)->setTime(14, 30);

        $this->actingAs($this->ownerOf($application))->post(route('employer.interviews.store'), [
            'application_id' => $application->id,
            'scheduled_at' => $when->format('Y-m-d\TH:i'),
            'duration_minutes' => 45,
            'type' => 'online',
            'location' => 'https://meet.google.com/xyz',
        ])->assertRedirect(route('employer.interviews.index'));

        $interview = Interview::query()->sole();
        $this->assertSame('14:30 - 15:15', $interview->timeRange());
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        Notification::assertSentTo($application->candidate, InterviewScheduled::class);

        $this->get(route('employer.interviews.index'))
            ->assertOk()->assertSee($application->candidate->name)->assertSee('14:30 - 15:15')->assertViewHas('stats.total', 1);

        $this->get(route('employer.interviews.index', ['range' => 'today']))
            ->assertOk()->assertSee('Chưa có lịch phỏng vấn nào');

        $this->get(route('employer.interviews.index', ['view' => 'calendar']))
            ->assertOk()->assertSee($application->candidate->name);

        $this->actingAs($application->candidate)->get(route('candidate.applications.index'))
            ->assertSee('14:30 ngày '.$when->format('d/m/Y'));
    }

    public function test_validation_and_ownership(): void
    {
        $application = $this->ownApplication();
        $other = $this->ownApplication();

        $this->actingAs($this->ownerOf($application))->post(route('employer.interviews.store'), [
            'application_id' => $application->id, 'scheduled_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'duration_minutes' => 60, 'type' => 'offline',
        ])->assertSessionHasErrors(['scheduled_at', 'location']);

        $this->post(route('employer.interviews.store'), [
            'application_id' => $other->id, 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'duration_minutes' => 60, 'type' => 'phone',
        ])->assertForbidden();

        $foreign = Interview::factory()->create(['application_id' => $other->id]);
        $this->patch(route('employer.interviews.status', $foreign), ['status' => 'cancelled'])->assertNotFound();
        $this->delete(route('employer.interviews.destroy', $foreign))->assertNotFound();
    }

    public function test_reschedule_and_cancel_notify_candidate(): void
    {
        Notification::fake();
        $application = $this->ownApplication();
        $interview = Interview::factory()->create(['application_id' => $application->id]);
        $this->actingAs($this->ownerOf($application));

        $this->put(route('employer.interviews.update', $interview), [
            'scheduled_at' => now()->addDays(5)->setTime(10, 0)->format('Y-m-d\TH:i'),
            'duration_minutes' => 60, 'type' => 'phone',
        ])->assertSessionHasNoErrors();

        $this->patch(route('employer.interviews.status', $interview), ['status' => 'cancelled']);

        $this->assertSame(InterviewStatus::Cancelled, $interview->fresh()->status);
        Notification::assertSentToTimes($application->candidate, InterviewScheduled::class, 2);
    }
}
