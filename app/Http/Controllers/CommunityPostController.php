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
use Throwable;

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
            ->when($search !== '', function ($q) use ($search) {
                // "%" and "_" in the search box are literal characters, not LIKE wildcards.
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
                $q->where(fn ($inner) => $inner
                    ->whereRaw("title like ? escape '!'", [$like])
                    ->orWhereRaw("body like ? escape '!'", [$like]));
            })
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
        $post = $this->withUploads(function (array &$stored) use ($request) {
            $post = $request->user()->communityPosts()->create($request->safe()->only(['title', 'topic', 'body']));
            $this->storeImages($post, $request->file('images', []), $stored);

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
        $removedPaths = $this->withUploads(function (array &$stored) use ($request, $post) {
            $post->update($request->safe()->only(['title', 'topic', 'body']));

            $removed = $post->images()->whereIn('id', $request->removedImageIds())->get();
            $post->images()->whereKey($removed->modelKeys())->delete();

            $this->storeImages($post, $request->file('images', []), $stored);

            return $removed->pluck('path')->all();
        });

        // Old files go only once the new state is committed, so a failed save never loses images.
        Storage::disk('public')->delete($removedPaths);

        return redirect()->route('community.show', $post)->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()->route('community.index')->with('success', 'Đã xoá bài viết.');
    }

    /**
     * Runs $work in a transaction; if it fails, files it already stored are removed again.
     *
     * @template T
     *
     * @param  callable(array<int, string>&): T  $work
     * @return T
     */
    private function withUploads(callable $work): mixed
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($work, &$stored) {
                return $work($stored);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($stored);

            throw $exception;
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, string>  $stored  receives each stored path
     */
    private function storeImages(CommunityPost $post, array $files, array &$stored): void
    {
        $position = (int) $post->images()->max('position');

        foreach ($files as $file) {
            $stored[] = $path = $file->store("community/{$post->id}", 'public');
            $post->images()->create([
                'path' => $path,
                'position' => ++$position,
            ]);
        }
    }
}
