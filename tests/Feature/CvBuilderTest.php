<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\Education;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvBuilderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'builder' => '1',
            'title' => 'CV Kinh doanh',
            'template' => 'ats',
            'accent_color' => '#2563eb',
            'objective' => 'Nhân viên kinh doanh 3 năm kinh nghiệm.',
            'name' => 'Trần Văn Nam',
            'headline' => 'Nhân viên kinh doanh',
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'skills' => ['Đàm phán', ' Excel ', 'excel', ''],
            'experiences' => [
                ['company' => 'Công ty ABC', 'position' => 'Sales', 'start_date' => '2022-03', 'is_current' => '1', 'description' => "Tăng doanh số 25%\nMở 30 khách hàng mới"],
            ],
            'educations' => [
                ['school' => 'Đại học Thương mại', 'major' => 'Marketing', 'degree' => 'Đại học', 'start_date' => '2017-09', 'end_date' => '2021-06'],
            ],
        ], $overrides);
    }

    public function test_builder_page_shows_steps_and_existing_profile_content(): void
    {
        $user = User::factory()->candidate()->create();
        Experience::factory()->for($user)->create(['company' => 'Công ty Cũ XYZ']);

        $this->actingAs($user)->get(route('candidate.cvs.create', ['template' => 'japanese']))
            ->assertOk()
            ->assertSee('Thông tin cá nhân')
            ->assertSee('Kinh nghiệm làm việc')
            ->assertSee('Điểm ATS')
            ->assertSee('Công ty Cũ XYZ')
            ->assertSee('value="japanese"', false);
    }

    public function test_builder_saves_cv_and_its_content(): void
    {
        $user = User::factory()->candidate()->create();

        $this->actingAs($user)->post(route('candidate.cvs.store'), $this->payload())->assertRedirect();

        $cv = Cv::sole();
        $this->assertSame('CV Kinh doanh', $cv->title);
        $this->assertTrue($cv->is_default);
        $this->assertSame('Trần Văn Nam', $user->fresh()->name);
        $this->assertSame(['Đàm phán', 'Excel'], $user->fresh()->profile()->skills);

        $experience = $user->experiences()->sole();
        $this->assertTrue($experience->is_current);
        $this->assertNull($experience->end_date);
        $this->assertSame('2022-03-01', $experience->start_date->toDateString());
        $this->assertSame('Đại học Thương mại', $user->educations()->sole()->school);
    }

    public function test_update_edits_kept_rows_deletes_removed_ones_and_ignores_foreign_ids(): void
    {
        $user = User::factory()->candidate()->create();
        $cv = Cv::factory()->for($user)->create();
        $kept = Experience::factory()->for($user)->create(['company' => 'Cũ']);
        $removed = Experience::factory()->for($user)->create();
        $foreign = Experience::factory()->create(['company' => 'Của người khác']);

        $this->actingAs($user)->put(route('candidate.cvs.update', $cv), $this->payload([
            'experiences' => [
                ['id' => $kept->id, 'company' => 'Đã sửa', 'position' => 'Lead', 'start_date' => '2020-01', 'end_date' => '2021-01'],
                ['id' => $foreign->id, 'company' => 'Mới', 'position' => 'Dev', 'start_date' => '2021-02', 'is_current' => '1'],
            ],
            'educations' => [],
        ]))->assertRedirect(route('candidate.cvs.index'));

        $this->assertSame('Đã sửa', $kept->fresh()->company);
        $this->assertModelMissing($removed);
        $this->assertSame('Của người khác', $foreign->fresh()->company);
        $this->assertEqualsCanonicalizing(['Đã sửa', 'Mới'], $user->experiences()->pluck('company')->all());
        $this->assertSame(0, Education::query()->where('user_id', $user->id)->count());
    }

    public function test_invalid_rows_are_reported(): void
    {
        $user = User::factory()->candidate()->create();

        $this->actingAs($user)->post(route('candidate.cvs.store'), $this->payload([
            'experiences' => [['company' => '', 'position' => 'Sales', 'start_date' => '2022-03']],
        ]))->assertSessionHasErrors(['experiences.0.company', 'experiences.0.end_date']);

        $this->assertSame(0, Cv::count());
    }

    public function test_preview_renders_unsaved_content_in_chosen_template(): void
    {
        $user = User::factory()->candidate()->create();

        $this->actingAs($user)->post(route('candidate.cvs.preview'), $this->payload([
            'name' => 'Tên Chưa Lưu',
            'experiences' => [['company' => 'Đang gõ dở']],
        ]))
            ->assertOk()
            ->assertSee('Tên Chưa Lưu')
            ->assertSee('Đang gõ dở');

        $this->assertNotSame('Tên Chưa Lưu', $user->fresh()->name);
    }
}
