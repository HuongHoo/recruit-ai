<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvBuilderLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_sections_templates_and_verified_companies(): void
    {
        Company::factory()->verified()->create(['name' => 'Công ty Đã Duyệt Hưng Thịnh']);
        Company::factory()->create(['name' => 'Công ty Chờ Duyệt']);

        $this->get(route('cv-builder'))
            ->assertOk()
            ->assertSee('Vượt qua vòng sàng lọc CV')
            ->assertSee('Mẫu CV cho từng thị trường tuyển dụng')
            ->assertSee('Ba bước để có CV hoàn chỉnh')
            ->assertSee('Công ty Đã Duyệt Hưng Thịnh')
            ->assertDontSee('Công ty Chờ Duyệt')
            ->assertSee(route('candidate.cvs.create', ['template' => 'japanese']), false);
    }

    public function test_header_links_to_landing_and_guests_are_sent_to_login_when_starting(): void
    {
        $this->get(route('home'))->assertSee(route('cv-builder'), false);

        $this->get(route('candidate.cvs.create'))->assertRedirect(route('login'));
    }

    public function test_candidate_start_button_opens_builder(): void
    {
        $this->actingAs(User::factory()->candidate()->create())
            ->get(route('cv-builder'))
            ->assertOk()
            ->assertSee('href="'.route('candidate.cvs.create').'"', false);
    }
}
