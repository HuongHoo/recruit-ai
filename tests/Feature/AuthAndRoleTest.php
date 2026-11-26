<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public static function roleProvider(): array
    {
        return [
            'candidate' => ['candidate', '/'],
            'employer' => ['employer', '/employer/dashboard'],
            'admin' => ['admin', '/admin/dashboard'],
        ];
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        foreach (['/candidate/dashboard', '/employer/dashboard', '/admin/dashboard'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_guest_can_view_public_pages(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    #[DataProvider('roleProvider')]
    public function test_user_is_redirected_to_own_dashboard_after_login(string $role, string $home): void
    {
        $user = User::factory()->{$role}()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect($home);
        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('roleProvider')]
    public function test_user_can_only_access_own_area(string $role, string $home): void
    {
        $user = User::factory()->{$role}()->create();

        $areas = ['candidate' => '/candidate/dashboard', 'employer' => '/employer/dashboard', 'admin' => '/admin/dashboard'];

        foreach ($areas as $otherRole => $area) {
            $response = $this->actingAs($user)->get($area);
            $otherRole === $role ? $response->assertOk() : $response->assertForbidden();
        }
    }

    #[DataProvider('roleProvider')]
    public function test_logged_in_user_cannot_see_login_or_register(string $role, string $home): void
    {
        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)->get('/login')->assertRedirect($home);
        $this->actingAs($user)->get('/register')->assertRedirect($home);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_locked_mid_session_is_logged_out(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/candidate/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_candidate_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'candidate',
        ])->assertRedirect('/');

        $this->assertSame(UserRole::Candidate, User::firstWhere('email', 'a@example.com')->role);
    }

    public function test_employer_register_page_shows_company_form(): void
    {
        $this->get('/register?role=employer')
            ->assertOk()
            ->assertSee('Thông tin công ty')
            ->assertSee('Công ty Headhunt / Tuyển dụng dịch vụ (Agency)');
    }

    public function test_employer_can_register_with_company_profile(): void
    {
        Storage::fake('public');

        $this->post('/register/employer', $this->employerPayload())
            ->assertRedirect('/employer/dashboard');

        $user = User::firstWhere('email', 'b@example.com');
        $this->assertSame(UserRole::Employer, $user->role);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Cong ty B', $user->company->name);
        $this->assertSame('agency', $user->company->business_model);
        Storage::disk('public')->assertExists($user->company->logo_path);
        $this->assertSame(CompanyStatus::Pending, $user->company->status);
    }

    public function test_employer_registration_requires_logo_terms_and_valid_business_model(): void
    {
        Storage::fake('public');

        $this->post('/register/employer', $this->employerPayload([
            'logo' => null,
            'terms' => null,
            'business_model' => 'unknown',
        ]))->assertSessionHasErrors(['logo', 'terms', 'business_model']);

        $this->assertDatabaseMissing('users', ['email' => 'b@example.com']);
    }

    public function test_role_field_cannot_escalate_candidate_registration(): void
    {
        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'h@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertRedirect('/');

        $this->assertSame(UserRole::Candidate, User::firstWhere('email', 'h@example.com')->role);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function employerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tran Thi B',
            'phone' => '0901 234 567',
            'email' => 'b@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'business_model' => 'agency',
            'company_name' => 'Cong ty B',
            'company_size' => '25-99',
            'industry' => 'IT - Phần mềm',
            'website' => 'https://example.com',
            'description' => 'Mo ta cong ty',
            'terms' => '1',
        ], $overrides);
    }

    public function test_intended_url_of_another_role_is_ignored_after_login(): void
    {
        $user = User::factory()->candidate()->create();

        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
