<nav class="mb-6 flex flex-wrap gap-2">
    @foreach ([
        ['Tất cả bài viết', route('admin.community.index'), request()->routeIs('admin.community.index') && ($filter ?? 'all') === 'all'],
        ['Bị báo cáo', route('admin.community.index', ['filter' => 'reported']), ($filter ?? null) === 'reported'],
        ['Đang ẩn', route('admin.community.index', ['filter' => 'hidden']), ($filter ?? null) === 'hidden'],
        ['Danh sách báo cáo', route('admin.community.reports'), request()->routeIs('admin.community.reports')],
    ] as [$label, $url, $active])
        <a href="{{ $url }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-slate-900 text-white' => $active, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => ! $active])>{{ $label }}</a>
    @endforeach
</nav>
