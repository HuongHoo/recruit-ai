<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_company_starts_pending(): void
    {
        $company = Company::factory()->create();

        $this->assertSame(CompanyStatus::Pending, $company->fresh()->status);
    }

    public function test_employer_with_pending_company_cannot_post_jobs(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($company->owner)->get('/employer/jobs/create')
            ->assertRedirect(route('employer.company.edit'));
        $this->actingAs($company->owner)->post('/employer/jobs', ['title' => 'PHP Dev'])
            ->assertRedirect(route('employer.company.edit'));
        $this->assertDatabaseCount('job_posts', 0);
    }

    public function test_pending_employer_sees_waiting_banner_on_dashboard(): void
    {
        $company = Company::factory()->create(['name' => 'Cong ty Cho']);

        $this->actingAs($company->owner)->get('/employer/dashboard')
            ->assertOk()
            ->assertSee('Công ty Cong ty Cho đang chờ xác thực');
    }

    public function test_verified_employer_can_open_job_form(): void
    {
        $employer = User::factory()->employer()->create();

        $this->actingAs($employer)->get('/employer/jobs/create')->assertOk();
    }

    public function test_admin_can_approve_company_and_employer_is_notified(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->patch(route('admin.companies.approve', $company))
            ->assertRedirect(route('admin.companies.index'));

        $company->refresh();
        $this->assertSame(CompanyStatus::Verified, $company->status);
        $this->assertNotNull($company->verified_at);
        $this->assertTrue($company->reviewer->is($admin));
        Notification::assertSentTo($company->owner, CompanyReviewed::class);
    }

    public function test_admin_must_give_reason_to_reject(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->patch(route('admin.companies.reject', $company), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame(CompanyStatus::Pending, $company->fresh()->status);
    }

    public function test_rejected_company_is_resubmitted_after_employer_edits_it(): void
    {
        $company = Company::factory()->rejected()->create();

        $this->actingAs($company->owner)->put(route('employer.company.update'), [
            'name' => 'Ten moi',
            'business_model' => 'in_house',
            'website' => 'https://moi.example.com',
            'phone' => '0901234567',
        ])->assertRedirect(route('employer.company.edit'));

        $this->assertSame(CompanyStatus::Pending, $company->fresh()->status);
    }

    public function test_verified_company_changing_name_needs_review_again(): void
    {
        $employer = User::factory()->employer()->create();
        $company = $employer->company;

        $this->actingAs($employer)->put(route('employer.company.update'), [
            'name' => 'Ten khac han',
            'business_model' => $company->business_model,
            'website' => $company->website,
            'phone' => $company->phone,
            'description' => 'Mo ta moi',
        ]);

        $this->assertSame(CompanyStatus::Pending, $company->fresh()->status);
    }

    public function test_verified_company_editing_description_stays_verified(): void
    {
        $employer = User::factory()->employer()->create();
        $company = $employer->company;

        $this->actingAs($employer)->put(route('employer.company.update'), [
            'name' => $company->name,
            'business_model' => $company->business_model,
            'website' => $company->website,
            'phone' => $company->phone,
            'description' => 'Mo ta moi',
        ]);

        $this->assertSame(CompanyStatus::Verified, $company->fresh()->status);
    }

    public function test_employer_cannot_verify_own_company_via_mass_assignment(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($company->owner)->put(route('employer.company.update'), [
            'name' => $company->name,
            'business_model' => 'in_house',
            'website' => $company->website,
            'phone' => $company->phone,
            'status' => 'verified',
        ]);

        $this->assertSame(CompanyStatus::Pending, $company->fresh()->status);
    }

    public function test_non_admin_cannot_review_companies(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($company->owner)->patch(route('admin.companies.approve', $company))->assertForbidden();
    }

    public function test_admin_can_lock_employer_but_not_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $employer = User::factory()->employer()->create();

        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $employer));
        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $otherAdmin))->assertSessionHasErrors('user');

        $this->assertFalse($employer->fresh()->is_active);
        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)->get(route('admin.companies.index'))->assertOk()->assertSee($company->name);
        $this->actingAs($admin)->get(route('admin.companies.show', $company))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    }
}
