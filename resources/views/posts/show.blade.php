@extends('layouts.public')
@section('title', $post->title)

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <a href="{{ route('posts.index', ['type' => $post->type->value]) }}" class="text-sm text-slate-500 hover:text-blue-700">&larr; {{ $post->type->label() }}</a>
        <p class="mt-6 text-sm font-semibold text-blue-600">{{ $post->category ?? $post->type->label() }}</p>
        <h1 class="mt-2 text-3xl leading-tight font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $post->title }}</h1>

        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500">
            @if ($post->type === \App\Enums\PostType::Event)
                <span>📅 Diễn ra: <b class="text-slate-700">{{ $post->event_date?->format('d/m/Y') }}</b></span>
                @if ($post->event_location) <span>📍 {{ $post->event_location }}</span> @endif
            @endif
            <span>Đăng ngày {{ $post->published_at->format('d/m/Y') }}</span>
        </div>

        @include('posts.partials.cover', ['class' => 'mt-8 aspect-video w-full rounded-2xl'])

        <p class="mt-8 text-lg font-medium text-slate-700">{{ $post->excerpt }}</p>
        <div class="mt-6 space-y-4 leading-relaxed text-slate-700">
            @foreach (preg_split('/\R{2,}/', trim($post->content)) as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <h2 class="text-xl font-bold text-slate-900">{{ $post->type->label() }} khác</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        <a href="{{ route('posts.show', $item) }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white hover:shadow-lg">
                            @include('posts.partials.cover', ['post' => $item, 'class' => 'aspect-video w-full'])
                            <div class="p-4">
                                <h3 class="line-clamp-2 font-semibold text-slate-900 group-hover:text-blue-700">{{ $item->title }}</h3>
                                <p class="mt-2 text-xs text-slate-400">{{ $item->displayDate() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
