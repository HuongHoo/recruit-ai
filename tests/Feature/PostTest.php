<?php

namespace Tests\Feature;

use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_only_admins_can_manage_posts(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Candidate]))
            ->get(route('admin.posts.index'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('admin.posts.index'))->assertOk();
    }

    public function test_admin_creates_an_event_with_cover_and_unique_slug(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        Post::factory()->create(['title' => 'Ngày hội việc làm']);

        $this->actingAs($admin)->post(route('admin.posts.store'), [
            'type' => 'event',
            'title' => 'Ngày hội việc làm',
            'category' => 'Đồng hành với trường đại học',
            'excerpt' => 'Mô tả ngắn',
            'content' => 'Nội dung',
            'event_date' => '2026-12-01',
            'event_location' => 'Hà Nội',
            'is_featured' => '1',
            'published_at' => now()->subMinute()->format('Y-m-d H:i'),
            'cover' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
        ])->assertRedirect(route('admin.posts.index'));

        $post = Post::query()->latest('id')->first();
        $this->assertSame(PostType::Event, $post->type);
        $this->assertSame('ngay-hoi-viec-lam-2', $post->slug);
        $this->assertSame($admin->id, $post->user_id);
        $this->assertTrue($post->is_featured);
        Storage::disk('public')->assertExists($post->cover_path);
    }

    public function test_event_requires_a_date(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'type' => 'event', 'title' => 'Sự kiện', 'excerpt' => 'x', 'content' => 'y',
        ])->assertSessionHasErrors('event_date');
    }

    public function test_drafts_are_hidden_from_the_public(): void
    {
        $draft = Post::factory()->draft()->create(['title' => 'Bản nháp bí mật']);

        $this->get(route('posts.show', $draft))->assertNotFound();
        $this->get(route('home'))->assertDontSee('Bản nháp bí mật');
    }

    public function test_home_page_shows_published_events_and_articles(): void
    {
        $event = Post::factory()->event()->create(['title' => 'Job Fair Đại học 2026']);
        Post::factory()->create(['title' => 'Mẹo phỏng vấn hiệu quả']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sự kiện nổi bật')
            ->assertSee('Job Fair Đại học 2026')
            ->assertSee('Mẹo phỏng vấn hiệu quả')
            ->assertSee(route('posts.show', $event), false);

        $this->get(route('posts.show', $event))->assertOk()->assertSee('Job Fair Đại học 2026');
    }

    public function test_admin_deletes_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.posts.destroy', $post))->assertRedirect();

        $this->assertModelMissing($post);
    }
}
