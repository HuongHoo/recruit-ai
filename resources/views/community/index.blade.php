@extends('layouts.public')
@section('title', 'Cộng đồng')

@section('content')
    <section class="bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-4xl px-4 pt-10 pb-6 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Cộng đồng</h1>
                    <p class="mt-1 text-slate-600">Góp ý về ứng dụng, chia sẻ kinh nghiệm tìm việc và hỏi đáp cùng mọi người.</p>
                </div>
                <a href="{{ route('community.create') }}" class="btn-primary">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Đăng bài
                </a>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <nav class="flex flex-wrap gap-2 text-sm">
                    @php $base = array_filter(['q' => $search]); @endphp
                    <a href="{{ route('community.index', $base) }}" @class(['rounded-full px-4 py-1.5 font-medium', 'bg-blue-600 text-white' => ! $topic, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700' => $topic])>Tất cả</a>
                    @foreach ($topics as $item)
                        @php $active = $topic === $item; @endphp
                        <a href="{{ route('community.index', $base + ['topic' => $item->value]) }}" @class(['rounded-full px-4 py-1.5 font-medium', 'bg-blue-600 text-white' => $active, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700' => ! $active])>{{ $item->label() }}</a>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route('community.index') }}" class="flex gap-2 sm:w-64">
                    @if ($topic) <input type="hidden" name="topic" value="{{ $topic->value }}"> @endif
                    <input type="search" name="q" value="{{ $search }}" placeholder="Tìm bài viết…" class="form-input" aria-label="Tìm bài viết">
                </form>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 pb-14 sm:px-6">
        @include('community._flash')

        <div class="space-y-4">
            @forelse ($posts as $post)
                @include('community._post-card')
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center">
                    <p class="font-medium text-slate-700">{{ $search !== '' || $topic ? 'Không tìm thấy bài viết phù hợp.' : 'Chưa có bài viết nào.' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Hãy là người đầu tiên chia sẻ!</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    </section>
@endsection
