<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Cv;
use App\Models\JobPost;
use App\Models\User;
use App\Models\VideoCv;
use App\Models\VideoCvAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VideoCvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function candidate(): User
    {
        return User::factory()->candidate()->create(['name' => 'Nguyễn Văn An']);
    }

    private function firstAnswer(User $user): VideoCvAnswer
    {
        return VideoCv::forUser($user)->answers()->first();
    }

    private function recordAnswer(User $user, VideoCvAnswer $answer, array $overrides = []): TestResponse
    {
        return $this->actingAs($user)->post(route('candidate.video-cv.answers.store', $answer), [
            'video' => UploadedFile::fake()->create('answer.webm', 2048, 'video/webm'),
            'duration_seconds' => 75,
            'source' => 'record',
            ...$overrides,
        ]);
    }

    /** A candidate with one recorded answer and a complete profile, ready to share. */
    private function readyCandidate(): User
    {
        $user = $this->candidate();
        $this->recordAnswer($user, $this->firstAnswer($user));
        VideoCv::forUser($user)->update(['position' => 'Lập trình viên PHP']);

        return $user;
    }

    public function test_landing_page_is_public_and_links_to_the_studio(): void
    {
        $this->get(route('video-cv'))->assertOk()
            ->assertSee('Tạo Video CV miễn phí')
            ->assertSee('id="video-cv-public-title"', false)
            ->assertSee(route('candidate.video-cv.edit'));

        $this->get(route('candidate.video-cv.edit'))->assertRedirect(route('login'));
    }

    public function test_first_visit_creates_video_cv_with_default_questions(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->get(route('candidate.video-cv.edit'))->assertOk()->assertSee('Kiểm tra thiết bị');

        $videoCv = $user->videoCv()->first();
        $this->assertSame('Nguyễn Văn An', $videoCv->full_name);
        $this->assertSame(count(config('recruitment.video_cv.default_questions')), $videoCv->answers()->count());

        foreach (['record', 'info', 'share'] as $step) {
            $this->actingAs($user)->get(route('candidate.video-cv.edit', ['step' => $step]))->assertOk();
        }
    }

    public function test_candidate_can_record_answer_and_rerecord_replaces_the_file(): void
    {
        $user = $this->candidate();
        $answer = $this->firstAnswer($user);

        $this->recordAnswer($user, $answer)->assertRedirect(route('candidate.video-cv.edit', ['step' => 'record']));
        $oldPath = $answer->fresh()->file_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->actingAs($user)->get(route('candidate.video-cv.answers.file', $answer))->assertOk()->assertHeader('Content-Type', 'video/webm');

        $this->actingAs($user)->postJson(route('candidate.video-cv.answers.store', $answer), [
            'video' => UploadedFile::fake()->create('take2.mp4', 1024, 'video/mp4'),
            'duration_seconds' => 40,
            'source' => 'upload',
        ])->assertOk()->assertJsonStructure(['redirect']);

        $answer->refresh();
        $this->assertSame(40, $answer->duration_seconds);
        $this->assertSame('upload', $answer->source);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($answer->file_path);
    }

    public function test_answer_upload_is_validated(): void
    {
        $user = $this->candidate();
        $answer = $this->firstAnswer($user);

        $this->recordAnswer($user, $answer, ['video' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])->assertSessionHasErrors('video');
        $this->recordAnswer($user, $answer, ['duration_seconds' => 121])->assertSessionHasErrors('duration_seconds');
        $this->recordAnswer($user, $answer, ['video' => UploadedFile::fake()->create('big.mp4', 61441, 'video/mp4')])->assertSessionHasErrors('video');

        $this->assertNull($answer->fresh()->file_path);
    }

    public function test_candidate_cannot_touch_another_candidates_answers(): void
    {
        $owner = $this->readyCandidate();
        $answer = $this->firstAnswer($owner);
        $other = $this->candidate();

        $this->recordAnswer($other, $answer)->assertNotFound();
        $this->actingAs($other)->get(route('candidate.video-cv.answers.file', $answer))->assertNotFound();
        $this->actingAs($other)->delete(route('candidate.video-cv.answers.destroy', $answer))->assertNotFound();
    }

    public function test_questions_can_be_edited_removed_and_added(): void
    {
        $user = $this->candidate();
        [$first, $second, $third] = VideoCv::forUser($user)->answers()->get()->all();
        $this->recordAnswer($user, $second);
        $secondPath = $second->fresh()->file_path;

        $this->actingAs($user)->put(route('candidate.video-cv.questions'), [
            'questions' => [
                ['id' => $third->id, 'question' => 'Vì sao bạn chọn công ty chúng tôi?'],
                ['id' => $first->id, 'question' => $first->question],
                ['id' => '', 'question' => 'Mục tiêu nghề nghiệp 3 năm tới của bạn?'],
            ],
        ])->assertRedirect();

        $answers = VideoCv::forUser($user)->answers()->get();
        $this->assertSame(['Vì sao bạn chọn công ty chúng tôi?', $first->question, 'Mục tiêu nghề nghiệp 3 năm tới của bạn?'], $answers->pluck('question')->all());
        $this->assertSame([1, 2, 3], $answers->pluck('position')->all());
        $this->assertNull(VideoCvAnswer::find($second->id));
        Storage::disk('local')->assertMissing($secondPath);

        $tooMany = array_fill(0, 6, ['question' => 'Câu hỏi thử nghiệm']);
        $this->actingAs($user)->put(route('candidate.video-cv.questions'), ['questions' => $tooMany])->assertSessionHasErrors('questions');
    }

    public function test_candidate_saves_profile_photo_and_attached_cv(): void
    {
        $user = $this->candidate();
        $cv = Cv::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put(route('candidate.video-cv.info'), [
            'full_name' => 'Nguyễn An',
            'position' => 'Chuyên viên Marketing',
            'intro' => '3 năm kinh nghiệm digital marketing.',
            'public_email' => 'an@example.com',
            'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
            'cv_id' => $cv->id,
        ])->assertRedirect(route('candidate.video-cv.edit', ['step' => 'share']));

        $videoCv = $user->videoCv()->first();
        $this->assertSame('Chuyên viên Marketing', $videoCv->position);
        $this->assertSame($cv->id, $videoCv->cv_id);
        Storage::disk('local')->assertExists($videoCv->photo_path);

        // Someone else's CV cannot be attached; a new upload is saved to the candidate's CV list.
        $foreignCv = Cv::factory()->create();
        $this->actingAs($user)->put(route('candidate.video-cv.info'), ['full_name' => 'An', 'position' => 'PM', 'cv_id' => $foreignCv->id])->assertSessionHasErrors('cv_id');

        $this->actingAs($user)->put(route('candidate.video-cv.info'), [
            'full_name' => 'An', 'position' => 'PM',
            'cv_file' => UploadedFile::fake()->create('CV moi.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('CV moi', $user->videoCv()->first()->cv->title);
        $this->assertSame(2, $user->cvs()->count());
    }

    public function test_sharing_requires_a_recorded_answer(): void
    {
        $user = $this->candidate();
        VideoCv::forUser($user);

        $this->actingAs($user)->post(route('candidate.video-cv.share'))->assertSessionHasErrors('share');
        $this->assertNull($user->videoCv()->first()->share_token);
    }

    public function test_share_link_shows_public_page_and_can_be_renewed_or_revoked(): void
    {
        $user = $this->readyCandidate();
        $answer = $this->firstAnswer($user);

        $this->actingAs($user)->post(route('candidate.video-cv.share'))->assertSessionHasNoErrors();
        $token = $user->videoCv()->first()->share_token;
        $this->assertSame(32, strlen($token));

        auth()->logout();
        $this->get(route('video-cv.public', $token))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Lập trình viên PHP')
            ->assertSee($answer->question);
        $this->get(route('video-cv.public.answer', [$token, $answer]))->assertOk();
        $this->assertSame(1, $user->videoCv()->first()->view_count);

        // Answers of another Video CV cannot be fetched through this token.
        $otherAnswer = $this->firstAnswer($this->readyCandidate());
        auth()->logout();
        $this->get(route('video-cv.public.answer', [$token, $otherAnswer]))->assertNotFound();

        // Renewing kills the old link.
        $this->actingAs($user)->post(route('candidate.video-cv.share'));
        $newToken = $user->videoCv()->first()->share_token;
        $this->assertNotSame($token, $newToken);
        $this->get(route('video-cv.public', $token))->assertNotFound();

        // Stopping sharing kills the link too.
        $this->actingAs($user)->delete(route('candidate.video-cv.share.destroy'));
        $this->get(route('video-cv.public', $newToken))->assertNotFound();
    }

    public function test_owner_can_preview_before_sharing(): void
    {
        $user = $this->readyCandidate();

        $this->actingAs($user)->get(route('candidate.video-cv.preview'))->assertOk()->assertSee('bản xem trước', false);
    }

    public function test_deleting_video_cv_removes_all_files(): void
    {
        $user = $this->readyCandidate();
        $path = $this->firstAnswer($user)->file_path;

        $this->actingAs($user)->delete(route('candidate.video-cv.destroy'))->assertRedirect(route('video-cv'));

        $this->assertNull($user->videoCv()->first());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_only_employer_who_received_the_application_can_watch(): void
    {
        $company = Company::factory()->verified()->create();
        $job = JobPost::factory()->create(['user_id' => $company->user_id]);
        $application = Application::factory()->create(['job_post_id' => $job->id]);
        $employer = User::find($company->user_id);

        $this->actingAs($employer)->get(route('employer.applications.show', $application))->assertOk()->assertSee('Ứng viên chưa có Video CV');

        $candidate = $application->candidate;
        $answer = $this->firstAnswer($candidate);
        $this->recordAnswer($candidate, $answer);

        $this->actingAs($employer)->get(route('employer.applications.show', $application))
            ->assertOk()->assertSee(route('employer.applications.video', [$application, $answer]));
        $this->actingAs($employer)->get(route('employer.applications.video', [$application, $answer]))->assertOk();

        // An answer belonging to someone else cannot be fetched through this application.
        $foreign = $this->firstAnswer($this->readyCandidate());
        $this->actingAs($employer)->get(route('employer.applications.video', [$application, $foreign]))->assertNotFound();

        $otherEmployer = User::find(Company::factory()->verified()->create()->user_id);
        $this->actingAs($otherEmployer)->get(route('employer.applications.video', [$application, $answer]))->assertForbidden();
    }
}
