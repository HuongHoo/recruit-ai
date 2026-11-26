<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvTemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_lists_every_template_with_sample_preview(): void
    {
        $response = $this->get(route('cv-templates.index'))->assertOk()->assertSee('Nguyễn Minh Anh');

        foreach (config('recruitment.cv.templates') as $template) {
            $response->assertSee($template['name']);
        }
    }

    public function test_public_header_links_to_create_cv_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tạo CV')
            ->assertSee(route('cv-builder'), false);
    }

    public function test_each_template_preview_page_renders(): void
    {
        foreach (array_keys(config('recruitment.cv.templates')) as $key) {
            $this->get(route('cv-templates.show', $key))
                ->assertOk()
                ->assertSee('Dùng mẫu này')
                ->assertSee('Chuyên viên Performance Marketing')
                ->assertSee('Đăng nhập để tạo CV theo mẫu');
        }

        $this->get(route('cv-templates.show', 'khong-ton-tai'))->assertNotFound();
    }

    public function test_candidate_sees_saved_cvs_and_create_link(): void
    {
        $candidate = User::factory()->candidate()->create();
        Cv::factory()->for($candidate)->create(['title' => 'CV Marketing', 'template' => 'modern']);
        Cv::factory()->for($candidate)->uploaded()->create(['title' => 'CV file PDF']);

        $this->actingAs($candidate)->get(route('cv-templates.show', 'ats'))
            ->assertOk()
            ->assertSee(route('candidate.cvs.create', ['template' => 'ats']), false)
            ->assertSee('CV Marketing')
            ->assertSee('CV file PDF')
            ->assertSee('Không hỗ trợ');
    }

    public function test_candidate_can_move_online_cv_to_another_template(): void
    {
        $candidate = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($candidate)->create(['template' => 'modern']);

        $this->actingAs($candidate)->patch(route('candidate.cvs.template', $cv), ['template' => 'ats'])
            ->assertRedirect(route('candidate.cvs.show', $cv));
        $this->assertSame('ats', $cv->fresh()->template);

        $this->actingAs($candidate)->get(route('candidate.cvs.show', $cv))->assertOk();
    }

    public function test_template_switch_rejects_uploaded_cv_unknown_template_and_other_users(): void
    {
        $candidate = User::factory()->candidate()->create();
        $uploaded = Cv::factory()->for($candidate)->uploaded()->create();
        $online = Cv::factory()->for($candidate)->create(['template' => 'modern']);

        $this->actingAs($candidate)->patch(route('candidate.cvs.template', $uploaded), ['template' => 'ats'])->assertSessionHasErrors('template');
        $this->actingAs($candidate)->patch(route('candidate.cvs.template', $online), ['template' => 'abc'])->assertSessionHasErrors('template');
        $this->actingAs(User::factory()->candidate()->create())->patch(route('candidate.cvs.template', $online), ['template' => 'ats'])->assertForbidden();

        $this->assertSame('modern', $online->fresh()->template);
    }
}
