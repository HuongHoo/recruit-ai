@extends('layouts.app')
@section('title', 'Báo cáo cộng đồng')

@section('content')
    @include('admin.community._tabs', ['filter' => null])

    <div class="mb-4 flex gap-4 text-sm">
        <a href="{{ route('admin.community.reports') }}" @class(['font-semibold', 'text-blue-700' => ! $resolved, 'text-slate-500 hover:text-slate-800' => $resolved])>Chưa xử lý</a>
        <a href="{{ route('admin.community.reports', ['resolved' => 1]) }}" @class(['font-semibold', 'text-blue-700' => $resolved, 'text-slate-500 hover:text-slate-800' => ! $resolved])>Đã xử lý</a>
    </div>

    <div class="space-y-3">
        @forelse ($reports as $report)
            @php
                $item = $report->reportable;
                $isComment = $item instanceof \App\Models\CommunityComment;
                $post = $isComment ? $item?->post : $item;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">{{ $isComment ? 'Bình luận' : 'Bài viết' }} · {{ $report->created_at->format('d/m/Y H:i') }}</p>
                        @if ($item)
                            <p class="mt-1 font-semibold text-slate-900">{{ $isComment ? \Illuminate\Support\Str::limit($item->body, 200) : $item->title }}</p>
                            @if ($post)
                                <a href="{{ route('community.show', $post).($isComment ? '#comment-'.$item->id : '') }}" target="_blank" class="text-xs text-blue-600 hover:underline">Xem trong bài “{{ \Illuminate\Support\Str::limit($post->title, 60) }}”</a>
                            @endif
                        @else
                            <p class="mt-1 text-slate-500 italic">Nội dung đã bị xoá.</p>
                        @endif
                        <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800"><b>{{ $report->reporter?->name }}:</b> {{ $report->reason }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-3 text-sm">
                        @if ($item && ! $isComment)
                            <form method="POST" action="{{ route('admin.community.toggle', $item) }}">
                                @csrf @method('PATCH')
                                <button class="font-semibold text-blue-600 hover:text-blue-700">{{ $item->is_hidden ? 'Hiện lại bài' : 'Ẩn bài' }}</button>
                            </form>
                        @endif
                        @if ($item && $isComment)
                            <form method="POST" action="{{ route('admin.community.comments.destroy', $item) }}" onsubmit="return confirm('Xoá bình luận này?')">
                                @csrf @method('DELETE')
                                <button class="font-semibold text-red-600 hover:text-red-700">Xoá bình luận</button>
                            </form>
                        @endif
                        @unless ($report->resolved_at)
                            <form method="POST" action="{{ route('admin.community.reports.resolve', $report) }}">
                                @csrf @method('PATCH')
                                <button class="btn-secondary px-3 py-1.5 text-xs">Đã xử lý</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400">Xử lý {{ $report->resolved_at->format('d/m/Y H:i') }}</span>
                        @endunless
                    </div>
                </div>
            </div>
        @empty
            <p class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center text-slate-500">Không có báo cáo nào.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $reports->links() }}</div>
@endsection
