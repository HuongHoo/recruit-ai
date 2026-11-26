@extends('layouts.app')
@section('title', 'Bài viết & sự kiện')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <nav class="flex flex-wrap gap-2">
            @foreach ([null => 'Tất cả', ...collect(\App\Enums\PostType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])] as $value => $label)
                @php $active = ($type?->value ?? '') === (string) $value; @endphp
                <a href="{{ route('admin.posts.index', array_filter(['type' => $value])) }}"
                   @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-slate-900 text-white' => $active, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => ! $active])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="flex flex-wrap gap-2">
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="type" value="{{ $type?->value }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo tiêu đề…" class="form-input w-56">
                <button class="btn-secondary">Tìm</button>
            </form>
            <a href="{{ route('admin.posts.create', ['type' => 'article']) }}" class="btn-primary">+ Bài viết</a>
            <a href="{{ route('admin.posts.create', ['type' => 'event']) }}" class="btn-primary bg-emerald-600 hover:bg-emerald-700">+ Sự kiện</a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">
                <tr>
                    <th class="px-6 py-3">Tiêu đề</th>
                    <th class="px-6 py-3">Loại</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3">Ngày</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($posts as $post)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                @if ($post->coverUrl())
                                    <img src="{{ $post->coverUrl() }}" alt="" class="h-10 w-16 shrink-0 rounded-md object-cover">
                                @else
                                    <span class="h-10 w-16 shrink-0 rounded-md bg-gradient-to-br from-blue-900 to-blue-600"></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">
                                        {{ $post->title }}
                                        @if ($post->is_featured) <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-700">Nổi bật</span> @endif
                                    </p>
                                    <p class="text-xs text-slate-500">{{ $post->category }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-slate-600">{{ $post->type->label() }}</td>
                        <td class="px-6 py-3">
                            @if ($post->isPublished())
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Đã đăng</span>
                            @elseif ($post->published_at)
                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Hẹn giờ</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Bản nháp</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ $post->displayDate() ?? '—' }}</td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            @if ($post->isPublished())
                                <a href="{{ route('posts.show', $post) }}" target="_blank" class="mr-3 text-slate-500 hover:text-slate-800">Xem</a>
                            @endif
                            <a href="{{ route('admin.posts.edit', $post) }}" class="mr-3 font-semibold text-blue-600 hover:text-blue-700">Sửa</a>
                            <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="inline" onsubmit="return confirm('Xoá nội dung này?')">
                                @csrf @method('DELETE')
                                <button class="font-semibold text-red-600 hover:text-red-700">Xoá</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Chưa có bài viết hoặc sự kiện nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $posts->links() }}</div>
@endsection
