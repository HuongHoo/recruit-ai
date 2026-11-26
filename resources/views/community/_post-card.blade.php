{{-- One post in the community list. Expects $post with author + images loaded. --}}
<article class="relative rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-200 hover:shadow-md">
    <div class="flex items-start justify-between gap-3">
        @include('community._author', ['user' => $post->author, 'time' => $post->created_at])
        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#{{ $post->topic->label() }}</span>
    </div>

    <h2 class="mt-3 text-lg font-semibold text-slate-900">
        <a href="{{ route('community.show', $post) }}" class="after:absolute after:inset-0 hover:text-blue-700">{{ $post->title }}</a>
        @if ($post->is_hidden)
            <span class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 align-middle text-xs font-semibold text-rose-700">Đã ẩn</span>
        @endif
    </h2>
    <p class="mt-1.5 line-clamp-3 text-sm text-slate-600">{{ $post->excerpt() }}</p>

    @if ($post->images->isNotEmpty())
        <div class="relative mt-3 w-40">
            <img src="{{ $post->images->first()->url() }}" alt="" loading="lazy" class="aspect-video w-40 rounded-lg object-cover">
            @if ($post->images->count() > 1)
                <span class="absolute right-1.5 bottom-1.5 rounded bg-slate-900/70 px-1.5 py-0.5 text-xs font-semibold text-white">+{{ $post->images->count() - 1 }}</span>
            @endif
        </div>
    @endif

    <div class="mt-4 flex items-center gap-5 text-sm text-slate-500">
        <span class="inline-flex items-center gap-1.5">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
            {{ $post->likes_count }}
        </span>
        <span class="inline-flex items-center gap-1.5">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14Z"/></svg>
            {{ $post->comments_count }}
        </span>
    </div>
</article>
