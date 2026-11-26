<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Toggles the signed-in member's like on a post and keeps likes_count in sync.
 */
class CommunityLikeController extends Controller
{
    public function __invoke(Request $request, CommunityPost $post): JsonResponse
    {
        abort_unless($post->isVisibleTo($request->user()), 404);
        Gate::authorize('interact', $post);

        $liked = DB::transaction(function () use ($request, $post) {
            $changes = $post->likes()->toggle($request->user()->id);
            $post->likes_count = $post->likes()->count();
            $post->save();

            return $changes['attached'] !== [];
        });

        return response()->json(['liked' => $liked, 'likes_count' => $post->likes_count]);
    }
}
