<?php

namespace App\Http\Controllers;

use App\Enums\CommunityTopic;
use App\Http\Requests\Community\CommunityPostRequest;
use App\Models\CommunityPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Community forum posts: everyone can read, active members can write.
 */
class CommunityPostController extends Controller
{
    public function index(Request $request): View
    {
        $topic = CommunityTopic::tryFrom((string) $request->query('topic'));
        $search = trim((string) $request->query('q'));

        $posts = CommunityPost::query()
            ->visibleTo($request->user())
            ->with(['author', 'images'])
            ->when($topic, fn ($q) => $q->where('topic', $topic))
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('title', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('community.index', [
            'posts' => $posts,
            'topic' => $topic,
            'search' => $search,
            'topics' => CommunityTopic::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', CommunityPost::class);

        return view('community.form', ['post' => new CommunityPost, 'topics' => CommunityTopic::cases()]);
    }

    public function store(CommunityPostRequest $request): RedirectResponse
    {
        $post = DB::transaction(function () use ($request) {
            $post = $request->user()->communityPosts()->create($request->safe()->only(['title', 'topic', 'body']));
            $this->storeImages($post, $request->file('images', []));

            return $post;
        });

        return redirect()->route('community.show', $post)->with('success', 'Đã đăng bài viết.');
    }

    public function show(Request $request, CommunityPost $post): View
    {
        abort_unless($post->isVisibleTo($request->user()), 404);

        $post->load([
            'author', 'images',
            'rootComments.author', 'rootComments.replies.author', 'rootComments.replies.replyTo',
        ]);

        return view('community.show', [
            'post' => $post,
            'liked' => $post->isLikedBy($request->user()),
        ]);
    }

    public function edit(CommunityPost $post): View
    {
        Gate::authorize('update', $post);

        return view('community.form', ['post' => $post->load('images'), 'topics' => CommunityTopic::cases()]);
    }

    public function update(CommunityPostRequest $request, CommunityPost $post): RedirectResponse
    {
        DB::transaction(function () use ($request, $post) {
            $post->update($request->safe()->only(['title', 'topic', 'body']));

            $removed = $post->images()->whereIn('id', $request->removedImageIds())->get();
            Storage::disk('public')->delete($removed->pluck('path')->all());
            $post->images()->whereKey($removed->modelKeys())->delete();

            $this->storeImages($post, $request->file('images', []));
        });

        return redirect()->route('community.show', $post)->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()->route('community.index')->with('success', 'Đã xoá bài viết.');
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeImages(CommunityPost $post, array $files): void
    {
        $position = (int) $post->images()->max('position');

        foreach ($files as $file) {
            $post->images()->create([
                'path' => $file->store("community/{$post->id}", 'public'),
                'position' => ++$position,
            ]);
        }
    }
}
