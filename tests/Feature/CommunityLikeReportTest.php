<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityLikeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_toggles_and_counts(): void
    {
        $post = CommunityPost::factory()->create();
        $user = User::factory()->candidate()->create();

        $this->actingAs($user)->postJson(route('community.like', $post))->assertOk()->assertExactJson(['liked' => true, 'likes_count' => 1]);
        $this->actingAs(User::factory()->employer()->create())->postJson(route('community.like', $post))->assertJson(['likes_count' => 2]);
        $this->actingAs($user)->postJson(route('community.like', $post))->assertExactJson(['liked' => false, 'likes_count' => 1]);
        $this->assertSame(1, $post->fresh()->likes_count);
        $this->assertDatabaseCount('community_post_likes', 1);
    }

    public function test_cannot_like_hidden_post(): void
    {
        $post = CommunityPost::factory()->hidden()->create();

        $this->actingAs(User::factory()->candidate()->create())->postJson(route('community.like', $post))->assertNotFound();
    }

    public function test_report_post_and_comment_once_per_user(): void
    {
        $post = CommunityPost::factory()->create();
        $comment = CommunityComment::factory()->for($post, 'post')->create();
        $user = User::factory()->candidate()->create();

        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'post', 'id' => $post->id, 'reason' => 'Spam'])
            ->assertOk()->assertJson(['message' => 'Đã gửi báo cáo. Cảm ơn bạn!']);
        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'post', 'id' => $post->id, 'reason' => 'Spam lần 2'])
            ->assertOk();
        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'comment', 'id' => $comment->id, 'reason' => 'Thô tục'])
            ->assertOk();

        $this->assertSame(1, $post->reports()->count());
        $this->assertSame(1, $comment->reports()->count());
        $this->assertSame(2, CommunityReport::query()->count());
    }

    public function test_reporting_again_after_resolution_reopens_the_report(): void
    {
        $post = CommunityPost::factory()->create();
        $user = User::factory()->candidate()->create();
        $report = $post->reports()->create(['user_id' => $user->id, 'reason' => 'Spam']);
        $report->forceFill(['resolved_at' => now()])->save();

        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'post', 'id' => $post->id, 'reason' => 'Vẫn còn spam sau khi sửa'])
            ->assertOk();

        $report->refresh();
        $this->assertNull($report->resolved_at);
        $this->assertSame('Vẫn còn spam sau khi sửa', $report->reason);
        $this->assertSame(1, $post->reports()->count());
    }

    public function test_report_validation(): void
    {
        $user = User::factory()->candidate()->create();
        $hidden = CommunityPost::factory()->hidden()->create();

        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'user', 'id' => 1, 'reason' => 'x'])->assertJsonValidationErrors('type');
        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'post', 'id' => 999, 'reason' => 'x'])->assertNotFound();
        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'post', 'id' => $hidden->id, 'reason' => 'x'])->assertNotFound();
        $this->actingAs($user)->postJson(route('community.report'), ['type' => 'post', 'id' => 1, 'reason' => str_repeat('a', 501)])->assertJsonValidationErrors('reason');
    }

    public function test_guest_cannot_like_or_report(): void
    {
        $post = CommunityPost::factory()->create();

        $this->postJson(route('community.like', $post))->assertUnauthorized();
        $this->postJson(route('community.report'), ['type' => 'post', 'id' => $post->id, 'reason' => 'x'])->assertUnauthorized();
    }

    public function test_show_page_reflects_like_state(): void
    {
        $post = CommunityPost::factory()->create();
        $user = User::factory()->candidate()->create();
        $post->likes()->attach($user);

        $this->actingAs($user)->get(route('community.show', $post))->assertSee('aria-pressed="true"', false);
    }
}
