<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use App\Notifications\CommunityCommentCreated;
use App\Notifications\CommunityReplyCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommunityCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_notifies_post_author_but_not_self(): void
    {
        Notification::fake();
        $post = CommunityPost::factory()->create();
        $member = User::factory()->candidate()->create();

        $this->actingAs($member)->post(route('community.comments.store', $post), ['body' => 'Hay quá!'])
            ->assertRedirect();
        $comment = CommunityComment::query()->sole();
        $this->assertNull($comment->parent_id);
        $this->assertSame(1, $post->fresh()->comments_count);
        Notification::assertSentTo($post->author, CommunityCommentCreated::class,
            fn ($n) => $n->toArray($post->author)['url'] === route('community.show', $post).'#comment-'.$comment->id);

        Notification::fake();
        $this->actingAs($post->author)->post(route('community.comments.store', $post), ['body' => 'Cảm ơn bạn']);
        Notification::assertNothingSent();
    }

    public function test_reply_to_reply_is_flattened_with_mention(): void
    {
        $post = CommunityPost::factory()->create();
        $root = CommunityComment::factory()->for($post, 'post')->create();
        $reply = CommunityComment::factory()->for($post, 'post')->create(['parent_id' => $root->id]);
        $member = User::factory()->candidate()->create();

        $this->actingAs($member)->post(route('community.comments.store', $post), ['body' => 'Đồng ý', 'parent_id' => $reply->id]);
        $new = CommunityComment::query()->latest('id')->first();
        $this->assertSame($root->id, $new->parent_id);
        $this->assertSame($reply->user_id, $new->reply_to_user_id);

        $this->actingAs($member)->post(route('community.comments.store', $post), ['body' => 'Trả lời gốc', 'parent_id' => $root->id]);
        $this->assertNull(CommunityComment::query()->latest('id')->first()->reply_to_user_id);

        $this->get(route('community.show', $post))->assertSee('@'.$reply->author->name);
    }

    public function test_reply_notifies_root_author_and_mentioned_user_once(): void
    {
        Notification::fake();
        $post = CommunityPost::factory()->create();
        $root = CommunityComment::factory()->for($post, 'post')->create();
        $reply = CommunityComment::factory()->for($post, 'post')->create(['parent_id' => $root->id]);

        $this->actingAs(User::factory()->candidate()->create())
            ->post(route('community.comments.store', $post), ['body' => 'Đồng ý', 'parent_id' => $reply->id]);

        Notification::assertSentToTimes($root->author, CommunityReplyCreated::class, 1);
        Notification::assertSentToTimes($reply->author, CommunityReplyCreated::class, 1);
        Notification::assertNotSentTo($post->author, CommunityCommentCreated::class);
    }

    public function test_parent_from_other_post_is_rejected(): void
    {
        $post = CommunityPost::factory()->create();
        $foreign = CommunityComment::factory()->create();

        $this->actingAs(User::factory()->candidate()->create())
            ->post(route('community.comments.store', $post), ['body' => 'x', 'parent_id' => $foreign->id])
            ->assertSessionHasErrors('parent_id');
        $this->actingAs(User::factory()->candidate()->create())
            ->post(route('community.comments.store', $post), ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');
    }

    public function test_cannot_comment_on_hidden_post(): void
    {
        $post = CommunityPost::factory()->hidden()->create();

        $this->actingAs(User::factory()->candidate()->create())
            ->post(route('community.comments.store', $post), ['body' => 'x'])->assertNotFound();
    }

    public function test_deleting_root_comment_removes_replies_and_updates_count(): void
    {
        $post = CommunityPost::factory()->create();
        $member = User::factory()->candidate()->create();
        $this->actingAs($member)->post(route('community.comments.store', $post), ['body' => 'gốc']);
        $root = CommunityComment::query()->sole();
        $this->actingAs($post->author)->post(route('community.comments.store', $post), ['body' => 'rep', 'parent_id' => $root->id]);
        $this->assertSame(2, $post->fresh()->comments_count);

        $this->actingAs($member)->delete(route('community.comments.destroy', $root))->assertRedirect();

        $this->assertDatabaseCount('community_comments', 0);
        $this->assertSame(0, $post->fresh()->comments_count);
    }

    public function test_only_author_or_admin_can_edit_delete_comment(): void
    {
        $comment = CommunityComment::factory()->create();
        $other = User::factory()->candidate()->create();

        $this->actingAs($other)->put(route('community.comments.update', $comment), ['body' => 'x'])->assertForbidden();
        $this->actingAs($other)->delete(route('community.comments.destroy', $comment))->assertForbidden();

        $this->actingAs($comment->author)->put(route('community.comments.update', $comment), ['body' => 'Đã sửa'])->assertRedirect();
        $this->assertSame('Đã sửa', $comment->fresh()->body);

        $this->actingAs(User::factory()->admin()->create())->delete(route('community.comments.destroy', $comment))->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_guest_cannot_comment(): void
    {
        $post = CommunityPost::factory()->create();

        $this->post(route('community.comments.store', $post), ['body' => 'x'])->assertRedirect(route('login'));
    }

    public function test_json_request_returns_rendered_comment(): void
    {
        $post = CommunityPost::factory()->create();

        $this->actingAs(User::factory()->candidate()->create())
            ->postJson(route('community.comments.store', $post), ['body' => '<i>xin chào</i>'])
            ->assertCreated()
            ->assertJsonPath('comments_count', 1)
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, '&lt;i&gt;xin chào'))->etc());
    }
}
