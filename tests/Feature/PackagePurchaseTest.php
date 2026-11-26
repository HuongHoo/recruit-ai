<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\Order;
use App\Models\PurchasedPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagePurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function employer(): User
    {
        return Company::factory()->verified()->create()->owner;
    }

    public function test_pricing_cart_checkout_confirm_and_activate(): void
    {
        $employer = $this->employer();
        $job = JobPost::factory()->create(['user_id' => $employer->id, 'title' => 'Tin được đẩy']);
        $plain = JobPost::factory()->create(['title' => 'Tin thường', 'created_at' => now()->addMinute()]);

        $this->actingAs($employer)->get(route('employer.packages.index'))
            ->assertOk()->assertSee('Chọn gói giúp tin của bạn nổi bật hơn')->assertSee('750.000 đ');

        $this->post(route('employer.packages.cart.store'), ['tier' => 'pro', 'days' => 14, 'quantity' => 2]);
        $this->post(route('employer.packages.cart.store'), ['tier' => 'basic', 'days' => 7, 'quantity' => 1]);
        $this->get(route('employer.packages.cart'))->assertSee('1.750.000 đ');

        $this->post(route('employer.packages.checkout'));
        $order = Order::query()->sole();
        $this->assertSame(1750000, $order->total);
        $this->assertSame(3, $order->packages()->count());
        $this->get(route('employer.orders.show', $order))->assertOk()->assertSee($order->code)->assertSee('img.vietqr.io', false);

        // Not usable before payment is confirmed.
        $this->get(route('employer.purchased.index'))->assertViewHas('unused', fn ($u) => $u->isEmpty());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.orders.confirm', $order))->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        $package = PurchasedPackage::query()->where('tier', 'pro')->first();
        $this->actingAs($employer)->post(route('employer.purchased.activate', $package), ['job_post_id' => $job->id])
            ->assertSessionHasNoErrors();

        $job->refresh();
        $this->assertSame('pro', $job->activePromotion()['tier']);
        $this->assertTrue($job->promoted_until->between(now()->addDays(14)->subMinute(), now()->addDays(14)->addMinute()));

        // Promoted job is listed before the newer plain job and carries the badge.
        $this->get(route('jobs.index'))->assertSeeInOrder(['Tin được đẩy', 'Tin thường'])->assertSee('🔥 Pro');

        // A package cannot be used twice.
        $this->post(route('employer.purchased.activate', $package), ['job_post_id' => $job->id])->assertStatus(422);
        $this->assertNotNull($plain);
    }

    public function test_buy_now_and_ownership_rules(): void
    {
        $employer = $this->employer();
        $other = $this->employer();
        $foreignJob = JobPost::factory()->create(['user_id' => $other->id]);

        $this->actingAs($employer)->post(route('employer.packages.buy'), ['tier' => 'promax', 'days' => 30, 'quantity' => 1]);
        $order = Order::query()->sole();
        $this->assertSame(3500000, $order->total);

        $this->post(route('employer.packages.buy'), ['tier' => 'pro', 'days' => 99, 'quantity' => 1])->assertStatus(422);

        $order->markPaid(User::factory()->admin()->create());
        $package = $order->packages()->first();

        $this->post(route('employer.purchased.activate', $package), ['job_post_id' => $foreignJob->id])
            ->assertSessionHasErrors('job_post_id');

        $this->actingAs($other)->get(route('employer.orders.show', $order))->assertNotFound();
        $this->post(route('employer.purchased.activate', $package), ['job_post_id' => $foreignJob->id])->assertNotFound();
    }
}
