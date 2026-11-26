@extends('layouts.app')
@section('title', 'Cộng đồng')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        @include('admin.community._tabs')
        <form method="GET" class="flex gap-2">
            @if ($filter !== 'all') <input type="hidden" name="filter" value="{{ $filter }}"> @endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo tiêu đề…" class="form-input w-56">
            <button class="btn-secondary">Tìm</button>
        </form>
    </div>

    @if ($openReportCount > 0)
        <p class="mb-4 text-sm text-slate-600">Có <b class="text-rose-600">{{ $openReportCount }}</b> báo cáo chưa xử lý — <a href="{{ route('admin.community.reports') }}" class="font-semibold text-blue-600 hover:underline">xem danh sách</a>.</p>
    @endif

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">
                <tr>
                    <th class="px-6 py-3">Bài viết</th>
                    <th class="px-6 py-3">Tác giả</th>
                    <th class="px-6 py-3">Tương tác</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($posts as $post)
                    <tr class="hover:bg-slate-50">
                        <td class="max-w-md px-6 py-3">
                            <a href="{{ route('community.show', $post) }}" target="_blank" class="font-semibold text-slate-900 hover:text-blue-700">{{ $post->title }}</a>
                            <p class="text-xs text-slate-500">#{{ $post->topic->label() }} · {{ $post->created_at->format('d/m/Y H:i') }}</p>
                        </td>
                        <td class="px-6 py-3 text-slate-600">{{ $post->author?->name }}<br><span class="text-xs text-slate-400">{{ $post->author?->role->label() }}</span></td>
                        <td class="px-6 py-3 whitespace-nowrap text-slate-600">♥ {{ $post->likes_count }} · 💬 {{ $post->comments_count }}</td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            @if ($post->is_hidden)
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Đã ẩn</span>
                            @else
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Hiển thị</span>
                            @endif
                            @if ($post->open_reports_count)
                                <span class="ml-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">{{ $post->open_reports_count }} báo cáo</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.community.toggle', $post) }}" class="mr-3 inline">
                                @csrf @method('PATCH')
                                <button class="font-semibold text-blue-600 hover:text-blue-700">{{ $post->is_hidden ? 'Hiện lại' : 'Ẩn' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.community.destroy', $post) }}" class="inline" onsubmit="return confirm('Xoá vĩnh viễn bài viết này cùng bình luận và ảnh?')">
                                @csrf @method('DELETE')
                                <button class="font-semibold text-red-600 hover:text-red-700">Xoá</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Không có bài viết nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $posts->links() }}</div>
@endsection
