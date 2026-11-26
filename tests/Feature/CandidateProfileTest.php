<?php

namespace Tests\Feature;

use App\Models\Education;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateProfileTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(): User
    {
        return User::factory()->candidate()->create();
    }

    public function test_candidate_can_view_profile_page(): void
    {
        $this->actingAs($this->candidate())
            ->get(route('candidate.profile.edit'))
            ->assertOk()
            ->assertSee('Thông tin cá nhân');
    }

    public function test_employer_cannot_access_candidate_profile(): void
    {
        $this->actingAs(User::factory()->employer()->create())
            ->get(route('candidate.profile.edit'))
            ->assertForbidden();
    }

    public function test_candidate_can_update_profile_preferences_and_skills(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->put(route('candidate.profile.update'), [
            'name' => 'Nguyễn Văn A',
            'phone' => '0912 345 678',
            'gender' => 'male',
            'headline' => 'Lập trình viên PHP',
            'desired_salary_min' => 15,
            'desired_salary_max' => 25,
            'desired_locations' => ['Hà Nội', 'Làm việc từ xa'],
            'skills' => 'PHP, Laravel , php, , MySQL',
        ])->assertRedirect(route('candidate.profile.edit'))->assertSessionHasNoErrors();

        $profile = $user->fresh()->candidateProfile;
        $this->assertSame('Nguyễn Văn A', $user->fresh()->name);
        $this->assertSame(['PHP', 'Laravel', 'MySQL'], $profile->skills);
        $this->assertSame(['Hà Nội', 'Làm việc từ xa'], $profile->desired_locations);
        $this->assertSame('15 - 25 triệu', $profile->salaryLabel());
    }

    public function test_profile_rejects_invalid_salary_range_and_unknown_location(): void
    {
        $this->actingAs($this->candidate())->put(route('candidate.profile.update'), [
            'name' => 'A',
            'desired_salary_min' => 30,
            'desired_salary_max' => 10,
            'desired_locations' => ['Sao Hỏa'],
        ])->assertSessionHasErrors(['desired_salary_max', 'desired_locations.0']);
    }

    public function test_candidate_can_add_update_and_delete_education(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->post(route('candidate.educations.store'), [
            'school' => 'Đại học Bách khoa',
            'major' => 'CNTT',
            'degree' => 'Đại học',
            'start_date' => '2018-09',
            'end_date' => '2022-06',
        ])->assertSessionHasNoErrors();

        $education = $user->educations()->sole();
        $this->assertSame('2018-09-01', $education->start_date->toDateString());

        $this->actingAs($user)->put(route('candidate.educations.update', $education), [
            'school' => 'Đại học Quốc gia',
            'start_date' => '2018-09',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Đại học Quốc gia', $education->fresh()->school);

        $this->actingAs($user)->delete(route('candidate.educations.destroy', $education));
        $this->assertModelMissing($education);
    }

    public function test_education_end_date_must_not_precede_start_date(): void
    {
        $this->actingAs($this->candidate())->post(route('candidate.educations.store'), [
            'school' => 'X',
            'start_date' => '2022-01',
            'end_date' => '2020-01',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_current_experience_requires_no_end_date(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->post(route('candidate.experiences.store'), [
            'company' => 'ABC',
            'position' => 'Dev',
            'start_date' => '2023-01',
            'end_date' => '2024-01',
            'is_current' => '1',
        ])->assertSessionHasNoErrors();

        $experience = $user->experiences()->sole();
        $this->assertTrue($experience->is_current);
        $this->assertNull($experience->end_date);
    }

    public function test_past_experience_requires_end_date(): void
    {
        $this->actingAs($this->candidate())->post(route('candidate.experiences.store'), [
            'company' => 'ABC',
            'position' => 'Dev',
            'start_date' => '2023-01',
            'is_current' => '0',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_candidate_cannot_modify_another_candidates_records(): void
    {
        $education = Education::factory()->create();
        $experience = Experience::factory()->create();
        $intruder = $this->candidate();

        $this->actingAs($intruder)->get(route('candidate.educations.edit', $education))->assertForbidden();
        $this->actingAs($intruder)->put(route('candidate.educations.update', $education), ['school' => 'Hacked', 'start_date' => '2020-01'])->assertForbidden();
        $this->actingAs($intruder)->delete(route('candidate.educations.destroy', $education))->assertForbidden();
        $this->actingAs($intruder)->delete(route('candidate.experiences.destroy', $experience))->assertForbidden();

        $this->assertModelExists($education);
        $this->assertModelExists($experience);
        $this->assertNotSame('Hacked', $education->fresh()->school);
    }

    public function test_dashboard_shows_profile_completion(): void
    {
        $user = $this->candidate();
        Education::factory()->for($user)->create();

        $this->actingAs($user)->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertViewHas('completion', 29);
    }
}
