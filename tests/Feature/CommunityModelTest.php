<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_post_removes_comments_likes_reports_and_image_files(): void
    {
        Storage::fake('public');
        $post = CommunityPost::factory()->create();
        $path = UploadedFile::fake()->image('a.jpg')->store("community/{$post->id}", 'public');
        $post->images()->create(['path' => $path, 'position' => 0]);
        $comment = CommunityComment::factory()->for($post, 'post')->create();
        $post->likes()->attach(User::factory()->create());
        $reporter = User::factory()->create();
        $post->reports()->create(['user_id' => $reporter->id, 'reason' => 'Spam']);
        $comment->reports()->create(['user_id' => $reporter->id, 'reason' => 'Spam']);

        $post->delete();

        $this->assertDatabaseCount('community_post_images', 0);
        $this->assertDatabaseCount('community_comments', 0);
        $this->assertDatabaseCount('community_post_likes', 0);
        $this->assertSame(0, CommunityReport::query()->count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_visible_to_hides_hidden_posts_except_for_admin(): void
    {
        CommunityPost::factory()->create();
        CommunityPost::factory()->create(['is_hidden' => true]);

        $this->assertSame(1, CommunityPost::query()->visibleTo(null)->count());
        $this->assertSame(1, CommunityPost::query()->visibleTo(User::factory()->candidate()->create())->count());
        $this->assertSame(2, CommunityPost::query()->visibleTo(User::factory()->admin()->create())->count());
    }

    public function test_is_liked_by_and_excerpt(): void
    {
        $post = CommunityPost::factory()->create(['body' => str_repeat('a', 300)]);
        $user = User::factory()->create();
        $post->likes()->attach($user);

        $this->assertTrue($post->isLikedBy($user));
        $this->assertFalse($post->isLikedBy(null));
        $this->assertSame(201, mb_strlen($post->excerpt()));
    }
}
