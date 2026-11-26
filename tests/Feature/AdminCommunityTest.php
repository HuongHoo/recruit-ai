<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommunityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_only_admin_can_access(): void
    {
        $this->actingAs(User::factory()->candidate()->create())->get(route('admin.community.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.community.index'))->assertOk()->assertSee('Cộng đồng');
        $this->actingAs($this->admin())->get(route('admin.community.reports'))->assertOk();
    }

    public function test_admin_hides_and_unhides_post(): void
    {
        $post = CommunityPost::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.community.toggle', $post))->assertRedirect();
        $this->assertTrue($post->fresh()->is_hidden);
        $this->actingAs($admin)->patch(route('admin.community.toggle', $post));
        $this->assertFalse($post->fresh()->is_hidden);
    }

    public function test_reported_filter_lists_only_posts_with_open_reports(): void
    {
        $reported = CommunityPost::factory()->create(['title' => 'Bài bị báo cáo']);
        $resolved = CommunityPost::factory()->create(['title' => 'Báo cáo đã xử lý']);
        CommunityPost::factory()->create(['title' => 'Bài bình thường']);
        CommunityPost::factory()->hidden()->create(['title' => 'Bài đang ẩn']);
        $reporter = User::factory()->create();
        $reported->reports()->create(['user_id' => $reporter->id, 'reason' => 'Spam']);
        $resolved->reports()->create(['user_id' => $reporter->id, 'reason' => 'Spam'])->forceFill(['resolved_at' => now()])->save();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.community.index', ['filter' => 'reported']))
            ->assertSee('Bài bị báo cáo')->assertDontSee('Báo cáo đã xử lý')->assertDontSee('Bài bình thường');
        $this->actingAs($admin)->get(route('admin.community.index', ['filter' => 'hidden']))
            ->assertSee('Bài đang ẩn')->assertDontSee('Bài bình thường');
        $this->actingAs($admin)->get(route('admin.community.index'))->assertSee('Bài bình thường')->assertSee('Bài đang ẩn');
    }

    public function test_admin_deletes_comment_and_resolves_report(): void
    {
        $comment = CommunityComment::factory()->create(['body' => 'Bình luận xấu']);
        $report = $comment->reports()->create(['user_id' => User::factory()->create()->id, 'reason' => 'Thô tục']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.community.reports'))->assertSee('Bình luận xấu')->assertSee('Thô tục');

        $this->actingAs($admin)->patch(route('admin.community.reports.resolve', $report))->assertRedirect();
        $this->assertNotNull($report->fresh()->resolved_at);

        $this->actingAs($admin)->delete(route('admin.community.comments.destroy', $comment))->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_admin_deletes_post(): void
    {
        $post = CommunityPost::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.community.destroy', $post))->assertRedirect(route('admin.community.index'));
        $this->assertModelMissing($post);
    }
}
