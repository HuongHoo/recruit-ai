<?php

namespace Tests\Feature;

use App\Enums\CvType;
use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateCvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function candidate(): User
    {
        return User::factory()->candidate()->create();
    }

    public function test_candidate_can_view_cv_list_and_create_page(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->get(route('candidate.cvs.index'))->assertOk()->assertSee('Bạn chưa có CV nào');
        $this->actingAs($user)->get(route('candidate.cvs.create'))->assertOk()->assertSee('Chọn mẫu CV');
    }

    public function test_candidate_can_create_online_cv_and_first_one_is_default(): void
    {
        $user = $this->candidate();

        $response = $this->actingAs($user)->post(route('candidate.cvs.store'), [
            'title' => 'CV Backend',
            'template' => 'modern',
            'accent_color' => '#059669',
            'objective' => 'Trở thành senior developer.',
        ]);

        $cv = $user->cvs()->sole();
        $response->assertRedirect(route('candidate.cvs.show', $cv));
        $this->assertSame(CvType::Online, $cv->type);
        $this->assertTrue($cv->is_default);

        $this->actingAs($user)->post(route('candidate.cvs.store'), [
            'title' => 'CV 2', 'template' => 'classic', 'accent_color' => '#2563eb',
        ]);
        $this->assertFalse($user->cvs()->where('title', 'CV 2')->sole()->is_default);
    }

    public function test_online_cv_rejects_unknown_template_and_colour(): void
    {
        $this->actingAs($this->candidate())->post(route('candidate.cvs.store'), [
            'title' => 'X', 'template' => 'hacker', 'accent_color' => 'red',
        ])->assertSessionHasErrors(['template', 'accent_color']);
    }

    public function test_online_cv_preview_renders_profile_data_for_each_template(): void
    {
        $user = $this->candidate();
        CandidateProfile::factory()->for($user)->create(['headline' => 'Kỹ sư phần mềm', 'skills' => ['Laravel']]);
        Experience::factory()->for($user)->create(['company' => 'Công ty Sao Mai']);

        foreach (['classic', 'modern', 'minimal'] as $template) {
            $cv = Cv::factory()->for($user)->create(['template' => $template]);

            $this->actingAs($user)->get(route('candidate.cvs.show', $cv))
                ->assertOk()
                ->assertSee($user->name)
                ->assertSee('Kỹ sư phần mềm')
                ->assertSee('Laravel')
                ->assertSee('Công ty Sao Mai')
                ->assertSee('Tải PDF');
        }
    }

    public function test_candidate_can_upload_pdf_cv(): void
    {
        $user = $this->candidate();
        $file = UploadedFile::fake()->create('cv-nguyen-van-a.pdf', 300, 'application/pdf');

        $this->actingAs($user)->post(route('candidate.cvs.upload'), ['file' => $file])
            ->assertRedirect(route('candidate.cvs.index'))
            ->assertSessionHasNoErrors();

        $cv = $user->cvs()->sole();
        $this->assertSame(CvType::Upload, $cv->type);
        $this->assertSame('cv-nguyen-van-a', $cv->title);
        $this->assertStringStartsWith("cvs/{$user->id}/", $cv->file_path);
        Storage::disk('local')->assertExists($cv->file_path);
    }

    public function test_upload_rejects_disallowed_types_and_large_files(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->post(route('candidate.cvs.upload'), [
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($user)->post(route('candidate.cvs.upload'), [
            'file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, $user->cvs()->count());
    }

    public function test_cv_limit_per_user_is_enforced(): void
    {
        $user = $this->candidate();
        Cv::factory()->for($user)->count(config('recruitment.cv.max_per_user'))->create();

        $this->actingAs($user)->post(route('candidate.cvs.store'), [
            'title' => 'One too many', 'template' => 'modern', 'accent_color' => '#2563eb',
        ])->assertSessionHasErrors('file');
    }

    public function test_owner_can_download_uploaded_cv(): void
    {
        $user = $this->candidate();
        $this->actingAs($user)->post(route('candidate.cvs.upload'), [
            'file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ]);
        $cv = $user->cvs()->sole();

        $this->actingAs($user)->get(route('candidate.cvs.download', $cv))
            ->assertOk()
            ->assertDownload('cv.pdf');
    }

    public function test_online_cv_download_redirects_to_print_view(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs($cv->user)->get(route('candidate.cvs.download', $cv))
            ->assertRedirect(route('candidate.cvs.show', ['cv' => $cv, 'print' => 1]));
    }

    public function test_other_users_cannot_view_download_edit_or_delete_a_cv(): void
    {
        $cv = Cv::factory()->create();
        $intruder = $this->candidate();
        $employer = User::factory()->employer()->create();

        $this->actingAs($intruder)->get(route('candidate.cvs.show', $cv))->assertForbidden();
        $this->actingAs($intruder)->get(route('candidate.cvs.download', $cv))->assertForbidden();
        $this->actingAs($intruder)->get(route('candidate.cvs.edit', $cv))->assertForbidden();
        $this->actingAs($intruder)->patch(route('candidate.cvs.default', $cv))->assertForbidden();
        $this->actingAs($intruder)->delete(route('candidate.cvs.destroy', $cv))->assertForbidden();
        $this->actingAs($employer)->get(route('candidate.cvs.show', $cv))->assertForbidden();

        $this->assertModelExists($cv);
    }

    public function test_setting_default_cv_unsets_the_previous_one(): void
    {
        $user = $this->candidate();
        $first = Cv::factory()->for($user)->default()->create();
        $second = Cv::factory()->for($user)->create();

        $this->actingAs($user)->patch(route('candidate.cvs.default', $second));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_deleting_default_cv_promotes_another_and_removes_file(): void
    {
        $user = $this->candidate();
        Storage::disk('local')->put('cvs/a.pdf', 'pdf');
        $default = Cv::factory()->for($user)->uploaded()->default()->create(['file_path' => 'cvs/a.pdf']);
        $other = Cv::factory()->for($user)->create();

        $this->actingAs($user)->delete(route('candidate.cvs.destroy', $default))
            ->assertRedirect(route('candidate.cvs.index'));

        $this->assertModelMissing($default);
        Storage::disk('local')->assertMissing('cvs/a.pdf');
        $this->assertTrue($other->fresh()->is_default);
    }

    public function test_uploaded_cv_can_only_be_renamed(): void
    {
        $cv = Cv::factory()->uploaded()->create();

        $this->actingAs($cv->user)->put(route('candidate.cvs.update', $cv), [
            'title' => 'CV mới', 'template' => 'modern',
        ])->assertSessionHasNoErrors();

        $cv->refresh();
        $this->assertSame('CV mới', $cv->title);
        $this->assertNull($cv->template);
    }
}
