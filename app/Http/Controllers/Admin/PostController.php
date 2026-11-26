<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin management of articles and events shown on the public site.
 */
class PostController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'type' => ['nullable', Rule::enum(PostType::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $type = PostType::tryFrom((string) $request->query('type'));
        $keyword = trim((string) $request->query('q'));

        return view('admin.posts.index', [
            'posts' => Post::query()
                ->with('author')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($keyword !== '', fn ($q) => $q->where('title', 'like', "%{$keyword}%"))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'type' => $type,
        ]);
    }

    public function create(Request $request): View
    {
        $post = new Post([
            'type' => PostType::tryFrom((string) $request->query('type')) ?? PostType::Article,
            'published_at' => now(),
        ]);

        return view('admin.posts.form', ['post' => $post]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = new Post($this->attributes($request));
        $post->user_id = $request->user()->id;
        $post->cover_path = $request->file('cover')?->store('post-covers', 'public');
        $post->save();

        return redirect()->route('admin.posts.index')->with('status', "Đã tạo {$post->type->label()} \"{$post->title}\".");
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->fill($this->attributes($request));

        if ($request->hasFile('cover') || $request->boolean('remove_cover')) {
            $this->deleteCover($post);
            $post->cover_path = $request->file('cover')?->store('post-covers', 'public');
        }

        $post->save();

        return redirect()->route('admin.posts.index')->with('status', "Đã cập nhật \"{$post->title}\".");
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->deleteCover($post);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', "Đã xoá \"{$post->title}\".");
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(PostRequest $request): array
    {
        $data = $request->safe()->except(['cover', 'remove_cover']);
        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['type'] !== PostType::Event->value) {
            $data['event_date'] = null;
            $data['event_location'] = null;
        }

        return $data;
    }

    private function deleteCover(Post $post): void
    {
        if ($post->cover_path) {
            Storage::disk('public')->delete($post->cover_path);
        }
    }
}
