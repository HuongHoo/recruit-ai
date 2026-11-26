@extends('layouts.public')
@section('title', $type?->label() ?? 'Bài viết & sự kiện')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $type ? $type->label().' nổi bật' : 'Bài viết & sự kiện' }}</h1>
        <nav class="mt-5 flex flex-wrap gap-2 text-sm">
            @foreach (['' => 'Tất cả', 'event' => 'Sự kiện', 'article' => 'Bài viết'] as $value => $label)
                @php $active = ($type?->value ?? '') === $value; @endphp
                <a href="{{ route('posts.index', array_filter(['type' => $value])) }}" @class(['rounded-full px-4 py-1.5 font-medium', 'bg-blue-600 text-white' => $active, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700' => ! $active])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($posts as $post)
                <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg">
                    @include('posts.partials.cover', ['class' => 'aspect-video w-full'])
                    <div class="flex flex-1 flex-col p-5">
                        <p class="text-xs font-semibold text-blue-600">{{ $post->category ?? $post->type->label() }}</p>
                        <h2 class="mt-1 line-clamp-2 font-semibold text-slate-900 group-hover:text-blue-700">
                            <a href="{{ route('posts.show', $post) }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
                        </h2>
                        <p class="mt-2 line-clamp-2 flex-1 text-sm text-slate-500">{{ $post->excerpt }}</p>
                        <p class="mt-4 text-xs text-slate-400">{{ $post->displayDate() }}</p>
                    </div>
                </article>
            @empty
                <p class="col-span-full rounded-2xl border border-slate-200 px-6 py-12 text-center text-slate-500">Chưa có nội dung nào.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    </section>
@endsection
