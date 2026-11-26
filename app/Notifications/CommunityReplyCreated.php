<?php

namespace App\Notifications;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells a member that someone replied in their comment thread or mentioned them in a reply.
 */
class CommunityReplyCreated extends Notification
{
    use Queueable;

    public function __construct(public CommunityPost $post, public CommunityComment $comment, public User $actor) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{icon: string, title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'chat',
            'title' => 'Có người trả lời bình luận của bạn',
            'message' => "{$this->actor->name} trong bài \"".Str::limit($this->post->title, 60).'": '.Str::limit($this->comment->body, 80),
            'url' => route('community.show', $this->post).'#comment-'.$this->comment->id,
        ];
    }
}
