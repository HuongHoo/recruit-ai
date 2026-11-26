<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use App\Notifications\CommunityCommentCreated;
use Database\Seeders\CommunitySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_treats_percent_and_underscore_literally(): void
    {
        CommunityPost::factory()->create(['title' => 'Giảm 50% phí', 'body' => 'x']);
        CommunityPost::factory()->create(['title' => 'Bài thường', 'body' => 'y']);

        $this->get(route('community.index', ['q' => '%']))->assertSee('Giảm 50% phí')->assertDontSee('Bài thường');
        $this->get(route('community.index', ['q' => '_']))->assertDontSee('Bài thường');
    }

    public function test_likes_and_comments_do_not_touch_post_updated_at(): void
    {
        $post = CommunityPost::factory()->create();
        $editedAt = $post->updated_at->toDateTimeString();
        $member = User::factory()->candidate()->create();

        $this->travel(2)->hours();
        $this->actingAs($member)->postJson(route('community.like', $post))->assertOk();
        $this->actingAs($member)->post(route('community.comments.store', $post), ['body' => 'Hay']);
        $comment = CommunityComment::query()->sole();
        $this->actingAs($member)->delete(route('community.comments.destroy', $comment));

        $this->assertSame($editedAt, $post->fresh()->updated_at->toDateTimeString());
    }

    public function test_inactive_member_sees_no_report_buttons(): void
    {
        $post = CommunityPost::factory()->create();
        CommunityComment::factory()->for($post, 'post')->create();

        $this->actingAs(User::factory()->candidate()->inactive()->create())
            ->get(route('community.show', $post))->assertOk()->assertDontSee('data-report=', false);
        $this->actingAs(User::factory()->candidate()->create())
            ->get(route('community.show', $post))->assertSee('data-report="comment"', false);
    }

    public function test_too_large_upload_returns_to_form_with_message(): void
    {
        $user = User::factory()->candidate()->create();

        $this->actingAs($user)
            ->call('POST', route('community.store'), [], [], [], [
                'CONTENT_LENGTH' => (string) (PHP_INT_MAX / 2),
                'HTTP_REFERER' => route('community.create'),
            ])
            ->assertRedirect(route('community.create', ['upload_too_large' => 1]));

        $this->actingAs($user)->get(route('community.create', ['upload_too_large' => 1]))
            ->assertSee('nên bài chưa được lưu');
        $this->assertDatabaseCount('community_posts', 0);
    }

    public function test_notification_for_removed_or_hidden_post_goes_to_community_with_message(): void
    {
        $post = CommunityPost::factory()->create();
        $comment = CommunityComment::factory()->for($post, 'post')->create();
        $author = $post->author;
        $author->notify(new CommunityCommentCreated($post, $comment, $comment->author));
        $notification = $author->notifications()->sole();

        $post->forceFill(['is_hidden' => true])->save();
        $this->actingAs($author)->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('community.index'))
            ->assertSessionHas('error', 'Bài viết này đã bị xoá hoặc tạm ẩn.');

        $post->delete();
        $this->actingAs($author)->get(route('notifications.show', $notification->id))->assertRedirect(route('community.index'));
    }

    public function test_seeded_mentions_point_at_an_earlier_reply_in_the_same_thread(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertGreaterThan(0, CommunityComment::query()->whereNotNull('reply_to_user_id')->count());

        CommunityComment::query()->whereNotNull('reply_to_user_id')->get()->each(function (CommunityComment $reply) {
            $this->assertNotNull($reply->parent_id);
            $this->assertTrue(
                CommunityComment::query()->where('parent_id', $reply->parent_id)->where('id', '<', $reply->id)
                    ->where('user_id', $reply->reply_to_user_id)->exists(),
                'a mention must name the author of an earlier reply in the thread',
            );
        });
        $this->seed(CommunitySeeder::class); // runs once: no duplicates
        $this->assertSame(6, CommunityPost::query()->count());
    }
}
