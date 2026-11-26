<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\CvMatchAnalysis;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvMatchAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);
    }

    private function fakeGemini(int $score = 72): void
    {
        $payload = [
            'score' => $score,
            'headline' => 'CV khá phù hợp với vị trí',
            'summary' => 'Ứng viên có kinh nghiệm Laravel nhưng thiếu tiếng Anh.',
            'criteria' => [['name' => 'Kỹ năng chuyên môn', 'cv' => 4, 'jd' => 5], ['name' => 'Ngoại ngữ', 'cv' => 9, 'jd' => 3]],
            'requirements' => [
                ['requirement' => '2 năm kinh nghiệm Laravel', 'status' => 'met', 'evidence' => 'Làm Laravel tại ABC 3 năm.'],
                ['requirement' => 'Tiếng Anh giao tiếp', 'status' => 'missing', 'evidence' => 'CV không đề cập.'],
                ['requirement' => 'Biết Docker', 'status' => 'bogus', 'evidence' => ''],
            ],
            'strengths' => ['Kinh nghiệm Laravel vững'],
            'suggestions' => ['Bổ sung chứng chỉ tiếng Anh'],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($payload)]]]]],
            ]),
        ]);
    }

    public function test_candidate_can_analyse_online_cv_against_job(): void
    {
        $this->fakeGemini();
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create(['title' => 'PHP Developer']);

        $this->actingAs($candidate)->get(route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id]))
            ->assertOk()->assertSee('Chưa có phân tích cho CV này');

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]))
            ->assertRedirect(route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id]));

        $analysis = CvMatchAnalysis::sole();
        $this->assertSame(72, $analysis->score);
        $this->assertSame(5, count($analysis->criteria));
        $this->assertSame(5, collect($analysis->criteria)->firstWhere('name', 'Ngoại ngữ')['cv'], 'values are clamped to 0-5');
        $this->assertSame('missing', $analysis->requirements[2]['status'], 'unknown statuses fall back to missing');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'gemini-test:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'PHP Developer'));

        $this->actingAs($candidate)->get(route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id]))
            ->assertOk()
            ->assertSee('Khá phù hợp')
            ->assertSee('2 năm kinh nghiệm Laravel')
            ->assertSee('Bổ sung chứng chỉ tiếng Anh');
    }

    public function test_finished_analysis_notifies_candidate_once_and_bell_links_to_result(): void
    {
        $this->fakeGemini(28);
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create(['title' => 'Nhân viên Vận hành Máy', 'company_name' => 'Job House']);
        $matchUrl = route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id]);

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]));
        // Cached result: no new notification.
        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]));

        $notification = $candidate->notifications()->sole();
        $this->assertSame('CV của bạn phù hợp 28% với vị trí Nhân viên Vận hành Máy tại Job House.', $notification->data['message']);

        $this->actingAs($candidate)->get(route('home'))
            ->assertOk()
            ->assertSee('Đã có kết quả so khớp CV')
            ->assertSee('Đánh dấu tất cả đã đọc');

        $this->actingAs($candidate)->get(route('notifications.show', $notification->id))->assertRedirect($matchUrl);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_someone_elses_notification(): void
    {
        $this->fakeGemini();
        $owner = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($owner)->create();
        $this->actingAs($owner)->post(route('candidate.cv-match.store', [JobPost::factory()->create(), 'cv_id' => $cv->id]));

        $this->actingAs(User::factory()->candidate()->create())
            ->get(route('notifications.show', $owner->notifications()->sole()->id))
            ->assertNotFound();
    }

    public function test_unchanged_cv_reuses_stored_analysis_unless_refresh_requested(): void
    {
        $this->fakeGemini();
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]));
        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]));
        Http::assertSentCount(1);

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]), ['refresh' => 1]);
        Http::assertSentCount(2);
        $this->assertSame(1, CvMatchAnalysis::count());
    }

    public function test_changing_the_job_marks_analysis_outdated(): void
    {
        $this->fakeGemini();
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]));
        $job->update(['requirements' => 'Yêu cầu mới hoàn toàn']);

        $this->actingAs($candidate)->get(route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id]))
            ->assertSee('đã thay đổi sau lần phân tích này');
    }

    public function test_uploaded_pdf_is_sent_as_inline_file(): void
    {
        $this->fakeGemini();
        Storage::fake('local');
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->uploaded()->create();
        Storage::disk('local')->put($cv->file_path, '%PDF-1.4 fake');

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [JobPost::factory()->create(), 'cv_id' => $cv->id]));

        Http::assertSent(fn (Request $request) => data_get($request->data(), 'contents.0.parts.2.inline_data.data') === base64_encode('%PDF-1.4 fake'));
    }

    public function test_word_cv_and_api_errors_show_friendly_messages(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);
        $candidate = User::factory()->candidate()->create();
        $job = JobPost::factory()->create();
        $wordCv = Cv::factory()->for($candidate)->uploaded()->create(['mime_type' => 'application/msword']);
        $onlineCv = Cv::factory()->for($candidate)->create();

        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $wordCv->id]))
            ->assertSessionHasErrors('analysis');
        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [$job, 'cv_id' => $onlineCv->id]))
            ->assertSessionHasErrors(['analysis' => 'Đã hết lượt dùng AI miễn phí trong lúc này. Vui lòng thử lại sau ít phút.']);

        $this->assertSame(0, CvMatchAnalysis::count());
    }

    public function test_candidate_cannot_analyse_other_users_cv_or_hidden_job(): void
    {
        $this->fakeGemini();
        $candidate = User::factory()->candidate()->create();
        $ownCv = Cv::factory()->for($candidate)->create();

        $this->actingAs($candidate)->get(route('candidate.cv-match.show', [JobPost::factory()->create(), 'cv_id' => Cv::factory()->create()->id]))->assertNotFound();
        $this->actingAs($candidate)->post(route('candidate.cv-match.store', [JobPost::factory()->closed()->create(), 'cv_id' => $ownCv->id]))->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_job_page_shows_application_support_box_for_candidates(): void
    {
        $candidate = User::factory()->candidate()->create();
        Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create(['deadline' => now()->addDays(5)]);

        // Guests see the box, but only as login links.
        $this->get(route('jobs.show', $job))->assertOk()->assertSee('Hỗ trợ ứng tuyển')->assertDontSee('Phân tích độ phù hợp');

        $this->actingAs($candidate)->get(route('jobs.show', $job))
            ->assertOk()->assertSee('Hỗ trợ ứng tuyển')->assertSee('Phân tích độ phù hợp')->assertSee('Còn 5 ngày');

    }
}
