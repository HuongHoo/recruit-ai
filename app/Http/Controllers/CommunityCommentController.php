<?php

namespace App\Http\Controllers;

use App\Http\Requests\Community\CommentRequest;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use App\Notifications\CommunityCommentCreated;
use App\Notifications\CommunityReplyCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

/**
 * Comments are two levels deep: replying to a reply attaches to the same root and mentions its author.
 */
class CommunityCommentController extends Controller
{
    public function store(CommentRequest $request, CommunityPost $post): RedirectResponse|JsonResponse
    {
        abort_unless($post->isVisibleTo($request->user()), 404);
        Gate::authorize('interact', $post);

        $user = $request->user();
        $target = $request->filled('parent_id') ? CommunityComment::query()->find($request->integer('parent_id')) : null;

        $comment = DB::transaction(function () use ($post, $user, $request, $target) {
            $comment = $post->comments()->make([
                'body' => $request->string('body')->trim()->value(),
                'parent_id' => $target ? ($target->parent_id ?? $target->id) : null,
                'reply_to_user_id' => $target?->parent_id ? $target->user_id : null,
            ]);
            $comment->author()->associate($user)->save();
            $post->increment('comments_count');

            return $comment;
        });

        $this->notify($post, $comment, $target, $user);

        if ($request->expectsJson()) {
            $comment->load(['author', 'replyTo']);

            return response()->json([
                'html' => view('community._comment', ['comment' => $comment, 'post' => $post, 'isReply' => $comment->parent_id !== null])->render(),
                'parent_id' => $comment->parent_id,
                'comments_count' => $post->comments_count,
            ], 201);
        }

        return redirect()->to(route('community.show', $post).'#comment-'.$comment->id);
    }

    public function update(CommentRequest $request, CommunityComment $comment): RedirectResponse
    {
        Gate::authorize('update', $comment);

        $comment->update(['body' => $request->string('body')->trim()->value()]);

        return redirect()->to(route('community.show', $comment->community_post_id).'#comment-'.$comment->id);
    }

    public function destroy(CommunityComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->deleteAndRecount();

        return redirect()->to(route('community.show', $comment->community_post_id).'#binh-luan')->with('success', 'Đã xoá bình luận.');
    }

    /**
     * Root comment → the post's author. Reply → the root comment's author and the mentioned person.
     * Never notifies the commenter themself, and each person at most once.
     */
    private function notify(CommunityPost $post, CommunityComment $comment, ?CommunityComment $target, User $actor): void
    {
        if ($comment->parent_id === null) {
            if ($post->user_id !== $actor->id && $post->author) {
                $post->author->notify(new CommunityCommentCreated($post, $comment, $actor));
            }

            return;
        }

        $root = $target->parent_id ? $target->parent : $target;
        $recipients = collect([$root->author, $comment->reply_to_user_id ? $target->author : null])
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actor->id);

        Notification::send($recipients, new CommunityReplyCreated($post, $comment, $actor));
    }
}
