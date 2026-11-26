<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * A member flags a post or comment for the admins. One report per member per item.
 */
class CommunityReportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:post,comment'],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ], attributes: ['reason' => 'lý do']);

        $item = $data['type'] === 'post'
            ? CommunityPost::query()->findOrFail($data['id'])
            : CommunityComment::query()->with('post')->findOrFail($data['id']);
        $post = $item instanceof CommunityPost ? $item : $item->post;

        abort_unless($post->isVisibleTo($request->user()), 404);
        Gate::authorize('interact', $post);

        // One report per member per item; reporting again reopens it with the new reason so admins see it.
        try {
            $report = $item->reports()->firstOrNew(['user_id' => $request->user()->id]);
            $report->reason = trim($data['reason']);
            $report->resolved_at = null;
            $report->save();
        } catch (UniqueConstraintViolationException) {
            // A double click already saved this member's report a moment ago.
        }

        return response()->json(['message' => 'Đã gửi báo cáo. Cảm ơn bạn!']);
    }
}
