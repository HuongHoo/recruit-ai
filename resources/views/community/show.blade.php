@extends('layouts.public')
@section('title', $post->title)

@section('content')
    @php $viewer = auth()->user(); @endphp
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <a href="{{ route('community.index', ['topic' => $post->topic->value]) }}" class="text-sm text-slate-500 hover:text-blue-700">&larr; {{ $post->topic->label() }}</a>

        <div class="mt-4">@include('community._flash')</div>

        <article class="rounded-2xl border border-slate-200 bg-white p-6">
            @if ($post->is_hidden)
                <p class="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">Đã ẩn — bài viết này chỉ quản trị viên nhìn thấy.</p>
            @endif

            <div class="flex items-start justify-between gap-3">
                @include('community._author', ['user' => $post->author, 'time' => $post->created_at, 'avatarClass' => 'size-11 text-sm'])
                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">#{{ $post->topic->label() }}</span>
            </div>

            <h1 class="mt-5 text-2xl leading-snug font-bold text-slate-900 sm:text-3xl">{{ $post->title }}</h1>
            <div class="mt-4 space-y-4 leading-relaxed break-words text-slate-700">
                @foreach (preg_split('/\R{2,}/', trim($post->body)) as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>

            @if ($post->images->isNotEmpty())
                <div @class(['mt-5 grid gap-2', 'grid-cols-1' => $post->images->count() === 1, 'grid-cols-2' => $post->images->count() > 1])>
                    @foreach ($post->images as $image)
                        <button type="button" data-lightbox="{{ $image->url() }}" class="overflow-hidden rounded-xl ring-1 ring-slate-200 focus-visible:ring-4 focus-visible:ring-blue-500/40 focus-visible:outline-none">
                            <img src="{{ $image->url() }}" alt="Ảnh {{ $loop->iteration }} của bài viết" loading="lazy" class="max-h-[28rem] w-full object-cover transition hover:scale-[1.02]">
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-sm">
                @include('community._post-actions')

                @if ($viewer?->can('update', $post))
                    <a href="{{ route('community.edit', $post) }}" class="ml-auto rounded-lg px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100">Sửa</a>
                @endif
                @if ($viewer?->can('delete', $post))
                    <form method="POST" action="{{ route('community.destroy', $post) }}" onsubmit="return confirm('Xoá bài viết này? Hành động không thể hoàn tác.')" @class(['ml-auto' => ! $viewer->can('update', $post)])>
                        @csrf @method('DELETE')
                        <button class="rounded-lg px-3 py-1.5 font-medium text-rose-600 hover:bg-rose-50">Xoá</button>
                    </form>
                @endif
            </div>
        </article>

        <section id="binh-luan" class="mt-8">
            <h2 class="text-lg font-bold text-slate-900">Bình luận (<span data-comment-count>{{ $post->comments_count }}</span>)</h2>

            @include('community._comments')

            @guest
                <p class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-center text-sm text-slate-600">
                    <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:underline">Đăng nhập để bình luận</a> và tham gia thảo luận.
                </p>
            @endguest
        </section>
    </section>

    {{-- Lightbox --}}
    <dialog data-lightbox-dialog class="m-auto max-h-[92vh] max-w-[92vw] bg-transparent p-0 backdrop:bg-slate-950/85">
        <form method="dialog"><button class="absolute top-2 right-2 grid size-9 place-items-center rounded-full bg-white/90 text-xl text-slate-800" aria-label="Đóng">&times;</button></form>
        <img data-lightbox-image src="" alt="" class="max-h-[92vh] max-w-[92vw] rounded-lg object-contain">
    </dialog>
    <script>
        (() => {
            const dialog = document.querySelector('[data-lightbox-dialog]');
            const image = document.querySelector('[data-lightbox-image]');
            document.querySelectorAll('[data-lightbox]').forEach((button) => button.addEventListener('click', () => {
                image.src = button.dataset.lightbox;
                dialog.showModal();
            }));
            dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
        })();
    </script>
    @stack('community-scripts')
@endsection
