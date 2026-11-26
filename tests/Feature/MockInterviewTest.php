<?php

namespace Tests\Feature;

use App\Enums\MockInterviewStatus;
use App\Models\Cv;
use App\Models\JobPost;
use App\Models\MockInterview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MockInterviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $payloads  one JSON payload per expected Gemini call
     */
    private function fakeGemini(array ...$payloads): void
    {
        $sequence = Http::sequence();

        foreach ($payloads as $payload) {
            $sequence->push(['candidates' => [['content' => ['parts' => [['text' => json_encode($payload)]]]]]]);
        }

        Http::fake(['generativelanguage.googleapis.com/*' => $sequence]);
    }

    /**
     * @return array<string, mixed>
     */
    private function questionsPayload(int $count = 4): array
    {
        return ['questions' => collect(range(1, $count))
            ->map(fn (int $n) => ['question' => "Câu hỏi số {$n} về Laravel?", 'focus' => "Chủ đề {$n}"])
            ->push(['question' => '', 'focus' => 'Rỗng'])
            ->all()];
    }

    public function test_guest_sees_application_support_with_login_links(): void
    {
        $job = JobPost::factory()->create();

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('Hỗ trợ ứng tuyển')
            ->assertSee('Phỏng vấn mô phỏng với AI')
            ->assertSee('Phân tích CV với việc làm này')
            ->assertSee(route('login'));
    }

    public function test_employer_does_not_see_application_support(): void
    {
        $job = JobPost::factory()->create();

        $this->actingAs(User::factory()->employer()->create())->get(route('jobs.show', $job))
            ->assertOk()
            ->assertDontSee('Hỗ trợ ứng tuyển');
    }

    public function test_candidate_without_cv_is_offered_an_upload_that_returns_to_the_job(): void
    {
        Storage::fake('local');
        $candidate = User::factory()->candidate()->create();
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('Chưa có CV')
            ->assertSee('Tải CV lên');

        $this->actingAs($candidate)->post(route('candidate.cvs.upload'), [
            'file' => UploadedFile::fake()->create('cv-cua-toi.pdf', 200, 'application/pdf'),
            'return_to_job' => $job->id,
            'support_tab' => 'interview',
        ])->assertRedirect(route('jobs.show', $job).'#ho-tro-ung-tuyen')->assertSessionHas('support_tab', 'interview');

        $this->assertSame('cv-cua-toi', $candidate->cvs()->sole()->title);
    }

    public function test_candidate_with_cv_sees_interview_form_with_languages(): void
    {
        $candidate = User::factory()->candidate()->create();
        Cv::factory()->for($candidate)->create(['title' => 'CV Laravel của tôi']);
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('CV Laravel của tôi')
            ->assertSee('Ngôn ngữ phỏng vấn')
            ->assertSee('Tiếng Nhật')
            ->assertSee('Bắt đầu phỏng vấn')
            ->assertSee(route('candidate.mock-interviews.store', $job));
    }

    public function test_full_interview_flow_from_questions_to_evaluation(): void
    {
        $this->fakeGemini($this->questionsPayload(), [
            'score' => 68,
            'headline' => 'Trả lời khá tốt nhưng thiếu ví dụ',
            'summary' => 'Bạn nắm kiến thức cơ bản.',
            'items' => [
                ['score' => 8, 'comment' => 'Giới thiệu rõ ràng.', 'suggestion' => 'Tôi là lập trình viên Laravel 3 năm...'],
                ['score' => 15, 'comment' => 'Tốt.', 'suggestion' => 'Mẫu 2'],
                ['score' => 9, 'comment' => 'AI bịa điểm cho câu bỏ trống', 'suggestion' => 'Mẫu 3'],
            ],
            'strengths' => ['Tự tin'],
            'improvements' => ['Dùng cấu trúc STAR'],
        ]);
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create(['title' => 'PHP Developer']);

        $response = $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', $job), ['cv_id' => $cv->id, 'language' => 'en']);

        $interview = MockInterview::sole();
        $response->assertRedirect(route('candidate.mock-interviews.setup', $interview));
        $this->assertCount(4, $interview->questions, 'empty questions are dropped');
        $this->assertSame(['', '', '', ''], $interview->answers);
        $this->assertSame('en', $interview->language);
        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'PHP Developer')
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'tiếng Anh'));

        $this->actingAs($candidate)->get(route('candidate.mock-interviews.setup', $interview))
            ->assertOk()->assertSee('Kiểm tra thiết bị trước khi bắt đầu')->assertSee('Hello, I am ready for the interview.');
        $this->actingAs($candidate)->get(route('candidate.mock-interviews.session', $interview))
            ->assertOk()->assertSee(json_encode('Câu hỏi số 1 về Laravel?'), false)->assertSee('en-US')
            ->assertDontSee('id="chat-assistant"', false); // the floating chat would cover the interview overlays

        $this->actingAs($candidate)->putJson(route('candidate.mock-interviews.answer', $interview), ['index' => 0, 'answer' => '  Tôi là Hương, lập trình viên Laravel.  '])
            ->assertNoContent();
        $this->assertSame('Tôi là Hương, lập trình viên Laravel.', $interview->fresh()->answers[0]);

        // Final submit also carries answers; question 3 and 4 are left blank.
        $this->actingAs($candidate)->post(route('candidate.mock-interviews.finish', $interview), ['answers' => ['Tôi là Hương, lập trình viên Laravel.', 'Vì tôi thích PHP.', '', '']])
            ->assertRedirect(route('candidate.mock-interviews.show', $interview));

        $interview->refresh();
        $this->assertSame(MockInterviewStatus::Completed, $interview->status);
        $this->assertSame(68, $interview->score);
        $this->assertSame([8, 10, 0, 0], array_column($interview->feedback['items'], 'score'), 'scores are clamped and blank answers get 0');
        $this->assertNotNull($interview->completed_at);

        $this->actingAs($candidate)->get(route('candidate.mock-interviews.show', $interview))
            ->assertOk()
            ->assertSee('Trả lời khá tốt nhưng thiếu ví dụ')
            ->assertSee('Dùng cấu trúc STAR')
            ->assertSee('Tôi là lập trình viên Laravel 3 năm...')
            ->assertSee('— Bỏ qua —');

        $this->actingAs($candidate)->get(route('candidate.mock-interviews.index'))
            ->assertOk()->assertSee('PHP Developer')->assertSee('68');

        // A finished interview can no longer be edited and its session page goes to the result.
        $this->actingAs($candidate)->putJson(route('candidate.mock-interviews.answer', $interview), ['index' => 0, 'answer' => 'x'])->assertStatus(409);
        $this->actingAs($candidate)->get(route('candidate.mock-interviews.session', $interview))->assertRedirect(route('candidate.mock-interviews.show', $interview));
    }

    public function test_setup_offers_both_interviewers(): void
    {
        $interview = MockInterview::factory()->create(['language' => 'vi']);

        $this->actingAs($interview->user)->get(route('candidate.mock-interviews.setup', $interview))
            ->assertOk()
            ->assertSee('Chọn người phỏng vấn')
            ->assertSee('Ngọc Lan')
            ->assertSee('Minh Quân');
    }

    public function test_session_shows_the_chosen_interviewer_with_a_personal_greeting(): void
    {
        $candidate = User::factory()->candidate()->create(['name' => 'Nguyễn Văn An']);
        $job = JobPost::factory()->create(['company_name' => 'Công ty ABC']);
        $interview = MockInterview::factory()->for($candidate)->for($job)->create(['language' => 'vi']);

        $this->actingAs($candidate)->get(route('candidate.mock-interviews.session', [$interview, 'interviewer' => 'male']))
            ->assertOk()
            ->assertSee('Minh Quân')
            ->assertSee('Minh Quân đã vào phòng');

        $this->assertStringStartsWith(
            'Xin chào An, tôi là Minh Quân, Trưởng phòng Nhân sự của Công ty ABC.',
            $interview->interviewer('male')['greeting'],
        );

        // Unknown choices fall back to the default interviewer.
        $this->actingAs($candidate)->get(route('candidate.mock-interviews.session', [$interview, 'interviewer' => 'robot']))
            ->assertOk()
            ->assertSee('Ngọc Lan đã vào phòng');
    }

    public function test_interviewer_speaks_the_interview_language(): void
    {
        $candidate = User::factory()->candidate()->create(['name' => 'Tran Thi Mai']);
        $interview = MockInterview::factory()->for($candidate)->create(['language' => 'en']);

        $interviewer = $interview->interviewer('female');

        $this->assertSame('Emma', $interviewer['name']);
        $this->assertStringStartsWith("Hello Tran Thi Mai, I'm Emma, HR Manager at", $interviewer['greeting']);
        $this->assertNotContains('david', $interviewer['voice']);
        $this->assertContains('david', $interviewer['avoidVoice']);
    }

    /**
     * @return array<string, mixed>
     */
    private function audioResponse(string $bytes, string $mime = 'audio/wav'): array
    {
        return ['candidates' => [['content' => ['parts' => [['inlineData' => ['mimeType' => $mime, 'data' => base64_encode($bytes)]]]]]]];
    }

    public function test_interviewer_lines_are_read_by_the_ai_voice_and_cached(): void
    {
        Storage::fake('local');
        config(['services.gemini.tts_models' => 'tts-a,tts-b']);
        Http::fake([
            '*/models/tts-a:generateContent' => Http::response(['error' => ['code' => 429]], 429),
            '*/models/tts-b:generateContent' => Http::response($this->audioResponse('RIFF-fake-wav')),
        ]);
        $interview = MockInterview::factory()->create(['language' => 'vi']);

        $url = route('candidate.mock-interviews.speech', [$interview, 'question-0', 'interviewer' => 'male']);
        $response = $this->actingAs($interview->user)->get($url)->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->assertSame('RIFF-fake-wav', $response->getFile()->getContent());

        // The rate-limited model handed over to the next; the question text and male voice were sent.
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'tts-b')
            && $request['contents'][0]['parts'][0]['text'] === $interview->questions[0]['question']
            && $request['generationConfig']['speechConfig']['voiceConfig']['prebuiltVoiceConfig']['voiceName'] === 'Charon');

        // Second time it comes from disk.
        $this->actingAs($interview->user)->get($url)->assertOk();
        Http::assertSentCount(2);
    }

    public function test_raw_pcm_voice_is_wrapped_as_wav(): void
    {
        Storage::fake('local');
        config(['services.gemini.tts_models' => 'tts-a']);
        Http::fake(['*' => Http::response($this->audioResponse(str_repeat("\0", 100), 'audio/L16;codec=pcm;rate=24000'))]);
        $interview = MockInterview::factory()->create();

        $wav = $this->actingAs($interview->user)->get(route('candidate.mock-interviews.speech', [$interview, 'greeting']))
            ->assertOk()->getFile()->getContent();

        $this->assertSame('RIFF', substr($wav, 0, 4));
        $this->assertSame(144, strlen($wav));
        $this->assertSame(24000, unpack('V', substr($wav, 24, 4))[1]);
    }

    public function test_speech_rejects_unknown_lines_other_users_and_busy_voice(): void
    {
        Storage::fake('local');
        config(['services.gemini.tts_models' => 'tts-a']);
        Http::fake(['*' => Http::response([], 429)]);
        $interview = MockInterview::factory()->create();

        $this->actingAs($interview->user)->get(route('candidate.mock-interviews.speech', [$interview, 'question-99']))->assertNotFound();
        $this->actingAs($interview->user)->get(route('candidate.mock-interviews.speech', [$interview, 'hello']))->assertNotFound();
        $this->actingAs($interview->user)->get(route('candidate.mock-interviews.speech', [$interview, 'ack-0']))
            ->assertStatus(503)->assertHeader('Retry-After', '20');
        $this->actingAs(User::factory()->candidate()->create())
            ->get(route('candidate.mock-interviews.speech', [$interview, 'greeting']))->assertForbidden();
    }

    public function test_answer_index_must_exist(): void
    {
        $interview = MockInterview::factory()->create();

        $this->actingAs($interview->user)->putJson(route('candidate.mock-interviews.answer', $interview), ['index' => 5, 'answer' => 'x'])
            ->assertUnprocessable();
    }

    public function test_candidate_cannot_access_someone_elses_interview(): void
    {
        $interview = MockInterview::factory()->create();
        $other = User::factory()->candidate()->create();

        $this->actingAs($other)->get(route('candidate.mock-interviews.session', $interview))->assertForbidden();
        $this->actingAs($other)->putJson(route('candidate.mock-interviews.answer', $interview), ['index' => 0, 'answer' => 'x'])->assertForbidden();
        $this->actingAs($other)->post(route('candidate.mock-interviews.finish', $interview))->assertForbidden();
    }

    public function test_word_cv_and_ai_errors_are_reported_on_the_job_page(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);
        $candidate = User::factory()->candidate()->create();
        $job = JobPost::factory()->create();
        $wordCv = Cv::factory()->for($candidate)->uploaded()->create(['mime_type' => 'application/msword']);
        $onlineCv = Cv::factory()->for($candidate)->create();

        $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', $job), ['cv_id' => $wordCv->id, 'language' => 'vi'])
            ->assertRedirect(route('jobs.show', $job).'#ho-tro-ung-tuyen')
            ->assertSessionHasErrors(['interview' => 'AI hiện chỉ đọc được CV trực tuyến hoặc CV dạng PDF. Hãy tải lên bản PDF của CV này.']);

        $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', $job), ['cv_id' => $onlineCv->id, 'language' => 'vi'])
            ->assertSessionHasErrors(['interview' => 'Đã hết lượt dùng AI miễn phí trong lúc này. Vui lòng thử lại sau ít phút.']);

        $this->assertSame(0, MockInterview::count());
    }

    public function test_failed_grading_keeps_answers_and_returns_to_session(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);
        $interview = MockInterview::factory()->create();

        $this->actingAs($interview->user)->post(route('candidate.mock-interviews.finish', $interview), ['answers' => ['Câu trả lời của tôi', '']])
            ->assertRedirect(route('candidate.mock-interviews.session', $interview))
            ->assertSessionHasErrors('interview');

        $interview->refresh();
        $this->assertSame('Câu trả lời của tôi', $interview->answers[0]);
        $this->assertSame(MockInterviewStatus::InProgress, $interview->status);
    }

    public function test_cannot_start_interview_with_other_users_cv_hidden_job_or_unknown_language(): void
    {
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();
        $job = JobPost::factory()->create();

        $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', $job), ['cv_id' => Cv::factory()->create()->id, 'language' => 'vi'])->assertNotFound();
        $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', JobPost::factory()->closed()->create()), ['cv_id' => $cv->id, 'language' => 'vi'])->assertNotFound();
        $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', $job), ['cv_id' => $cv->id, 'language' => 'fr'])->assertSessionHasErrors('language');
    }

    public function test_overloaded_model_falls_back_to_the_next_one(): void
    {
        config(['services.gemini.fallback_models' => 'gemini-backup']);
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-test:*' => Http::response(['error' => ['message' => 'high demand']], 503),
            'generativelanguage.googleapis.com/v1beta/models/gemini-backup:*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($this->questionsPayload())]]]]],
            ]),
        ]);
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create();

        $this->actingAs($candidate)->post(route('candidate.mock-interviews.store', JobPost::factory()->create()), ['cv_id' => $cv->id, 'language' => 'vi'])
            ->assertSessionHasNoErrors();

        $this->assertSame('gemini-backup', MockInterview::sole()->model);
        Http::assertSentCount(2);
    }
}
