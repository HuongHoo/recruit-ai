<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityPostTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Phỏng vấn AI giúp mình đậu vòng 1',
            'topic' => 'experience',
            'body' => 'Mình luyện 3 lần thì tự tin hơn hẳn.',
        ], $overrides);
    }

    public function test_guest_can_read_list_and_post_but_not_create(): void
    {
        $post = CommunityPost::factory()->create(['title' => 'Bài công khai']);

        $this->get(route('community.index'))->assertOk()->assertSee('Bài công khai');
        $this->get(route('community.show', $post))->assertOk()->assertSee('Đăng nhập để bình luận');
        $this->get(route('community.create'))->assertRedirect(route('login'));
        $this->post(route('community.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_member_creates_post_with_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->employer()->create();

        $response = $this->actingAs($user)->post(route('community.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ]));

        $post = CommunityPost::query()->sole();
        $response->assertRedirect(route('community.show', $post));
        $this->assertSame($user->id, $post->user_id);
        $this->assertCount(2, $post->images);
        foreach ($post->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertStringStartsWith("community/{$post->id}/", $image->path);
        }
        $this->get(route('community.show', $post))->assertSee('Nhà tuyển dụng');
    }

    public function test_more_than_four_images_or_wrong_type_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->candidate()->create();
        $five = array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg"), range(1, 5));

        $this->actingAs($user)->post(route('community.store'), $this->payload(['images' => $five]))
            ->assertSessionHasErrors('images');
        $this->actingAs($user)->post(route('community.store'), $this->payload([
            'images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
        ]))->assertSessionHasErrors('images.0');
        $this->actingAs($user)->post(route('community.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('big.jpg')->size(3000)],
        ]))->assertSessionHasErrors('images.0');
        $this->actingAs($user)->post(route('community.store'), $this->payload(['title' => str_repeat('a', 151), 'topic' => 'nope']))
            ->assertSessionHasErrors(['title', 'topic']);

        $this->assertDatabaseCount('community_posts', 0);
    }

    public function test_inactive_user_cannot_create(): void
    {
        $this->actingAs(User::factory()->candidate()->inactive()->create())
            ->post(route('community.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('community_posts', 0);
    }

    public function test_only_author_can_update_and_author_or_admin_can_delete(): void
    {
        $post = CommunityPost::factory()->create();
        $other = User::factory()->candidate()->create();

        $this->actingAs($other)->get(route('community.edit', $post))->assertForbidden();
        $this->actingAs($other)->put(route('community.update', $post), $this->payload())->assertForbidden();
        $this->actingAs($other)->delete(route('community.destroy', $post))->assertForbidden();

        $this->actingAs($post->author)->put(route('community.update', $post), $this->payload(['title' => 'Đã sửa']))
            ->assertRedirect(route('community.show', $post));
        $this->assertSame('Đã sửa', $post->fresh()->title);

        $this->actingAs(User::factory()->admin()->create())->delete(route('community.destroy', $post))
            ->assertRedirect(route('community.index'));
        $this->assertModelMissing($post);
    }

    public function test_update_removes_and_adds_images_within_limit(): void
    {
        Storage::fake('public');
        $post = CommunityPost::factory()->create();
        $images = collect(range(1, 4))->map(fn ($i) => $post->images()->create([
            'path' => UploadedFile::fake()->image("{$i}.jpg")->store("community/{$post->id}", 'public'),
            'position' => $i,
        ]));

        $this->actingAs($post->author)->put(route('community.update', $post), $this->payload([
            'images' => [UploadedFile::fake()->image('new.jpg')],
        ]))->assertSessionHasErrors('images');

        $this->actingAs($post->author)->put(route('community.update', $post), $this->payload([
            'remove_images' => [$images[0]->id],
            'images' => [UploadedFile::fake()->image('new.jpg')],
        ]))->assertSessionHasNoErrors();

        $this->assertCount(4, $post->fresh()->images);
        $this->assertModelMissing($images[0]);
        Storage::disk('public')->assertMissing($images[0]->path);
    }

    public function test_hidden_post_is_404_for_non_admin_and_visible_to_admin(): void
    {
        $post = CommunityPost::factory()->hidden()->create(['title' => 'Bài bị ẩn']);

        $this->get(route('community.show', $post))->assertNotFound();
        $this->actingAs($post->author)->get(route('community.show', $post))->assertNotFound();
        $this->get(route('community.index'))->assertDontSee('Bài bị ẩn');
        $this->actingAs(User::factory()->admin()->create())->get(route('community.show', $post))
            ->assertOk()->assertSee('Đã ẩn');
    }

    public function test_filter_by_topic_and_search(): void
    {
        CommunityPost::factory()->create(['topic' => 'feedback', 'title' => 'Góp ý giao diện']);
        CommunityPost::factory()->create(['topic' => 'question', 'title' => 'Hỏi về lương']);

        $this->get(route('community.index', ['topic' => 'feedback']))->assertSee('Góp ý giao diện')->assertDontSee('Hỏi về lương');
        $this->get(route('community.index', ['q' => 'lương']))->assertSee('Hỏi về lương')->assertDontSee('Góp ý giao diện');
    }

    public function test_body_is_escaped(): void
    {
        $post = CommunityPost::factory()->create(['body' => '<script>alert(1)</script>', 'title' => '<b>x</b>']);

        $this->get(route('community.show', $post))->assertDontSee('<script>alert(1)', false)->assertDontSee('<b>x</b>', false);
        $this->get(route('community.index'))->assertDontSee('<b>x</b>', false);
    }

    public function test_header_shows_community_link(): void
    {
        $this->get(route('home'))->assertSee(route('community.index'));
    }
}
