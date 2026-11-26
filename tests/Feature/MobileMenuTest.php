<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_mobile_menu_lists_main_pages_login_and_employer_link(): void
    {
        $this->get(route('community.index'))
            ->assertOk()
            ->assertSee('aria-label="Mở menu"', false)
            ->assertSeeInOrder([
                'data-mobile-menu',
                route('jobs.index'), route('cv-builder'), route('video-cv'), route('community.index'),
                route('login'), route('register', ['role' => 'employer']),
            ], false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_signed_in_candidate_mobile_menu_has_no_login_link(): void
    {
        $response = $this->actingAs(User::factory()->candidate()->create())->get(route('community.index'));

        $response->assertSee('data-mobile-menu', false);
        $menu = str($response->getContent())->after('data-mobile-menu')->before('</details>')->value();
        $this->assertStringContainsString(route('community.index'), $menu);
        $this->assertStringNotContainsString(route('login'), $menu);
    }
}
