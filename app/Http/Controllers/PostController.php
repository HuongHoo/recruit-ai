<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public listing and detail pages for published articles and events.
 */
class PostController extends Controller
{
    public function index(Request $request): View
    {
        $type = PostType::tryFrom((string) $request->query('type'));

        return view('posts.index', [
            'posts' => Post::query()->published()
                ->when($type, fn ($q) => $q->where('type', $type))
                ->latest('published_at')
                ->paginate(9)
                ->withQueryString(),
            'type' => $type,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('posts.show', [
            'post' => $post,
            'related' => Post::query()->published()
                ->where('type', $post->type)
                ->whereKeyNot($post->id)
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
